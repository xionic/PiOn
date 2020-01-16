<?php

abstract class Hardware {
	public const HW_GET = "get";
	public const HW_SET = "set";
	public $name;
	public $node_name;
	public $type;
	public $args;
	public $value_certainty = null; //can we be sure the hardware has actually changed when set? i.e. wireless plugs which provide no feedback. The value is "set" and we have to hope the hardware received and completed the order. bool
	public $capabilities = array(); // array of "get, "set", both or empty
	
	function __construct($name, $node_name, $capabilities, $args){
		$this->name = $name;
		$this->node_name = $node_name;
		$this->capabilities = $capabilities;
		$this->args = $args;
	}
	
	function get($item_args){
		plog("Hardware GET request for type: {$this->type} with item_args: ".json_encode($item_args), DEBUG);
		if(in_array(Hardware::HW_GET, $this->capabilities)){
			return $this->hardware_get($item_args);
		} else {
			throw new OperationNotSupportedException("Item GET not supported");
		}
	}
	
	function set($item_args, $value){
		plog("Hardware SET request for type: {$this->type} with value: $value and item_args: ".json_encode($item_args), DEBUG);
		if(in_array(Hardware::HW_SET, $this->capabilities)){
			return $this->hardware_set($item_args, $value);
		} else {
			throw new OperationNotSupportedException("Item SET not supported");
		}
	}
	
	protected abstract function hardware_get($item_args);
	protected abstract function hardware_set($item_args, $value);
}

?>