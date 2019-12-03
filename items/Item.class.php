<?php

use Clue\React\Block;

abstract class Item {
	
	public $name;
	public $node;
	public $type_args;
	public $value;
	
	public function get(){
		if(get_node($this->node)->name == NODE_NAME){
		
			return $this->get_local();
		} else { // Item is on a remote node		
			$target_node = get_model()->get_node($this->node);
			$req = new Socket_Message();
			$req->item_name = $this->name;
			$req->sending_node = NODE_NAME;
			$req->target_node = $target_node->name;
			$req->target_port = $target_node->port;
			$req->action = "get";
			$req->type = "req";
			
			$value = null;
			$inner_promise = null;
			$connector = new React\Socket\Connector(get_loop(), array('timeout' => 5));
			$to_node = get_node($this->node);
			$promise = $connector->connect($to_node->hostname . ":" . $to_node->port)->then(function (React\Socket\ConnectionInterface $connection) use ($req, &$value, &$inner_promise) {
				plog("writing socket message to remote", DEBUG);
				$connection->write($req->to_json());
				$connection->on('data', function ($data) use ($connection, &$value){
						plog("reading socket message from remote: " . $data, DEBUG);
						$connection->end();
						$value = $data;
						get_loop()->stop();
				});	
			});
			//wait for response from remote node			
			Block\await($promise, get_loop(), 5000);
			if($value == null){
				get_loop()->run();
			}
			
			$item = json_decode($value);
			return $item;
			
			
			
		}
		
	}
	public function set_value(){
		
	}
	
	protected abstract function get_local();
	protected abstract function set_local();
	
	public function __construct($name,$node,$type_args){
		$this->name = $name;
		$this->node = $node;
		$this->type_args = $type_args;
	}
	
	public function register_itemtype(){}
	
}

?>