<?php
namespace PiOn\Item;

use \Pion\Hardware\Hardware as Hardware;

use \Amp\Promise;
use \Amp\Success;

class ItemSwitch extends Item {
	public const type = "switch";
	private $state = 0; // default to off
	
	protected function get_value_local(): Promise {//Value
		if($this->hardware != null){
			$this->state = intval($this->hardware->get($this->hardware_args));
			var_dump($this->state);
			return new Success(new Value([
				"data" => $this->state,
				"certainty" => Value::UNCERTAIN,
			]));
		} else {
			return new Success(new Value($this->state));
		}
	}
	function set_value_local(Value $set_val): Promise {//Value
		$this->state = intval($set_val->data);
		if($this->hardware != null){
			return new Success(new Value([
				"data" => intval($this->hardware->set($this->hardware_args, $set_val)) ,
				"certainty" => Value::UNCERTAIN,
			]));
		} else {			
			return new Success(new Value([
				"data" => $this->state,
				"certainty" => Value::UNCERTAIN,
			]));
		}
		
	}

	
}

?>