<?php

class ItemTest extends Item {
	
	public $value;
	
	public function __construct($name, $node, $args, $hw, $hw_args){
		parent::__construct($name, $node, $hw, $hw_args);
		$this->type="text";
	}
	
	protected function get_value_local(): ItemMessage{		
		$this->value = trim("Item get_value called at ". `date`);
		$im = new ItemMessage($this->name, ItemMessage::GET, $this->value, true, null);
		return $im;
	}
	protected function set_value_local($value):ItemMessage{
		$this->value = $value;
		$im = new ItemMessage($this->item_name, ItemMessage::GET, null, true, null);
		return $im;
	}
	
}

?>