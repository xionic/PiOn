<?php

namespace PiOn\Item;

use \PiOn\Session;
use \PiOn\Item\Value;

use \xionic\Argh\Argh;
use \xionic\Argh\ValidationException;

use \Amp\Promise;
use \Amp\Success;

use Williamson\TPLinkSmartplug\TPLinkManager;
use Williamson\TPLinkSmartplug\TPLinkCommand;

class ItemTPLinkBulb extends Item {

    public const type = "TPLinkBulb";
    private $hue = 0;
    private $saturation = 50;
    private $brightness = 100;
    private $tplink_device;

    public function init(){

        try{

			Argh::validate($this->item_args, [
                "device_name" => ["notblank"],
                "ip" => ["notblank"],
                "port" => ["int"],
                "timeout" => ["int"],
                "timeout_socket" => ["int"],
                
			], null, false);

		} catch(ValidationException $ve){
			plog("Invalid item_args for type ItemTPLinkBulb '{$this->name}", ERROR, Session::$INTERNAL);
			plog($ve->getMessage(), FATAL, Session::$INTERNAL);
		}

        $tp_manager = new TPLinkManager([
            $this->item_args->device_name => [
                'ip' => $this->item_args->ip,
                'port' => $this->item_args->port,
                'timeout' => $this->item_args->timeout,
                'timeout_socket' => $this->item_args->timeout_socket,
                'deviceType' => 'IOT.SMARTBULB',
            ]
        ]);

        $this->tplink_device = $tp_manager->device($this->item_args->device_name);
        //$tp_dev->powerOff(); 
        //$tp_dev->powerOn(); 
        //var_dump(json_decode($tp_dev->sendCommand(TPLinkCommand::systemInfo())));
        
    }

    private function build_request(): array {
       return [
            "hue" => $this->hue,
            "saturation" => $this->saturation,
            "brightness" => $this->brightness,
        ];
       /* return [
            "hue" => 200,
            "saturation" => 50,
            "brightness" => 100
        ];*/
    }

    protected function get_value_local(Session $session): Promise {
        return new Success(new Value());
    }

    protected function set_value_local(Session $session, Value $value): promise {
        $this->hue = (int) floor($value->data->h);
        $this->saturation = (int) floor($value->data->s * 100);
        $this->brightness = (int) floor($value->data->v * 100);
        plog("Setting color value of item {$this->name}' to h: {$this->hue}, s: {$this->saturation}. b: {$this->brightness}", DEBUG, $session);
        var_dump($this->build_request());
        $response = $this->tplink_device->sendCommand(TPLinkCommand ::lightControlValues($this->build_request()));

        $value->has_error = false;
        return new Success($value);
    }
}

?>