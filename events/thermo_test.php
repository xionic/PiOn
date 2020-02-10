<?php
use \PiOn\Event\Scheduler;
use \PiOn\Event\FixedIntervalTimer;
use \PiOn\Event\WeeklyTimer;
use \PiOn\Event\EventManager;
use \PiOn\Item\Value;



Scheduler::register_task("Update Nick Temp", "xealot_server", new FixedIntervalTimer(30), function(){
	\Amp\call(function(){
		yield get_item('Nick Room Temp')->get_value();
	});
});
/*
Scheduler::register_task("Nick heater up", "xealot_server", new WeeklyTimer("*", [23], [0],[0]), function(){
	get_item('GPIO Toggler test 1')->set_value(new Value(23.5));
});
Scheduler::register_task("Nick heater down", "xealot_server", new WeeklyTimer("*", [11], [0],[0]), function(){
	get_item('GPIO Toggler test 1')->set_value(new Value(22));
});
Scheduler::register_task("Lights On", "pi1", new WeeklyTimer("*", [16], [0],[0]), function(){
	get_item("TV Light")->set_value(new Value(1));
	get_item("Table Lamp")->set_value(new Value(1));
	get_item("GPIO Toggler test 4")->set_value(new Value(1));
});
Scheduler::register_task("Living Lights off", "pi1", new WeeklyTimer("*", [23], [0],[0]), function(){
	get_item("TV Light")->set_value(new Value(0));
	get_item("Table Lamp")->set_value(new Value(0));
});*/

?>
