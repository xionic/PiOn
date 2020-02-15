<?php
namespace PiOn;

use \Pion\Hardware\Hardware;
use \Pion\Item\Item;
use \Pion\Item\Value as Value;
use \PiOn\Node\Node;
use \Pion\InvalidArgException;
use \PiOn\Session;

use \xionic\Argh\Argh;
use \xionic\Argh\ValidationException;

class Model {
	
	protected $nodes = array();
	protected $items = array();
	protected $hardware = array();
	
	function __construct($model_conf){

		//Validation !!!
		try{

			Argh::validate($model_conf, [
				"nodes" => ["array"],
				"/nodes/*/" => ['obj'],
				"/nodes/*/name" => ['notblank', function($value){
					//TODO ensure unique
					return true;
				}],
				"/nodes/*/hostname" => ["notblank"],
				"/nodes/*/port" => ["int", "notzero"],

				"hardware" => ["array"],
				"/hardware/*/" => ['obj'],
				"/hardware/*/name" => ['notblank', function($value){
					//TODO ensure unique
					return true;
				}],
				"/hardware/*/type" => ["notblank"],
				"/hardware/*/typeargs" => ["?obj"],
				"/hardware/*/node" => ["notblank", function($value){
					//TODO ensure exists
					return true;
				}],
				"/hardware/*/capabilities" => ["array"],
				"/hardware/*/capabilities/*/" => ["regex /(get|set)/"],

				"items" => ["array"],
				"/items/*/" => ['obj'],
				"/items/*/name" => ['notblank', function($value){
					//TODO ensure unique
					return true;
				}],
				"/items/*/node" => ["notblank", function($value){
					//TODO ensure exists
					return true;
				}],
				"/items/*/itemargs" => ["?obj"],
				"/items/*/hardware" => ["optional", "obj"],
				"/items/*/hardware/name" => ["optional", "notblank", function($value){
					//TODO ensure exists
					return true;
				}],
				"/items/*/hardware/args" => ["optional", "obj"]	,
			]);

		} catch(ValidationException $ve){
			plog("Failed to read config.json", ERROR, Session::$INTERNAL);
			plog($ve->getMessage(), FATAL, Session::$INTERNAL);
		}
		plog("config.json validation succeeded", VERBOSE, Session::$INTERNAL);

		//load nodes
		foreach($model_conf->nodes as $node){
			$this->nodes[$node->name] = new \PiOn\Node\NodeStandard($node->name, $node->hostname, $node->port);
		}
		
		//load hardware
		foreach($model_conf->hardware as $hw){ //SECURITY
			$class = "\PiOn\Hardware\Hardware".$hw->type;
			//var_dump($class);
			//new Value("r");
			//new \PiOn\Hardware\HardwareGPIO(null, null, null, null);
			$this->hardware[$hw->name] = new $class($hw->name, $hw->node, $hw->capabilities, $hw->typeargs);
		}
		
		//load items
		foreach($model_conf->items as $item){ //SECURITY
			$hw = null;
			$hw_args = null;
			if(property_exists($item->hardware, "name")){
				$hw = $this->get_hardware($item->hardware->name);
				$hw_args = $item->hardware->args;
			}				
			$this->items[$item->name] = Item::create_item($item->type, $item->name, $item->node, $item->itemargs, $hw, $hw_args);			
		}
		
		//var_dump($this);
		
		
	}
	
	//now all  items are constructed, do init
	function init(){
		$init_promises = [];
		foreach($this->items as $item){	
			if(method_exists($item, "init"))		
				$init_promises[] = $item->init();
		}
		//\Amp\Promise\wait(\Amp\Promise\all($init_promises));
		plog("Model init finished", INFO, Session::$INTERNAL);
	}
	
	function get_item($item_name): Item{
		//var_dump($this->items[$item_name]);
		if(array_key_exists($item_name, $this->items)){
		//echo "NIUSDFSF $item_name\n";
			return $this->items[$item_name];
		}
		else{
			plog("Unknown item name: $item_name", ERROR, Session::$INTERNAL);
			throw new InvalidArgException("Unknown item name: $item_name");
		}
	}
	
	function get_items(): array{
		return $this->items;
	}
	
	function get_node($name): Node{
		return $this->nodes[$name];
	}
	function get_node_by_hostname($hostname): String {
		foreach($this->nodes as $node){
			if($node->hostname == $hostname)
				return $node;
		}
		return false;
	}
	function get_nodes(): array{
		return $this->nodes;
	}
	
	function get_hardware($hw_name): ?Hardware{
		if(array_key_exists($hw_name, $this->hardware))
			return $this->hardware[$hw_name];
		else
			return null;
	}
	
}


?>