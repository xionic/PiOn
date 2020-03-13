<?php
use \PiOn\Event\EventManager;
use \PiOn\Item\Value;
use \PiOn\Session;

EventManager::register_item_event_handler("Nick Location", ITEM_EVENT, ITEM_VALUE_CHANGED, function($event_name, $item_name, $value){
	if($value->data == "nick room"){ echo "ONNNNNNN\n\n";
		get_item("Nick Bed Lights")->set_value(Session::$INTERNAL, new Value(1));
	} else { echo "OOOOOOOOOOFFFFFFFFFFFFf\n\n";
		get_item("Nick Bed Lights")->set_value(Session::$INTERNAL, new Value(0));
	}
}, "xealot_server");

EventManager::register_item_event_handler("Nick Location", ITEM_EVENT, ITEM_VALUE_CHANGED, function($event_name, $item_name, $value){
	if($value->data == "living room"){ echo "ONNNNNNN\n\n";
		get_item("ESP8266 Plug Test")->set_value(Session::$INTERNAL, new Value(1));
	} else { echo "OOOOOOOOOOFFFFFFFFFFFFf\n\n";
		get_item("ESP8266 Plug Test")->set_value(Session::$INTERNAL, new Value(0));
	}
}, "xealot_server");




?>