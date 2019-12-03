<?php

class ItemTest extends Item {
	public $type = "test";
	
	public function __construct($name, $node, $args){
		parent::__construct($name, $node, $args);
		$this->value = "Item Instatiated at ". `date`;
	}
	
	protected function get_local(){
		plog("Resolving local value for item: " . $this->name, DEBUG);
		return $this;
	}
	protected function set_local(){
		
	}
	
}

?>