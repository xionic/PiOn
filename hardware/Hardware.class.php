<?php

abstract class Hardware {
	public $name;
	public $node_name;
	public $type;
	public $args;
	
	function __construct($name, $node_name, $args){
		$this->name = $name;
		$this->node_name = $node_name;
		$this->args = $args;
	}
	
	abstract function get($item_args);
	abstract function set($item_args, $value);
}

?>