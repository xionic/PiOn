<?php
namespace PiOn\Item;

use \PiOn\Item\Value;

use \Amp\Promise;
use \Amp\Success;

class ItemSetpoint extends Item{
	private $setpoint = 20; //default
	
	function get_value_local(): Promise {
		return new Success(new Value($this->setpoint));
	}
	function set_value_local(Value $value): Promise {
		$this->setpoint = $value->data;
		return new Success(new Value($this->setpoint));
	}
 
}

?>