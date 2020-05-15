<?php
use \PiOn\Event\Scheduler;
use \PiOn\Event\FixedIntervalTimer;
use \PiOn\Event\WeeklyTimer;
use \PiOn\Event\EventManager;
use \PiOn\Item\Value;
use \Pion\Session;



/*Scheduler::register_task("Update Nick Temp", "xealot_server", new FixedIntervalTimer(30), function(){
	\Amp\call(function(){
		//trigger a change event if value has changed
		$val = yield get_item('Nick Room Temp')->get_value(Session::$INTERNAL);
		//yield get_item('Nick Room Temp')->set_value(Session::$INTERNAL, $val);
		
	});
});*/
/*
Scheduler::register_task("Nick heater up", "xealot_server", new WeeklyTimer("*", [23], [0],[0]), function(){
	get_item('GPIO Toggler test 1')->set_value(new Value(23.5));
});
Scheduler::register_task("Nick heater down", "xealot_server", new WeeklyTimer("*", [11], [0],[0]), function(){
	get_item('GPIO Toggler test 1')->set_value(new Value(22));
});*/

/*
Scheduler::register_task("test", "xealot_server", new FixedIntervalTimer(10), function(){
	\Amp\call(function(){
		//trigger a change event if value has changed
		yield get_item('Nick Room Temp')->set_value(Session::$INTERNAL, new Value(rand(0,50)));
		//yield get_item('Nick Room Temp')->set_value(Session::$INTERNAL, $val);
		
	});
});*/


?>
