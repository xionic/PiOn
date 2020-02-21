<?php
namespace PiOn\Item;

use \Pion\Hardware\Hardware as Hardware;
use \PiOn\Item\ItemMessage;
use \PiOn\Event\EventMessage;
use \PiOn\RestMessage;
use \PiOn\Hardware\OperationNotSupportedException;
use \PiOn\Event\EventManager;
use \PiOn\Event\ItemEvent;
use \PiOn\Transform\TransformManager;
use \PiOn\Session;

use \xionic\Argh\Argh;

use \Amp\Http\Client\Connection\UnprocessedRequestException;
use \Amp\Http\Client\HttpClientBuilder;
use \Amp\Http\Client\Request;
use \Amp\Promise;
use \Amp\Call;

abstract class Item {
	
	public $name;
	public $node_name;
	public $hardware;
	public $hardware_args;
	public $item_args;
	public $type = null;
	public $last_value; 
		
	public function get_value(Session $session): Promise { // Resolves to Value
		return \Amp\Call(function() use($session) {
			if(get_node($this->node_name)->name == NODE_NAME){ //item is local to this node		
				plog("Item GET '" . $this->name . "' is local", VERBOSE, $session);
				
				$item_value = null;

				try{
					$item_value = yield $this->get_value_local($session);
				} catch(OperationNotSupportedException $e){ //GET not supported. return sucess:	
					if($this->last_value == null){						
						$item_value = new Value($this->last_value->data, true, "OperationNotSupportedException", $this->last_value->timestamp, $this->last_value->certainty);
					} else {
						$item_value = new Value(null, true, "GET not supported and last_value not yet set", time(), Value::CERTAIN);
					}
					//var_dump($resp_item_message);
				}

				//transform the value if configured
				if(property_exists($this->item_args, "transform")){
					$item_value->data = TransformManager::transform($this->item_args->transform, $item_value->data);
				}
				
				//fire item change event if needed	
				$newval = $item_value->data;
				$oldval = $this->last_value->data;	
				$this->last_value = $item_value;		
				if(
					(is_object($newval) && is_object($oldval) && $newval != $oldval)
					|| ($oldval != $newval)
				){	
					EventManager::trigger_event(Session::$INTERNAL, new ItemEvent(ITEM_VALUE_CHANGED, $this->name, $item_value));
				}
				//var_dump($this->resp_item_message->value);die;
				
				
			
			
			} else { // Item is on a remote node
				plog("Item GET '" . $this->name . "' is on another host '" . $this->node_name . "'. Requesting value...", VERBOSE, $session);
				$target_node = get_model()->get_node($this->node_name);
				
				$item_message = new ItemMessage($this->name, ItemMessage::GET);			
				$rest_message = new RestMessage(RestMessage::REQ, RestMessage::REST_CONTEXT_ITEM, NODE_NAME, $target_node->name,$target_node->port, $item_message);
				
				try {
					$resp_rest_message = yield send($session, $rest_message);
				} catch (UnprocessedRequestException $e){
					plog("Cound not connect to node '{$target_node->name}'", ERROR, $session);
					return new Value(null, false, "Cound not connect to node '{$target_node->name}'");
				}
				$resp_item_message = ItemMessage::from_obj($resp_rest_message->payload);
				$item_value = Value::from_obj($resp_item_message->value);
				
				
				if(!$item_value instanceof Value){
					throw new Exception("Promise resolved to nvalid type");
				}
			}
			return $item_value;

		});
	}
	
	public function set_value(Session $session, Value $value): Promise { // Resolves to Value
		return \Amp\Call(function() use ($value, $session){
			
			plog("Trying to set value of item: '{$this->name}' to '".json_encode($value->data)."'", DEBUG, $session);
			if(get_node($this->node_name)->name == NODE_NAME){ //item is local to this node	

				plog("Item SET '" . $this->name . "' is local", VERBOSE, $session);

				//we need to actuaklly set the value before firing events
				$result = yield $this->set_value_local($session, $value);

				//fire item change event if needed	
				$newval = $value->data;
				$oldval = $this->last_value->data;	
				$this->last_value = $value;		
				if(
					(is_object($newval) && is_object($oldval) && $newval != $oldval)
					|| ($oldval != $newval)
				){	
					EventManager::trigger_event(Session::$INTERNAL, new ItemEvent(ITEM_VALUE_CHANGED, $this->name, $value));
				}

				return $result;	

			} else { // item on remote node
				plog("Item SET '" . $this->name . "' is on another host '" . $this->node_name . "'. Sending value...", VERBOSE, $session);
				$target_node = get_model()->get_node($this->node_name);
				$item_message = new ItemMessage($this->name, ItemMessage::SET, $value);			
				$rest_message = new RestMessage(RestMessage::REQ, RestMessage::REST_CONTEXT_ITEM, NODE_NAME, $target_node->name, $target_node->port, $item_message);
				$resp_rest_message = yield send($session, $rest_message);
				$resp_item_message = ItemMessage::from_obj($resp_rest_message->payload);
				return Value::from_obj($resp_item_message->value);
			}
		});
	}
	
	public static function create_item(String $item_type, String $name, String $node, Object $item_args, ?Hardware $hw, ?Object $hw_args): Item {
		
		$class = "\PiOn\Item\Item$item_type";
		$item = new $class();
		$item->name = $name;
		$item->type = $item_type;
		$item->node_name = $node;
		$item->item_args = $item_args;
		$item->hardware = $hw;
		$item->hardware_args = $hw_args;
		$item->last_value = new Value(null);
		
		/*Argh::validate($item,[
			"name" => ["notblank"],
			"node_name" => ["notblank"],
			"item_args" => ["obj"],			
		]);*/
		return $item;
	}
	
	//abstract protected function init(): Promise;
	abstract protected function get_value_local(Session $session): Promise;
	abstract protected function set_value_local(Session $session, Value $value): Promise;
	
	
}

?>