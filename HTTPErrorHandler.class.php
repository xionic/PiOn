<?php
namespace PiOn;
//namespace Amp\Http\Server;

use \Amp\Promise;
use \Amp\Success;
use \Amp\Http\Server\Request;
use \Amp\Http\Server\Response;

class HTTPErrorHandler implements \Amp\Http\Server\ErrorHandler
{
    /**
     * @param int          $statusCode Error status code, 4xx or 5xx.
     * @param string|null  $reason Reason message. Will use the status code's default reason if not provided.
     * @param Request|null $request Null if the error occurred before parsing the request completed.
     *
     * @return Promise
     */
    public function handleError(int $statusCode, string $reason = null, Request $request = null): Promise{
		$req_path = $request->getUri()->getScheme() . "://" . $request->getUri()->getHost() . ":" . $request->getUri()->getPort(). $request->getUri()->getPath();
		plog("RETURNING HTTP SERVER ERROR CODE: $statusCode, reason: $reason Requested URI: $req_path" , INFO);
		 $response = new Response($statusCode, [
            "content-type" => "text/html; charset=utf-8"
        ], "HTTP $statusCode");

        $response->setStatus($statusCode, $reason);

        return new Success($response);
	}
}

?>