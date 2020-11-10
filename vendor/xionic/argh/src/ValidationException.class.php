<?php
namespace xionic\Argh;

use Argh;

class ValidationException extends \Exception {
	
	function __construct(String $reason, String $offendingArg, $offendingValue) {
		if(is_object($offendingValue)){
			$offendingValue = " object of class " . get_class($offendingValue);
		}
		else{
			$offendingValue = "'$offendingValue'";
		}
		$reason .= ". Argument: '$offendingArg' Value: $offendingValue"; 
		parent::__construct($reason);
	}
}

?>