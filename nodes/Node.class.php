<?php
namespace PiOn\Node;

abstract class Node {
	
	public $name;
	public $hostname;
	public $port;
	
	function __construct($name, $hostname, $port){
		$this->name = $name;
		$this->hostname = $hostname;
		$this->port = $port;
	}
	
	function get_base_url($scheme = "HTTP"){
		return $scheme . "://" . $this->hostname . ":" . $this->port;
	}

	function get_base_url_ip($scheme = "HTTP"){
		return $scheme . "://" . gethostbyname($this->hostname) . ":" . $this->port;
	}
	
}

?>
