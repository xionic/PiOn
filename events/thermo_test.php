<?php
use \PiOn\Event\Scheduler;
use \PiOn\Event\FixedIntervalTimer;
use \PiOn\Event\WeeklyTimer;
use \PiOn\Event\EventManager;
use \PiOn\Item\Value;

$settemp = 23.5;

Scheduler::register_task("Update Nick Temp", "xealot_server", new FixedIntervalTimer(30), function(){
	echo "TIMER TEST YYAYYYAYAYAYY\n";
	\Amp\call(function(){
		yield get_item('Nick Room Temp')->get_value();
	});
});

Scheduler::register_task("Nick heater up", "xealot_server", new WeeklyTimer("*", [23], [0],[0]), function() use($settemp){
	$settemp = 23.5;
});
Scheduler::register_task("Nick heater down", "xealot_server", new WeeklyTimer("*", [11], [0],[0]), function() use($settemp){
	$settemp = 22;
});
Scheduler::register_task("Lights On", "pi1", new WeeklyTimer("*", [16,19], [0],[0]), function(){
	get_item("GPIO Toggler test 2")->set_value(new Value(1));
	get_item("GPIO Toggler test 3")->set_value(new Value(1));
	get_item("GPIO Toggler test 4")->set_value(new Value(1));
});
Scheduler::register_task("Living Lights off", "pi1", new WeeklyTimer("*", [23], [0],[0]), function(){
	get_item("GPIO Toggler test 2")->set_value(new Value(0));
	get_item("GPIO Toggler test 3")->set_value(new Value(0));
});



EventManager::register_item_event_handler("Nick Room Temp", ITEM_EVENT, "xealot_server", ITEM_VALUE_CHANGED, function($event_name, $item_name, Value $value) use($settemp){
	$temp = $value->data;
	$hyst = 0.3;
	$set = $settemp;
	plog("Setting Nick thermo. temp:$temp set:$set\n", VERBOSE);
	//$temp = 23.1;
	if($temp > $set){
		get_item("GPIO Toggler test 1")->set_value(new Value(0));
	} else if ($temp < ($set - $hyst)){
		get_item("GPIO Toggler test 1")->set_value(new Value(1));
	}
});



?>