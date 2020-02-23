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
    public $item_names;

    /**
     * @var bool $get_now Causes item's current values to be send after subscribing - must be REQUEST_ALL or REQUEST_VALUE
     * 
     */
    public $get_now;

    function __construct($item_names, String $type, string $get_now = null){
        $this->item_names = $item_names;
        $this->type = $type;
        $this->get_now = $get_now;
    }

    static function from_json($json): SubscribeMessage {
        return self::from_obj(json_decode($json));
    }

    static function from_obj($obj): SubscribeMessage{
        Argh::validate($obj, [
            "get_now" => ["optional", "boolean"],
			"item_names" => ["obj"],
			"type" => ["notblank"],
			"/item_names/*" => ["array"],
			"/item_names/*/*" => ["notblank"]
        ]);

        $get_now = property_exists($obj, "get_now") ? $obj->get_now : null;
        
       return new SubscribeMessage($obj->item_names, $obj->type, $get_now);
    }
}

?>