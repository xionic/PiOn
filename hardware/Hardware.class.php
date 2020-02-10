<?php
namespace Pion\Hardware;

use \PiOn\Hardware\OperationNotSupportedException;
use \PiOn\Item\Value;

abstract class Hardware {
	public const HW_GET = "get";
	public const HW_SET = "set";
	public $name;
	public $node_name;
	public $type;
	public $args;
	public $value_certainty = Value::UNKNOWN; //can we be sure the hardware has actually changed when set? i.e. wireless plugs which provide no feedback. The value is "set" and we have to hope the hardware received and completed the order. bool
	public $capabilities = array(); // array of "get, "set", both or empty
	
	
	function __construct(String $name, String $node_name, array $capabilities, Object $args){
		$this->name = $name;
		$this->node_name = $node_name;
		$this->capabilities = $capabilities;
		$this->args = $args;
	}
	
	function get(Object $item_args){
		plog("Hardware GET request for type: {$this->type} with item_args: ".json_encode($item_args), DEBUG);
		if(in_array(Hardware::HW_GET, $this->capabilities)){
			return $this->hardware_get($item_args);
		} else {
			throw new OperationNotSupportedException("GET not supported by Hardware '{$this->name}'");
		}
	}
	
	function set(Object $item_args, $value){
		
		plog("Hardware SET request for type: {$this->type} with value: ".json_encode($value->data) . " and item_args: ".json_encode($item_args), DEBUG);
		if(in_array(Hardware::HW_SET, $this->capabilities)){
			return $this->hardware_set($item_args, $value);
		} else {
			throw new OperationNotSupportedException("SET not supported by hardware '{$this->name}'");
		}
	}
	
	protected abstract function hardware_get(Object $item_args);
	protected abstract function hardware_set(Object $item_args, Value $value);
}

?>