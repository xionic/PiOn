<?php
namespace PiOn\Hardware;

use \PiOn\Item\Value;
use \PiOn\Session; 

use \MarkKimsal\Mqtt\Client;
use \MarkKimsal\Mqtt\Packet\Publish;

use \Amp\Promise;
use \Amp\Success;
use \Amp\Deferred;

/**
 * args:
 *  - server_addr
 *  - server_port
 *  - username
 *  - password
 * 
 * itemargs:
 *  - get_topic
 * 
 */
class HardwareMQTT extends Hardware{

    public const value_certainty = Value::CERTAIN;
    
    private $client;
    private $url;
    private $registrations = [];
    private $connected = false;
    private $connected_deferred = null;

	 
	 function __construct(String $name, String $node_name, array $capabilities, Object $args){
		parent::__construct($name, $node_name, $capabilities,  $args);
		$this->type = "HardwareMQTT";
        $this->value_certainty = true;
        $this->url = "tcp://" . $args->server_addr  . ":" .  $args->server_port . "?clientId=Pion_" . urlencode($node_name) . "&username=" . urlencode($args->username) . "&password=" . urlencode($args->password);
        $this->client = new Client($this->url);        
	 }
	 
	 function hardware_get(Session $session, Object $item_args): Promise{
        return \Amp\call(function() use($session, $item_args){
            foreach ($this->registrations as &$reg) { //should be registered if all is well
                if ($reg->get_topic == $item_args->get_topic) {
                    if($reg->cur_value != null){
                        return $reg->cur_value;
                    } else {
                        //We need to prompt the device to give us it's value, then wait for the response
                        plog("HardwareMQTT sending to update topic: " . $item_args->update_topic, DEBUG, $session);
                        $this->client->publish("", $item_args->update_topic, 2);
                        $reg->deferred = new Deferred();
                        yield $reg->deferred->promise();
                        return $reg->cur_value;
                    }
                } 
            }
            throw new \Exception("topic not registered");
        });
	 }
	 
	 function hardware_set(Session $session, Object $item_args, Value $value): Promise {
		 return \Amp\call(function() use($session, $item_args, $value){
            plog("Hardware SET for " . $this->type . " with item-args: " . json_encode($item_args), DEBUG, $session);
            yield $this->client->publish($value->data, $item_args->set_topic, 2);
            return new Success;

		 });
     }
     
    function hardware_register(Session $session, Object $item_args, Callable $callback): Promise {
        return \Amp\call(function() use($session, $item_args, $callback) {
            plog("Hardware REGISTER for " .
            $this->type . " with item-args: " . json_encode($item_args), DEBUG, $session);
            yield $this->check_connected();
            $registration = new HardwareMQTT_Registration($item_args->get_topic, $item_args->set_topic, $item_args->update_topic, $callback);
            $this->registrations[] = $registration;
            yield $this->client->subscribe($registration->get_topic);            
            plog("MQTT subsribed to topic: '" . $registration->get_topic . "'", DEBUG, $session);
        });
    }


    function on_message(Publish $publish_packet){
        $topic = $publish_packet->getTopic();
        $message = $publish_packet->getMessage();
        plog("Got MQTT message for topic: '" . $topic . "' and message: '" . $message . "'", DEBUG, Session::$INTERNAL);

        foreach($this->registrations as &$reg){
            if($reg->get_topic == $topic) {
                $reg->cur_value = $message;
                if($reg->deferred != null){
                    $reg->deferred->resolve();
                    $reg->deferred = null;
                }
                call_user_func($reg->callback, $message);
            }
        }

        
    }

    /**
     * if not already connected, make connection - cannto be done in constructor as it requires promises
     */
    function check_connected(): Promise{
        return \Amp\call(function(){
            if($this->connected_deferred != null){ // we're already connecting, just wait
                yield $this->connected_deferred->promise();
            } else if(!$this->connected){
                $this->connected_deferred = new Deferred;
                yield $this->client->connect();
                plog("MQTT connection established to " . $this->url, VERBOSE, Session::$INTERNAL);
                $this->connected = true;
                $this->connected_deferred->resolve();
                $this->client->on('message', function(Publish $publish_packet){
                    $this->on_message($publish_packet);
                });
            } else {
                return new \Amp\Success;
            }
        });
    }

    
	
	 
}

class HardwareMQTT_Registration {
    public $get_topic;
    public $set_topic;
    public $update_topic;
    public $callback;
    public $cur_value;
    public $deferred;

    public function __construct(string $get_topic, string $set_topic, string $update_topic, callable $callback){
        $this->get_topic = $get_topic;
        $this->set_topic = $set_topic;
        $this->update_topic = $update_topic;
        $this->callback = $callback;
        $this->cur_value = null;
        $this->deferred = null;
    }
}
