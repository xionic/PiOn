<?php
namespace PiOn\Item;

use \PiOn\Item\Value;
use \PiOn\Session;

use \Amp\Promise;
use \Amp\Success;

class ItemSetpoint extends Item{
	private $setpoint = 22; //default
	public const type = "Setpoint";
	
	function init(){
		$this->last_value = new Value($this->setpoint);
	}
	
	function get_value_local(Session $session): Promise {
		return new Success(new Value($this->setpoint));
	}
	function set_value_local(Session $session, Value $value): Promise {
		$this->setpoint = $value->data;
		return new Success(new Value($this->setpoint));
	}
 
}

?>