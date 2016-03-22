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
				$request_file = $_SERVER['argv'][$i + 1];
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

	if( !$testidor->getPayloads() ) {
		TestIdor::help('Payloads not found!');
	}
}
// ---


// init
{
	$reference = new TestIdorRequest();
	if( !$reference->loadFile($request_file) ) {
		TestIdor::help('Request file not found!');
	}
	$reference->setSsl( $ssl );

	$testidor->setReference( $reference );
	$testidor->runReference();
}
// ---


// main loop
{
	$testidor->run();
}
// ---


exit();

?>
