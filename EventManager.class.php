<?php
namespace PiOn\Event;

use \PiOn\StandardClass;
use \PiOn\RestMessage as RestMessage;

class EventManager {
	
	private static $event_handlers; //event_handlers->event_type->event_name->item_name[] = stdclass{handler:func,node_name:...}
	
	public static function init(): void {
		plog("Initialising EventManager", VERBOSE);
		//build handler tree
		self::$event_handlers = new \stdclass();	
		foreach(Event::event_types as $event_type){		
			self::$event_handlers->$event_type = new \stdclass();
			foreach(Event::events as $event_name){
				self::$event_handlers->$event_type->$event_name = new \stdclass();
				foreach(get_model()->get_items() as $item){
					$item_name = $item->name;					
					self::$event_handlers->$event_type->$event_name->$item_name = array();				
				}
			}
		}
	}

	public static function register_item_event_handler(String $item_name, String $event_type, String $node_name, String $event_name, Callable $handler): void { //TODO validate events		
		self::$event_handlers->{ITEM_EVENT}->$event_name->$item_name[] = new StandardClass(array("node_name" => $node_name, "callback" => $handler));
	}
	
	public static function trigger_event(Event $event){
		plog("Event triggered on item: '{$event->item_name}' event: {$event->event_name} has " . count(self::$event_handlers->{$event->type}->{$event->event_name}->{$event->item_name}) . " handers registered", DEBUG);;
		foreach(self::$event_handlers->{$event->type}->{$event->event_name}->{$event->item_name} as $handler){
			if($handler->node_name == NODE_NAME){ //event is to be run locally
				plog("Event handler is to be run locally", DEBUG);
				\Amp\Call(function () use($handler, $event) {
					call_user_func($handler->callback, $event->event_name, $event->item_name, $event->value);
				});
			} else {
				plog("Event handler is to be run remotely on node: {$handler->node_name}", DEBUG);
				$event_message = $event->to_EventMessage();
				$node = get_node($handler->node_name);
				$rest_message = new RestMessage(RestMessage::REQ, RestMessage::REST_CONTEXT_EVENT, NODE_NAME, $node->name, $node->port, $event_message->to_json());

				
				$promise = send($rest_message);
				//$rest_message = yield $promise;
				
			}		
		}
	}
}


?>