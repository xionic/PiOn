<?php
namespace PiOn\Item;

use \Pion\Hardware\Hardware;
use \Pion\Hardware\OperationNotSupportedException;
use \PiOn\Session;

use \Amp\Promise;
use \Amp\Success;

class ItemText extends Item{
	public const type = "Text";
	private $value;
	
	protected function get_value_local(Session $session): Promise {
		return \Amp\call(function() use($session){
			//var_dump($this->hardware->get($this->item_args))-;
			$this->value = yield $this->hardware->get($session, $this->item_args);		
			return $this->value;
		});
	}
	
	protected function set_value_local(Session $session, Value $value): Promise {
		return \Amp\call(function() use($session){
			// we call get because it just hits a URL ATM, no POST option or anything
			$this->value = yield $this->hardware->get($session, $this->item_args);		
			return $this->value;
		});
	}

}

?>