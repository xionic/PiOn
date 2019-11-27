<?php

abstract class Node {
	
	public $name;
	public $hostname;
	public $port;
	
	function __construct($name, $hostname, $port){
		$this->name = $name;
		$this->hostname = $hostname;
		$this->port = $port;
	}
	
}

?>
