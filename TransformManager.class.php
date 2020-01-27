<?php
namespace PiOn\Transform;

class TransformManager {
	
	private static $transforms = [];
	
	static function register_transform(String $name, Callable $transform): void {
		self::$transforms[$name] = $transform;
		//var_dump(self::$transforms);
	}
	
	static function transform($transform_name, $data) {
		
		return call_user_func(self::$transforms[$transform_name], $data);
	}
	
}


?>