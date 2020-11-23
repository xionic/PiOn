<?php
namespace PiOn\Scheduler;

use PiOn\Scheduler\ScheduledTask;
use \PiOn\Session;
use \PiOn\Task\Task;
use \PiOn\Timer\Timer;

class Scheduler {
	
	private static $tasks; 
	
	static function init(){
		self::$tasks = []; //array of ScheduledTasks;
	}
	
	static function register_task(String $name, String $node_name, Timer $timer, Callable $callback): void { 
		//TODO validation

		if($node_name == NODE_NAME){
			$task = new Task($name, $callback);
			plog("Scheduling task: $name", VERBOSE, Session::$INTERNAL);
			$st = new ScheduledTask($task, $timer);
			self::$tasks[$name] = $st;
			$st->init();
			$st->start();
		}
	}
	
}

?>