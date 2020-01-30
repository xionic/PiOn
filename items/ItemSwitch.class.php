<?php
namespace PiOn\Item;

use \Pion\Hardware\Hardware as Hardware;

use \Amp\Promise;
use \Amp\Success;

class ItemSwitch extends Item {
	public const type = "switch";
	private $state = 0; // default to off
	
	protected function get_value_local(): Promise {//Value
	echo "HERE\n";
		if($this->hardware != null){
			return new Success(new Value($this->hardware->get($this->hardware_args)));			
		} else {
			return new Success(new Value($this->state));
		}
	}
	function set_value_local($value): Promise {//Value
		if($this->hardware != null){
			return new Success(new Value($this->hardware->set($this->hardware_args, $value)));
		} else {
			$this->state = $set_val->value;
			return new Success(new Value($this->state));
		}
		
	}

	
}

?>