<?php
namespace PiOn\Item;

use \PiOn\Hardware\OperationNotSupportedException;
use \PiOn\Session;
use \PiOn\Event\EventManager;
use \PiOn\Event\ItemEvent;
use \PiOn\Item\Value;

use \Amp\Promise;
use \Amp\Success;
use \Amp\Websocket\Client\Connection;
use \Amp\Websocket\Rfc6455Client;
use \Amp\Websocket\Message;

use function Amp\Websocket\Client\connect;

class ItemLocationArea extends Item{
    public const type = "LocationArea";
	private $location;
	private $find_ws;

	function _init(): bool {
		\Amp\call(function() {
			plog("ItemLocationArea ({$this->name}) connecting websocket to {$this->item_args->websocket_url}", VERBOSE, Session::$INTERNAL);
			$find_ws = yield connect($this->item_args->websocket_url);
			plog("ItemLocationArea ({$this->name}) websocket re-connected", DEBUG, Session::$INTERNAL);

			$find_ws->onClose(function(Rfc6455Client $client, int $close_code, string $close_reason) use (&$find_ws): void {
				\Amp\call(function() use (&$find_ws) {
					//Reconnect after close
					$find_ws = yield connect($this->item_args->websocket_url);
					plog("ItemLocationArea ({$this->name}) websocket re-connected", DEBUG, Session::$INTERNAL);
				});
			});
			yield $find_ws->send("Hello!");

			while ($message = yield $find_ws->receive()) {
				/** @var Message $message */
				$payload = yield $message->buffer();
				plog("ItemLocationArea ({$this->name}) websocket received message: $payload", DEBUG, Session::$INTERNAL);
				$find_data = json_decode($payload);
				$this->location = $find_data->location;	
				EventManager::trigger_event(Session::$INTERNAL, new ItemEvent(ITEM_VALUE_CHANGED, $this->name, new Value($this->location)));	
			}
		});
		return true;
	}
	
	protected function get_value_local(Session $session): Promise {
		return  new Success(new value($this->location));
	}
	
	protected function set_value_local(Session $session, Value $value): Promise {
		throw new OperationNotSupportedException("SET not supported");
	}
}

?>