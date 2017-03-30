<?php
$type = $modx->getOption('type', $scriptProperties, "checkbox");
$classes = $modx->getOption('classes', $scriptProperties, "");
$id = $modx->getOption('id', $scriptProperties, "");
$min = $modx->getOption('min', $scriptProperties, "");
$max = $modx->getOption('max', $scriptProperties, "");
$value = $modx->getOption('value', $scriptProperties, "");
$condition = $modx->getOption('condition', $scriptProperties, "invalid-condition");


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


$output = '<input type="'.$type.'" id='.$id.'"" class="mediocre-filter '.$classes.'" name="'.toHex($condition).'[]" value="'.$value.'"';

if ($min != '') {$output = $output.' min="'.$min.'"';}
if ($max != '') {$output = $output.' max="'.$max.'"';}

if ($type == "checkbox"){
	$vars = $_GET[toHex($condition)];
	foreach($vars as $var){
		if ($var == $value){
			$output = $output.' checked';
		}
	}
}

$output = $output.'>';

return $output;