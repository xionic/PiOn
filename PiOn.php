#!/usr/bin/env php
<?php
	require_once("vendor/autoload.php");
	
	require_once("constants.php"); // load first
	require_once("Timer.class.php"); //fix(hack) load order
	require_once("vendor/xionic/argh/src/Argh.class.php"); // cannot get to work with composer
	foreach (glob("{.,hardware,items,nodes}/*.php", GLOB_BRACE) as $filename)
	{
		require_once $filename; //SECURITY
	}
	
	use \PiOn\Model;
	use \PiOn\Session;
	use \PiOn\Event\EventManager;
	use \PiOn\Node\Node;
	use \PiOn\Item\Item;
	use \PiOn\Item\ItemMessage;
	use \PiOn\RestMessage;
	use \PiOn\Event\Scheduler;
	use \PiOn\WebSocketManager;

	use \Psr\Log\NullLogger;
	use \Monolog\Logger;
	use \Monolog\Handler\RotatingFileHandler;
	use \Monolog\Handler\StreamHandler;
	use \Seld\JsonLint\JsonParser;

	use \FastRoute\simpleDispatcher;
	use \FastRoute\Dispatcher;
	use \FastRoute\RouteCollector ;

	use \Psr\Http\Message\ServerRequestInterface;
	use \React\EventLoop\Factory;
	use \React\Http\Message\Response;
	use \React\Http\Server;

	use Ratchet\MessageComponentInterface;
	use Ratchet\ConnectionInterface;
	
	//Session init
	Session::init();

	$logger = create_logger("main");
	plog("Starting PiOn...", INFO, Session::$INTERNAL);
	
	//Load Config
	plog("Reading config.json", INFO, Session::$INTERNAL);
	$config = null;
	$parser = new JsonParser;
	$cur_file = "";
	try {
		$cur_file = "config/config.json";
		$config = $parser->parse(file_get_contents($cur_file));
		$config->model = (Object)[];

		$cur_file = "config/nodes.config.json";
		$config->model->nodes = $parser->parse(file_get_contents($cur_file));
		$cur_file = "config/items.config.json";
		$config->model->items = $parser->parse(file_get_contents($cur_file));
		$cur_file = "config/hardware.config.json";
		$config->model->hardware = $parser->parse(file_get_contents($cur_file));
	} catch(Exception $e){
		$details = $e->getDetails();
		plog("Failed to parse $cur_file. Error at line: {$details['line']}", FATAL, Session::$INTERNAL);
	}
	
	//Model creation
	$model = new Model($config->model);
	$this_node = $model->get_node(NODE_NAME);
	
	//Events and timer init
	EventManager::init();
	Scheduler::init();	
	
	//model initialisation
	$model->init();

	foreach (glob("{config/events,transforms}/*.php", GLOB_BRACE) as $filename) // events rely on PiOn model being loaded and inited
		{
			require_once $filename; //SECURITY
		}

	$loop = Factory::create();

	$dispatcher = FastRoute\simpleDispatcher(function(FastRoute\RouteCollector $routes) use ($listTasks, $addTask) {
		$routes->addRoute('GET', '/api/', function(ServerRequestInterface $request): Response{
			return handle_rest_request($request);
		
		});
	});

	$server = new Server($loop, function (ServerRequestInterface $request) {

		$routeInfo = $dispatcher->dispatch($request->getMethod(), $request->getUri()->getPath());

		switch ($routeInfo[0]) {
			case FastRoute\Dispatcher::NOT_FOUND:
				return new Response(404, ['Content-Type' => 'text/plain'],  'Not found');
			case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
				return new Response(405, ['Content-Type' => 'text/plain'], 'Method not allowed');              
			case FastRoute\Dispatcher::FOUND:
				return $routeInfo[1]($request);
		}

		throw new LogicException('Something went wrong in routing.');
	/*
		return new Response(
			200,
			array(
				'Content-Type' => 'text/plain'
			),
			"Hello world\n"
		);*/
	});

	//$socket = new \React\Socket\Server("0.0.0.0:" . $this_node->port, $loop);
	$socket = new \React\Socket\Server("0.0.0.0:28080", $loop);
	$server->listen($socket);

	$loop->run();
