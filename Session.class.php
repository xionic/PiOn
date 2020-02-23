<?php
namespace PiOn;

class Session {
	
	private $id;
	private $attributes = [];
	private static $req_num_counter = 0;
	private $req_num;
	public static $INTERNAL; // Session object for code not run under a session. E.g. Event callbacks
	
	function __construct($prefix = ""){
		$this->id = \uniqid();
		$this->req_num = $prefix . self::$req_num_counter++;
	}
	
	function set_attribute(string $key, $data): void {		
		$this->attributes[$key] = $data;	
	}	
	
	function remove_attribute(string $key): void {
		if(array_key_exists($key, $this->attributes))
			unset($this->attributes[$key]);
		else
			throw new Exception("No such session key: '$key'");
	}
	
	function get_attribute(string $key){
		if(array_key_exists($key, $this->attributes))
			return $this->attributes[$key];
		else
			throw new Exception("No such session key: '$key'");
	}
	
	function has_attribute(string $key): bool {
		return array_key_exists($key, $this->attributes);		
	}
	
	public function get_req_num(): string{
		return $this->req_num;
	}

	public function get_id(): string {
		return $this->id;
	}
	
	public static function init(){
		Session::$INTERNAL = new Session();
		Session::$INTERNAL->req_num = 0;
		Session::$INTERNAL->id = 0;
	}
	
}



?>