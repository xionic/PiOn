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
				"nodes" => ["obj"],
				"/nodes/*/" => ['obj'],
				
				"/nodes/*/hostname" => ["notblank"],
				"/nodes/*/port" => ["int", "notzero"],

				"hardware" => ["obj"],
				"/hardware/*/" => ['obj'],
				
				"/hardware/*/type" => ["notblank"],
				"/hardware/*/typeargs" => ["?obj"],
				"/hardware/*/node" => ["notblank", function($value){
					//TODO ensure exists
					return true;
				}],
				"/hardware/*/capabilities" => ["array"],
				"/hardware/*/capabilities/*/" => ["regex /(^get$|^set$)/"],

				"items" => ["obj"],
				"/items/*/" => ['obj'],
				
				"/items/*/node" => ["notblank", function($value){
					//TODO ensure exists
					return true;
				}],
				"/items/*/itemargs" => ["?obj"],
				"/items/*/enabled" => ["optional", "bool"],
				"/items/*/hardware" => ["optional", "obj"],
				"/items/*/hardware/name" => ["optional", "notblank", function($value){
					//TODO ensure exists
					return true;
				}],
				"/items/*/hardware/args" => ["optional", "obj"]	,
			], null, false);

		} catch(ValidationException $ve){
			plog("Failed to validate config", ERROR, Session::$INTERNAL);
			plog($ve->getMessage(), FATAL, Session::$INTERNAL);
		}
		plog("config.json validation succeeded", VERBOSE, Session::$INTERNAL);

		//load nodes
		foreach($model_conf->nodes as $node_name => $node){
			plog("Creating Node '{$node_name}'", DEBUG, Session::$INTERNAL);
			$this->nodes[$node_name] = new \PiOn\Node\NodeStandard($node_name, $node->hostname, $node->port);
		}
		
		//load hardware
		foreach($model_conf->hardware as $hardware_name => $hw){ //SECURITY
			plog("Creating Hardware '{$hardware_name} of type '{$hw->type}''", DEBUG, Session::$INTERNAL);
			$class = "\PiOn\Hardware\Hardware".$hw->type;
			//var_dump($class);
			//new Value("r");
			//new \PiOn\Hardware\HardwareGPIO(null, null, null, null);
			$this->hardware[$hardware_name] = new $class($hardware_name, $hw->node, $hw->capabilities, $hw->typeargs);
		}
		
		//load items
		foreach($model_conf->items as $item_name => $item){ //SECURITY
			if(!property_exists($item, "enabled") || $item->enabled){
				plog("Creating Item '{$item_name}' of type '{$item->type}'", DEBUG, Session::$INTERNAL);
				$hw = null;
				$hw_args = null;
				if(property_exists($item, "hardware") && property_exists($item->hardware, "name")){
					$hw = $this->get_hardware($item->hardware->name);
					$hw_args = $item->hardware->args;
				}				
				$this->items[$item_name] = Item::create_item($item->type, $item_name, $item->node, $item->itemargs, $hw, $hw_args);		
			}	
		}
		
		//var_dump($this);
		
		
	}
	
	//now all  items are constructed, do init
	function init(){
		$init_promises = [];
		foreach($this->items as $item){	
			if(method_exists($item, "init")){
				$item->init();
			}
		}
		//\Amp\Promise\wait(\Amp\Promise\all($init_promises));
		plog("Model init finished", INFO, Session::$INTERNAL);
	}
	
	function get_item($item_name): Item{
		Argh::validate(["item_name" => $item_name],[
			"item_name" => ["notblank"]
		]);
		if(array_key_exists($item_name, $this->items)){
		//echo "NIUSDFSF $item_name\n";
			return $this->items[$item_name];
		}
		else{
			plog("Unknown item name: $item_name", ERROR, Session::$INTERNAL); //TODO
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