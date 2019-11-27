<?php

class ItemTest extends Item {
	public $type = "test";
	
	public function __construct($name, $node, $args){
		parent::__construct($name, $node, $args);
	}
	
	protected function get_local(){
		$ret = new stdclass();
		$ret->name = $this->name;
		$ret->type = $this->type;
		$ret->value = `date`;
		return $ret;
	}
	protected function set_local(){
		
	}
	
}

?>