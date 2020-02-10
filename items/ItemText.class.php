<?php
namespace PiOn\Item;

use \Pion\Hardware\Hardware as Hardware;
use \PiOn\Session;

use \Amp\Promise;
use \Amp\Success;

class ItemText extends Item{
	public const type = "Text";
	private $text;
	
	protected function get_value_local(Session $session): Promise {
		//var_dump($this->hardware->get($this->item_args))-;
		$this->text = $this->hardware->get($session, $this->item_args);		
		return new Success(new Value($this->text));
	}
	
	protected function set_value_local(Session $session, Value $value): Promise {
		return new Failure(new OperationNotSupportedException);
	}

}

?>