<?php

/**
 * I don't believe in license
 * You can do want you want with this program
 * - gwen -
 */

include( 'Utils.php' );
include( 'TestIdor.php' );
include( 'TestIdorRequest.php' );


// parse command line
{
	$testidor = new TestIdor();
	$ssl = false;
	$argc = $_SERVER['argc'] - 1;

	for ($i = 1; $i <= $argc; $i++) {
		switch ($_SERVER['argv'][$i]) {
			case '-f':
				$testidor->setRequestFile($_SERVER['argv'][$i + 1]);
				$i++;
				break;

			case '-h':
				TestIdor::help();
				break;

			case '-s':
				$ssl = true;
				break;

			case '-t':
				$testidor->setTolerance($_SERVER['argv'][$i + 1]);
				$i++;
				break;

			case '-p':
				$testidor->parsePayloads($_SERVER['argv'][$i + 1]);
				$i++;
				break;
		}
	}

	if (!$testidor->getRequestFile()) {
		TestIdor::help('Request file not found!');
	}

	if (!$testidor->getPayloads()) {
		TestIdor::help('Payloads not found!');
	}

	//var_dump($testidor);
	//exit();
}
// ---


// parse request file
{
	$request = trim( file_get_contents($testidor->getRequestFile()) ); // the full request
	$request = str_replace( "\r", "", $request );
	$t_request = explode( "\n\n", $request ); // separate headers and post parameters
	$t_headers = explode( "\n", $t_request[0] ); // headers
	$h_request = array_map( function($str){return explode(':',trim($str));}, $t_headers ); // splited headers

	$first = array_shift( $t_headers ); // first ligne is: method, url, http version
	list($method,$url,$http) = explode( ' ', $first );

	$post = ''; // post parameters
	if( count($t_request) > 1 ) {
		$post = $t_request[1];
	}

	$host = '';
	$cookies = '';
	$h_replay = array(); // headers kept in the replay request

	foreach( $h_request as $header )
	{
		$h = trim( array_shift($header) );

		switch( $h )
		{
			case 'Accept':
			case 'Accept-Language':
			case 'Accept-Encoding':
			case 'Connection':
			case 'Content-Type':
			case 'Referer':
			case 'User-Agent':
				$h_replay[ $h ] = $h.': '.trim( implode(':',$header) );
				break;

			case 'Cookie':
				$cookies = $h.': '.trim( implode(':',$header) );
				break;

			case 'Host':
				$host = trim( implode(':',$header) );
				break;

			case 'Content-Length':
			default:
				break;
		}
	}
}
// ---


// reference request
{
	$testidor->createReference( $host, $ssl, $url, $method, $http, $h_replay, $cookies, $post );
}
// ---


// main loop
{
	$testidor->run();
}
// ---


exit();

?>
