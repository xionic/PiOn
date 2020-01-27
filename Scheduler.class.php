<?php
namespace PiOn\Event;

class Scheduler {
	
	private static $tasks; 
	
	static function init(){
		self::$tasks = [];
	}
	
	static function register_task(String $name, String $node_name, Timer $timer, Callable $callback): void {

		plog("Scheduling task: $name", VERBOSE);
		if($node_name == NODE_NAME){
			self::$tasks[$name] = new Task($name, $timer, $callback);
			self::$tasks[$name]->start();
		}
	}
	
}

?>