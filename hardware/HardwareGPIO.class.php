<?php
namespace PiOn\Hardware;

use \PiOn\Item\Value;

class HardwareGPIO extends Hardware {
	 
	public const value_certainty = Value::CERTAIN;
	
	function __construct($name, $node_name, $capabilities, $args){
		echo "HEEEERE\n";
		parent::__construct($name, $node_name, $capabilities,  $args);
		$this->type = "HardwareGPIO";
		$this->value_certainty = true;
	}
	
	protected function hardware_get(Object $item_args){	
		return new Value(HardwareGPIO::get_pin($item_args->pin), $this->value_certainty);
	}
	
	protected function hardware_set(Object $item_args, Value $value){
		if (HardwareGPIO::set_pin($item_args->pin, $value->data )){
			return value;
		} else {
			//throw exception
		}
	}
	
	public static function get_pin($board_pin){
		return trim(`gpio -1 read $board_pin`);
	}
	
	public static function set_pin($board_pin, $value){
		$pin_state = intval($value);
		//echo "--------------------------------- gpio mode $value out\n";
		$cmd = "gpio -1 mode $board_pin out";
		plog("HardwareGPIO running command: '$cmd'", DEBUG);
		$result = trim(exec($cmd)); //TODO do this with process functions instead for error feedback etc.
		if($result != "")
			throw new Exception("GPIO error: $result");
		
		$cmd = "gpio -1 write $board_pin $pin_state" ;
		plog("HardwareGPIO running command: '$cmd'", DEBUG);
		$result = trim(shell_exec($cmd));
		if($result != "")
			throw new Exception("GPIO error: $result");
		return true;
	}
	
}

?>