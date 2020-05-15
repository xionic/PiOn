<?php
namespace PiOn\Item;

use \PiOn\Item\Value;
use \PiOn\Session;

use \Amp\Promise;
use \Amp\Success;

class ItemTemperature {
	private $temp = 0; //default
	public const type = "Temperature";
	
	function get_value_local(Session $session): Promise {
		return new Success (new Value($this->temp));
	}
	function set_value_local(Session $session, Value $value): Promise {
		$this->temp = $value;
		return new Success(new Value($this->temp));
	}
 
}

?>