<?php

class ItemMessage {
	public const GET = "get";
	public const SET = "set";
	public $item_name;
	public $value;
	public $action;
	public $action_success;
	public $error_msg;
	
	public function __construct($item_name, $action, $value = null, bool $action_success = null, String $error_msg = null){
		$this->item_name = $item_name;
		$this->value = $value;
		$this->action = $action;
		$this->action_success = $action_success; //true on success
		$this->error_msg; //null on success
	}
	
	public function to_json(): String{
		$obj = new stdclass();
		$obj->item_name = $this->item_name;
		$obj->value = $this->value;
		$obj->action = $this->action;
		$obj->action_success = $this->action_success;
		$obj->error_msg = $this->error_msg;
		return json_encode($obj);
	}
	
	public static function from_json(String $json): ItemMessage {
		$obj = json_decode($json);
		return new ItemMessage(
			$obj->item_name,
			$obj->action,
			$obj->value,
			$obj->action_success,
			$obj->error_msg
		);
	}
}

?>