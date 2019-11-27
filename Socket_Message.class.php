<?php

class Socket_Message {
	public $type; //req or resp
	public $sending_node;
	public $target_node; //probably only populated for req's
	public $action; // get or set
	public $item_name; 
	public $payload; // object passed as args to the get/set handler function as type appropriate
	
	function __construct($json = ""){
		if($json != ""){
			$obj = json_decode($json);
			$this->type = $obj->type;
			$this->sending_node = $obj->sending_node;
			$this->action = $obj->action;
			$this->item_name = $obj->item_name;
			$this->payload = $obj->payload;
		}
	}
	
	function to_json(){
		return json_encode($this);
	}
}
//{"type":"req", "sending_node":"anode", "action":"get", "item_name": "date test", "payload": {}}
?>

