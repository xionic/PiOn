<?php

class Model {
	
	protected $nodes = array();
	protected $items = array();
	protected $hardware = array();
	
	function __construct($model_conf){
		
		//load nodes
		foreach($model_conf->nodes as $node){
			$this->nodes[$node->name] = new NodeStandard($node->name, $node->hostname, $node->port, $node->http_port);
		}
		
		//load hardware
		
		//load items
		foreach($model_conf->items as $item){ //SECURITY
			$class = $item->type;
			$this->items[$item->name] = new $class($item->name, $item->node, $item->typeargs);
		}
		
		//var_dump(array($this->nodes, $this->hardware, $this->items));
	}
	
	function get_item($item_name){
		return $this->items[$item_name];
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
	
}


?>