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
			
			$item_message = new ItemMessage($this->name, ItemMessage::GET);
			
			$rest_message = new RestMessage(RestMessage::REQ, NODE_NAME, $target_node->name, $target_node->port, $item_message->to_json());
		
			$value = null;
			$reponse_received = false;
			
			$client = Amp\Http\Client\HttpClientBuilder::buildDefault();
			$url = $target_node->get_base_url() . "/?data=". urlencode($rest_message->to_json());
			$THIS = $this;
			$call = Amp\Call(static function() use($client, $target_node, $url, $THIS){
				plog("Making REST request to ". $target_node->hostname. ", url: ".urldecode($url), DEBUG);
				$resp = yield $client->request(new Request($url));
				$json = yield $resp->getBody()->buffer();
				if($resp->getStatus() != 200){					
					throw new Exception("Error status received from " . $target_node->hostname . ": " . $resp->getStatus() . " Body is: " . $json);
				}
				var_dump($json);
				$v = ItemMessage::from_json($json);
				plog("Successfully retrieved remote value from node: " . $THIS->node_name. ", Value: {$v->value}", DEBUG);
				return $v;
			});			

			return $call;		
		}	
		
	}
	public function set_value($value): Promise{
		if(get_node($this->node_name)->name == NODE_NAME){ //item is local to this node		
			return \Amp\Call(function() use($value){
				return $this->set_value_local($value);
			});
		} else { // item on remote node
			plog("Item '" . $this->name . "' is on another host '" . $this->node_name . "'. Sending value...", VERBOSE);
			$target_node = get_model()->get_node($this->node_name);
			
			$item_message = new ItemMessage($this->name, ItemMessage::SET, $value);
			
			$rest_message = new RestMessage(RestMessage::REQ, NODE_NAME, $target_node->name, $target_node->port, $item_message->to_json());

			$reponse_received = false;
			
			$client = Amp\Http\Client\HttpClientBuilder::buildDefault();
			$url = $target_node->get_base_url() . "/?data=". urlencode($rest_message->to_json());
			$THIS = $this;
			$call = Amp\Call(static function() use($client, $target_node, $url, $THIS){
				plog("Making REST request to ". $target_node->hostname. ", url: ".urldecode($url), DEBUG);
				$resp = yield $client->request(new Request($url));
				$json = yield $resp->getBody()->buffer();
				plog("Got response from {$target_node->hostname}: $json", DEBUG);
				if($resp->getStatus() != 200){					
					throw new Exception("Error status received from " . $target_node->hostname . ": " . $resp->getStatus() . " Body is: " . $json);
				}
				$rest_reply = RestMessage::from_json($json);				
								
				plog("Successfully set remote value from node: " . $THIS->node_name, DEBUG);
				return $rest_reply->payload;
			});
			return $call;
		}
	}
	
	protected abstract function get_value_local(): ItemMessage;
	protected abstract function set_value_local($value): ItemMessage;
	
	public function __construct($name,$node,$hardware, $hardware_args){
		$this->name = $name;
		$this->node_name = $node;
		$this->hardware = $hardware;
		$this->hardware_args = $hardware_args;
				
	}
}

?>