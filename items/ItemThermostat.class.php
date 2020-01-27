<?php
namespace PiOn\Item;

use \Pion\Hardware\Hardware as Hardware;

use \Amp\Promise;
use \Amp\Success;

class ItemThermostat extends Item {
	
	public const type = "thermostat";
	
	private $state_switch;
	private $heater_switch;
	private $temp_item;
	private $setpoint;
	
	public function init(){
		$this->state_switch = get_item($this->item_args->switch_item);
		$this->heater_switch = get_item($this->item_args->heater_item);
		$this->temp_item = get_item($this->item_args->temp_item);
		$this->setpoint = get_item($this->item_args->setpoint_item);
		$this->setpoint = get_item($this->item_args->setpoint_item);
	}
	
	//returns value structure {state: on|off, setpoint: int, heater_state: bool, current_temp: float}
	protected function get_value_local(): Promise{
		return \Amp\call(function(){
			$state =  (yield $this->state_switch->get_value())->data;
			$heater = (yield $this->heater_switch->get_value())->data;
			$temp = (yield $this->temp_item->get_value())->data;
			$setpoint = (yield $this->setpoint->get_value())->data;
			
		
			$value = (Object) ["state"=> $state, "heater_state" => $heater, "current_temp" => $temp, "setpoint" => $setpoint];
		
		});		
	}
	
	protected function set_value_local($value):Promise{
		$this->value = $value;
		return new Value($this->setpoint);
	}
	
}

?>