<?php
namespace PiOn;

class StandardClass extends \stdClass {
	function __construct(Array $attributes = array()){
		foreach($attributes as $key => $value){
			$this->$key = $value;
		}
	}
}

?>