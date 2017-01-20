<?php
$parent = 2;
$start = round(microtime(true) * 1000);
$fields = 'pagetitle,longtitle,description,content,TV.source';
$fieldsArray = explode(',',$fields);
$array = array();
$searchQuery = 'the';
$GLOBALS['searchItemCount'] = 0;

function fetchData($modx, $stack, $parentID, $fields, $search){
    $theData = $modx->getIterator('modResource', array('parent' => $parentID));
    foreach ($theData as $idx => $item) {
        $GLOBALS['searchItemCount'] = $GLOBALS['searchItemCount'] + 1;
        if(checkFilters($modx, $item) == true){
            $rankedItem = rankResource($modx, $item, $fields, $search);
            if ($rankedItem!= false){
                array_push($stack, $rankedItem);
            }
        }
        if(count($modx->getChildIds($item->get('id'),1))>0){
            $stack = fetchData($modx, $stack, $item->get('id'), $fields, $search);
        }
    }
    return $stack;
}

function checkFilters($modx, $obj){
    return true;
}

function rankResource($modx, $obj, $fields, $query){
	
	$rank = 0;
	$fieldsSize = count($fields);
	$queryArray = explode(' ',$query);
	foreach ($fields as $idx => $field) {
		if(substr($field, 0, 3) == 'TV.'){
			$focusField = $obj->getTVValue(substr($field, 3));
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

    if($rank > 0){
//echo $focusField.':'.$rank.'<br/>';
        $obj->set('pagerank',$rank);
        return $obj;
    } else {
        return false;
    }

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
$results = fetchData($modx, $array, $parent, $fieldsArray, $searchQuery);

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