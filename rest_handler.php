<?php

use Amp\Http\Status;
use Amp\Http\Server\Response;
use Amp\Http\Server\Request;
use Amp\Http\Client\InvalidRequestException;

function handle_rest_request(Request $request): Generator{
	
	$path = $request->getURI()->getPath();
	if($path != "/"){
		plog("Invalid REST Path : $path. This should not happen", ERROR);
		return new Response(500, [
			"content-type" => "text/plain; charset=utf-8"
		], "Incorrect request routing");
	}
	//parse query string
	$QS = null;
	parse_str($request->getURI()->getQuery(), $QS);
	
	//get client ip and port
	$ip = $request->getClient()->getRemoteAddress()->getHost();
	$port = $request->getClient()->getRemoteAddress()->getPort();
	
	if(!isset($QS["data"])){
		plog("Missing 'data' parameter", ERROR);
		return new Response(400, [
			"content-type" => "text/plain; charset=utf-8"
		], "Missing 'data' parameter");		
	}		
	$json = $QS["data"];
	$rest_message = RestMessage::from_json($json);
	var_dump($rest_message->payload);
	$item_message = ItemMessage::from_json($rest_message->payload);

	//plog("HTTP REST {$item_message->action} req from " . $ip.":".$port . " with params: " . urldecode(http_build_query($QS)), VERBOSE);
	
	if(!in_array($item_message->action, [ItemMessage::GET, ItemMessage::SET])){
		plog("Invalid Action:" . $item_message->action, VERBOSE);
		return new Response(400, [
			"content-type" => "text/plain; charset=utf-8"
		], "Invalid action {$item_message->action}");
	}
	$item = null;
	if(! $item = get_item($item_message->item_name)){
		plog("Requested item unknown: {$item_message->item_name}", VERBOSE);
		return new Response(400, [
			"content-type" => "text/plain; charset=utf-8"
		], "Unknown Item: {$item_message->item_name}");
	}
	$json_str = null;
	switch($item_message->action){
		case ItemMessage::GET:
			plog("get request received for item: '{$item_message->item_name}'", DEBUG);
			if(($message = yield $item->get_value()) == null){
				throw new Exception("Null response received for item: {$item_message->item_name}");
			}

			$json_str = $message->to_json();
			//var_dump($value);		
			
			break;
		case ItemMessage::SET:
			plog("set request received for item: '{$item_message->item_name}'", DEBUG);			
			if(($message = yield $item->set_value($item_message->value)) == null){
				throw new Exception("Null response received for item: {$item_message->item_name}");
			}
			
			$json_str = $message;
			break;
		
	}
	plog("{$item_message->action} req returning payload: ". urldecode($json_str), DEBUG);
			
	$rest_message = new RestMessage(RestMessage::RESP, NODE_NAME, null, null, $json_str);
	var_dump($rest_message->to_json());
	return new Response(200, [
		"content-type" => "text/json; charset=utf-8",				
		"access-control-allow-origin" => "*",
	],$rest_message->to_json());
	
	
	plog("Passed rest handler switch - something went wrong", ERROR);
	return new Response(200, [
		"content-type" => "text/plain; charset=utf-8",
	],"Something went wrong");
	
}
?>