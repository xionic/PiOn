<?php
use \PiOn\Transform\TransformManager
;
TransformManager::register_transform("EAPS_temps", function ($data){
	$data = json_decode($data);
	if($data == null)
		return null;
	return $data->data[0]->value;
});

?>