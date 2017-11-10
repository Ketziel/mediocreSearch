<?php
$type = $modx->getOption('type', $scriptProperties, "checkbox");
$classes = $modx->getOption('classes', $scriptProperties, "");
$id = $modx->getOption('id', $scriptProperties, "");
$min = $modx->getOption('min', $scriptProperties, "");
$max = $modx->getOption('max', $scriptProperties, "");
$value = $modx->getOption('value', $scriptProperties, "");
$condition = $modx->getOption('condition', $scriptProperties, "invalid-condition");
$label = $modx->getOption('label', $scriptProperties, "");
$labelBefore = $modx->getOption('labelBefore', $scriptProperties, false);
$labelAfter = $modx->getOption('labelAfter', $scriptProperties, false);

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

if ($id == ''){
	$id = toHex($condition);
}

$output = '<input type="'.$type.'" id="'.$id.toHex($value).'" class="mediocre-filter '.$classes.'" name="'.toHex($condition).'[]" value="'.toHex($value).'"';

if ($min != '') {$output = $output.' min="'.$min.'"';}
if ($max != '') {$output = $output.' max="'.$max.'"';}

if ($type == "checkbox"){
	$vars = $_GET[toHex($condition)];
	if ($vars != null){
        foreach($vars as $var){
    	    $output = $output.' data-'.$var.'="'.toHex($value).'"';
    		if ($var == toHex($value)){
    			$output = $output.' checked';
    		}
    	}
	}
}

$output = $output.'>';

$label = '<label for="'.$id.toHex($value).'">'.$label.'</label>';

if ($labelBefore == true){
	$output = $label.$output;
}
if ($labelAfter == true){
	$output = $output.$label;
}

return $output;