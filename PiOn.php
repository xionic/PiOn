#!/usr/bin/env php
<?php
require_once("vendor/autoload.php");

require_once("constants.php"); // load first
require_once("vendor/xionic/argh/src/Argh.class.php"); // cannot get to work with composer
require_once("Timer/Timer.class.php"); //fix(hack) load order
foreach (glob("{.,hardware,items,nodes,Timer,Scheduler}/*.php", GLOB_BRACE) as $filename) {
	require_once $filename; //SECURITY
}

use \PiOn\Config;
use \PiOn\Model;
use \PiOn\Session;
use \PiOn\Event\EventManager;
use \PiOn\Node\Node;
use \PiOn\Item\Item;
use \PiOn\Item\ItemMessage;
use \PiOn\RestMessage;
use \PiOn\Scheduler\Scheduler;
use \PiOn\WebSocketManager;

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
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;
use Seld\JsonLint\JsonParser;
use Seld\JsonLint\ParsingException;
use \Amp\Promise;
use \Amp\Http\Server\Router;
use \Amp\Http\Server\StaticContent\DocumentRoot;

//Session init
Session::init();

$logger = create_logger("main");
plog("Starting PiOn...", INFO, Session::$INTERNAL);

//Load Config
plog("Reading config.json", INFO, Session::$INTERNAL);

$parser = new JsonParser;
$cur_file = "";
$model_conf = (object)[];
try {
	$cur_file = "config/config.json";
	$config_items = $parser->parse(file_get_contents($cur_file));
	foreach($config_items as $conf => $v){
		Config::set($conf, $v);
	}


	$cur_file = "config/nodes.config.json";
	$model_conf->nodes = $parser->parse(file_get_contents($cur_file));
	$cur_file = "config/items.config.json";
	$model_conf->items = $parser->parse(file_get_contents($cur_file));
	$cur_file = "config/hardware.config.json";
	$model_conf->hardware = $parser->parse(file_get_contents($cur_file));

} catch (ParsingException  $e) {
	$details = $e->getDetails();
	plog("Failed to parse $cur_file. Error at line: {$details['line']}", FATAL, Session::$INTERNAL);
}

//Model creation
$model = new Model($model_conf);
Config::set("model", $model);
$this_node = $model->get_node(NODE_NAME);

//Events and timer init
EventManager::init();
Scheduler::init();

//model initialisation
$model->init();

/*Loop::setErrorHandler(function (\Throwable $e) {
		try {
			throw $e;
		} catch (\Amp\MultiReasonException $mre){
			echo "Loop Exception\n";
			foreach($mre->getReasons() as $reason){
				echo "$reason\n";
			}
		} catch (\Throwable $e){
			echo "error handler -> " . $e->getMessage() . PHP_EOL;
		}
	});
	*/
try {
	Loop::run(function () use ($this_node, $logger) {
		

		foreach (glob("{config/events,transforms}/*.php", GLOB_BRACE) as $filename) // events rely on PiOn model being loaded and inited
		{
			require_once $filename; //SECURITY
		}

		$sockets[]  = Socket\Server::listen("0.0.0.0:" . $this_node->port);

		//websocket
		$ws_manager = new WebSocketManager;
		$ws = $ws_manager->get_websocket();

		//static content handler
		$documentRoot = new DocumentRoot(__DIR__ . '/web/build');
		$router = new Amp\Http\Server\Router;
		$router->addRoute('GET', '/api/', new CallableRequestHandler(function (Request $request) {
			return yield handle_rest_request($request);
		}));
		$router->addRoute('GET', '/websocket/', $ws);
		$router->setFallback($documentRoot);
		$server = new HttpServer($sockets, $router, $logger);


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
} catch (Amp\MultiReasonException $me) {
	var_dump($me);
}
function plog(string $text, int $level, \PiOn\Session $session): void {
	$logger = get_logger();
	$text = $session->get_req_num() . ": $text";
	if ($level == FATAL) {
		$logger->emergency($text);
		die();
	} else if ($level >= 0) {
		switch ($level) {
			case ERROR:
				$logger->error($text);
				break;
			case VERBOSE:
			case INFO:
				$logger->info($text);
				break;
			case DEBUG:
				$logger->debug($text);
				break;
		}
	}
}

function passert($cond, \PiOn\Session $session, String $message = ""): void {
	if (!$cond) {
		$d = debug_backtrace();
		plog("passert FAILED at: " . $d[0]["file"] . ":" . $d[0]["line"] . " $message", FATAL, $session);
	}
}

function get_model(): Model {	
	//var_dump(Config::get("model"));
	return Config::get("model");
}
function get_item($name): Item {
	return get_model()->get_item($name);
}
function get_items(): array {
	return get_model()->get_items();
}
function get_node($name): Node {
	return get_model()->get_node($name);
}
function get_nodes(): array {
	return get_model()->get_nodes();
}
function get_loop(): \Amp\Loop {
	global $loop;
	return $loop;
}


function create_logger(String $name): Logger {
	
	$log_file_path = "log/PiOn.log";
	$logger = new Logger($name);
	$logHandlerConsole = new StreamHandler('php://stdout', Logger::DEBUG);
	$logHandlerConsole->setFormatter(new ConsoleFormatter);
	$logger->pushHandler($logHandlerConsole);

	$logHandler = new RotatingFileHandler($log_file_path, 28);
	//$log_file = fopen($log_file_path, "a");
	//$logFileHandler = new \Monolog\Handler\StreamHandler($log_file);
	$logger->pushHandler($logHandler);
	return $logger;
}
function get_logger(): Logger {
	global $logger;
	return $logger;
}

function respond(String $content, int $status_code, String $content_type = "text/plain", $headers = array()): Response {
	return new Amp\Http\Server\Response($status_code, [
		"content-type" => "$content_type; charset=utf-8",
		"access-control-allow-origin" => "*",
	], $content);
}

function send(\PiOn\Session $session, RestMessage $rest_message): Promise { //returns RestMessage
	//plog("Sending to {$rest_message->target_node} message: " .$rest_message->to_json(), DEBUG);
	$value = null;
	$reponse_received = false;

	$client = Amp\Http\Client\HttpClientBuilder::buildDefault();
	$target_node = get_node($rest_message->target_node);
	$url = $target_node->get_base_url_ip() . "/api/?data=" . urlencode($rest_message->to_json());

	$call = Amp\Call(static function () use ($client, $target_node, $url, $session) {
		plog("Making REST request to " . $target_node->hostname . ", url: " . urldecode($url), DEBUG, $session);
		$resp = yield $client->request(new \Amp\Http\Client\Request($url));
		$json = yield $resp->getBody()->buffer();
		if ($resp->getStatus() != 200) {
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