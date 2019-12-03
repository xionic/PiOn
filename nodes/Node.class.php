<?php

abstract class Node {
	
	public $name;
	public $hostname;
	public $port;
	public $http_port;
	
	function __construct($name, $hostname, $port, $http_port){
		$this->name = $name;
		$this->hostname = $hostname;
		$this->port = $port;
		$this->http_port = $http_port;
	}
	
}

?>
