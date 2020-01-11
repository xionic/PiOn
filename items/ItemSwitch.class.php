<?php

class ItemSwitch extends Item {
	public $type = "switch";
	public function __construct($name, $node, $args, $hw, $hw_args){
		parent::__construct($name, $node, $hw, $hw_args);
	}
	
	
	function get_value_local(){
		return $this->hardware->get($this->hardware_args);
		
	}
	function set_value_local($value){ //TODO: Validation
		$this->hardware->set($this->hardware_args, $value);
		$msg = $this->to_ItemMessage();
		$msg->value = $value;
		$msg->action_success;
		return $msg;
		
	}

	
}

?>