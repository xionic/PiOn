<?php

use Amp\Http\Server\RequestHandler\CallableRequestHandler;
use Amp\Http\Server\Response;
use Amp\Http\Server\Router;
use Amp\Http\Server\Server;
use Amp\Http\Server\StaticContent\DocumentRoot;
use Amp\Http\Status;

function handle_static_request(Amp\Http\Server\Request $request){
	
}

/*
function handle_static_request(Amp\Http\Server\Request $request){
	
	//get and sanitize path
	$path = $request->getURI()->getPath();
	str_replace("..", "", $path);
	$path = trim($path,"/");
	if(substr($path, 0, 3) != "web"){
		plog("Non web path given to static handler: $path", ERROR);
		return respons("NOT FOUND");
	}
	$path = preg_replace("/^web/", "", $path);
	$path = "web/build/default" . $path;
	if($path == "web/build/default")
		$path = "web/build/default/index.html";

	if(!is_readable($path)){	
		plog("File not found, returning 404: $path", DEBUG);
		return respond("NOT FOUND", 404);
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
	
		$response = file_get_contents($path);
	plog("Serving static file " . $path, VERBOSE);

	return respond($response, 200, $content_type); //security	
}
*/

?>