<?php
namespace xionic\Argh;

require '../Argh.class.php';

$test1 = new ValidationException("hey", "you", "there");
$test2 = new \stdclass();

$arr = ["class1" => $test1, "class2" => $test2];

try{
	Argh::validate($arr, [
		"class1" => ["class ValidationException"],
		"class2" => ["class ValidationException"],
		
		]);
} catch (ValidationException $ve){
	echo "ValidationException: " . $ve->getMessage();
}



?>
