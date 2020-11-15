<?php
namespace Pion\Hardware;

use \PiOn\Hardware\OperationNotSupportedException;
use \PiOn\Item\Value;
use \PiOn\Session;
use \PiOn\Transform\TransformManager;

use \Amp\Promise;
use \Amp\Success;

abstract class Hardware {
	public const HW_GET = "get";
	public const HW_SET = "set";
	public const HW_REGISTER = "register";
	public $name;
	public $node_name;
	public $type;
	public $args;
	public $value_certainty = Value::UNKNOWN; //can we be sure the hardware has actually changed when set? i.e. wireless plugs which provide no feedback. The value is "set" and we have to hope the hardware received and completed the order. bool
	public $capabilities = array(); // array of "get, "set", "register"
	
	
	function __construct(String $name, String $node_name, array $capabilities, Object $args){
		$this->name = $name;
		$this->node_name = $node_name;
		$this->capabilities = $capabilities;
		$this->args = $args;
	}
	
	function get(Session $session, Object $item_args): Promise { // Resolves to Value
		return \Amp\call(function() use($session, $item_args){
			plog("Hardware GET request for type: {$this->type} with item_args: ".json_encode($item_args), DEBUG, $session);
			if(in_array(Hardware::HW_GET, $this->capabilities)){
				$data = yield $this->hardware_get($session, $item_args);

				if (property_exists($item_args, "transform")) {
					$trans_value = TransformManager::transform_get(Session::$INTERNAL, $item_args->transform, $data);
					$trans_value->certainty = $this->value_certainty;
				} else {
					$trans_value = new Value([
						"data" => $data,
						"certainty" => $this->value_certainty
					]);
				}

				return new Success($trans_value);
			} else {
				throw new OperationNotSupportedException("GET not supported by Hardware '{$this->name}'");
			}
		});
	}
	
	function set(Session $session, Object $item_args, Value $value): Promise {
		return \Amp\call(function () use ($session, $item_args, $value) {
			plog("Hardware SET request for type: {$this->type} with value: ".json_encode($value->data) . " and item_args: ".json_encode($item_args), DEBUG, $session);
			if(in_array(Hardware::HW_SET, $this->capabilities)){

				if (property_exists($item_args, "transform")) {
					$trans_value = new Value(TransformManager::transform_set(Session::$INTERNAL, $item_args->transform, $value->data));
				} else {
					$trans_value = $value;
				}
				$data = yield $this->hardware_set($session, $item_args, $trans_value);
				return new Success(new Value([
					"data" => $data,
					"certainty" => $this->value_certainty
				]));
			} else {
				throw new OperationNotSupportedException("SET not supported by hardware '{$this->name}'");
			}
		});
	}

	function register(Session $session, Object $item_args, Callable $callback): void {
		plog("Hardware REGISTER request for type: {$this->type} with item_args: ".json_encode($item_args), DEBUG, $session);
		if(in_array(Hardware::HW_REGISTER, $this->capabilities)){
			//wrap the callback so we can apply transforms
			$this->hardware_register($session, $item_args, function($data) use ($callback, $item_args){
				if (property_exists($item_args, "transform")) {
					$trans_value = TransformManager::transform_get(Session::$INTERNAL, $item_args->transform, $data);
				} else {
					$trans_value = new Value([
						"data" => $data,
						"certainty" => $this->value_certainty
					]);
				}
				return call_user_func($callback, $trans_value);
			});
		} else {
			throw new OperationNotSupportedException("REGISTER not supported by Hardware '{$this->name}'");
		}
	}
	
	protected abstract function hardware_get(Session $session, Object $item_args): Promise;
	protected abstract function hardware_set(Session $session, Object $item_args, Value $value): Promise;

	/***
	 * Register for updates from hardware ia callback
	 */
	protected abstract function hardware_register(Session $session, Object $item_args, Callable $callback): Promise;
}

?>