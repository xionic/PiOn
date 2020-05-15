<?php

use \PiOn\Event\Scheduler;
use \PiOn\Event\FixedIntervalTimer;
use \PiOn\Event\WeeklyTimer;
use \PiOn\Event\EventManager;
use \PiOn\Item\Value;
use \Pion\Session;

/*
Scheduler::register_task("Test timer", "xealot_server", new FixedIntervalTimer(2000), function(){
	echo "TIMER TEST YYAYYYAYAYAYY\n";
	\Amp\call(function(){
		var_dump(yield get_item('GPIO Toggler test 4')->set_value(new Value(null)));
	});
});
*/
/*
Scheduler::register_task("Test cal timer", "xealot_server", new WeeklyTimer([4],[20,21,22,23],"*",[0]), function(){
	\Amp\call(function(){
		$val = yield get_item('GPIO Toggler test 4')->get_value();
		//var_dump($val);
		echo "CALENDAR TIMER TEST YYAYYYAYAYAYY {$val->value->value}\n";
		yield get_item('GPIO Toggler test 4')->set_value(new Value(null));
	});

});*/

Scheduler::register_task("Lights On 1", "pi1", new WeeklyTimer("*", [20], [15],[0]), function(){
	\Amp\call(function(){
		yield get_item("ESP8266 Plug Test")->set_value(Session::$INTERNAL, new Value(Value::ON));
	});
});

Scheduler::register_task("Lights On 2", "pi1", new WeeklyTimer("*", [20], [15],[0]), function(){
	\Amp\call(function(){
		yield get_item("Table Lamp")->set_value(Session::$INTERNAL, new Value(Value::ON));
		yield get_item("Nick Bed Lights")->set_value(Session::$INTERNAL, new Value(Value::ON));
	});
});

Scheduler::register_task("Lights On 3", "pi1", new WeeklyTimer("*", [20], [30],[0]), function(){
	\Amp\call(function(){
		yield get_item("TV Light")->set_value(Session::$INTERNAL, new Value(Value::ON));
	});
});

Scheduler::register_task("TV Light off", "pi1", new WeeklyTimer("*", [22], [30],[0]), function(){
	\Amp\call(function(){
		yield get_item("TV Light")->set_value(Session::$INTERNAL, new Value(Value::OFF));
	});
});

Scheduler::register_task("ESP8266 Light off", "pi1", new WeeklyTimer("*", [23], [0],[0]), function(){
	\Amp\call(function(){
		yield get_item("ESP8266 Plug Test")->set_value(Session::$INTERNAL, new Value(Value::OFF));
	});
});

Scheduler::register_task("Table Light off", "pi1", new WeeklyTimer("*", [23], [15],[0]), function(){
	\Amp\call(function(){
		yield get_item("Table Lamp")->set_value(Session::$INTERNAL, new Value(Value::OFF));
	});
});

///////////// NICK HEATING //////////////////

Scheduler::register_task("Nick Heating On", "xealot_server", new WeeklyTimer("*", [0], [30],[0]), function(){
	\Amp\call(function(){
		yield get_item("GPIO test")->set_value(Session::$INTERNAL, new Value(Value::ON));
	});
});

Scheduler::register_task("Nick Heating Off", "xealot_server", new WeeklyTimer("*", [9], [30],[0]), function(){
	\Amp\call(function(){
		yield get_item("GPIO test")->set_value(Session::$INTERNAL, new Value(Value::OFF));
	});
});




/*

Scheduler::register_task("timer test", "pi1", new WeeklyTimer("*", [20], [44],[0]), function(){
	\Amp\call(function(){
		yield get_item("Nick Bed Lights")->set_value(Session::$INTERNAL, new Value(Value::ON));
	});
});*/

?>