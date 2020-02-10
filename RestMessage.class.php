<?php
namespace PiOn;

use \xionic\Argh\Argh;

class RestMessage {
	public const REQ = "REQ";
	public const RESP = "RESP";
	public const REST_CONTEXT_EVENT = "REST_CONTEXT_EVENT";
	public const REST_CONTEXT_ITEM = "REST_CONTEXT_ITEM";
	public $type; //req or resp (set requests are answered by resp's)
	public $context; //currently "ITEM" or "EVENT"
	public $sending_node;
	public $target_node; //probably only populated for req's
	public $target_port; // same
	public $payload; // object passed as args to the get/set handler function as type appropriate
	
	function __construct(String $type, String $context, ?String $sending_node, ?String $target_node, ?String $target_port, $payload){
		$this->type = $type;
		$this->context = $context;
		$this->sending_node = $sending_node;
		$this->target_node = $target_node;
		$this->target_port = $target_port;
		$this->payload = $payload;
	}
	
	public static function from_json(String $json): RestMessage {
		if($json != ""){
			if(!$obj = json_decode($json)){
				return false;
			}
			/*try{*/
			//var_dump($obj);
			Argh::validate($obj, [
				"type" => ["notblank"],
				"context" => ["notblank"],
				"sending_node" => ["optional", "?notblank"],
				"target_node" => ["optional", "?notblank"],
				"target_port" => ["optional", "?notblank"],
				"payload" => [],
			]);
			/*} catch(ValidationException){
				
			}*/
			return new RestMessage($obj->type, $obj->context, $obj->sending_node, $obj->target_node, $obj->target_port, $obj->payload);			
		}
	}
	
	function to_json(): String{
		return json_encode($this);
	}
}
?>

