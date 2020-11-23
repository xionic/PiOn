<?php

namespace PiOn\Timer;

use \PiOn\Session;

use \Amp\Loop;

class WeeklyTimer extends Timer {

	private $secs, $mins, $hours, $days;
	private $week_fire_times = []; // fire times in seconds into the week. i.e. max value is 7*24*60*60
	private $interval_id;
	private $running = false;

	//takes arrays of units, or "*" for all
	function __construct($days, $hours, $mins, $secs) {
		$this->days = $days;
		$this->hours = $hours;
		$this->mins = $mins;
		$this->secs = $secs;

		//date_default_timezone_set(date_default_timezone_get());
	}

	function init(): void {
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
		//var_dump("sow: " .  $local_start_of_week);
		$secs_in_to_week = ($days * 86400) + ($hours * 3600) + ($mins * 60) + $secs; // nu,mber of seconds into the week to run
		$secs_in_week = 604800; //number in seconds in a week;
		//var_dump("secs_in_to_week: " . $secs_in_to_week);
		$next_run_time_rel = ($local_start_of_week + $secs_in_to_week) - self::local_time();
		//var_dump("next_run_time_rel: " . $next_run_time_rel);
		if ($next_run_time_rel < 0) { //run time for this week is in the past, schedule for next week
			$next_run_time_rel += $secs_in_week; 
		}
		//var_dump($real_next_run_time * 1000l);
		//var_dump("next_run_time_rel (week corrected):" . $next_run_time_rel);
		return $next_run_time_rel;
	}

	function start(): void {
		$this->running = true;
		if ($this->days == "*") {
			$this->days = [];
			foreach (range(0, 6) as $d) {
				$this->days[] = $d;
			}
		}
		if ($this->hours == "*") {
			$this->hours = [];
			foreach (range(0, 23) as $d) {
				$this->hours[] = $d;
			}
		}
		if ($this->mins == "*") {
			$this->mins = [];
			foreach (range(0, 59) as $d) {
				$this->mins[] = $d;
			}
		}
		if ($this->secs == "*") {
			$this->secs = [];
			foreach (range(0, 59) as $d) {
				$this->secs[] = $d;
			}
		}

		foreach ($this->days as $day) {
			foreach ($this->hours as $hour) {
				foreach ($this->mins as $min) {
					foreach ($this->secs as $sec) {
						//just for logging
						$ds = "+$day days $hour hours $min minutes $sec secs midnight sunday this week";
						$ts = strtotime($ds);
						plog("Adding weekly timer (day# $day) for task '{$this->task->name}' for every " . date("D H:i:s", $ts), DEBUG, Session::$INTERNAL);

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

	//registers the next event with the loop
	private function fire_next(array $fire_time): void {
		if ($this->running) {
			$day = $fire_time["day"];
			$hour = $fire_time["hour"];
			$min = $fire_time["min"];
			$sec = $fire_time["sec"];

			$next_run_time_rel = $this->calc_next_run_time_from_now($day, $hour, $min, $sec);
			$THIS = $this;

			$this->register_delay($next_run_time_rel, $day, $hour, $min, $sec);
		}
	}

	private function register_delay(int $next_run_time_rel, int $day, int  $hour, int $min, int $sec) {
		$this->schedule_delta($next_run_time_rel * 1000, function () use ($day, $hour, $min, $sec) {
			$this->fire_next(["day" => $day, "hour" => $hour, "min" => $min, "sec" => $sec]);
		});
	}

	function cancel(): void {
		$this->running = false;
		Loop::cancel($this->interval_id);
	}
}
