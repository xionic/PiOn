#!/usr/bin/env php
<?php
	require_once("vendor/autoload.php");
	require_once("constants.php"); // load first
	foreach (glob("{.,hardware,items,nodes}/*.php", GLOB_BRACE) as $filename)
	{
		require_once $filename; //SECURITY
	}

	use Amp\Loop;

	use Amp\ByteStream\ResourceOutputStream;
	use Amp\Http\Server\HttpServer;
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
	
	
	//Load Config
	$config_json = file_get_contents("config/config.json");
	$config = null;
	$parser = new JsonParser;
	try {
		$config = $parser->parse($config_json);
	} catch(Exception $e){
		var_dump($e->getDetails());
		$details = $e->getDetails();
		plog("Failed to parse config.json. Error at line: {$details['line']}", FATAL);
	}

	
	//Model creation
	$model = new Model($config->model);
	$this_node = $model->get_node(NODE_NAME);
	

	Loop::run(function() use ($this_node){
		
		$logHandler = new StreamHandler(new ResourceOutputStream(STDOUT));
		$logHandler->setFormatter(new ConsoleFormatter);
		$logger = new Logger('server');
		$logger->pushHandler($logHandler);
		
		$sockets[]  = Socket\Server::listen("0.0.0.0:" . $this_node->port);
		$server = new HttpServer($sockets, new CallableRequestHandler(function (Amp\Http\Server\Request $request) {
			try{
				$ip = $request->getClient()->getRemoteAddress()->getHost();
				$port = $request->getClient()->getRemoteAddress()->getPort();
				plog("HTTP req from " . $ip.":".$port . " for " . $request->getUri()->getPath()."?".urldecode($request->getURI()->getQuery()), DEBUG);
				$path = $request->getURI()->getPath();
				$response = null;
				if(substr($path,0,5) == "/web/"){
					return handle_static_request($request);
				} else {
					return handle_rest_request($request);
				}
			} catch(\Exception $e){
				plog("ERROR:" . var_export($e, true), ERROR);
				return new Response(500, [
					"content-type" => "text/plain; charset=utf-8"
				], "ERROR:" . var_export($e, true)); //SECURITY
			}
        
		}), $logger);
		yield $server->start();
		
	/*
		
		//error logging
		$server->on('error', function (Exception $e) {
			plog('Error: ' . $e->getMessage() . PHP_EOL,ERROR);
			if ($e->getPrevious() !== null) {
				echo 'Previous: ' . $e->getPrevious()->getMessage() . PHP_EOL;
			}
		});

		$socket = new React\Socket\Server("0.0.0.0:" . $this_node->http_port, $loop);
		$server->listen($socket);
*/
		// Stop the server gracefully when SIGINT is received.
		// This is technically optional, but it is best to call Server::stop().
		Amp\Loop::onSignal(SIGINT, function (string $watcherId) use ($server) {
			Amp\Loop::cancel($watcherId);
			yield $server->stop();
		});

		echo "HTTP server running on " . NODE_NAME . ":" . $this_node->port . PHP_EOL;
	
	
	});
	echo "MAIN LOOP ENDED!!!\n";
	
	function plog($text, $level){
		if($level == FATAL){
			die(date("Y/m/d H:i:s") . ": (FATAL) " . $text . PHP_EOL);
		}
		else if($level >= 0){
			echo date("Y/m/d H:i:s") . ":" . $text . PHP_EOL;
		}
	}
	function get_model(){
		global $model;
		return $model;
	}
	function get_item($name){
		return get_model()->get_item($name);
	}
	function get_node($name){
		return get_model()->get_node($name);
	}
	function get_loop(){
		global $loop;
		return $loop;
	}

?>