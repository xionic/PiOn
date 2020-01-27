<?php
namespace PiOn\Event;

use \Amp\Loop;

class FixedIntervalTimer implements Timer{
	
	private $interval;
	private $start_offset;
	private $interval_id;
	
	//interval and start_offset in seconds
	function __construct($interval, $start_offset = 0){
		$this->interval = $interval * 1000; // convert to ms;
		$this->start_offset = $start_offset;
	}
	
	function start(Callable $callback): void {
		$this->interval_id = Loop::repeat($this->interval, function() use($callback){
			call_user_func($callback);
		});
	}
	
	//satisfy interface
	function init_schedule(): void {}
	
	function cancel(): void {
		\Amp\cancel($this->interval_id);
	}
}

?>