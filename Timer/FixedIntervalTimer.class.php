<?php
namespace PiOn\Timer;

use \PiOn\Session;

class FixedIntervalTimer extends Timer{
	
	private $interval;
	private $schedule_id;
	
	//interval and start_offset in seconds
	function __construct($interval){
		$this->interval = $interval * 1000; // convert to ms;
	}
	
	function start(): void {
		plog("Starting FixedIntervalTimer with interval " . $this->interval, DEBUG, Session::$INTERNAL);
		$this->schedule_id = $this->schedule_repeat($this->interval);
	}	

	function init(): void {
	}
	
	function cancel(): void {
		$this->cancel($this->schedule_id);
	}
}

?>