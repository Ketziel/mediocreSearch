<?php
/* ==========================================================================
   Required Functions
   ========================================================================== */

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


/* ==========================================================================
   Plugin Settings
   ========================================================================== */
   
$start = round(microtime(true) * 1000);
$parent = $modx->getOption('parent', $scriptProperties, 1);
$fields = $modx->getOption('fields', $scriptProperties, 'pagetitle,content');
$includeTVs = $modx->getOption('includeTVs', $scriptProperties, 1);
$includeTVList = explode(',',$modx->getOption('includeTVList', $scriptProperties, ''));
$resultTpl = $modx->getOption('resultTpl', $scriptProperties, '');
$resultsPerPage = $modx->getOption('resultsPerPage', $scriptProperties, 0);
$GLOBALS['mediocreSortOrder'] = json_decode($modx->getOption('sortby', $scriptProperties, '{"pagetitle":"ASC","menuindex":"DESC"}'), true);
$fieldsArray = explode(',',$fields);
$array = array();
$filters = json_decode($modx->getOption('filters', $scriptProperties, '{}'), true);
$GLOBALS['searchItemCount'] = 0;

/* POST Variables */
$searchQuery = $_GET['search'];
$searchQuery = str_replace('+',' ',$_GET['search']);
if ($_GET['num'] != null){$resultsPerPage = $_GET['num'];}
if ($_GET['page'] != null){$currentPage = $_GET['page'];} else {$currentPage = 1;}
$filters = buildFilterArray($filters);

/* Pagination*/
$paginationCount = $modx->getOption('paginationCount', $scriptProperties, 4);
$paginationWrapperClass = $modx->getOption('paginationWrapperClass', $scriptProperties, 'pagination');
$paginationPageClass = $modx->getOption('paginationPageClass', $scriptProperties, 'page');
$paginationCurrentClass = $modx->getOption('paginationCurrentClass', $scriptProperties, 'current');
$paginationNext = $modx->getOption('paginationNext', $scriptProperties, '>');
$paginationPrev = $modx->getOption('paginationPrev', $scriptProperties, '<');


/* ==========================================================================
   Filter Functions
   ========================================================================== */

function buildFilterArray($filters){
	$queryArray = $_GET;
	foreach($queryArray as $query => $val){
	    if ($query != 'search' && $query != 'q' && $query != 'num' && $query != 'page') {
    		if(is_array($val) && $query){
    			$strVal = '';
    			foreach($val as $v){
    				if ($strVal != ''){
    					$strVal = $strVal.',';
    				}
    				$strVal = $strVal.toStr($v);
    			}
    		}
    		if (!array_key_exists(toStr($query),$filters)){
    			$filters[toStr($query)] = $strVal;
    		} else {
    			$filters[toStr($query)] = $filters[toStr($query)].','.$strVal;
    		}
	    }
	}
	return $filters;
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
                    case 'contains':
						$valid = checkFilter(function ($f, $v) {return strpos($f, $v) !== false;}, $field,$val);
                        break;
                    default:
                        $valid = checkFilter(function ($f, $v) {return ($f == $v);}, $field,$val);
                }

            }
            
        }
        
    }
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

/* ==========================================================================
   Resource Ranking
   ========================================================================== */

