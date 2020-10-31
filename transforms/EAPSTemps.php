<?php
use \PiOn\Transform\TransformManager;
use \PiOn\Item\Value;

TransformManager::register_transform("EAPS_temps", function ($data){
	$data = json_decode($data);
	var_dump($data);
	if($data == null)
		return null;
	$val = new Value($data->data[0]->value);	
	$val->timestamp = $data->data[0]->created;
	return $val;
});

?>