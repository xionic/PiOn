<?php
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
?>