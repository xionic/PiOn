<?php
/*
function plog($text, $level){
	\PiOn\plog($text, $level);
}*/

function conf_get($name){
	global $config;
	return $config->$name;
}

?>