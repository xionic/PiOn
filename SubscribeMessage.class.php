<?php
namespace PiOn;

use \xionic\Argh\Argh;

class SubscribeMessage {

    public const SUBSCRIBE = "SUBSCRIBE"; //subscribes to one or more items for one or more item events
    public const REQUEST_ALL = "REQUEST_ALL"; //sends current values of all subscribed items
    public const REQUEST_VALUES = "REQUEST_VALUES"; //sends current values of all items just subscribed to

    /**
     * @var String["item_name" => String[event_name]] $item_names
     */
    public $subscriptions;

    /**
     * @var bool $get_now Causes item's current values to be send after subscribing - must be REQUEST_ALL or REQUEST_VALUE
     * 
     */
    public $get_now;

    /**
     * @paran Object $subscriptions [item_name => [event_names]]
     */
    function __construct($subscriptions, String $type, string $get_now = null){
        $this->subscriptions = $subscriptions;
        $this->type = $type;
        $this->get_now = $get_now;
    }

    static function from_json($json): SubscribeMessage {
        return self::from_obj(json_decode($json));
    }

    static function from_obj($obj): SubscribeMessage{
        Argh::validate($obj, [
            "get_now" => ["optional", "boolean"],
			"subscriptions" => ["obj"],
			"type" => ["notblank"],
			"/subscriptions/*" => ["array"],
			"/subscriptions/*/*" => ["notblank"]
        ]);

        $get_now = property_exists($obj, "get_now") ? $obj->get_now : null;
        
       return new SubscribeMessage($obj->subscriptions, $obj->type, $get_now);
    }
}

?>