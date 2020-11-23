<?php
namespace PiOn\Timer;

use \PiOn\Session;

class AtTimeTimer extends Timer{	

	private $schedule_id;
	
	//interval and start_offset in seconds
	function __construct($timestamp){
		$this->timestamp = $timestamp;
	}
	
	function start(): void {
		plog("Starting AtTimeTimer with timestamp " . $this->timestamp, DEBUG, Session::$INTERNAL);
		$this->schedule_id = $this->schedule_repeat($this->timestamp);
	}
	
	function init(): void {
	}
	
	function cancel(): void {
		$this->cancel($this->schedule_id);
	}
}
