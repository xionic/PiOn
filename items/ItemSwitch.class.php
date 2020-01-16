<?php

class ItemSwitch extends Item {
	public const type = "switch";
	public function __construct($name, $node, $args, $hw, $hw_args){
		parent::__construct($name, $node, $hw, $hw_args);
	}
	
	protected function get_value_local(): ItemMessage{
		$val = $this->hardware->get($this->hardware_args);
		$im = new ItemMessage($this->name, ItemMessage::GET, $val, true, null);
		return $im;		
	}
	function set_value_local($value): ItemMessage{ //TODO: Validation
		$this->hardware->set($this->hardware_args, $value);		
		return new ItemMessage($this->name, ItemMessage::SET, null, true, null);
		
	}

	
}

?>