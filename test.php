<?php /*
require_once("vendor/autoload.php");
use \Amp\Promise;
use \Amp\Loop;



class prom implements Promise {
	
	function onResolve( $c){
		echo "RESOLVING\n";
		return "Hey";
	}
}

//new \Amp\Coroutine(1);
Loop::run(function(){
	var_dump( yield \Amp\call(function() {
		echo "imma wait\n";
		//sleep(2);
		$url = 'https://ssl.xionic.co.uk/EAPS/?client_key=afd2f391-34ed-4e70-abdf-1407fe92cfe0&action=value&tag=flatstats&key=Nick%20Room%20Temperature';
		$client = Amp\Http\Client\HttpClientBuilder::buildDefault();
		$pval = yield $client->request(new \Amp\Http\Client\Request($url));
		echo "imma resolve now woth  \n";
		return $pval;
	}));
});

*/

?>