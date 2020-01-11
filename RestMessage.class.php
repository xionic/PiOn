<?php

define("REQ", "rest_reqest");
define("RESP", "rest_response");

class RestMessage {
	public $type; //req or resp (set requests are answered by resp's)
	public $sending_node;
	public $target_node; //probably only populated for req's
	public $target_port; // same
	public $payload; // object passed as args to the get/set handler function as type appropriate
	
	function __construct($type, $sending_node, $target_node, $target_port, $payload){
		$this->type = $type;
		$this->sending_node = $sending_node;
		$this->target_node = $target_node;
		$this->target_port = $target_port;
		$this->payload = $payload;
	}
	
	public static function from_json(String $json){
		if($json != ""){
			$obj = json_decode($json);
			return new RestMessage($obj->type, $obj->sending_node, $obj->target_node, $obj->target_port, $obj->payload);			
		}
	}
	
	function to_json(){
		return json_encode($this);
	}
}
?>

