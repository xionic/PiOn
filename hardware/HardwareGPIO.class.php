<?php
namespace PiOn\Hardware;

use \PiOn\Item\Value;
use \PiOn\Session; 

use \Amp\Promise;

class HardwareGPIO extends Hardware {
	 
	public const value_certainty = Value::CERTAIN;
	
	function __construct($name, $node_name, $capabilities, $args){
		parent::__construct($name, $node_name, $capabilities,  $args);
		$this->type = "HardwareGPIO";
		$this->value_certainty = true;
	}
	
	protected function hardware_get(Session $session, Object $item_args): Promise{
		return \Amp\call(function() use($session, $item_args){
			return HardwareGPIO::get_pin($session, $item_args->pin);
		});
	}
	
	protected function hardware_set(Session $session, Object $item_args, Value $value): Promise{
		return \Amp\call(function() use($session, $item_args, $value){
			if (HardwareGPIO::set_pin($session, $item_args->pin, $value->data )){
				return $value->data;
			} else {
				//throw exception
			}
		});
	}
	
	public static function get_pin(Session $session, $board_pin){
		return trim(`gpio -1 read $board_pin`);
	}
	
	public static function set_pin(Session $session, $board_pin, $value){
		$pin_state = intval($value);
		//echo "--------------------------------- gpio mode $value out\n";
		$cmd = "gpio -1 mode $board_pin out";
		plog("HardwareGPIO running command: '$cmd'", DEBUG, $session);
		$result = trim(exec($cmd)); //TODO do this with process functions instead for error feedback etc.
		if($result != "")
			throw new Exception("GPIO error: $result");
		
		$cmd = "gpio -1 write $board_pin $pin_state" ;
		plog("HardwareGPIO running command: '$cmd'", DEBUG, $session);
		$result = trim(shell_exec($cmd));
		if($result != "")
			throw new Exception("GPIO error: $result");
		return true;
	}
	
}

?>