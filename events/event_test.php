<?php
use \PiOn\Event\EventManager;
/*
EventManager::register_item_event_handler("GPIO Toggler test 4", ITEM_EVENT, "pi1", ITEM_VALUE_CHANGED, function(){
	echo "YAAAAYYYY IM AN EVENT CALLBACK ON pi1\n";
});
*/

EventManager::register_item_event_handler("GPIO Toggler test 4", ITEM_EVENT, "xealot_server", ITEM_VALUE_CHANGED, function(){
	echo "YAAAAYYYY IM AN EVENT CALLBACK ON XEALOT\n";
});





?>