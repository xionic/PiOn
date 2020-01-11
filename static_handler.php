<?php
function handle_static_request(Amp\Http\Server\Request $request){
	
	//get and sanitize path
	$path = $request->getURI()->getPath();
	str_replace("..", "", $path);
	$path = trim($path,"/");
	if(substr($path, 0, 3) != "web"){
		plog("Non web path given to static handler: $path", ERROR);
		return new Amp\Http\Server\Response(500, [
			"content-type" => "text/plain; charset=utf-8"
		], "NOT FOUND");
	}
	$path = preg_replace("/^web/", "", $path);
	$path = "web/build/default" . $path;
	if($path == "web/build/default")
		$path = "web/build/default/index.html";

	if(!is_readable($path)){	
		plog("File not found, returning 404: $path", DEBUG);
		return new Amp\Http\Server\Response(404, [
			"content-type" => "text/plain; charset=utf-8"
		], "NOT FOUND");
	};
	$content_type = "text/plain";
	$response = null;
	switch(pathinfo($path, PATHINFO_EXTENSION)){
		
		case "html": $content_type = "text/html; charset=UTF-8"; break;
		case "json":
		case "mjs":
		case "js": $content_type = "text/javascript; charset=UTF-8"; break;
		case "css": $content_type = "text/css; charset=UTF-8"; break;
		default: plog("Unknown file type served: " . pathinfo($path, PATHINFO_EXTENSION), ERROR); 
	}
	/*if($path == "./web/index.html"){ //template expansion
		plog("Expanding index.html template", VERBOSE);
		$out = generate_html_includes();
		$response = file_get_contents($path);
		$response = preg_replace("/\{\{ModuleScriptAndStyle\}\}/", $out, $response);
	}
	else*/
		$response = file_get_contents($path);
	plog("Serving static file " . $path, VERBOSE);

	return new Amp\Http\Server\Response(200, [
		"content-type" => $content_type,
	], $response); //security	
}


?>