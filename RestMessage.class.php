<?php

class RestMessage {
	public const REQ = "req";
	public const RESP = "resp";
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
	
	public static function from_json(String $json): RestMessage {
		if($json != ""){
			$obj = json_decode($json);
			return new RestMessage($obj->type, $obj->sending_node, $obj->target_node, $obj->target_port, $obj->payload);			
		}
	}
	
	function to_json(): String{
		return json_encode($this);
	}
}
?>

