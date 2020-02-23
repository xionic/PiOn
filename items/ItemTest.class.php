<?php
namespace PiOn\Item;

use \Pion\Hardware\Hardware;
use \PiOn\Session;

use \Amp\Promise;
use \Amp\Success;

class ItemTest extends Item {
	
	public $value;
	public const type = "Test";	
	
	protected function get_value_local(Session $session): Promise{		
		$this->value = trim("Item get_value called at ". `date`);
		$im = new Success(new Value($this->value));
		return $im;
	}
	protected function set_value_local(Session $session, Value $value):Promise{
		$this->value = $value;
		return new Success(new Value($this->value));
	}
	
}

?>