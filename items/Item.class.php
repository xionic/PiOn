<?php

use \Amp\Http\Client\HttpClientBuilder;
use \Amp\Http\Client\Request;
use \Amp\Promise;

abstract class Item {
	
	public $name;
	public $node_name;
	public $hardware;
	public $hardware_args = array();
	public $type = null;
	
	public function get_value(): Promise {
		
		if(get_node($this->node_name)->name == NODE_NAME){ //item is local to this node		
			plog("Item '" . $this->name . "' is local", VERBOSE);
			$call = \Amp\Call(function(){
				return $this->get_value_local();				
			});
			return $call;
		} else { // Item is on a remote node
			plog("Item '" . $this->name . "' is on another host '" . $this->node_name . "'. Requesting value...", VERBOSE);
			$target_node = get_model()->get_node($this->node_name);
			
			$item_message = new ItemMessage($this->name, ITEM_GET);
			
			$req = new RestMessage(REQ, $this->node_name, NODE_NAME, $target_node->name, $target_node->port, null);
		
			$value = null;
			$reponse_received = false;
			
			$client = Amp\Http\Client\HttpClientBuilder::buildDefault();
			$url = $target_node->get_base_url() . "/?". urlencode($item_message->to_json());
			$call = Amp\Call(static function() use($client, $target_node, $url){
				plog("Making HTTP request to ". $target_node->hostname. ", url: $url", VERBOSE);
				$resp = yield $client->request(new Request($url));
				$json = yield $resp->getBody()->buffer();
				if($resp->getStatus() != 200){					
					throw new Exception("Error status received from " . $target_node->hostname . ": " . $resp->getStatus() . " Body is: " . $json);
				}
				var_dump($json);
				$v = json_decode($json)->value;
				plog("Successfully retrieved remote value from node: " . $this->node_name. " Value: $v", DEBUG);
				return $v;
			});			

			return $call;		
		}	
		
	}
	public function set_value($value){
		if(get_node($this->node_name)->name == NODE_NAME){ //item is local to this node
		
			return $this->set_value_local($value);
		} else { // item on remote node
			
		}
	}
	
	protected abstract function get_value_local();
	protected abstract function set_value_local($value);
	
	public function __construct($name,$node,$hardware, $hardware_args){
		$this->name = $name;
		$this->node_name = $node;
		$this->hardware = $hardware;
		$this->hardware_args = $hardware_args;
				
	}
	
	public function to_ItemMessage(){
		return new ItemMessage($this->name, $this->node_name, $this->type);
	}
	
	public function register_itemtype(){}
	
}

?>