<?php 
namespace PiOn\hardware;

use \PiOn\Item\Value;
use \PiOn\Event\Scheduler;
use \PiOn\Event\FixedIntervalTimer;
use \PiOn\StandardClass;
use \PiOn\Session; 

use Amp\Loop;

/*
Acts as an array of binary switches, but is backed by GPIO on and off pins. i.e. each switch has 2 pins one of which must be taken HIGH for a short duration to turn the actual device on or off

args: active_low, duration, resend, switches{switch_num: {on:int, off:int}, ...}

*/
class HardwareDualGPIOToggle extends Hardware {
	
	private $states = [];
	public const value_certainty = Value::UNCERTAIN;
	
	function __construct($name, $node_name, $capabilities, $args){
		parent::__construct($name, $node_name, $capabilities, $args);
		$this->type = "HardwareDualGPIOToggle";
		$this->value_certainty = Value::UNCERTAIN;
		
		//init toggle states
		if($node_name == NODE_NAME){
			foreach($args->switches as $key => $pin){
				$this->states[$key] = 0;
				//$this->hardware_set(new StandardClass(array("switch_num" => $key)), new value(0));
			}
		}
		
		
		/*
		if(property_exists($args, "resend")){ // create recurring task to resend states
			$THIS = $this;
			Scheduler::register_task("Resend states for HardwareDualGPIOToggle '{$this->name}'", $this->node_name, new FixedIntervalTimer($args->resend), function() use ($THIS){
				//plog(", DEBUG);
				foreach($this->states as $key => $state){
					
					$prom = \Amp\call(function(){
						$THIS->hardware_set((Object)["switch_num" => $key], new Value(null));						
					});
				}
			});
		}*/
		
	}
	
	function hardware_get(Session $session, Object $item_args){
		return $this->states[$item_args->switch_num];
	}
	
	function hardware_set(Session $session, Object $item_args, Value $value){
		$switch_num = $item_args->switch_num;
		$on_pin = $this->args->switches->$switch_num->on;
		$off_pin = $this->args->switches->$switch_num->off;
		
		//blip the relevant pin high for duration milliseconds
		$relevant_pin = $value->data ? $on_pin : $off_pin;
		$high_state = $this->args->active_low ? 0 : 1;
		HardwareGPIO::set_pin($session, $relevant_pin, $high_state);
		Loop::delay($this->args->duration, function() use($session, $relevant_pin, $high_state){
			HardwareGPIO::set_pin($session, $relevant_pin, !$high_state);
		});
		
		$this->states[$item_args->switch_num] = $value->data;
		return $this->states[$item_args->switch_num];
	}
	
	
}

?>