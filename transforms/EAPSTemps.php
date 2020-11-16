<?php
use \PiOn\Transform\TransformManager;
use \PiOn\Transform\Transform;
use \PiOn\Item\Value;

TransformManager::register_transform("EAPS_temps",  new class extends Transform {

	function transform_get($data): Value{
		$data = json_decode($data);
		//var_dump($data);
		if($data == null)
			return null;
		$val = new Value($data->data[0]->value);	
		$val->timestamp = $data->data[0]->created;
		return $val;
	}
	
});

?>