function rankResource($modx, $obj, $fields, $query){
	if ($query == ''){
		$rank = 1;
	} else {
		$rank = 0;
		$fieldsSize = count($fields);
		$queryArray = explode(' ',$query);
		foreach ($fields as $idx => $field) {
		   if($field != '' && $field != ' ' && $field != null){				
				$outputFilter = '';
				if (strpos($field, ':') !== false) {
				   $temp = explode(':',$field);
				   $field = $temp[0];
				   $outputFilter  = $temp[1];
				}
			   
				if(substr($field, 0, 3) == 'TV.'){
					if (strpos($field, '>') !== false) {
						$focusField = '';
						$f =  explode('>',$field);
						
						$json = json_decode($obj->getTVValue(substr($f[0], 3)), true);
						
						$focusField = migxString($modx, $json, $f, 1);
					} else {
						$focusField = $obj->getTVValue(substr($field, 3));
					}
				} else {
					$focusField = $obj->get($field);
				}
				
				if ($outputFilter != ''){
					$focusField = $modx->runSnippet($outputFilter,array(
					   'input' => $focusField
					));
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

						if (stripos($focusField, $text) !== false) {
							$foundCount++;

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
    $leftChar = substr($field,$stringPosStart - 1, 1);
    $rightChar = substr($field,$stringPosEnd + 1, 1);

    if (ctype_alpha($leftChar)==false && ctype_alpha($rightChar)==false){
        return true;
    } else {
        return false;
    }
}


/* ==========================================================================
   Sort Function
   ========================================================================== */

function cmp($a, $b)
{
    $aRank = $a->get('pagerank');
    $bRank = $b->get('pagerank');
    if ($aRank == $bRank) {
		foreach ($GLOBALS['mediocreSortOrder']  as $field => $order){
			 if(substr($idx, 0, 3) == 'TV.'){
				$aField = $a->getTVValue(substr($field, 3));
				$bField = $b->getTVValue(substr($field, 3));
			} else {
				$aField = $a->get($field);
				$bField = $b->get($field);
			}
			if ($aField != $bField){
				if ($order == 'ASC'){
					if (strcasecmp($aField, $bField) != 0){
						return strcasecmp($aField, $bField);
					} 
				} else if ($order == 'DESC') {
					if (strcasecmp($bField, $aField) != 0){
						return strcasecmp($bField, $aField);
					} 
				}
			}
		}
        return 0;
    }
    return ($aRank > $bRank) ? -1 : 1;
}

/* ==========================================================================
   Search
   ========================================================================== */

function fetchData($modx, $stack, $parentID, $fields, $search, $filters){
    $theData = $modx->getIterator('modResource', array('parent' => $parentID,'published' => true));
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

/* Run Search */
$results = fetchData($modx, $array, $parent, $fieldsArray, $searchQuery, $filters);
usort($results, "cmp");
$modx->setPlaceholder('mediocreResultsCount',count($results));


/* ==========================================================================
   Pagination
   ========================================================================== */
   
$totalPages = 1;
if ($resultsPerPage > 0 && $resultsPerPage < count($results)){
    $totalPages = ceil(count($results)/$resultsPerPage);
    if ($currentPage > $totalPages){
        $currentPage = $totalPages;
    }
    $results = array_slice($results, (($currentPage-1) * $resultsPerPage), $resultsPerPage, true);
}

$pagination = '<div class="'.$paginationWrapperClass.'">';
$fullUrl = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
$visiblePagination["Start"] = $currentPage;
$visiblePagination["End"] = $currentPage;

for( $i= 1 ; $i <= $paginationCount ; $i++ ) {
    if($visiblePagination["Start"] != 1){
        $visiblePagination["Start"]--;
    }
    if($visiblePagination["End"] != $totalPages){
        $visiblePagination["End"]++;
    }
}

if ($currentPage > 1 && $totalPages > 2) {
    if (strpos($fullUrl, 'page=') !== false) {
        $prevUrl = str_replace("page=".$currentPage,"page=".($currentPage-1),$fullUrl); 
    } else {
        $prevUrl = $fullUrl."&page=1";
    }
    $pagination .= '<span class="'.$paginationPageClass.'"><a href="'.$prevUrl.'">'.$paginationPrev.'</a></span>'; 
}

for( $i= 1 ; $i <= $totalPages ; $i++ ) {
    if (strpos($fullUrl, 'page=') !== false) {
        $numUrl = str_replace("page=".$currentPage,"page=".$i,$fullUrl); 
    } else {
        $numUrl = $fullUrl."&page=".$i;
    }
    if ($i == 1 || $i == $totalPages || $i >= $visiblePagination["Start"] && $i <= $visiblePagination["End"]){
        $pagination .= '<span class="'.$paginationPageClass.($i == $currentPage ? ' '.$paginationCurrentClass : '').'"><a href="'.$numUrl.'">'.$i.'</a></span>'; 
    } elseif ($i == ($visiblePagination["Start"]-1)||$i == ($visiblePagination["End"]+1)){
        $pagination .= '<span class="'.$paginationPageClass.'"><a href="'.$numUrl.'">...</a></span>'; 
    }
}

if ($currentPage < $totalPages && $totalPages > 2) {
    if (strpos($fullUrl, 'page=') !== false) {
        $nextUrl = str_replace("page=".$currentPage,"page=".($currentPage+1),$fullUrl); 
    } else {
        $nextUrl = $fullUrl."&page=2";
    }
    $pagination .= '<span class="'.$paginationPageClass.'"><a href="'.$nextUrl.'">'.$paginationNext.'</a></span>'; 
}

$pagination .= '</div>';


/* ==========================================================================
   Results Output
   ========================================================================== */
   
$output ='';
foreach ($results as $idx => $item) {
    if ($includeTVs == 1){
        if(count($includeTVList) != 1 && $includeTVList[0] != ''){
            foreach ($includeTVList as $tvName) {
                $tvs[$tvName] = (string)$item->getTVValue($tvName);
            }
        } else {
            $templateVars =& $item->getMany('TemplateVars');
            foreach ($templateVars as $tvId => $templateVar) {
                $tvs[$templateVar->get('name')] = $templateVar->get('value');
            }
        }
    } else {
        $tvs['noTV'] = "none";    
    }
	$output = $output.$modx->getChunk($resultTpl, array_merge($item->toArray(),$tvs));
}


/* ==========================================================================
   Placeholders
   ========================================================================== */
   
$end = round(microtime(true) * 1000);

$modx->setPlaceholder('mediocreResults',$output);
$modx->setPlaceholder('mediocreSearchedCount',$GLOBALS['searchItemCount']);
$modx->setPlaceholder('mediocreQuery',$searchQuery);
$modx->setPlaceholder('mediocreQueryTime',($end-$start));
$modx->setPlaceholder('mediocrePagination',$pagination);