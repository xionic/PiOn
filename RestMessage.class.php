<?php
namespace PiOn;

use \xionic\Argh\Argh;

class RestMessage {
	//message types
	public const REQ = "REQ";
	public const RESP = "RESP";

	//payload types
	public const REST_CONTEXT_EVENT = "REST_CONTEXT_EVENT";
	public const REST_CONTEXT_ITEM = "REST_CONTEXT_ITEM";
	public const REST_CONTEXT_ITEMS = "REST_CONTEXT_ITEMS"; //array of items
	public const REST_CONTEXT_SUBSCRIBE = "REST_CONTEXT_SUBSCRIBE";
	public const REST_CONTEXT_ERROR = "REST_CONTEXT_ERROR";

	private static $id_counter = 0;
	public $type; //req or resp (set requests are answered by resp's)
	public $context; //currently "ITEM" or "EVENT"
	public $sending_node;
	public $target_node; //probably only populated for req's
	public $target_port; // same
	public $payload; // object passed as args to the get/set handler function as type appropriate
	public $id;
	
	function __construct(String $type, String $context, ?String $sending_node, ?String $target_node, ?String $target_port, $payload){
		$this->type = $type;
		$this->context = $context;
		$this->sending_node = $sending_node;
		$this->target_node = $target_node;
		$this->target_port = $target_port;
		$this->payload = $payload;
		$this->id = RestMessage::$id_counter++;
	}

	function build_resp($payload): RestMessage {
		$resp = new RestMessage("RESP", $this->context, NODE_NAME, $this->sending_node, null, $payload);
		return $resp;
	}

	function build_error_resp(String $err_msg): RestMessage {
		$resp = new RestMessage("RESP", self::REST_CONTEXT_ERROR, NODE_NAME, $this->sending_node, null, ["error" => $err_msg]);
		return $resp;
	}
	
	public static function from_json(String $json): RestMessage {
		if($json != ""){
			if(!$obj = json_decode($json)){
				throw new \Exception("Failed to parse JSON: $json");
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

