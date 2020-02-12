<?php
namespace PiOn\Item;

use \Pion\Hardware\Hardware as Hardware;
use \PiOn\Event\EventManager;
use \PiOn\Event\Scheduler;
use \PiOn\Event\FixedIntervalTimer;
use \PiOn\Session;

use \xionic\Argh\Argh;

use \Amp\Promise;
use \Amp\Success;

/**
 * available item_args:
 *  - heater_item: Name of switch item which controls the heater
 *  - setpoint_item: Name of setpoint item
 *  - switch_item: Name of thermostat master switch
 *  - temp_item: Name of temperature sensor item
 *  - hyst: hysterysis value
 *  - update_interval: interval in seconds in which to poll the temperature item for updates. Default 30
 */
class ItemThermostat extends Item {
	
	public const type = "Thermostat";
	
	private $state_switch;
	private $heater_switch;
	private $temp_item;
	private $setpoint;
	
	public function init(){
		
		$this->state_switch = get_item($this->item_args->switch_item);
		$this->heater_switch = get_item($this->item_args->heater_item);
		$this->temp_item = get_item($this->item_args->temp_item);
		$this->setpoint = get_item($this->item_args->setpoint_item);

		$temp_update_interval = property_exists($this->item_args, "update_interval") ? $this->item_args -> update_interval : 30;
		
		//register event to listen for setpoint and temp changes and update heater state accordingly
		$THIS = $this;
		EventManager::register_item_event_handler($this->temp_item->name, ITEM_EVENT, "xealot_server", ITEM_VALUE_CHANGED, function($event_name, $item_name, Value $value) use ($THIS){
			$THIS->event_handler($event_name, $item_name, $value);
		});

		EventManager::register_item_event_handler($this->setpoint->name, ITEM_EVENT, "xealot_server", ITEM_VALUE_CHANGED, function($event_name, $item_name, Value $value) use ($THIS) {
			$THIS->event_handler($event_name, $item_name, $value);
		});

		EventManager::register_item_event_handler($this->state_switch->name, ITEM_EVENT, "xealot_server", ITEM_VALUE_CHANGED, function($event_name, $item_name, Value $value) use ($THIS) {
			$THIS->event_handler($event_name, $item_name, $value);
		});

		//force a change event trigger if temp has changed		
		Scheduler::register_task("Update Nick Temp", "xealot_server", new FixedIntervalTimer($temp_update_interval), function(){
			\Amp\call(function(){				
				yield get_item('Nick Room Temp')->get_value(Session::$INTERNAL);				
			});
		});
		
	}

	/**
	 * Handles the logic of the thermostat using events from the temp sensor and setpoint
	 */
	function event_handler(string $event_name, string $item_name, Value $value): void {	 echo "HELLOzn\n";		
		\Amp\call(function() use($event_name, $item_name, $value){

			if((yield $this->state_switch->get_value(Session::$INTERNAL))->data){

				$hyst = $this->item_args->hyst;

				// we may get one of temp or setpoint free in $value, or it may be a trigger from the switch and not helpful in terms of efficiency
				$event_item = get_item($item_name);
				$setpoint_val = null;
				$temp_val = null;
				if($event_item->type == ItemSetpoint::type){
					$setpoint_val = $value->data;
					$temp_val = (yield $this->temp_item->get_value(Session::$INTERNAL))->data;
				} else if($event_item->type == ItemTemperature::type){
					$temp_val = $value->data;
					$setpoint_val = (yield $this->setpoint->get_value(Session::$INTERNAL))->data;
				} else {
					$temp_val = (yield $this->temp_item->get_value(Session::$INTERNAL))->data;
					$setpoint_val = (yield $this->setpoint->get_value(Session::$INTERNAL))->data;
				}

				plog("Processing thermostat '{$this->name}'. temp:$temp_val set:$setpoint_val, hyst:$hyst\n", VERBOSE, Session::$INTERNAL);
				//$temp = 23.1;
				if($temp_val > $setpoint_val){
					$this->heater_switch->set_value(Session::$INTERNAL,new Value(0));
				} else if ($temp_val < ($setpoint_val - $hyst)){
					$this->heater_switch->set_value(Session::$INTERNAL, new Value(1));
				}
			} else {
				$this->heater_switch->set_value(Session::$INTERNAL,new Value(0));
			}
		});
	}
	
	//returns value structure {state: on|off, setpoint: int, heater_state: bool, current_temp: float}
	protected function get_value_local(Session $session): Promise{
		return \Amp\call(function() use ($session){
			$state =  (yield $this->state_switch->get_value($session));
			$heater = (yield $this->heater_switch->get_value($session));
			$temp = (yield $this->temp_item->get_value($session));
			$setpoint = (yield $this->setpoint->get_value($session));
			
		
			$value = (Object) ["state"=> $state, "heater_state" => $heater, "current_temp" => $temp, "setpoint" => $setpoint];
			//var_dump($value);
			return new Value($value);
		
		});		
	}
	
	protected function set_value_local(Session $session, Value $values): Promise{ //validation
		Argh::validate($values, [
			"/data/state" => ["obj"],
			"/data/setpoint" => ["obj"],
		]);
		return \Amp\call(function() use($session, $values){
			//var_dump($values);
			yield $this->state_switch->set_value($session, Value::from_obj($values->data->state));
			yield $this->setpoint->set_value($session, Value::from_obj($values->data->setpoint));
			return yield $this->get_value_local($session);
		});
	}
	
}

?>