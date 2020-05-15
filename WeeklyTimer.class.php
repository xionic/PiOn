<?php
namespace PiOn\Event;

use \DateTime;
use \DateTimeZone;

use \PiOn\Session;

use \Amp\Loop;

class WeeklyTimer implements Timer {
	
	private $sec, $min, $hour, $days;
	private $week_fire_times = []; // fire times in seconds into the week. i.e. max value is 7*24*60*60
	private $interval_id;
	private $running = false;
	private $task; /** @var $task Task */
	
	//takes arrays of units, or "*" for all
	function __construct($days_or_astro, $hours = null, $mins = null, $secs = null){
		$this->days = $days_or_astro;

		if(($days_or_astro != "sunrise" && $days_or_astro != "sunset") && ($hours == null || $mins == null || $secs == null)){
			plog('Weekly timer must be constructed with ($days, $mins, $hours, $secs all) != null, or the strings "sunset|sunrise" as the first argument (rest omitted)', FATAL, Session::$INTERNAL);
		}
		$this->hours = $hours;
		$this->mins = $mins;
		$this->secs = $secs;

		//date_default_timezone_set(date_default_timezone_get());
	}
	
	public function init_schedule(): void {
				
		//var_dump($week_fire_times);
		
	}

	/**
	 * Returns the current base timestamp used for relative weekly calculations
	 */
	private static function get_local_start_of_week(): int {
		$local_time = localtime(time(), true);
		$day_offset = $local_time["tm_wday"];
		$hour_offset = $local_time["tm_hour"];
		$min_offset = $local_time["tm_min"];
		$sec_offset = $local_time["tm_sec"];

		$total_offset = $day_offset * 86400;
		$total_offset += $hour_offset * 3600;
		$total_offset += $min_offset * 60;
		$total_offset += $sec_offset;

		$start_of_week = time() - $total_offset;
		
		echo "get_local_start_of_week returning: $start_of_week" . PHP_EOL;
		return $start_of_week;
		
	}

	/**
	 * Returns next run time in secs. May be in the next week.
	 */
	private function calc_next_run_time_from_now(int $days, int $hours, int $mins, int $secs): int {
		//echo "d $days h $hours m $mins s $secs\n";
		$local_start_of_week = self::get_local_start_of_week();
		var_dump("sow: " .  $local_start_of_week);
		$secs_in_to_week = ($days * 86400) + ($hours * 3600) + ($mins * 60) + $secs;
		var_dump("local_time: " . self::local_time());
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
		
		foreach($this->days as $day){
			foreach($this->hours as $hour){
				foreach($this->mins as $min){
					foreach($this->secs as $sec){
						//just for logging
						$ds = "+$day days $hour hours $min minutes $sec secs midnight sunday this week";
						$ts = strtotime($ds);
						plog("Adding weekly timer (day# $day) for task '{$this->task->name}' for every " . date("D H:i:s", $ts), DEBUG, Session::$INTERNAL) ;

						$next_run_time_rel = $this->calc_next_run_time_from_now($day, $hour, $min, $sec);
					/*var_dump($next_run_time_rel);
						var_dump($this->get_start_of_week());
						var_dump($next_run_time_rel + $this->get_start_of_week());*/
						$this->register_delay($next_run_time_rel, $day, $hour, $min, $sec);
					}
				}
			}
		}
	}

	private function register_delay($next_run_time_rel, $day, $hour, $min, $sec): void{
		$delay_string = "";
		//var_dump("THIS ". $next_run_time_rel % 86400);
		if(($d = floor($next_run_time_rel / 86400)) > 0){
			$delay_string .= "$d days";
		}
		if(($h = floor(($next_run_time_rel % 86400) / 3600)) > 0){
			$delay_string .= " $h hours";
		}
		if(($m = floor((($next_run_time_rel % 86400) % 3600) / 60)) > 0){
			$delay_string .= " $m mins ";
		}
		$delay_string .= $m%60 . " secs";
// var_dump(strtotime("sunday last week"));
// var_dump(self::local_time());
// var_dump($next_run_time_rel + self::local_time());
		plog("Weekly timer scheduling task '{$this->task->name}' (day#: $day) for " . date("Y/m/d H:i:s", $next_run_time_rel + self::local_time()).". Delaying for $delay_string ($next_run_time_rel secs total)", VERBOSE, Session::$INTERNAL);
		$THIS = $this;
		$task = $this->task;
		plog("next_run_tim_rel: $next_run_time_rel", DEBUG, Session::$INTERNAL);
		Loop::delay($next_run_time_rel*1000, function () use ($task, $THIS, $day, $hour, $min, $sec){
			call_user_func($task->callback);
			$this->fire_next(["day" => $day, "hour" => $hour, "min" => $min, "sec" => $sec]);
		});
	}

	private static function local_time(): int {
		/*$d = new DateTime("now", new DateTimeZone(date_default_timezone_get()));
		var_dump("ts: " . $d->getTimestamp() . " offset:" . $d->getOffset());
		return $d->getTimestamp() + $d->getOffset();*/
		return time();
	}
	
	
	//registers the next event with the loop
	private function fire_next(array $fire_time): void {
		if($this->running){
			$day = $fire_time["day"];
			$hour = $fire_time["hour"];
			$min = $fire_time["min"];
			$sec = $fire_time["sec"];

			$next_run_time_rel = $this->calc_next_run_time_from_now($day, $hour, $min, $sec);
			$THIS = $this;

			$this->register_delay($next_run_time_rel, $day, $hour, $min, $sec);
		}
	}
	
	function cancel(): void {
		$this->running = false;
		\Amp\cancel($this->interval_id);
	}
}

?>