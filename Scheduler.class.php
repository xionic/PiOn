<?php
namespace PiOn\Timer;

use \PiOn\Session;

class Scheduler {
	
	private static $tasks; 
	
	static function init(){
		self::$tasks = [];
	}
	
	static function register_task(String $name, String $node_name, Timer $timer, Callable $callback): void { // validation

		if($node_name == NODE_NAME){
			plog("Scheduling task: $name", VERBOSE, Session::$INTERNAL);
			self::$tasks[$name] = new Task($name, $timer, $callback);
			$timer->start(self::$tasks[$name]);
		}
	}
	
}

?>