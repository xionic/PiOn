<?php

use Amp\Http\Client\HttpClientBuilder;
use Amp\Http\Client\Request;
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
			$req = new Rest_Message();
			$req->item_name = $this->name;
			$req->sending_node = NODE_NAME;
			$req->target_node = $target_node->name;
			$req->target_port = $target_node->port;
			$req->action = "get";
			$req->type = "req";
			
			$value = null;
			$reponse_received = false;
			
			$to_node = get_node($this->node_name);
			
			$client = Amp\Http\Client\HttpClientBuilder::buildDefault();
			$url = "http://" . $to_node->hostname . ":" . $to_node->http_port . "/?action=get&item_name=". urlencode($this->name);
			$call = Amp\Call(static function() use($client, $to_node, $url){
				plog("Making HTTP request to ". $to_node->hostname. ", url: $url", VERBOSE);
				$resp = yield $client->request(new Request($url));
				$json = yield $resp->getBody()->buffer();
				return json_decode($json)->value;
			});			
			plog("Successfully retrieved remote value from node: " . $this->node_name. " Value: $v", DEBUG);

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