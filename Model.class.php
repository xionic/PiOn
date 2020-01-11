<?php

class Model {
	
	protected $nodes = array();
	protected $items = array();
	protected $hardware = array();
	
	function __construct($model_conf){
		
		//load nodes
		foreach($model_conf->nodes as $node){
			$this->nodes[$node->name] = new NodeStandard($node->name, $node->hostname, $node->port);
		}
		
		//load hardware
		foreach($model_conf->hardware as $hw){ //SECURITY
			$class = "Hardware".$hw->type;
			$this->hardware[$hw->name] = new $class($hw->name, $hw->node, $hw->typeargs);
		}
		
		//load items
		foreach($model_conf->items as $item){ //SECURITY
			$class = "Item".$item->type;
			$this->items[$item->name] = new $class($item->name, $item->node, $item->typeargs, $this->get_hardware($item->hardware->name), $item->hardware->args);
			
		}
		
		//var_dump($this);
	}
	
	function get_item($item_name){
		//var_dump($this->items[$item_name]);
		if(array_key_exists($item_name, $this->items)){
		//echo "NIUSDFSF $item_name\n";
			return $this->items[$item_name];
		}
		else
			return false;
	}
	function get_node($name){
		return $this->nodes[$name];
	}
	function get_node_by_hostname($hostname){
		foreach($this->nodes as $node){
			if($node->hostname == $hostname)
				return $node;
		}
		return false;
	}
	
	function get_hardware($hw_name){
		if(array_key_exists($hw_name, $this->hardware))
			return $this->hardware[$hw_name];
		else
			return false;
	}
	
}


?>