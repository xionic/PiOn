#!/usr/bin/env php
<?php
	require_once("vendor/autoload.php");
	
	require_once("constants.php"); // load first
	require_once("Timer.class.php"); //fix(hack) load order
	foreach (glob("{.,hardware,items,nodes}/*.php", GLOB_BRACE) as $filename)
	{
		require_once $filename; //SECURITY
	}
	
	use \PiOn\Model;
	use \PiOn\Event\EventManager;
	use \PiOn\Node\Node;
	use \PiOn\Item\Item;
	use \PiOn\Item\ItemMessage;
	use \PiOn\RestMessage;
	use \PiOn\Event\Scheduler;

	use Amp\Loop;
	use Amp\ByteStream\ResourceOutputStream;
	use Amp\Http\Server\HttpServer;
	use \Amp\Http\Client\HttpClientBuilder;
	use Amp\Http\Server\RequestHandler\CallableRequestHandler;
	use Amp\Http\Server\Request;
	use Amp\Http\Server\Response;
	use Amp\Http\Status;
	use Amp\Socket;
	use Amp\Log\ConsoleFormatter;
	use Amp\Log\StreamHandler;
	use Psr\Log\NullLogger;
	use Monolog\Logger;
	use Seld\JsonLint\JsonParser;
	use \Amp\Promise;
	use \Amp\Http\Server\Router;
	use \Amp\Http\Server\StaticContent\DocumentRoot;
	
	$logger = create_logger("main");
	
	//Load Config
	$config_json = file_get_contents("config/config.json");
	$config = null;
	$parser = new JsonParser;
	try {
		$config = $parser->parse($config_json);
	} catch(Exception $e){
		$details = $e->getDetails();
		plog("Failed to parse config.json. Error at line: {$details['line']}", FATAL);
	}

	
	//Model creation
	$model = new Model($config->model);
	$this_node = $model->get_node(NODE_NAME);
	$model->init();
	
	//Events and timer init
	EventManager::init();
	Scheduler::init();
	
	
	

	Loop::run(function() use ($this_node){
		
	foreach (glob("{events,transforms}/*.php", GLOB_BRACE) as $filename) // events rely on PiOn being loaded
	{
		require_once $filename; //SECURITY
	}
		
	$sockets[]  = Socket\Server::listen("0.0.0.0:" . $this_node->port);
	//static content handler
	$documentRoot = new DocumentRoot(__DIR__ . '/web/build/default');
	$router = new Amp\Http\Server\Router;
	$router->addRoute('GET', '/api/', new CallableRequestHandler(function (Request $request) {
		return yield handle_rest_request($request);
	}));
	$router->setFallback($documentRoot);
	$server = new HttpServer($sockets, $router, create_logger("server")); 
	
	//create HTTP server
	
	/*$server = new HttpServer($sockets, new CallableRequestHandler(function (Amp\Http\Server\Request $request) {
		try{

			$ip = $request->getClient()->getRemoteAddress()->getHost();
			$port = $request->getClient()->getRemoteAddress()->getPort();
			plog("HTTP req from " . $ip.":".$port . " for " . $request->getUri()->getPath()."?".urldecode($request->getURI()->getQuery()), DEBUG);
			$path = $request->getURI()->getPath();
			$response = null;
			if(substr($path,0,5) == "/web/"){
				return handle_static_request($request);
			} else {					
				return yield handle_rest_request($request);
			}
		} catch(\Exception $e){
			//plog("ERROR:" . @var_export($e, true), ERROR);
			throw $e;
			return respond("ERROR:" . var_export($e, true),500); //SECURITY
		}
	
	}), create_logger("server"));*/
	$server->setErrorHandler(new \PiOn\HTTPErrorHandler());
	yield $server->start();		

	// Stop the server gracefully when SIGINT is received.
	// This is technically optional, but it is best to call Server::stop().
	Amp\Loop::onSignal(SIGINT, function (string $watcherId) use ($server) {
		Amp\Loop::cancel($watcherId);
		yield $server->stop();
	});
	
	});
	echo "MAIN LOOP ENDED!!!\n";
	
	function plog($text, $level){
		$logger = get_logger("main");
		if($level == FATAL){
			$logger->emergency($text);
			die();
		}
		else if($level >= 0){			
			switch($level){
				case ERROR: $logger->error($text); break;
				case VERBOSE: 
				case INFO: $logger->info($text); break;
				case DEBUG: $logger->debug($text); break;
			}
		}
	}
	function get_model(): Model{
		global $model;
		return $model;
	}
	function get_item($name): Item{
		return get_model()->get_item($name);
	}
	function get_node($name): Node{
		return get_model()->get_node($name);
	}
	function get_loop(): \Amp\Loop{
		global $loop;
		return $loop;
	}
	
	function create_logger($name){
		global $loggers;
		$loggers[$name] = new Logger($name);
		$logHandler = new StreamHandler(new ResourceOutputStream(STDOUT));
		$logHandler->setFormatter(new ConsoleFormatter);
		$loggers[$name]->pushHandler($logHandler);
		return $loggers[$name];
	}
	function get_logger($name){
		global $loggers;
		return $loggers[$name];
	}
	
	function respond(String $content, int $status_code, String $content_type = "text/plain", $headers = array()):Response{
		return new Amp\Http\Server\Response($status_code, [
			"content-type" => "$content_type; charset=utf-8",
			"access-control-allow-origin" => "*",
		], $content);
	}
	
	function send(RestMessage $rest_message): Promise{ //returns RestMessage
		//plog("Sending to {$rest_message->target_node} message: " .$rest_message->to_json(), DEBUG);
		$value = null;
		$reponse_received = false;
		
		$client = Amp\Http\Client\HttpClientBuilder::buildDefault();
		$target_node = get_node($rest_message->target_node);
		$url = $target_node->get_base_url() . "/api/?data=". urlencode($rest_message->to_json());

		$call = Amp\Call(static function() use($client, $target_node, $url){
			plog("Making REST request to ". $target_node->hostname. ", url: ".urldecode($url), DEBUG);
			$resp = yield $client->request(new \Amp\Http\Client\Request($url));
			$json = yield $resp->getBody()->buffer();
			if($resp->getStatus() != 200){					
				throw new Exception("Error status received from " . $target_node->hostname . ": " . $resp->getStatus() . " Body is: " . $json);
			}
			$rest_message = RestMessage::from_json($json);
			plog("Responding with: " . json_encode($rest_message), DEBUG);

			//plog("Successfully retrieved remote value from node: " . $THIS->node_name. ", Value: ". ($item_message->value == null ? "NULL":$item_message->value), DEBUG);
			
			return $rest_message;
		});	
		//var_dump($call);

		return $call;
	}

?>