<?php
namespace PiOn\Item;

use \PiOn\Item\Value;

use \Amp\Promise;
use \Amp\Success;

class ItemTemperature {
	private $temp = 0; //default
	
	function get_value_local(): Promise {
		return new Success (new Value($this->temp));
	}
	function set_value_local($value): Promise {
		$this->temp = $value;
		return new Success(new Value($this->temp));
	}
 
}

?>