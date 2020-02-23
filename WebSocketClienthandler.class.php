<?php
namespace Pion;

use \PiOn\Item;
use \PiOn\Item\Value;
use \PiOn\Event\Event;
use \PiOn\Event\EventManager;
use \PiOn\RestMessage;
use \PiOn\Item\ItemMessage;
use \PiOn\SubscribeMessage;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use \Amp\Http\Client\Connection\UnprocessedRequestException;
use \Amp\Promise;
use \Amp\Success;
use \Amp\Websocket\Server\Websocket;
use \Amp\Websocket\Client;
use \Amp\Websocket\Server\ClientHandler;
use function \Amp\call;

class WebSocketClientHandler implements ClientHandler{
    private $websocket;
    /**
     * @var [Client::id => [item_name => [event_name]]] $subscriptions
     */
    private $subscriptions = [];
    private $subscribers = [];

    function __construct(){
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

    public function onStart(Websocket $ws): Promise {
        $this->websocket = $ws;
            return new Success;
    }

    public function onStop(Websocket $ws): Promise {
        return new Success;
    }

    public function handleHandshake(Request $request, Response $response): Promise{
        return new Success($response);
    }

    public function handleClient(Client $client, Request $request, Response $response): Promise {
        $session  = new Session("ws:");
        $this->subscribers[$client->getId()] = $client;
        $this->subscribers[$client->getId()]->onClose(function($client, $close_clode, $close_reason){
            $this->remove_client($client);
        });
        plog("+ + + WS New Connection. Client Id: " . $client->getId() . " Assigned session_id " . $session->get_id(), DEBUG, $session);

        return call(function() use($session, $client) { 
            while ($message = yield $client->receive()) {
                \assert($message instanceof Message);
                $msg = yield $message->buffer();
                plog("+ + + Received WebSocket message: $msg", DEBUG, $session);
                $rest_message = RestMessage::from_json($msg);
                \Amp\call(function() use ($rest_message, $session, $client){
                    switch($rest_message->context){
                        case RestMessage::REST_CONTEXT_SUBSCRIBE:
                            $sub_message = SubscribeMessage::from_obj($rest_message->payload);
                            plog("SUBSCRIBE message for type: {$sub_message->type}, get_now: {$sub_message->get_now}", DEBUG, $session);
                            switch($sub_message->type){
                                case SubscribeMessage::SUBSCRIBE:
                                    foreach($sub_message->item_names as $item_name => $events){
                                        foreach($events as $event_name){
                                        // $this->subscriptions[$client->getId()][$item_name][] = $event_name;
                                        $this->subscriptions[$item_name][$event_name][] = $client->getId();
                                        }
                                    }
                                    if($sub_message->get_now == SubscribeMessage::REQUEST_ALL){
                                        plog("client requested values of all items", DEBUG, $session);
                                        $resp_rest_message = yield $this->request_all($session, $client);
                                        yield $this->send($session, $client, $resp_rest_message->to_json());

                                    } else if($sub_message->get_now == SubscribeMessage::REQUEST_VALUES){
                                        plog("client requested values of just subscribed items", DEBUG, $session);
                                        $resp_rest_message = yield $this->request_values($session, $client, get_object_vars($sub_message->item_names));
                                        yield $this->send($session, $client, $resp_rest_message->to_json());
                                    }
                                    break;

                                case SubscribeMessage::REQUEST_ALL:                                
                                    $resp_rest_message = yield $this->request_all($session, $client);
                                    yield $this->send($session, $client, $resp_rest_message->to_json());
                                    break;

                                default:
                                    yield $this->send_error($session, $client, "Invalid SubscribeMeessage type: {$sub_message->type}");
                            }   
                            break;
                        default:
                            $msg = "Invalid RestMessage context: {$rest_message->context}";
                            yield $this->send_error($session, $client, $msg);
                            
                    }
                });

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

    private function request_all(Session $session, Client $client): Promise {
        return $this->request_values($this->get_client_subscriptions($client->getId()));           
    }

    private function request_values(Session $session, Client $client, array $item_names): Promise {
        return \Amp\call(function() use($session, $client, $item_names){
            $item_messages = [];
            $proms  = [];
            foreach($item_names as $item_name => $event_array){
                try {                    
                    $item = get_item($item_name);
                } catch(InvalidArgException $ie) {
                    $this->send_error($session, $client, "Invalid item requested: $item_name");
                    continue;
                }

                plog("WS request_values requesting value for item: '$item_name'", DEBUG, $session);                
                try {
                    $proms[$item_name] =  $item->get_value($session);
                } catch (UnprocessedRequestException $e){
                    plog("Cound not connect to node '{$target_node->name}'", ERROR, $session);
                    $this->send_error($session, $client, "Cound not connect to node '{$target_node->name}'");
                    continue;
                }
            }
            $results = yield \Amp\Promise\some($proms);
            $failed = $results[0];
            $succeeded = $results[1];
            foreach($succeeded as $item_name => $value){
                $item_messages[] = new ItemMessage($item_name, ItemMessage::GET, $value);
            }
            return new RestMessage(RestMessage::RESP, RestMessage::REST_CONTEXT_ITEMS, NODE_NAME, null, null, $item_messages);
        });
    }

    private function send(Session $session, $client, string $data): Promise{
        plog("- - - WS Sending $data", DEBUG, $session);
        return $client->send($data);
    }

    private function send_error(Session $session, $client, string $err_msg): Promise {
        plog("- - - WS sending ERROR message: $err_msg", ERROR, $session);
        return $client->send((new RestMessage(RestMessage::RESP, RestMessage::REST_CONTEXT_ERROR, NODE_NAME, "client", null, $err_msg))->to_json());
    }

    private function remove_client(Client $client): void {
        unset($this->subscribers[$client->getId()]);
        foreach($this->subscriptions as $item_name => $event_name_arr){
            foreach($event_name_arr as $event_name => $client_arr){
                foreach($client_arr as $client_id){
                    if($client_id == $client->getId()){
                        unset($this->subscriptions[$item_name][$event_name]);
                    }
                }
            }
        }
    }
}

?>