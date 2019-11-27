<?php
function handle_rest_request(Psr\Http\Message\ServerRequestInterface $request){
	
	$path = $request->getURI()->getPath();
	if($path != "/"){
		plog("Invalid REST Path : $path", ERROR);
		return new React\Http\Response(
			400,
			array('Content-Type' => 'text/plain'),
			"Invalid Path"
		);
	}
	$QS = $request->getQueryParams();
	$action = @$QS["action"];
	$item_name = @$QS["item_name"];
	plog("action: $action",VERBOSE);
	
	if(!in_array($action, ["get", "set"])){
		return new React\Http\Response(
			400,
			array('Content-Type' => 'text/plain'),
			"Invalid action"
		);
	}
	
	switch($action){
		case "get":
			$out_obj = get_model()->get_item($item_name)->get();
			return new React\Http\Response(
				200,
				array('Content-Type' => 'text/json'),
				json_encode($out_obj)
			);
			break;
		case "set":
			break;
		default:
			return new React\Http\Response(
				400,
				array('Content-Type' => 'text/plain'),
				"Invalid Action: $action"
			);
			break;
		
	}
	
	
	return new React\Http\Response(
        500,
        array('Content-Type' => 'text/plain'),
		"Something went wrong"
    );
	
}
?>