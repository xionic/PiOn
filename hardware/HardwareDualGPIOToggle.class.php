<?php 
namespace PiOn\hardware;

use \PiOn\Item\Value;
use \PiOn\Event\Scheduler;
use \PiOn\Event\FixedIntervalTimer;
use \PiOn\StandardClass;
use \PiOn\Session; 

use \Amp\Loop;
use \Amp\Promise;
use \Amp\Success;
use \Amp\Deferred;

/*
Acts as an array of binary switches, but is backed by GPIO on and off pins. i.e. each switch has 2 pins one of which must be taken HIGH for a short duration to turn the actual device on or off

args: active_low, duration, resend, switches{switch_num: {on:int, off:int}, ...}

*/
class HardwareDualGPIOToggle extends Hardware {
	
	private $states = [];
	public const value_certainty = Value::UNCERTAIN;
	private $queue = [];
	/**
	 * @var bool $locked acts as a concurrency blocker
	 */
	private $locked = false; 
	
	function __construct($name, $node_name, $capabilities, $args){
		parent::__construct($name, $node_name, $capabilities, $args);
		$this->type = "HardwareDualGPIOToggle";
		$this->value_certainty = Value::UNCERTAIN;
		
		//init toggle states
		if($node_name == NODE_NAME){
			foreach($args->switches as $key => $pin){
				$this->states[$key] = 0;
			}
			if(property_exists($args, "resend")){ // create recurring task to resend states
				$THIS = $this;
				Scheduler::register_task("Resend states for HardwareDualGPIOToggle '{$this->name}'", $this->node_name, new FixedIntervalTimer($args->resend), function() use ($THIS){
					//plog(", DEBUG);
					foreach($THIS->states as $key => $state){
						plog("Reasserting value of HardwareDualGPIOToggle switch #$key to $state", DEBUG, Session::$INTERNAL);
						\Amp\call(function() use ($THIS, $key, $state){
							$THIS->hardware_set((Object)["switch_num" => $key], new Value($state));	
						});
					}
				});
			}
		}
		
		
		
		
	}
	
	function hardware_get(Session $session, Object $item_args): Promise{
		return \Amp\call(function() use($session, $item_args){
			return $this->states[$item_args->switch_num];
		});
	}
	
	function hardware_set(Session $session, Object $item_args, Value $value): Promise{
		return \Amp\call(function() use($session, $item_args, $value){

			yield $this->wait_in_line($session);		
			$this->locked = true;
			$switch_num = $item_args->switch_num;
			$on_pin = $this->args->switches->$switch_num->on;
			$off_pin = $this->args->switches->$switch_num->off;
			
			//blip the relevant pin high for duration milliseconds
			$relevant_pin = $value->data ? $on_pin : $off_pin;
			$high_state = $this->args->active_low ? 0 : 1;
			HardwareGPIO::set_pin($session, $relevant_pin, $high_state);
			Loop::delay($this->args->duration, function() use($session, $relevant_pin, $high_state){
				HardwareGPIO::set_pin($session, $relevant_pin, !$high_state);
				$this->locked = false;
				$this->next_in_line($session);
			});
			
			$this->states[$item_args->switch_num] = $value->data;
			return $this->states[$item_args->switch_num];
		});
	}

	/**
		 * forms a queue for setting values, preventing 2 "threads" executing the function before the delay of the previous call has completed
		 */
		function wait_in_line(Session $session): Promise{
			if(count($this->queue ) == 0 && !$this->locked){
				return new Success();
			} else {
				plog("HardwareDualGPIOToggle: 'set' currently locked, entering queue", INFO, $session);
				$d = new Deferred;
				$this->queue[] = $d;
				//var_dump($d->promise());
				return $d->promise();
			}
		}


		/**
		 * Resolved the promise of the next in line, allowing it to execute hardware_set
		 */
		function next_in_line(){
			if(count($this->queue) > 0){
				/**
				 * @var Promise $next
				 */
				$next = array_shift($this->queue);
				Loop::defer(function() use ($next){
					$next->resolve();
				});
			}
		}
	
	
}

?>