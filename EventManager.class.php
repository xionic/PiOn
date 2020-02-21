<?php
namespace PiOn\Event;

use \PiOn\StandardClass;
use \PiOn\RestMessage as RestMessage;
use \Pion\Session;

class EventManager {
	
	private static $event_handlers; //event_handlers->event_type->event_name->item_name[] = handler_func;
	
	public static function init(): void {
		plog("Initialising EventManager", VERBOSE, Session::$INTERNAL);
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

	public static function register_all_item_events_handler(Callable $handler): void { //TODO validate events		
		plog("Registering for all events", DEBUG, Session::$INTERNAL);
		foreach(Event::event_types as $ev_type){
			foreach(Event::events as $ev_name){
				foreach(get_items() as $item){
					self::$event_handlers->{$ev_type}->$ev_name->{$item->name}[] = $handler;
				}
			}
		}
		/*self::$event_handlers->{ITEM_EVENT}->$event_name->$item_name[] = new StandardClass(array("node_name" => NODE_NAME, "callback" => $handler));*/
	}

	public static function register_item_event_handler(String $item_name, String $event_type, String $event_name, Callable $handler): void { //TODO validate events		
		plog("Registering item event for item: '$item_name' and event_type: '$event_type' for event: '$event_name'", VERBOSE, Session::$INTERNAL);
		self::$event_handlers->{ITEM_EVENT}->$event_name->$item_name[] = $handler;
	}

	//send an event to local handlers
	public static function handle_event(Session $session, Event $event): void {
		plog("Event has " . count(self::$event_handlers->{$event->type}->{$event->event_name}->{$event->item_name}) . " local handlers", DEBUG, $session);
		foreach(self::$event_handlers->{$event->type}->{$event->event_name}->{$event->item_name} as $handler){
			call_user_func($handler, $event->event_name, $event->item_name, $event->value);
		}
	}
	
	//locally originating events must be broadcast to all other nodes. This is what should be called on an event
	public static function trigger_event(Session $session, Event $event){
		plog("Event triggered on item: '{$event->item_name}' event: {$event->event_name} has " . count(self::$event_handlers->{$event->type}->{$event->event_name}->{$event->item_name}) . " handlers registered", DEBUG, $session);;
		
		//trigger local event handlers
		EventManager::handle_event($session, $event);

		//broadcast event to all other nodes
		foreach(get_nodes() as $node){
			if($node->name == NODE_NAME) continue;
			plog("Sending event to remote node: {$node->name}", DEBUG, Session::$INTERNAL);
			$event_message = $event->to_EventMessage();
			$rest_message = new RestMessage(RestMessage::REQ, RestMessage::REST_CONTEXT_EVENT, NODE_NAME, $node->name, $node->port, $event_message->to_json());					
			$promise = send($session, $rest_message);
		}
	}		
}


?>