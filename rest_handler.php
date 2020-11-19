<?php

use \PiOn\Item\ItemMessage;
use \PiOn\Item\Value;
use \PiOn\RestMessage;
use \PiOn\Event\EventMessage;
use \PiOn\Event\ItemEvent;
use \PiOn\Event\EventManager;
use \PiOn\InvalidArgException;
use \PiOn\Session;
use \PiOn\Hardware\OperationNotSupportedException;

use \Amp\Promise;
use \Amp\Success;
use \Amp\Http\Status;
use \Amp\Http\Server\Response;
use \Amp\Http\Server\Request;
use \Amp\Http\Client\InvalidRequestException;

function handle_rest_request(Request $request): Promise{
	return\Amp\call(function() use ($request) {
		$session = new Session();
			
		$path = $request->getURI()->getPath();
		if($path != "/api/"){
			plog("Invalid REST Path : $path. This should not happen", ERROR, $session);
			return respond("Incorrect request routing", 500);
		}
		//parse query string
		$QS = null;
		parse_str($request->getURI()->getQuery(), $QS);
		
		//get client ip and port
		$ip = $request->getClient()->getRemoteAddress()->getHost();
		$port = $request->getClient()->getRemoteAddress()->getPort();
		
		if(!isset($QS["data"])){
			plog("Missing 'data' parameter", ERROR, $session);
			return respond("Missing 'data' parameter", 400);		
		}		
		$json = $QS["data"];
		if(!$rest_message = RestMessage::from_json($json)){
			return respond("data parameter contains invalid JSON", 400);
		}
		plog("----NEW---- REST req from $ip:$port with data: $json", DEBUG, $session);
		$resp_rest_message = yield handle_RestMessage($session, $rest_message);
		return respond($resp_rest_message->to_json(), 200, "text/json");
	});
}
/**
 * Resolves to RestMessage
 */
function handle_RestMessage(Session $session, RestMessage $rest_message): Promise {
	return \Amp\Call(function() use ($rest_message, $session) {
		
		$json_str = null;
		$response_obj = null;
		switch($rest_message->context){
			case RestMessage::REST_CONTEXT_ITEM:
				try {
				//var_dump($rest_message);
					$item_message = ItemMessage::from_obj($rest_message->payload);

					plog("Rest Message context : {$rest_message->context}", DEBUG, $session);
					
					if(!in_array($item_message->action, [ItemMessage::GET, ItemMessage::SET])){
						plog("Invalid Action: " . $item_message->action, VERBOSE, $session);
						return respond("Invalid action {$item_message->action}", 400);
					}
					$item = null;
					try{
						$item = get_item($item_message->item_name);
					}
					catch(InvalidArgException $e){
						plog("Requested item unknown: {$item_message->item_name}", VERBOSE, $session);
						return respond("Unknown Item: {$item_message->item_name}", 400);
					}
				
				
					$item_value = null;
					switch($item_message->action){
						case ItemMessage::GET:
							plog("get request received for item: '{$item_message->item_name}'", DEBUG, $session);
							$item_value = yield $item->get_value($session);
							break;
						case ItemMessage::SET:
							plog("set request received for item: '{$item_message->item_name}' with value " . json_encode($item_message->value->data), DEBUG, $session);			
							$item_value = yield $item->set_value($session, $item_message->value);
								
						break;
						default:
							return respond("Invalid Item Action: {$item_message->action}", 400);
					}							
					
					$response_obj = new ItemMessage($item_message->item_name, $item_message->action, $item_value);
					plog("Item '{$item->name}' {$item_message->action} req returning actual value: ". json_encode($item_value->data), DEBUG, $session);
				} catch (OperationNotSupportedException $e){
					return new Success($rest_message->build_error_resp("Operation '{$item_message->action}' not supported for type '" .$item->type . "'"));
				}
			break;
			
			case RestMessage::REST_CONTEXT_EVENT:
				$event_message = EventMessage::from_json($rest_message->payload);
				plog("EventMessage from {$rest_message->sending_node} for event :'{$event_message->event_name}' with props: " . json_encode($event_message->props), DEBUG, $session);
				switch($event_message->context){
					case ITEM_EVENT: 
						$item_event = new ItemEvent($event_message->event_name, $event_message->props->item_name, Value::from_obj($event_message->props->value));
						EventManager::handle_event($session, $item_event);
						break;
						
				}
				
			break;
			
			default:
				plog("Invalid context: {$rest_message->context}", VERBOSE, $session);
				return respond("Invalid context: {$rest_message->context}", 400);
		}
		
		$resp_rest_message = $rest_message->build_resp($response_obj);
		return new Success($resp_rest_message);
	});
}
?>