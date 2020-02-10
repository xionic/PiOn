<?php
namespace PiOn\Event;

abstract class Event{
	
	public $type; //ItemEvent etc
	public $event_name; //from EventManager 'events' const
	
	const events = array(
		"ITEM_VALUE_CHANGED",
	);
	const event_types = array(
		"ITEM_EVENT",
	);
	
	function __construct(String $type, String $event_name){ 
		$this->type = $type;
		$this->event_name = $event_name;		
	}
	
	abstract function to_json(): String;
	abstract function to_EventMessage(): EventMessage;
}


//define constants for events
foreach(Event::events as $event_name){
	define($event_name, $event_name);
}
foreach(Event::event_types as $event_type){
	define($event_type, $event_type);
}

?>