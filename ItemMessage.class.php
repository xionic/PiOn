<?php
namespace PiOn\Item;

use \PiOn\Item\Value;
use \xionic\Argh\Argh;

class ItemMessage {
	public const GET = "GET";
	public const SET = "SET";
	public $item_name;
	public $value;
	public $action;
	
	public function __construct(String $item_name, String $action, ?Value $value = null){
		$this->item_name = $item_name;
		$this->value = $value;
		$this->action = strtoupper($action);
	}
	
	public function to_json(): String{
		$obj = new \stdclass();
		$obj->item_name = $this->item_name;
		$obj->value = $this->value;
		$obj->action = $this->action;
		return json_encode($obj);
	}
	
	public static function from_json(String $json): ItemMessage {
		//debug_print_backtrace();
		$obj = json_decode($json);
		return ItemMessage::from_obj($obj);
	}
	
	public static function from_obj(Object $obj): ItemMessage {		
		Argh::validate($obj, [
			"item_name" => ["notblank"],
			"action" => ["notblank"],
			"value" => []
		]);
		$value = null;
		if(property_exists($obj, "value") && $obj->value != null){			
			$value = Value::from_obj($obj->value);
		}
		return new ItemMessage(
			$obj->item_name,
			$obj->action,
			$value
		);
	}
}

?>