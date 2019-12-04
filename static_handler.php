<?php
function handle_static_request(Psr\Http\Message\ServerRequestInterface $request){
	
	//get and sanitize path
	$path = "." . $request->getURI()->getPath();
	str_replace("..", "", $path);
	$path = trim($path,"/");
	if($path == "./web")
		$path = "./web/index.html";
		
	plog("NICKTEST  ". $path,2);
	if(!is_readable($path)){
		return new React\Http\Response(
        404,
        array('Content-Type' => 'text/plain'),
		"NOT FOUND"
    );
	}
	$content_type = "text/plain";
	switch(pathinfo($path, PATHINFO_EXTENSION)){
		case "html": $content_type = "text/html; charset=UTF-8"; break;
		case "json":
		case "js": $content_type = "text/javascript; charset=UTF-8"; break;
		case "css": $content_type = "text/css; charset=UTF-8"; break;
	}
	
	plog("Serving static file " . $path, VERBOSE);
	return new React\Http\Response(
        200,
        array('Content-Type' => $content_type),
		file_get_contents($path) //security
    );
	
}
?>