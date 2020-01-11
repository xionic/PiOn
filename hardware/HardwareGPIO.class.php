<?php

class HardwareGPIO extends Hardware {
	public static $value_certainty = true; //can we be sure the hardware has actually changed when set? i.e. wireless plugs which provide no feedback. The value is "set" and we have to hope the hardware received and completed the order.
	
	function __construct($name, $node_name, $args){
		parent::__construct($name, $node_name, $args);
		$this->type = "HardwareGPIO";
	}
	
	function get($item_args){
		plog("HardwareGPIO get for " . json_encode($item_args), DEBUG);
		//`gpio set $item_args->pin in`;
		$result = trim(`gpio read $item_args->pin`);
		return $result;
	}
	
	function set($item_args, $value){
		plog("HardwareGPIO set for " . json_encode($item_args), DEBUG);
		//`gpio set $item_args->pin in`;
		$result = trim(`gpio mode $item_args->pin out`); //TODO do this with process functions instead for error feedback etc.
		if($result != "")
			throw new Exception("GPIO error: $result");
		$result = trim(`gpio write $item_args->pin escapeshellarg($value)`);
		if($result != "")
			throw new Exception("GPIO error: $result");
		return true;
	}
	
}

?>