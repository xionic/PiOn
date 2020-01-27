<?php  
namespace Pion\Hardware;

use \PiOn\Item\Value;

class HardwareHTTP extends Hardware{

	public const value_certainty = Value::CERTAIN;

	function __construct(String $name, String $node_name, array $capabilities, Object $args){
		parent::__construct($name, $node_name, $capabilities,  $args);
		$this->type = "HardwareHTTP";
		$this->value_certainty = true;
	}

	function hardware_get(Object $item_args){	
		$data = file_get_contents($item_args->url);
		plog("HardwareHTTP request returned: $data", DEBUG);
		return $data;
	}

	function hardware_set(Object $item_args, $value){
		// return new Value($this->do_exec($item_args->get_command), Value::CERTAIN);
	}

 
}
 
 ?>