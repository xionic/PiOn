<?php
namespace PiOn\Item;

use \PiOn\Item\Value;

use \Amp\Promise;
use \Amp\Success;

class ItemTemperature {
	private $setpoint = 20; //default
	
	function get_value_local(): Value {
		return new Success (new Value($this->setpoint));
	}
	function set_value_local($value): Value {
		$this->setpoint = $value;
		return new Success(new Value($this->setpoint));
	}
 
}

?>