/*
try{
	Loop::run(function() use ($this_node){
		
		


			
		$sockets[]  = Socket\Server::listen("0.0.0.0:" . $this_node->port);

		//websocket
		$ws_manager = new WebSocketManager;
		$ws = $ws_manager->get_websocket();

		//static content handler
		$documentRoot = new DocumentRoot(__DIR__ . '/web/build/default');
		$router = new Amp\Http\Server\Router;
		$router->addRoute('GET', '/api/', new CallableRequestHandler(function (Request $request) {	
			return yield handle_rest_request($request);
		}));
		$router->addRoute('GET', '/websocket/', $ws);
		$router->setFallback($documentRoot);
		$server = new HttpServer($sockets, $router, create_logger("server")); 
		
		
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
	} catch(Amp\MultiReasonException $me){
		var_dump($me);
	}
*/

	function plog(string $text, int $level, \PiOn\Session $session): void {
		$logger = get_logger("main");	
		$text = $session->get_req_num() . ": $text";
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

	function passert( $cond, \PiOn\Session $session, String $message = ""): void{
		if(!$cond){
			$d = debug_backtrace();
			plog("passert FAILED at: " . $d[0]["file"] . ":" . $d[0]["line"] . " $message", FATAL, $session);
		}
	}

	function get_model(): Model{
		global $model;
		return $model;
	}
	function get_item($name): Item{		
		return get_model()->get_item($name);
	}
	function get_items(): array {		
		return get_model()->get_items();
	}
	function get_node($name): Node{
		return get_model()->get_node($name);
	}
	function get_nodes(): array {
		return get_model()->get_nodes();
	}
	function get_loop(): \Amp\Loop{
		global $loop;
		return $loop;
	}
	
	
	function create_logger(String $name): Logger{
		global $loggers;
		$log_file_path = "log/PiOn.log";
		$loggers[$name] = new Logger($name);
		$logHandlerConsole = new StreamHandler(STDOUT);
		//$logHandlerConsole->setFormatter(new ConsoleFormatter);
		$loggers[$name]->pushHandler($logHandlerConsole);
		
		$logHandler = new RotatingFileHandler($log_file_path, 28);
		//$log_file = fopen($log_file_path, "a");
		//$logFileHandler = new \Monolog\Handler\StreamHandler($log_file);
		$loggers[$name]->pushHandler($logHandler);
		return $loggers[$name];
	}
	function get_logger(String $name): Logger{
		global $loggers;
		return $loggers[$name];
	}
	
	function respond(String $content, int $status_code, String $content_type = "text/plain", $headers = array()):Response{
		return new Amp\Http\Server\Response($status_code, [
			"content-type" => "$content_type; charset=utf-8",
			"access-control-allow-origin" => "*",
		], $content);
	}
	
	function send(\PiOn\Session $session, RestMessage $rest_message): Promise{ //returns RestMessage
		//plog("Sending to {$rest_message->target_node} message: " .$rest_message->to_json(), DEBUG);
		$value = null;
		$reponse_received = false;
		
		$client = Amp\Http\Client\HttpClientBuilder::buildDefault();
		$target_node = get_node($rest_message->target_node);
		$url = $target_node->get_base_url_ip() . "/api/?data=". urlencode($rest_message->to_json());

		$call = Amp\Call(static function() use($client, $target_node, $url, $session) {
			plog("Making REST request to ". $target_node->hostname. ", url: ".urldecode($url), DEBUG, $session);
			$resp = yield $client->request(new \Amp\Http\Client\Request($url));
			$json = yield $resp->getBody()->buffer();
			if($resp->getStatus() != 200){					
				throw new Exception("Error status received from " . $target_node->hostname . ": " . $resp->getStatus() . " Body is: " . $json);
			}
			$rest_message = RestMessage::from_json($json);
			$url_comps = parse_url($url);
			$from_str = $url_comps["scheme"] . "://" . $url_comps["host"] . ":" . $url_comps["port"];
			plog("Got response from '{$from_str}': " . json_encode($rest_message), DEBUG, $session);

			//plog("Successfully retrieved remote value from node: " . $THIS->node_name. ", Value: ". ($item_message->value == null ? "NULL":$item_message->value), DEBUG);
			
			return $rest_message;
		});	
		//var_dump($call);

		return $call;
	}

?>