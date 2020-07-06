<?php
namespace PiOn\Hardware;


class InvalidHardwareException extends \Exception {
	
	function __construct($mess){
        parent::__construct($mess);
	}
}

?>