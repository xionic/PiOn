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
	}
	
	//returns value structure {state: on|off, setpoint: int, heater_state: bool, current_temp: float}
	protected function get_value_local(): Promise{
		return \Amp\call(function(){
			$state =  (yield $this->state_switch->get_value());
			$heater = (yield $this->heater_switch->get_value());
			$temp = (yield $this->temp_item->get_value());
			$setpoint = (yield $this->setpoint->get_value());
			
		
			$value = (Object) ["state"=> $state, "heater_state" => $heater, "current_temp" => $temp, "setpoint" => $setpoint];
			var_dump($value);
			return new Value($value);
		
		});		
	}
	
	protected function set_value_local($values):Promise{ //validation
		return \Amp\call(function() use($values){
			var_dump($values);
			yield $this->state_switch->set_value(Value::from_obj($values->data->state));
			yield $this->setpoint->set_value(Value::from_obj($values->data->setpoint));
			return yield $this->get_value_local();
		});
	}
	
}

?>