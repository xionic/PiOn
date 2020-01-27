<?php

use \PiOn\Item\ItemMessage;
use \PiOn\Item\Value;
use \PiOn\RestMessage;
use \PiOn\Event\EventMessage;
use \PiOn\Event\ItemEvent;
use \PiOn\Event\EventManager;
use \PiOn\InvalidArgException;

use \Amp\Http\Status;
use \Amp\Http\Server\Response;
use \Amp\Http\Server\Request;
use \Amp\Http\Client\InvalidRequestException;

function handle_rest_request(Request $request): \Amp\Promise{
	return \Amp\Call(function() use ($request) {	
		$path = $request->getURI()->getPath();
		if($path != "/"){
			plog("Invalid REST Path : $path. This should not happen", ERROR);
			return respond("Incorrect request routing", 500);
		}
		//parse query string
		$QS = null;
		parse_str($request->getURI()->getQuery(), $QS);
		
		//get client ip and port
		$ip = $request->getClient()->getRemoteAddress()->getHost();
		$port = $request->getClient()->getRemoteAddress()->getPort();
		
		if(!isset($QS["data"])){
			plog("Missing 'data' parameter", ERROR);
			return respond("Missing 'data' parameter", 400);		
		}		
		$json = $QS["data"];
		$rest_message = RestMessage::from_json($json);
		//var_dump($rest_message); 
		$json_str = null;
		switch($rest_message->context){
			case RestMessage::REST_CONTEXT_ITEM:
				$item_message = ItemMessage::from_json($rest_message->payload);

				plog("Rest Message context : {$rest_message->context}", DEBUG);
				
				if(!in_array($item_message->action, [ItemMessage::GET, ItemMessage::SET])){
					plog("Invalid Action: " . $item_message->action, VERBOSE);
					return respond("Invalid action {$item_message->action}", 400);
				}
				$item = null;
				try{
					$item = get_item($item_message->item_name);
				}
				catch(InvalidArgException $e){
					plog("Requested item unknown: {$item_message->item_name}", VERBOSE);
					return respond("Unknown Item: {$item_message->item_name}", 400);
				}
			
			
				$item_value = null;
				switch($item_message->action){
					case ItemMessage::GET:
						plog("get request received for item: '{$item_message->item_name}'", DEBUG);
						$item_value = yield $item->get_value();
							
						break;
					case ItemMessage::SET:
						plog("set request received for item: '{$item_message->item_name}' with value {$item_message->value->data}", DEBUG);			
						$item_value = yield $item->set_value($item_message->value);
							
					break;
					default:
						return respond("Invalid Item Action: {$item_message->action}", 400);
				}							
				
				$json_str = (new ItemMessage($item_message->item_name, $item_message->action, $item_value))->to_json();
				plog("Item '{$item->name}' {$item_message->action} req returning value: ". var_export($item_value->data, true), DEBUG);
				
			break;
			
			case RestMessage::REST_CONTEXT_EVENT:
				$event_message = EventMessage::from_json($rest_message->payload);
				switch($event_message->context){
					case ITEM_EVENT: 
						$item_event = new ItemEvent($event_message->event_name, $event_message->props->item_name, new Value($event_message->props->value));
						EventManager::trigger_event($item_event);
						break;
						
				}
				
			break;
			
			default:
				plog("Invalid context: {$rest_message->context}", VERBOSE);
				return respond("Invalid context: {$rest_message->context}", 400);
		}
		$resp_rest_message = new RestMessage(RestMessage::RESP, $rest_message->context, NODE_NAME, null, null, $json_str);	
		return respond($resp_rest_message->to_json(), 200, "text/json");

	});
}
?>