<?php

include( 'Idor.php' );
include( 'Utils.php' );


function usage( $error='' ) {
	echo "Usage: ".$_SERVER['argv'][0]." <request file> <step> [tolerance]\n";
	if( $error ) {
		echo "Error: ".$error."!\n";
	}
	exit();
}


if( $_SERVER['argc']<3 || $_SERVER['argc']>4 ) {
	usage();
}

$step = (int)$_SERVER['argv'][2];
//var_dump( $step );

if( $_SERVER['argc'] == 4 ) {
	$tolerance = (int)$_SERVER['argv'][3];
	if ($tolerance <= 0) {
		$tolerance = Idor::DEFAULT_TOLERANCE;
	}
} else {
	$tolerance = Idor::DEFAULT_TOLERANCE;
}

$request_file = $_SERVER['argv'][1];
//var_dump( $request_file );
if( !is_file($request_file) ) {
	usage( 'File not found' );
}

$request = trim( file_get_contents($request_file) ); // la totalité de la requête
$request = str_replace( "\r", "", $request );
$t_request = explode( "\n\n", $request );
//var_dump( $t_request );
$t_headers = explode( "\n", $t_request[0] ); // les headers
//var_dump( $t_headers );
$h_request = array_map( function($str){return explode(':',trim($str));}, $t_headers ); // les headers découpés
//var_dump( $h_request );

// traitement de la 1ère ligne (method, url, http)
$first = array_shift( $t_headers );
//var_dump( $first );
list($method,$url,$http) = explode( ' ', $first );
//var_dump( $method );
//var_dump( $url );
//var_dump( $http );
//exit();

// traitement des paramètres (post)
$params = '';
if( count($t_request) > 1 ) {
	$params = $t_request[1];
}


$h_replay = array();
$cookies = '';


foreach( $h_request as $header )
{
	$h = trim( array_shift($header) );

	switch( $h )
	{
		case 'User-Agent':
		case 'Referer':
			$h_replay[ $h ] = $h.': '.trim( implode(':',$header) );
			break;

		case 'Cookie':
			$cookies = $h.': '.trim( implode(':',$header) );
			break;

		case 'Host':
			$url = 'http://'.trim(implode('',$header)).$url;
			break;

		case 'Accept':
		case 'Accept-Language':
		case 'Accept-Encoding':
		default:
			break;
	}
}

//var_dump($method);
//var_dump($url);
//var_dump($http);
//var_dump($h_replay);
//var_dump($cookies);
//var_dump($params);


// requête de référence
{
	$reference = new Idor();
	$reference->setUrl( $url );
	$reference->method = $method;
	$reference->http = $http;
	$reference->setHeaders( $h_replay );
	$reference->setCookies( $cookies );
	$reference->setParams( $params );

	$reference->request();
	//var_dump( $reference );
	//exit();

	$tolerance2 = (int)($reference->getResultLength() * $tolerance / 100);
	echo "\nRC=".$reference->getResultCode().', RL='.$reference->getResultLength().', T='.$tolerance.'%, T2='.$tolerance2."\n";
	$tolerance = $tolerance2;
	//exit();
}
// ---


// fonction principale
{
	echo "\n------------------ URL ------------------\n";
	Idor::run( 'getUrl', 'setUrl' );
	echo "\n------------------ HEADERS ------------------\n";
	foreach( $h_replay as $k=>$v ) {
		Idor::run( 'getHeader', 'setHeader', $k );
	}
	echo "\n------------------ COOKIES ------------------\n";
	Idor::run( 'getCookies', 'setCookies' );
	echo "\n------------------ POST DATA ------------------\n";
	Idor::run( 'getParams', 'setParams' );
	echo "\n";
}
// ---


exit();

?>
