<?php

namespace PiOn\Hardware;

use \PiOn\Session;
use \PiOn\Hardware\Hardware;
use \PiOn\Item\Value;

use \Amp\Promise;
use \Amp\Success;
use \slavino\tplinkhs110\TPLinkHS110Device;

class HardwareTPLinkSwitch extends Hardware {

    public const value_certainty = Value::CERTAIN;
    private $tp_devices = [];

   function __construct(String $name, String $node_name, array $capabilities, Object $args){
        parent::__construct($name, $node_name, $capabilities,  $args);
       $this->type = "HardwareTPLinkSwitch";

       foreach($args->devices as $device){
           $this->tp_devices[$device->name] = new TPLinkHS110Device([
                "ipAddr" => $device->host,
                "port" => $device->port,
            ], $device->name);
       }
    }

   function hardware_get(Session $session, Object $item_args): Promise {
        return \Amp\call(function() use($session, $item_args){	
		
            $resp = json_decode($this->tp_devices[$item_args->name]->sendCommand('{"system":{"get_sysinfo":null}}}'));
            // var_dump($resp);
            // var_dump($resp->system->get_sysinfo->relay_state); die;
            return new Success($resp->system->get_sysinfo->relay_state);
         });
   }

   function hardware_set(Session $session, Object $item_args, Value $value): Promise {//Value
        return \Amp\call(function() use($session, $item_args){	
                
        /* $this->tp_device = new TPLinkHS110Device([
                "ipAddr" => $item_args->host,
                "port" => $item_args->port,
            ], $this->name);*/
            if(!array_key_exists($item_args->name, $this->tp_devices)){
                throw new InvalidHardwareException("Unknown TPLINK hardware device: " . $item_args->name);
            }
            if($value->data){
                $this->tp_devices[$item_args->name]->switchOn();
            } else {
                $this->tp_devices[$item_args->name]->switchOff();
            }

            return new Success($value->data);
        });
	}

}


?>
