<?php
namespace PiOn\Event;

use \PiOn\Item\Value;

class ItemEvent extends Event {
	
	public $item_name;
	public $value;
	
	function __construct(String $event_name, String $item_name, Value $value){
		parent::__construct(ITEM_EVENT, $event_name);
		$this->item_name = $item_name;
		$this->value = $value;
	}

	function to_json(): String {
		$obj = new StandardObject(array(
			"item_name" => $this->item_name,
			"event_name" => $this->event_name,
			"type" => $this->type,
			"value" => $this->value,
			
		));
		return json_encode($obj);
	}
	
	function to_EventMessage(): EventMessage {
		return new EventMessage(ITEM_EVENT, $this->event_name, new \PiOn\StandardClass(array("item_name" => $this->item_name, "value" => $this->value)));	
	}
}

?>