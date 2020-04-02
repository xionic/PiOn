<?php
namespace PiOn\Event;

use \Amp\Loop;
use \PiOn\Session;

class WeeklyTimer implements Timer {
	
	private $sec, $min, $hour, $days;
	private $week_fire_times = []; // fire times in seconds into the week. i.e. max value is 7*24*60*60
	private $interval_id;
	private $running = false;
	
	//takes arrays of units, or "*" for all
	function __construct($days_or_astro, $hours = null, $mins = null, $secs = null){
		$this->days = $days_or_astro;

		if(($days_or_astro != "sunrise" && $days_or_astro != "sunset") && ($hours == null || $mins == null || $secs == null)){
			plog('Weekly timer must be constructed with ($days, $mins, $hours, $secs all) != null, or the strings "sunset|sunrise" as the first argument (rest omitted)', FATAL, Session::$INTERNAL);
		}
		$this->hours = $hours;
		$this->mins = $mins;
		$this->secs = $secs;
	}
	
	public function init_schedule(): void {
		if($this->days == "sunset"){
			$this->days = [];
			foreach(["mon", "tue", "wed", "thu", "fri", "sat", "sun"] as $d){
				$this->days[] = $d;
				$day_ts = strtotime("next $d");
				echo time("c", $day_ts);
				//$ss_time = date_sunrise()
			} die;
		} else if($this->days == "sunset"){

		}
		else {
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
		}
		$relative_time = strtotime("midnight sunday this week");
		foreach($this->days as $day){
			foreach($this->hours as $hour){
				foreach($this->mins as $min){
					foreach($this->secs as $sec){
						$ds = "+$day days $hour hours $min minutes $sec secs midnight sunday this week";
						$ts = strtotime($ds);
						if(!$ts){
							//throw exception
							throw new \Exception("Failed to parse date string: $ds");
						}
						plog("Adding weekly timer for $day days $hour hours $min minutes $sec secs = " . date("D H:i:s", $ts), DEBUG, Session::$INTERNAL) ;
						$this->week_fire_times[] = $ts - $relative_time;						
					}
				}
			}
		}		
		//var_dump($week_fire_times);
		
	}

	/**
	 * Returns the current base timestamp used for relative weekly calculations
	 */
	/*private function get_current_base(){

	}*/
	
	//Return number of seconds until this timer should next fire
	private function next_run_time_rel (): int {
		sort($this->week_fire_times);
		foreach($this->week_fire_times as $rel_fire_time){
			//add the time of the week fire time to the beginning of this week
			$real_fire_time = $rel_fire_time + strtotime("midnight sunday this week");
			plog("next_run_time_rel: $rel_fire_time $real_fire_time",DEBUG, Session::$INTERNAL);
			//compare our real fire time to the current time and see if the event is in the past or future
			$diff = $real_fire_time - time();
			if($diff > 0){ // this is the next run time
				return $diff;
			}
		}
		throw new \Exception("No next run time found");
	}
	
	function start(Callable $callback): void {
		$this->running = true;
		$this->fire_next($callback);
	}
	
	//registers the next event with the loop
	private function fire_next(Callable $callback): void {
		if($this->running){
			$THIS = $this;
			$next_run_time = $this->next_run_time_rel();
			$next_run_time_millis = $next_run_time*1000;
			$h = floor($next_run_time / 3600);
			$m = floor(($next_run_time % 3600)/60);
			$s = ($next_run_time % 3600) % 60;
			plog("Weekly timer scheduling next event with loop in ". $next_run_time ." seconds ($h hours, $m mins, $s secs)", VERBOSE, Session::$INTERNAL);
			Loop::delay($next_run_time_millis, function () use ($callback, $THIS){
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