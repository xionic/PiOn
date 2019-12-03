#!/usr/bin/env php
<?php
	require_once("vendor/autoload.php");
	require_once("constants.php"); // load first
	foreach (glob("{.,hardware,items,nodes}/*.php", GLOB_BRACE) as $filename)
	{
		require_once $filename;
	}


	
	
	//Load Config
	$config_json = file_get_contents("config/config.json");
	$config  = json_decode($config_json) or die ("Failed to parse config.json\n");
	
	//var_dump($config);
	
	//Model creation
	$model = new Model($config->model);
	$this_node = $model->get_node(NODE_NAME);
	
	$loop = React\EventLoop\Factory::create();
	
	//start intra-node socket server
	$sock_server = new React\Socket\Server("0.0.0.0:" . $this_node->port, $loop);
	$sock_server->on('connection', function (React\Socket\ConnectionInterface $connection) {
		plog('Socket connection from ' . $connection->getRemoteAddress() . PHP_EOL, VERBOSE);
		
		
		$connection->on('data', function($data) use ($connection){
			plog("SOCKET RAW MESSAGE " . $data, 3);
			$sn = new Socket_Message($data);
			
			$resp = handle_socket_request($sn) ;
			$json = $resp->to_json();
			plog("SOCK RAW RESPONSE: ".$json, DEBUG);
			$connection->write($json . PHP_EOL);
			
			//$connection->write('hello there!' . PHP_EOL);
			//$connection->write("STop sending shit" . PHP_EOL);
			//$connection->close();
		});

	});

	//start HTTP server
	$server = new React\Http\Server(function (Psr\Http\Message\ServerRequestInterface $request) {
		$path = $request->getURI()->getPath();
		if(substr($path,0,5) == "/web/"){
			return handle_static_request($request);
		} else {
			$t = handle_rest_request($request);
			//echo "TTTTTTTTTTTTTTTTTTTT " . var_export($t,true) . "\n";
			return $t;
		}
	});
	
	//error logging
	$server->on('error', function (Exception $e) {
		plog('Error: ' . $e->getMessage() . PHP_EOL,ERROR);
		if ($e->getPrevious() !== null) {
			echo 'Previous: ' . $e->getPrevious()->getMessage() . PHP_EOL;
		}
	});

	$socket = new React\Socket\Server("0.0.0.0:" . $this_node->http_port, $loop);
	$server->listen($socket);

	echo "Server running on " . NODE_NAME . ":" . $this_node->port . PHP_EOL;


	$loop->run(true);
	echo "MAIN LOOP ENDED!!!\n";
	
	function plog($text, $level){
		if($level >= 0){
			echo date("Y/m/d H:i:s") . ":" . $text . PHP_EOL;
		}
	}
	function get_model(){
		global $model;
		return $model;
	}
	function get_node($name){
		return get_model()->get_node($name);
	}
	function get_loop(){
		global $loop;
		return $loop;
	}

?>