<?php
namespace xionic\Argh;

require '../Argh.class.php';

/**
* callback function for argument validation - used by ArgValidator class
*/
$handleArgValidationError = function ($msg, $argName="", $argValue="")
{
	echo "<pre>";
	echo "There has been a validation error";
	var_dump($msg);
	var_dump($argName);
	var_dump($argValue);
	echo "</pre>";
	exit;
};
//A closure can also be used instead of "handleArgValidationError"



Argh::validate($_GET, array(
	"test1" => array("string", "notblank"),
	"test2" => array(function($a){return $a > 0;}),
	"test3" => array("lbound 5","ubound 500"),
	"test4" => array("regex /a/"),
	"test5" => array("notzero"),
	"test5" => array("array"),
	"test6" => array("lbound 4.5", "ubound 9.1"),
	), $handleArgValidationError
);

?>