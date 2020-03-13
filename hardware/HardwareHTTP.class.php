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
			if(property_exists($item_args, "get_url")){
				$data = file_get_contents($item_args->get_url);
			} else {
				$data = null;
			}
			plog("HardwareHTTP GET request returned: $data", DEBUG, $session);
			return $data;
		});
	}

	//value will be appended to URL
	function hardware_set(Session $session, Object $item_args, $value): Promise{
		return \Amp\call(function() use ($session, $item_args, $value){
			$data = file_get_contents($item_args->set_url . "/" . urlencode($value->data));
			plog("HardwareHTTP SET request returned: $data", DEBUG, $session);
			return $data;
		});
	}

 
}
 
 ?>