<?php
namespace xionic\Argh;

require '../Argh.class.php';

$arr = array(
	"test1" => "val1",
	"test2" => array(
		"subkey1" => "1",
		"subkey2" => "subval2",
		"subkey3" => array(
			"l2k1" => "hi",
			"l2k2" => 1234,
			"l2k3" => ""
		)
	),
	"test3" => array(
			"1",
			"2",
			"zz3",
			"4",
			"5"
		)
);

try{
	$apiargs = Argh::validate($arr, array(
		"test1" => array("string", "notblank"),
		"test2" => array("array"),
		"/test2/subkey1" => array("int"),
		"/test2/subkey3/l2k1" => array("string"),
		"/test2/subkey3/l2k2" => array("int"),
		"/test2/subkey3/l2k3" => array("string", "notblank"),
		"/test3/0/"		=> array("int"),
		"/test3/*/"		=> array("int"), // this is expanded to /test3/0, /test3/1, ../test3/lengthofarray
		"/test3/*/one/with/more"	=> array("string"),
		));
} catch (ValidationException $ve){
	echo "ValidationException: " . $ve->getMessage();
}



?>
