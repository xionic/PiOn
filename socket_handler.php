<?php

function handle_socket_request(Socket_Message $sm){
	
	switch($sm->action){
		case "get":
		var_dump(get_model());
				//get the value and prepare a response
				$value = get_model()->get_item($sm->item_name)->get();
				$resp = new Socket_Message();
				$resp->sending_node = NODE_NAME;
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