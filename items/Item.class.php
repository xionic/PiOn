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
		
	public function get_value(): Promise { // Resolves to Value
		return \Amp\Call(function(){
			if(get_node($this->node_name)->name == NODE_NAME){ //item is local to this node		
				plog("Item '" . $this->name . "' is local", VERBOSE);
				
				$cur_value = null;
				try{
					$item_value = yield $this->get_value_local();
				} catch(OperationNotSupportedException $e){ //GET not supported. return sucess: false and value: null
					$item_value = new Value($this->last_value->data, true, "OperationNotSupportedException", $this->last_value->timestamp, $this->last_value->certainty);
					//var_dump($resp_item_message);
				}

				//transform the value if configured
				if(property_exists($this->item_args, "transform")){
					$item_value->data = TransformManager::transform($this->item_args->transform, $item_value->data);
				}
				
				//fire item change event if needed
				//if($resp_item_message->value->value != $this->last_value->value){
				if(true){			
					EventManager::trigger_event(new ItemEvent(ITEM_VALUE_CHANGED, $this->name, $item_value));
				}
				//var_dump($this->resp_item_message->value);die;
				$this->last_value = $item_value;
				return $item_value;
			
			
			} else { // Item is on a remote node
				plog("Item '" . $this->name . "' is on another host '" . $this->node_name . "'. Requesting value...", VERBOSE);
				$target_node = get_model()->get_node($this->node_name);
				
				$item_message = new ItemMessage($this->name, ItemMessage::GET);			
				$rest_message = new RestMessage(RestMessage::REQ, RestMessage::REST_CONTEXT_ITEM, NODE_NAME, $target_node->name,$target_node->port, $item_message->to_json());
				
				try {
					$resp_rest_message = yield send($rest_message);
				} catch (UnprocessedRequestException $e){
					plog("Cound not connect to node '{$target_node->name}'", ERROR);
					return new Value(null, false, "Cound not connect to node '{$target_node->name}'");
				}
				$resp_item_message = ItemMessage::from_json($resp_rest_message->payload);
				$item_value = Value::from_obj($resp_item_message->value);
				
				
				if(!$item_value instanceof Value){
					throw new Exception("Promise resolved to nvalid type");
				}
				return $item_value;

			}	
		});
	}
	public function set_value($value): Promise { // Resolves to Value
		return \Amp\Call(function() use ($value){
			plog("Trying to set value of item: '{$this->name}' to '{$value->data}'", DEBUG);
			if(get_node($this->node_name)->name == NODE_NAME){ //item is local to this node	
			
				//trigger item update event
				/*if($value !== $this->last_value->value){				
					$event = new ItemEvent(ITEM_VALUE_CHANGED, $this->name);
					EventManager::trigger_event($event);
				}*/
				$this->last_value = $value;				
				return $this->set_value_local($value);
				
				
			} else { // item on remote node
				plog("Item '" . $this->name . "' is on another host '" . $this->node_name . "'. Sending value...", VERBOSE);
				$target_node = get_model()->get_node($this->node_name);

				$item_message = new ItemMessage($this->name, ItemMessage::SET, $value);			
				$rest_message = new RestMessage(RestMessage::REQ, RestMessage::REST_CONTEXT_ITEM, NODE_NAME, $target_node->name, $target_node->port, $item_message->to_json());
				$resp_rest_message = yield send($rest_message);
				$resp_item_message = ItemMessage::from_json($resp_rest_message->payload);
				return Value::from_obj($resp_item_message->value);
			}
		});
	}
	
	public static function create_item(String $item_type, String $name, String $node, Object $item_args, ?Hardware $hw, ?\stdclass $hw_args): Item {
		$class = "\PiOn\Item\Item$item_type";
		$item = new $class();
		$item->name = $name;
		$item->node_name = $node;
		$item->item_args = $item_args;
		$item->hardware = $hw;
		$item->hardware_args = $hw_args;
		$item->last_value = new Value(null);
		return $item;
	}
	
	//abstract protected function init(): Promise;
	abstract protected function get_value_local(): Promise;
	abstract protected function set_value_local($value): Promise;
	
	
}

?>