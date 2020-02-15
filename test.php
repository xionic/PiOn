<?php 

return;

require_once("vendor/autoload.php");
	use Amp\Loop;	
	use Amp\ByteStream\ResourceOutputStream;
	use Amp\Http\Server\HttpServer;
	use \Amp\Http\Client\HttpClientBuilder;
	use Amp\Http\Server\RequestHandler\CallableRequestHandler;
	use Amp\Http\Server\Request;
	use Amp\Http\Server\Response;
	use Amp\Http\Status;
	use Amp\Socket;
	use Amp\Deferred;
	use Amp\Log\ConsoleFormatter;
	use Amp\Log\StreamHandler;
	use Psr\Log\NullLogger;
	use Monolog\Logger;
	use Seld\JsonLint\JsonParser;
	use \Amp\Promise;
	use \Amp\Http\Server\Router;
	use \Amp\Http\Server\StaticContent\DocumentRoot;

class prom implements Promise {
	function __construct(){
		echo "CONST\n";
	}	
	static $num = 1;
	function onResolve( $c){
		echo "RESOLVING " . self::$num++ . "\n";
		return null;
	}
}

class task {
	public $done = false;
	public $data = null;
	function __construct(){

	}

	function dosomething(): Promise{
		$d = new Deferred;
		Loop::delay(500, function() use ($d){
			$d->resolve("I'm RESOLVED :)\n");
		});
		echo "promise made\n";
		return $d->promise();
	}

	function setData($data){
		$this->data = $data;
	}
}

function pdump(){ 
	
	$db = debug_backtrace();
	//
	for($n = 1; $n < (count($db) > 4 ? 4 : count($db)); $n++){
		$d = $db[$n];
		if($n != 1)
			echo " ---> ";
		if(!array_key_exists("file", $d)){
			$d["file"] = "prob closure";
			$d["line"] = "??";
		}
		echo basename($d["file"]) . ":" . $d["line"] . "::" . $d["function"] ;
		//var_dump($db);
	}
	echo PHP_EOL;
}

Loop::run(function(){

	var_dump(yield \Amp\call(function(){
		$t = new task;
		return yield $t->dosomething();
	}));



	//$prom = new prom;
	/*
	$d = debug_backtrace()[0];	
	echo $d["file"] . ":" . $d["line"] . "->" . $d["function"] . PHP_EOL;
	*/
	//var_dump(debug_backtrace());die;
	//die;



/*
		
		/*Loop::delay(500,function(){
			echo "DELAYED\n";
		});
		$client = Amp\Http\Client\HttpClientBuilder::buildDefault();
	//	$url = "http://localhost:8888";
		$url = "https://ssl.xionic.co.uk/test.php";

		var_dump( yield Amp\Call(static function() use($client,$url) {
			echo "Starting req\n";
			xdebug_start_trace('/tmp/xdebug.trace');
			$resp = yield $client->request(new \Amp\Http\Client\Request($url));
			xdebug_stop_trace();
			echo "req made\n";
			$json = yield $resp->getBody()->buffer();		

			//plog("Successfully retrieved remote value from node: " . $THIS->node_name. ", Value: ". ($item_message->value == null ? "NULL":$item_message->value), DEBUG);
			echo "json: $json\n";
			return $json;
		}));	
		*/

	/*
	$sockets[]  = Socket\Server::listen("0.0.0.0:" . 8020);

	//static content handler
	$documentRoot = new DocumentRoot(__DIR__ . '/web/build/default');
	$router = new Amp\Http\Server\Router;
	$loggers = new Logger("test");
	$logHandler = new StreamHandler(new ResourceOutputStream(STDOUT));
	$logHandler->setFormatter(new ConsoleFormatter);

	$router->setFallback($documentRoot);
	$server = new HttpServer($sockets, new CallableRequestHandler(function(Request $req){
		return new Amp\Http\Server\Response(200, [
			"content-type" => "text/plain; charset=utf-8",
			"access-control-allow-origin" => "*"
		], "HEY3");
	}), $loggers); 
	
	
	//$server->setErrorHandler(new \PiOn\HTTPErrorHandler());
	yield $server->start();	*/
});



?>