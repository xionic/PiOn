<?php
use \PiOn\Transform\TransformManager;
use \PiOn\Transform\Transform;
use \PiOn\Item\Value;


TransformManager::register_transform("Tasmota_Plug",  new class extends Transform {

	function transform_get($data): Value {
		return new Value($data == "ON" ? 1 : 0);
	}

	function transform_set($data) {
		return $data == "1" ? "ON" : "OFF";
	}

});

?>