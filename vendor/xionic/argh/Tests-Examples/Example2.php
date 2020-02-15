<?php
namespace xionic\Argh;

require '../Argh.class.php';

try {
	Argh::validate($_GET, array(
		"test1" => array("string", "notblank"),
		"test2" => array(function($a){return $a > 0;}),
		"test3" => array("lbound 5.0","ubound 500"),
		"test4" => array("regex /a/"),
		"test5" => array("notzero"),
		"test5" => array("array"),
		)
	);
} catch (ValidationException $ve){
	echo $ve->getMessage();
	var_dump($ve);
}


?>