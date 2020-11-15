<?php
 namespace PiOn\Item; 
 
 use \PiOn\InvalidArgException;
 
 class Value{

	public const CERTAIN = "CERTAIN";
	public const UNCERTAIN = "UNCERTAIN";
	public const UNKNOWN = "UNKNOWN";
	public const ON = 1;
	public const OFF = 0;
	 
	 public $data;
	 public $timestamp; // Time the value is "valid" at. Up to Items to provide.
	 public $certainty; //null for set requests
	 public $has_error; //bool
	 public $error_message; //String
	 
	 //can take an array of values with same arg names instead as first arg
	 function __construct($data = null, bool $has_error = false, ?String $error_message = null, ?int $timestamp = null, ?String $certainty = Value::CERTAIN){
		$arr_data = null;
		if(is_array($data)){
			$d = $data;
			foreach($d as $key => $value){
				switch($key){
					case "data":
						$arr_data = $value;
						break;
					case "has_error":
						$has_error = $value;
						break;
					case "error_message":
						$error_message = $value;
						break;
					case "timestamp":
						$timestamp = $value;
						break;
					case "certainty":
						$certainty = $value;
						break;
					default:
						throw new \Exception("Invalid argument to Value contrsuctor array: $key");
				}
			}
		}
		if(is_array($data) && $arr_data == null){ //data was not set in array - we need to set $data accordingly
			$data = null;
		}
		if($timestamp == null){
		 $timestamp = time();
		}
		$this->data = $data;
		$this->has_error = $has_error;
		$this->error_message = $error_message;
		$this->timestamp = $timestamp;
		$this->certainty = $certainty;
	 }
	 
	 public static function from_obj(Object $obj): Value{		
		if($obj == null)
			throw new \Exception("object cannot be null");
		
		if(!property_exists($obj, "data"))
			throw new \Exception("Value objects must contain data");
		
		if(!property_exists($obj, "has_error"))
			$obj->has_error = false;
		
		if(!property_exists($obj, "error_message"))
			$obj->error_message = null;
		
		if(!property_exists($obj, "timestamp"))
			$obj->timestamp = null;
		
		if(!property_exists($obj, "certainty"))
			$obj->certainty = Value::CERTAIN;
		
		
		 return new Value(
			$obj->data,
			$obj->has_error,
			$obj->error_message,
			$obj->timestamp,
			$obj->certainty
		);
	 }
	 
	 public static function from_json($json): Value{
		$obj = json_decode($json);
		return Value::from_obj($obj);
	 }
	 
 }
 
 ?>