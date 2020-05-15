<?php
namespace PiOn;

use \PiOn\Item;
use \PiOn\Item\Value;
use \PiOn\Event\Event;
use \PiOn\Event\EventManager;
use \PiOn\RestMessage;
use \PiOn\Item\ItemMessage;
use \PiOn\SubscribeMessage;
use \PiOn\WebSocketClientHandler;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use \Amp\Http\Client\Connection\UnprocessedRequestException;
use \Amp\Promise;
use \Amp\Success;
use Amp\Websocket\Server\Websocket;
use Amp\Websocket\Client;
use Amp\Websocket\ClientHandler;
use function \Amp\call;

class WebSocketManager   {

    /**
     * @var Websocket $ws
     */
    public $ws;

    function __construct(){

        $this->ws = new WebSocket(new WebSocketClientHandler());     
        
    }

    public function get_websocket(): Websocket {
        return $this->ws;
    }
    
}

?>