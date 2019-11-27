<?php

abstract class Item {
	
	public $name;
	public $node;
	public $type_args;
	public $value;
	
	public function get(){
		if($this->node == NODE_NAME){
			return $this->get_local();
		} else { // Item is on a remote node
			$req = new Socket_Message();
			$req->sending_node = NODE_NAME;
			$req->action = "get";
			$req->type = "req";
			$req->payload = $this->get_local();
			
			$connector = new React\Socket\Connector($loop);
			$connector->connect('127.0.0.1:8080')->then(function (React\Socket\ConnectionInterface $connection) {
				$connection->write($req->to_json());
			});
			 $connection->on('data', function ($data) use ($connection) {
				$connection->close();
				return json_decode($data);
			});
		}
		
	}
	public function set(){
		
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