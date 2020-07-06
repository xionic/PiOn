<?php
namespace PiOn\Hardware;


class OperationNotSupportedException extends \Exception {
	
	function __construct($mess){
		parent::__construct($mess);
	}
}

?>