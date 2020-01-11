<?php

define ("ITEM_GET", "item_get");
define ("ITEM_SET", "item_set");

class ItemMessage {
	public $name;
	public $value;
	public $action;
	public $action_success;
	public $error_msg;
	
	function __construct($name, $action, $value = null, bool $action_success = null, String $error_msg = null){
		$this->name = $name;
		$this->value = $value;
		$this->action = $action;
		$this->action_success = $action_success; //true on success
		$this->error_msg; //null on success
	}
	
	function to_json(): String{
		$obj = new stdclass();
		$obj->item_name = $this->name;
		$obj->value = $this->value;
		$obj->action = $this->action;
		$obj->action_success = $this->action_success;
		$obj->error_msg = $this->error_msg;
		return json_encode($obj);
	}
}

?>