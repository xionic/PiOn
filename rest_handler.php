<?php

use Amp\Http\Status;
use Amp\Http\Server\Response;
use Amp\Http\Client\InvalidRequestException;

function handle_rest_request(Amp\Http\Server\Request $request){
	
	$path = $request->getURI()->getPath();
	if($path != "/"){
		plog("Invalid REST Path : $path. This should not happen", ERROR);
		return new Amp\Http\Server\Response(500, [
			"content-type" => "text/plain; charset=utf-8"
		], "Incorrect request routing");
	}
	//parse query string
	$QS = null;
	parse_str($request->getURI()->getQuery(), $QS);
	
	//get client ip and port
	$ip = $request->getClient()->getRemoteAddress()->getHost();
	$port = $request->getClient()->getRemoteAddress()->getPort();
	
	$item_name = $QS["item_name"];
	$action = $QS["action"];

	plog("HTTP REST $action req from " . $ip.":".$port . " with params: " . http_build_query($QS), VERBOSE);
	
	if(!in_array($action, ["get", "set"])){
		
		return new Response(400, [
			"content-type" => "text/plain; charset=utf-8"
		], "Invalid action $action");
	}
	
	switch($action){
		case "get":
			plog("get request received for item: '$item_name'", DEBUG);
			if(! $item = get_item($item_name)){
				
				return new Response(400, [
					"content-type" => "text/plain; charset=utf-8"
				], "Unknown Item: $item_name");
			}
			
			$value = yield $item->get_value();
			
			$message = new ItemMessage($item->name, ITEM_GET, $value, true);
			$json_str = $message->to_json();
			//var_dump($value);
			plog("Get req returning payload: ". $json_str, DEBUG);
			
			$rest_message = new RestMessage("resp", NODE_NAME, null, null, $json_str);
			
			return new Amp\Http\Server\Response(200, [
				"content-type" => "text/json; charset=utf-8",				
				"access-control-allow-origin" => "*",
			],$json_str);
			
			break;
		case "set":
			plog("set request received for item: '$item_name'", DEBUG);
			if(! $item = get_model()->set_item($item->name)){
				
				return new Response(400, [
					"content-type" => "text/plain; charset=utf-8"
				], "Unknown Item: $item_name");
			}
			
			break;		
		
	}
	
	
	return new Amp\Http\Server\Response(200, [
		"content-type" => "text/plain; charset=utf-8",
	],"Something went wrong");
	
}
?>