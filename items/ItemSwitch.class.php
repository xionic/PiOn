<?php
namespace PiOn\Item;

use \Pion\Hardware\Hardware as Hardware;
use \PiOn\Session;
use\PiOn\Item\Value;

use \Amp\Promise;
use \Amp\Success;

class ItemSwitch extends Item {
	public const type = "Switch";
	private $state = 0; // default to off
	
	protected function get_value_local(Session $session): Promise {//Value
		return \Amp\call(function() use($session){
		
			if($this->hardware != null){
				$new_value =  yield $this->hardware->get($session, $this->hardware_args);
				$this->state = intval($new_value->data);
				//var_dump($this->state);
				return $new_value;
			} else {
				return new Value($this->state);
			}
		});
	}
	function set_value_local(Session $session, Value $set_val): Promise {//Value
		return \Amp\call(function() use($session, $set_val){
			$this->state = intval($set_val->data);
			if($this->hardware != null){
				return  yield $this->hardware->set($session, $this->hardware_args, $set_val);
			} else {			
				return new Value([
					"data" => $this->state,
					"certainty" => Value::CERTAIN,
				]);
			}
		});
		
	}
}
