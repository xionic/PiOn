<?php
namespace PiOn\Item;

use \Pion\Hardware\Hardware as Hardware;

use \Amp\Promise;
use \Amp\Success;

class ItemTest extends Item {
	
	public $value;
	public const type = "test";	
	
	protected function get_value_local(): Promise{		
		$this->value = trim("Item get_value called at ". `date`);
		$im = new Success(new Value($this->value));
		return $im;
	}
	protected function set_value_local($value):Promise{
		$this->value = $value;
		return new Success(new Value($this->value));
	}
	
}

?>