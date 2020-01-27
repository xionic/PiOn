<?php
namespace PiOn\Hardware;


class OperationNotSupportedException extends \Exception {
	protected  $message;
	
	function __construct($mess){
		$this->message = $mess;
	}
}

?>