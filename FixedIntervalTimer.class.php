<?php
namespace PiOn\Event;

use \Amp\Loop;
use \PiOn\Session;

class FixedIntervalTimer implements Timer{
	
	private $interval;
	private $start_offset;
	private $interval_id;
	protected $task;
	
	//interval and start_offset in seconds
	function __construct($interval, $start_offset = 0){
		$this->interval = floor($interval * 1000); // convert to ms;
		$this->start_offset = $start_offset;
	}
	
	function start(Task $task): void {
		$this->task = $task;
		plog("New FixedIntervalTimer with interval " . $this->interval, DEBUG, Session::$INTERNAL);
		$this->interval_id = Loop::repeat($this->interval, function() use($task){
			call_user_func($task->callback);
		});
	}
	
	//satisfy interface
	function init_schedule(): void {}
	
	function cancel(): void {
		\Amp\cancel($this->interval_id);
	}
}

?>