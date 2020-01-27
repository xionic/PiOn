<?php
namespace PiOn\Hardware;

use \PiOn\Item\Value;

class HardwareExec extends Hardware{

	public const value_certainty = Value::CERTAIN;
	 
	 function __construct(String $name, String $node_name, array $capabilities, Object $args){
		parent::__construct($name, $node_name, $capabilities,  $args);
		$this->type = "HardwareExec";
		$this->value_certainty = true;
	 }
	 
	 function hardware_get(Object $item_args): Value{		
		 return new Value($this->do_exec($item_args->get_command), false, null, time(), $this->value_certainty);
	 }
	 
	 function hardware_set(Object $item_args, Value $value): value {
		 return new Value($this->do_exec($item_args->set_command));
	 }
	 
	 private function do_exec(String $cmd): String{
		 plog("Hardware {$this->name} running shell command: {$cmd}", DEBUG);
		 return exec($cmd);
	 }
	 
}

?>