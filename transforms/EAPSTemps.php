<?php
use \PiOn\Transform\TransformManager
;
TransformManager::register_transform("EAPS_temps", function ($data){
	$data = json_decode($data);
	return $data->data[0]->value;
});

?>