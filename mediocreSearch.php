<?php
/* Required Functions */
if (!function_exists('toHex')) {
	function toHex($str) {
		return array_shift( unpack('H*', $str) );
	}
}

if (!function_exists('toStr')) {
	function toStr($hex) {
		return  pack('H*', $hex);
	}
}

/* Plugin Settings */
$parent = 2;
$start = round(microtime(true) * 1000);
$fields = 'pagetitle,TV.baseEnchantment,description,TV.powers>effect';
$fieldsArray = explode(',',$fields);
$array = array();
$filters = array('template:==' => 13);
$GLOBALS['searchItemCount'] = 0;


/* POST Variables */
$searchQuery = $_GET['search'];
$searchQuery = str_replace('+',' ',$_GET['search']);
$filters = buildFilterArray($filters);

//var_dump($_GET);
/*

    foreach ($filters as $idx => $filter) {

			$x = array_shift( unpack('H*', $idx) );
			$y = pack('H*', $x);

			echo $x;
			echo '<br/>';
			echo $y;
			echo '<br/>';
	}*/



function buildFilterArray($filters){

	//template$eq$13,tv.gold$gte$1000
	$queryArray = $_GET;
	foreach($queryArray as $query => $val){
		if(is_array($val) && $query){
			$strVal = '';
			foreach($val as $v){
				if ($strVal != ''){
					$strVal = $strVal.',';
				}
				$strVal = $strVal.$v;
			}
		}
		if (!array_key_exists(toStr($query),$filters)){
			$filters[toStr($query)] = $strVal;
		} else {
			$filters[toStr($query)] = $filters[toStr($query)].','.$strVal;
		}
	}
	
	return $filters;
}




function fetchData($modx, $stack, $parentID, $fields, $search, $filters){
    $theData = $modx->getIterator('modResource', array('parent' => $parentID));
    foreach ($theData as $idx => $item) {
        $GLOBALS['searchItemCount'] = $GLOBALS['searchItemCount'] + 1;
        if(checkFilters($modx, $item, $filters) == true){
            $rankedItem = rankResource($modx, $item, $fields, $search);
            if ($rankedItem!= false){
                array_push($stack, $rankedItem);
            }
        }
        if(count($modx->getChildIds($item->get('id'),1))>0){
            $stack = fetchData($modx, $stack, $item->get('id'), $fields, $search, $filters);
        }
    }
    return $stack;
}

function checkFilters($modx, $obj, $filters){
    
    $valid = true;
    foreach($filters as $filter => $val){
        if ($valid){
            $filter = explode(':',$filter);
            if(count($filter) > 1){
                if(substr($filter[0], 0, 3) == 'TV.'){
                    $field = $obj->getTVValue(substr($filter[0], 3));
                } else {
                    $field = $obj->get($filter[0]);
                }

                switch ($filter[1]) {
                    case '==':
                        //$valid = ($field == $val);
						$valid = checkFilter(function ($f, $v) {return ($f == $v);}, $field,$val);
                        break;
                    case '>':
                        $valid = checkFilter(function ($f, $v) {return ($f > $v);}, $field,$val);
                        break;
                    case '<':
                        $valid = checkFilter(function ($f, $v) {return ($f < $v);}, $field,$val);
                        break;
                    case '>=':
                        $valid = checkFilter(function ($f, $v) {return ($f >= $v);}, $field,$val);
                        break;
                    case '<=':
                        $valid = checkFilter(function ($f, $v) {return ($f <= $v);}, $field,$val);
                        break;
                    default:
                        $valid = checkFilter(function ($f, $v) {return ($f == $v);}, $field,$val);
                }

            }
            
        }
        
    }
    
    //echo $obj->get('template').'<br/>';
    return $valid;
}

function checkFilter($compare, $field, $val){
						
	if (stripos($val,',') !== false){
		$match = 0;
		$valArr = explode(',',(string)$val);
		foreach($valArr as $va){
			if (is_numeric($va)){
				if ($compare($field, (int)$va) == 1){
						$match = 1;
				}
			} else {
				if ($compare($field, $va) == 1){
						
						$match = 1;
				}
			}
		}
		return $match;
	} else {
		if (is_numeric($val)){
			return $compare($field, (int)$val);
		} else {
			return $compare($field, $val);
		}
	}
}

