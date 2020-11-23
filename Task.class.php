<?php
namespace PiOn\Task;

use \PiOn\Session;

class Task {
	
	public $callback;
	public $name;	
	
	function __construct(String $name, Callable $callback){
		$this->name = $name;
		$this->callback = function() use ($name, $callback){
			plog("Firing timer '{$this->name}'", VERBOSE, Session::$INTERNAL);
			call_user_func($callback);
		};
	}
	
}

?>