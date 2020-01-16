<?php

class HardwareGPIO extends Hardware {
	 
	
	function __construct($name, $node_name, $capabilities, $args){
		parent::__construct($name, $node_name, $capabilities,  $args);
		$this->type = "HardwareGPIO";
		$this->value_certainty = true;
	}
	
	protected function hardware_get($item_args){	
		return HardwareGPIO::get_pin($item_args->pin);
	}
	
	protected function hardware_set($item_args, $value){
		return HardwareGPIO::set_pin($item_args->pin, $value);
	}
	
	public static function get_pin($board_pin){
		return trim(`gpio -1 read $board_pin`);
	}
	
	public static function set_pin($board_pin, $value){
		//echo "--------------------------------- gpio mode $value out\n";
		$cmd = "gpio -1 mode $board_pin out";
		plog("HardwareGPIO running command: '$cmd'", DEBUG);
		$result = trim(shell_exec($cmd)); //TODO do this with process functions instead for error feedback etc.
		if($result != "")
			throw new Exception("GPIO error: $result");
		
		$cmd = "gpio -1 write $board_pin ". intval($value);
		plog("HardwareGPIO running command: '$cmd'", DEBUG);
		$result = trim(shell_exec($cmd));
		if($result != "")
			throw new Exception("GPIO error: $result");
		plog("gpio command issued successfully", DEBUG);
		return true;
	}
	
}

?>