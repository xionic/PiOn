<?php  
namespace Pion\Hardware;

use \PiOn\Item\Value;
use \PiOn\Session; 

use \Amp\Promise;

class HardwareHTTP extends Hardware{

	public const value_certainty = Value::CERTAIN;

	function __construct(String $name, String $node_name, array $capabilities, Object $args){
		parent::__construct($name, $node_name, $capabilities,  $args);
		$this->type = "HardwareHTTP";
		$this->value_certainty = true;
	}

	function hardware_get(Session $session, Object $item_args): Promise{	
		return \Amp\call(function() use ($session, $item_args){
			$data = file_get_contents($item_args->url);
			plog("HardwareHTTP request returned: $data", DEBUG, $session);
			return $data;
		});
	}

	function hardware_set(Session $session, Object $item_args, $value): Promise{
		// return new Value($this->do_exec($item_args->get_command), Value::CERTAIN);
	}

 
}
 
 ?>