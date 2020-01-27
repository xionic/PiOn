<?php
 namespace PiOn\Item; 
 
 use \PiOn\InvalidArgException;
 
 class Value{

	public const CERTAIN = "CERTAIN";
	public const UNCERTAIN = "UNCERTAIN";
	public const UNKNOWN = "UNKNOWN";
	 
	 public $data;
	 public $timestamp; // Time the value is "valid" at. Up to Items to provide.
	 public $certainty; //null for set requests
	 public $has_error; //bool
	 public $error_message; //String
	 
	 function __construct($data = null, bool $has_error = false, ?String $error_message = null, ?int $timestamp = null, ?String $certainty = Value::CERTAIN){		 
		 if($timestamp == null){
			 $timestamp = time();
		 }
		 $this->data = $data;
		 $this->has_error = $has_error;
		 $this->error_message = $error_message;
		 $this->timestamp = $timestamp;
		 $this->certainty = $certainty;
	 }
	 
	 public static function from_obj($obj): Value{
		 //var_dump($obj);
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