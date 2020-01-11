#!/usr/bin/env php
<?php
/*
	Generates modules.html in web directory by scanning module directories and generating apporpriate js and css imports in HTML

*/
function generate_html_includes(){
	$base_path = getcwd(). "/../web/modules/";
	echo $base_path . "\n";
	echo getcwd() . "\n";
	$modules_dir = dir($base_path);
	$includes_script = array();
	$includes_css = array();
	
	$inc_str = "";
	while (($file = $modules_dir->read()) !== false){
		if($file == "." || $file == "..") continue;
		if(is_dir($base_path.$file)){
		$inc_str .= "import {module_$file} from \"/modules/$file/$file.js\";\n";
		}
	} 
	$modules_dir->close();
	
	/*$include_str = "";
	$tabs = "\t";
	foreach($includes_script as $path){
		$include_str .= "$tabsimport module_\n";
	}
	foreach($includes_css as $path){
		//$include_str .= "$tabs<link type='text/css' rel='stylesheet' href='$path'>\n";
	}
	return $include_str;*/
	return $inc_str;
}

$incs = generate_html_includes();
$target = "modules.js";
file_put_contents($target, $incs);

?>