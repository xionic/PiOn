<?php
namespace PiOn\Timer;

use \PiOn\Session;

class Task {
	
	public $callback;
	private $timer;
	public $name;	
	
	function __construct(String $name, Timer $timer, Callable $callback){
		$this->name = $name;
		$this->timer = $timer;
		$this->timer->init_schedule();
		$this->callback = function() use ($name, $callback){
			plog("Firing timer '{$this->name}'", VERBOSE, Session::$INTERNAL);
			call_user_func($callback);
		};
	}
	
}

?>