<?php
namespace xionic\Argh;

require '../Argh.class.php';

try{
	$o = new \stdclass();
	$o->name = "testname";
	$o->job = "drummer";
	$o->others = new \stdclass();
	$o->others->other_name = "steven";
	$o->others->secret = new \stdclass();
	$o->others->secret->supersecrets = "level3";
	
	
	Argh::validate($o, [
		"others" => ["class stdclass"],
		"/others/secret/supersecrets" => ["int"],
		
		]);
} catch (ValidationException $ve){
	echo "ValidationException: " . $ve->getMessage() . "<br>";
}

try{
	$o = new \stdclass;
	$o->test = new \stdclass;

	
	
	Argh::validate($o, [
		"test" => ["?class stdclass"],
		
		]);
} catch (ValidationException $ve){
	echo "ValidationException: " . $ve->getMessage();
}



?>
