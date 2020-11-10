<?php 



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
	use \Amp\Dns;



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
 $res = yield \Amp\Dns\resolve("tp-link-hs110-5");
 var_dump($res);
	

});



?>