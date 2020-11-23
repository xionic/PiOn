<?php
namespace PiOn\Timer;

use \PiOn\Session;
use \PiOn\Task\Task;

use \Amp\Loop;


abstract class Timer {

	

	public abstract function start(): void;
	public abstract function init(): void;

	protected $task;

	public function set_task(Task $task){
		$this->task = $task;
	}

	/**
	 * Schedules given task for execution in specified delta of ms
	 * complete_callback is called after task is complete
	 */
	protected function schedule_delta(int $ms, Callable $complete_callback = null){
		plog("Scheduling Task ({$this->task->name}) to run in {$ms}ms", DEBUG, Session::$INTERNAL);
		return Loop::delay($ms, function() use($complete_callback) {
			$result = call_user_func($this->task->callback);
			if ($complete_callback !== null) {
				call_user_func($complete_callback, $result);
			}
		});
	}

	/**
	 * Schedules given task for execution in specified repeat interval of ms
	 * complete_callback is called after task is complete
	 */
	protected function schedule_repeat(int$ms, callable $complete_callback = null) {
		plog("Scheduling repeating Task ({$this->task->name}) to run at {$ms}ms intervals", DEBUG, Session::$INTERNAL);
		return $this->watcher_id = Loop::repeat($ms, function () use ($complete_callback) {
			$result = call_user_func($this->task->callback);
			if ($complete_callback !== null) {
				call_user_func($complete_callback, $result);
			}
		});
	}

	/**
	 * Schedules given task for execution in at specified timestamp in secs
	 * complete_callback is called after task is complete
	 */
	protected function schedule_at_time(int $timestamp, Callable $complete_callback = null) {

		$next_run_time_rel = $timestamp - self::local_time();
		if($next_run_time_rel <= 0){
			plog("Timer: Next run time is negative - skipping task: {$this->task->name}", ERROR, Session::$INTERNAL);
			//return;
		}

		$delay_string = "";
		$rem = 0;
		//var_dump("THIS ". $next_run_time_rel % 86400);
		if (($d = floor($next_run_time_rel / 86400)) > 0) {
			$delay_string .= "$d days ";
		}
		$rem = floor($next_run_time_rel % 86400);

		if (($h = floor($rem / 3600)) > 0) {
			$delay_string .= " $h hours ";
		}
		$rem = floor($rem % 3600);

		if (($m = floor($rem / 60)) > 0) {
			$delay_string .= " $m mins ";
		}
		$rem = floor($rem % 60);

		$delay_string .= "$rem secs";
		// var_dump(strtotime("sunday last week"));
		// var_dump(self::local_time());
		// var_dump($next_run_time_rel + self::local_time());
		plog("Timer scheduling task '{$this->task->name}' for " . date("Y/m/d H:i:s", $next_run_time_rel + self::local_time()) . ". Delaying for $delay_string ($next_run_time_rel secs total)", VERBOSE, Session::$INTERNAL);

		//	plog("next_run_tim_rel: $next_run_time_rel", DEBUG, Session::$INTERNAL);
		//var_dump($next_run_time_rel*1000);
		Loop::delay($next_run_time_rel * 1000, function () use ($complete_callback) {
			plog("in callback", DEBUG, Session::$INTERNAL);
			$result = call_user_func($this->task->callback);
			if($complete_callback !== null){
				call_user_func($complete_callback, $result);
			}
		});


		/*return $this->watcher_id = Loop::repeat($ms, function () use ($task) {
				call_user_func($task->callback);
			});*/
	}

	/**
	 * cancels a previously established schedule using the returned id;
	 */
	protected function cancel_schedule($id){
		Loop::cancel($id);
	}

	protected static function local_time(): int {
		/*$d = new DateTime("now", new DateTimeZone(date_default_timezone_get()));
		var_dump("ts: " . $d->getTimestamp() . " offset:" . $d->getOffset());
		return $d->getTimestamp() + $d->getOffset();*/
		return time();
	}
		
}

?>