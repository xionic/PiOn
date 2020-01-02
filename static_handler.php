<?php
function handle_static_request(Amp\Http\Server\Request $request){
	
	//get and sanitize path
	$path = "." . $request->getURI()->getPath();
	str_replace("..", "", $path);
	$path = trim($path,"/");
	if($path == "./web")
		$path = "./web/index.html";

	if(!is_readable($path)){	
	
	return new Amp\Http\Server\Response(404, [
			"content-type" => "text/plain; charset=utf-8"
		], "NOT FOUND");
	};
	$content_type = "text/plain";
	$response = null;
	switch(pathinfo($path, PATHINFO_EXTENSION)){
		
		case "html": $content_type = "text/html; charset=UTF-8"; break;
		case "json":
		case "js": $content_type = "text/javascript; charset=UTF-8"; break;
		case "css": $content_type = "text/css; charset=UTF-8"; break;
	}
	if($path == "./web/index.html"){ //template expansion
		plog("Expanding index.html template", VERBOSE);
		$out = generate_html_includes();
		$response = file_get_contents($path);
		$response = preg_replace("/\{\{ModuleScriptAndStyle\}\}/", $out, $response);
	}
	else
		$response = file_get_contents($path);
	plog("Serving static file " . $path, VERBOSE);

	return new Amp\Http\Server\Response(200, [
		"content-type" => $content_type,
	], $response); //security	
}

function generate_html_includes(){
	$base_path = "web/modules/";
	$modules_dir = dir($base_path);
	$includes_script = array();
	$includes_css = array();
	
	while (($file = $modules_dir->read()) !== false){
		if($file == "." || $file == "..") continue;
		if(is_dir($base_path.$file)){
			$includes_script[] = "modules/" . $file . "/" . $file . ".js";
			$includes_css[] = "modules/" . $file . "/" . $file . ".css";
		}
	} 
	$modules_dir->close();
	
	$include_str = "";
	$tabs = "\t";
	foreach($includes_script as $path){
		$include_str .= "$tabs<script type='text/javascript' src='$path'></script>\n";
	}
	foreach($includes_css as $path){
		$include_str .= "$tabs<link type='text/css' rel='stylesheet' href='$path'>\n";
	}
	return $include_str;
}
?>