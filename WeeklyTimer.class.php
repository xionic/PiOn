<?php
namespace PiOn\Event;

use \Amp\Loop;
use \PiOn\Session;

class WeeklyTimer implements Timer{
	
	private $sec, $min, $hour, $days;
	private $week_fire_times = []; // fire times in seconds into the week. i.e. max value is 7*24*60*60
	private $interval_id;
	private $running = false;
	
	//takes arrays of units, or "*" for all
	function __construct($days, $hours, $mins, $secs){
		$this->days = $days;
		$this->hours = $hours;
		$this->mins = $mins;
		$this->secs = $secs;
	}
	
	public function init_schedule(): void {
		if($this->days == "*"){
			$this->days = [];
			foreach(range(0, 6) as $d){
				$this->days[] = $d;
			}
		}
		if($this->hours == "*"){
			$this->hours = [];
			foreach(range(0, 23) as $d){
				$this->hours[] = $d;
			}
		}
		if($this->mins == "*"){
			$this->mins = [];
			foreach(range(0, 59) as $d){
				$this->mins[] = $d;
			}
		}
		if($this->secs == "*"){
			$this->secs = [];
			foreach(range(0, 59) as $d){
				$this->secs[] = $d;
			}
		}
		$relative_time = strtotime("midnight last sunday");
		foreach($this->days as $day){
			foreach($this->hours as $hour){
				foreach($this->mins as $min){
					foreach($this->secs as $sec){
						$ds = "+$day days $hour hours $min minutes $sec secs midnight last sunday";
						$ts = strtotime($ds);
						if(!$ts){
							//throw exception
							throw new \Exception("Failed to parse date string: $ds");
						}
						plog("Adding weekly timer for now + $day days $hour hours $min minutes $sec secs = " . date("D H:i:s", $ts), DEBUG, Session::$INTERVAL) ;
						$this->week_fire_times[] = $ts - $relative_time;
						//add next week's schedule too to make finding the next fire time easier
						$ts = strtotime("+$day days $hour hours $min minutes $sec secs midnight next sunday");
						$this->week_fire_times[] = $ts - $relative_time;
						//$d = new DateTime;$d->setTimestamp(intval($week_fire_times[0])); echo $d->format("D H:i:s") . PHP_EOL;
						
					}
				}
			}
		}		
		//var_dump($week_fire_times);
		
	}
	
	//Return number of seconds until this timer should next fire
	private function next_run_time_rel (): int {
		sort($this->week_fire_times);
		foreach($this->week_fire_times as $rel_fire_time){
			//add the time of the week fire time to the beginning of this week
			$real_fire_time = $rel_fire_time + strtotime("midnight last sunday");
			//compare our real fire time to the current time and see if the event is in the past or future
			$diff = $real_fire_time - time();
			if($diff > 0){ // this is the next run time
				return $diff;
			}
		}
		throw new Exception("No next runtime found");
	}
	
	function start(Callable $callback): void {
		$this->running = true;
		$this->fire_next($callback);
	}
	
	//registers the next event with the loop
	private function fire_next(Callable $callback): void {
		if($this->running){
			$THIS = $this;
			Loop::delay($THIS->next_run_time_rel()*1000, function () use ($callback, $THIS){
				call_user_func($callback);
				$THIS->fire_next($callback);
			});
		}
	}
	
	function cancel(): void {
		$this->running = false;
		\Amp\cancel($this->interval_id);
	}
}

?>