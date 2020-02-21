<?php
namespace PiOn;

use \PiOn\Item;
use \PiOn\Item\Value;
use \PiOn\Event\Event;
use \PiOn\Event\EventManager;
use \PiOn\RestMessage;
use \PiOn\Item\ItemMessage;
use \PiOn\SubscribeMessage;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use \Amp\Promise;
use \Amp\Success;
use Amp\Websocket\Client;
use function \Amp\call;

class WebSocket extends \Amp\Websocket\Server\Websocket {

    /**
     * @var [Client::id => [item_name => [event_name]]] $subscriptions
     */
    private $subscriptions = [];
    private $subscribers = [];

    function __construct(){
        parent::__construct();
      /*  foreach(get_items() as $item){
            foreach(Event::$events as $event_name){
                $this->subscriptions[$item->name][$event_name] = [];
            }
        }*/

        //init sinscriptions
        foreach(Event::events as $ev_name){
            foreach(get_items() as $item){
                $this->subscriptions[$item->name][$ev_name] = [];
            }
        }

        EventManager::register_all_item_events_handler(function (string $event_name, string $item_name, Value $value){
            plog("Websocket received event for item: '$item_name', event: '$event_name', # subscriptions: " . count($this->subscriptions[$item_name][$event_name]), DEBUG, Session::$INTERNAL);
            //var_dump($this->subscriptions);
            foreach($this->subscriptions[$item_name][$event_name] as $client_id){
                $item_message = new ItemMessage($item_name, ItemMessage::GET, $value);
                $rest_message = new RestMessage(RestMessage::REQ, RestMessage::REST_CONTEXT_ITEM, NODE_NAME, "client", null, $item_message);
                plog("Websocket sending update for item: '$item_name'", DEBUG, Session::$INTERNAL);
                $this->subscribers[$client_id]->send($rest_message->to_json());

             }
        });
    }

    public function onHandshake(Request $request, Response $response): Promise
    {
       /* if (!\in_array($request->getHeader('origin'), ['http://localhost:1337', 'http://127.0.0.1:1337', 'http://[::1]:1337'], true)) {
            $response->setStatus(403);
        }*/

        return new Success($response);
    }

    public function onConnect(Client $client, Request $request, Response $response): Promise
    {
        $session  = new Session("ws:");
        $this->subscribers[$client->getId()] = $client;
        return call(function() use($session, $client) { 
            while ($message = yield $client->receive()) {
                \assert($message instanceof Message);
                $msg = yield $message->buffer();
                plog("- - - Received WebSocket message: $msg", DEBUG, $session);
                $rest_message = RestMessage::from_json($msg);

                switch($rest_message->context){
                    case RestMessage::REST_CONTEXT_SUBSCRIBE:
                        $sub_message = SubscribeMessage::from_obj($rest_message->payload);
                        plog("SUBSCRIBE message for type: {$sub_message->type}", DEBUG, $session);
                        switch($sub_message->type){
                            case SubscribeMessage::SUBSCRIBE:
                                foreach($sub_message->item_names as $item_name => $events){
                                    foreach($events as $event_name){
                                       // $this->subscriptions[$client->getId()][$item_name][] = $event_name;
                                       $this->subscriptions[$item_name][$event_name][] = $client->getId();
                                    }
                                }
                                if($sub_message->get_now){
                                    plog("client requested refresh of all subscribed items", DEBUG, $session);
                                    $resp_rest_message = yield $this->refresh_all($session, $client);
                                    //var_dump($resp_rest_message);
                                    yield $client->send($resp_rest_message->to_json());
                                }
                                break;

                            case SubscribeMessage::REFRESH_ALL:                                
                                $resp_rest_message = yield $this->refresh_all($session, $client);
                                yield $client->send($resp_rest_message->to_json());
                                break;

                            default:
                                 yield $this->send_error($session, $client, "Invalid SubscribeMeessage type: {$sub_message->type}");
                        }   
                        break;
                    default:
                        $msg = "Invalid RestMessage context: {$rest_message->context}";
                        yield $this->send_error($session, $client, $msg);
                }

              /*  $resp_rest_message = yield handle_RestMessage(Session::$INTERNAL, $rest_message);
                yield $client->send($resp_rest_message->to_json());*/

            }
        });  
    }

    /**
     * returns assoc array item_name => $event[]
     */
    private function get_client_subscriptions(String $client_id): array{
        $subs = [];
        foreach($this->subscriptions as $item_name => $event_names){
            foreach ($event_names as $event_name){
                $subs[$item_name][] = $event_name;
            }
        }
        return $subs;
    }

    private function refresh_all(Session $session, Client $client): Promise {
        return \Amp\call(function() use ($session, $client){
            $values = [];
            
            foreach($this->get_client_subscriptions($client->getId()) as $item_name => $events){
                try {                    
                    $item = get_item($item_name);
                } catch(InvalidArgException $ie) {
                    $this->send_error($session, $client, "Invalid item requested: $item_name");
                    continue;
                }

                plog("WS refresh_all requesting value for item: '$item_name'", DEBUG, $session);
                $values[] = new ItemMessage(
                    $item_name,
                    ItemMessage::GET,
                    yield $item->get_value($session)
                );
            }
            $resp_rest_message = new RestMessage(RestMessage::RESP, RestMessage::REST_CONTEXT_ITEMS, NODE_NAME, null, null, $values);
            return $resp_rest_message;
        });
    }

    private function send_error(Session $session, Client $client, string $err_msg): Promise {
        plog("Websocket sending ERROR message: $err_msg", ERROR, $session);
        return $client->send((new RestMessage(RestMessage::RESP, RestMessage::REST_CONTEXT_ERROR, NODE_NAME, "client", null, $err_msg))->to_json());
    }
}

?>