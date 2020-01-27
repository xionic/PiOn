<?php
namespace PiOn\Event;

use \PiOn\StandardClass;

class EventMessage{
	public const ITEMUPDATE = "ITEMUPDATE";
	public $context;
	public $event_name;
	public $props = array();
	
	function __construct(String $context, String $event_name, Object $props){
		$this->context = $context;
		$this->event_name = $event_name;
		$this->props = $props;
	}
	
	function to_json(): String {
		return json_encode(new StandardClass(array(
			"context" => $this->context,
			"event_name" => $this->event_name,
			"props" => $this->props
		)));
	}
	
	static function from_json(String $json): EventMessage {
		$obj = json_decode($json);
		return new EventMessage($obj->context, $obj->event_name, $obj->props);
	}
}

?>