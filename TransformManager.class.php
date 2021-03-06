<?php
namespace PiOn\Transform;

use \PiOn\Item\Value;
use \PiOn\Session;
use\PiOn\Transform\Transform;

class TransformManager {
	
	private static $transforms = [];
	
	static function register_transform(String $name, Transform $transform): void {
		self::$transforms[$name] = $transform;
		//var_dump(self::$transforms);
	}
	
	static function transform_get(Session $session, String $transform_name, $data): Value {		
		$new_value =  self::$transforms[$transform_name]->transform_get($data);
		plog("GET Transform applied ($transform_name). Transformed '$data' to '" . $new_value->data . "'", DEBUG, $session);
		return $new_value;
	}

	static function transform_set(Session $session, String $transform_name, $data) {
		$new_value = self::$transforms[$transform_name]->transform_set($data);
		plog("SET Transform applied ($transform_name). Transformed '$data' to '" . $new_value . "'", DEBUG, $session);
		return $new_value;
	}
	
}


?>