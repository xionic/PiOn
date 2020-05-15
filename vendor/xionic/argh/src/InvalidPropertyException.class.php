<?php
namespace xionic\Argh;

use Argh;

class InvalidPropertyException extends \Exception {
	
	function __construct(String $prop){
		parent::__construct("Unknown property: $prop");
	}
}

?>