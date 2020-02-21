<?php
namespace PiOn;

use \xionic\Argh\Argh;

class SubscribeMessage {

    public const SUBSCRIBE = "SUBSCRIBE"; //subscribes to one or more items for one or more item events
    public const REFRESH_ALL = "REFRESH_ALL"; //sends current values of all subscribed items

    /**
     * @var String["item_name" => String[event_name]] $item_names
     */
    public $item_names;

    /**
     * @var bool $get_now Causes item's current values to be send after subscribing
     */
    public $get_now;

    function __construct($item_names, String $type, bool $get_now = false){
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

        $get_now = property_exists($obj, "get_now") ? $obj->get_now : false;
        
       return new SubscribeMessage($obj->item_names, $obj->type, $get_now);
    }
}

?>