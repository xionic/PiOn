<?php

function handle_socket_request(Socket_Message $sm){
	
	plog("Received socket request from " . $sm->sending_node, DEBUG);
	switch($sm->action){
		case "get":
		//var_dump(get_model());
				//get the value and prepare a response
				$value = get_model()->get_item($sm->item_name)->value;
				$resp = new Socket_Message();
				$resp->item_name = $sm->item_name;
				$resp->sending_node = NODE_NAME;
				$resp->target_node = get_node($sm->sending_node)->name;
				$resp->action = "get";
				$resp->type = "resp";
				$resp->payload = $value;
				return $resp;
			break;
		case "set":
			break;
	
	}
		
}

?>