function rankResource($modx, $obj, $fields, $query){
	
	if ($query == ''){
		$rank = 1;
	} else {
		$rank = 0;
		$fieldsSize = count($fields);
		$queryArray = explode(' ',$query);
		foreach ($fields as $idx => $field) {
		   if($field != '' && $field != ' ' && $field != null){
				if(substr($field, 0, 3) == 'TV.'){
					if (strpos($field, '>') !== false) {
						//echo($field);
						//echo '<br/>';
						$focusField = '';
						$f =  explode('>',$field);
						
						$json = json_decode($obj->getTVValue(substr($f[0], 3)), true);
						
						$focusField = migxString($modx, $json, $f, 1);
						
						/*foreach ($json as $idx => $item) {
							echo $item->{'effect'};
							echo '<br/><br/>';
						}*/
						/*var_dump($json[0]);
						
						
						if ($json != null){
							for ($i = 1; $i < count($f); $i++){
								if ($i == (count($f) - 1)){
									$focusField = $focusField.' || '.$json[$f[$i]];
								} else {
									$json = json_decode($json[$f[$i]], true);
								}
							}						
						}*/
					} else {
						$focusField = $obj->getTVValue(substr($field, 3));
					}
				} else {
					$focusField = $obj->get($field);
				}
				$fieldIdx = $idx;
				$matchCount = 0;

				if (stripos($focusField, $query) !== false) {
					if (doublePoints($focusField, $query) == true){
						$rank = $rank + ((($fieldsSize - $idx) * $fieldsSize * count($queryArray))*2);
					} else {
						$rank = $rank + (($fieldsSize - $idx) * $fieldsSize * count($queryArray));
					}
				} else {
					foreach ($queryArray as $idx => $text) {
						//$stringPosStart = stripos($focusField, $text);


						if (stripos($focusField, $text) !== false) {
							$foundCount++;
							/*$stringPosEnd = stripos($focusField, $text) + strlen($text) - 1;
							$startChar = substr($focusField,$stringPosStart, 1);
							$endChar = substr($focusField,$stringPosEnd, 1);
							$leftChar = substr($focusField,$stringPosStart - 1, 1);
							$rightChar = substr($focusField,$stringPosEnd + 1, 1);*/

							if (doublePoints($focusField, $text) == true){
								$rank = $rank + (($foundCount + $fieldsSize - $fieldIdx)*2);
							} else {
								$rank = $rank + ($foundCount + $fieldsSize - $fieldIdx);
							}
						}
					}
			   }
		   }

		}
		
	}
	

    if($rank > 0){
//echo $focusField.':'.$rank.'<br/>';
        $obj->set('pagerank',$rank);
        return $obj;
    } else {
        return false;
    }

}

function migxString($modx, $json, $f, $depth, $focusField = ''){
	if ($json != null){
		foreach ($json as $idx => $item) {
			if ($depth == (count($f) - 1)){
				$focusField  = ($focusField == '') ? ('') : ($focusField.' || ');
				$focusField = $focusField.$item[$f[$depth]];
			} else {
				$focusField = migxString($modx, json_decode($item[$f[$depth]], true), $f, $depth+1, $focusField);
			}
		}
	}
	return $focusField;
}

function doublePoints($field, $text){
    $stringPosStart = stripos($field, $text);
    $stringPosEnd = stripos($field, $text) + strlen($text) - 1;
/*    $startChar = substr($field,$stringPosStart, 1);
    $endChar = substr($field,$stringPosEnd, 1);*/
    $leftChar = substr($field,$stringPosStart - 1, 1);
    $rightChar = substr($field,$stringPosEnd + 1, 1);

//if (($leftChar=='' || $leftChar==' ') && ($rightChar==' ' || $rightChar=='')){
    if (ctype_alpha($leftChar)==false && ctype_alpha($rightChar)==false){
        return true;
    } else {
        return false;
    }
}

//sort function
function cmp($a, $b)
{

    $aRank = $a->get('pagerank');
    $bRank = $b->get('pagerank');
    if ($aRank == $bRank) {
        return 0;
    }
    return ($aRank > $bRank) ? -1 : 1;
}

//run search
$results = fetchData($modx, $array, $parent, $fieldsArray, $searchQuery, $filters);

usort($results, "cmp");

//Time Taken
$end = round(microtime(true) * 1000);
echo ('<h1>Searching for : '.$searchQuery.'</h1>');
echo ('<h2>Found '.count($results ).' results from '. $GLOBALS['searchItemCount'].' pages, in '.($end-$start).'milliseconds</h2>');


//Test Output
foreach ($results as $idx => $item) {
        echo $idx.'. '.($item->get('pagetitle')).' (rank:'.($item->get('pagerank')).')';
        echo '<br/><br/>';
}