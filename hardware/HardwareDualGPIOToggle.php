<?php 
/*
args: switch_num, duration

*/
class HardwareDualGPIOToggle extends Hardware {
	
	function __construct($name, $node_name, $capabilities, $args){
		parent::__construct($name, $node_name, $capabilities, $args);
		$this->type = "HardwareDualGPIOToggle";
		$this->value_certainty = true;
	}
	
	function hardware_get($item_args){
		throw new OperationNotSupportedException("GET not supported for type: " . $this->type);
	}
	
	function hardware_set($item_args, $value){
		$switch_num = $item_args->switch_num;
		$on_pin = $this->args->$switch_num->on;
		$off_pin = $this->args->$switch_num->off;
		
		//blip the relevant pin high for duration milliseconds
		$relevant_pin = $value ? $on_pin : $off_pin;
		$high_state = $this->args->active_low ? 0 : 1;
		HardwareGPIO::set_pin($relevant_pin, $high_state);
		sleep($this->args->duration/1000);
		HardwareGPIO::set_pin($relevant_pin, !$high_state);
		
	}
	
	
}

?>