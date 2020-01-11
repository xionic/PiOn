<?php

class ItemTest extends Item {
	
	
	public function __construct($name, $node, $args, $hw, $hw_args){
		parent::__construct($name, $node, $hw, $hw_args);
		$this->type="text";
	}
	
	protected function get_value_local(){
		plog("Resolving local value for item: " . $this->name, DEBUG);
		return trim("Item get_value called at ". `date`);
	}
	protected function set_value_local($value){
		
	}
	
}

?>