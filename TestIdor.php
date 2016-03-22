<?php

/**
 * I don't believe in license
 * You can do want you want with this program
 * - gwen -
 */

class TestIdor
{
	const DEFAULT_TOLERANCE = 5;
	const PAYLOADS_DELIM = ';';
	const PAYLOADS_VALUE_DELIM = ',';
	const PAYLOADS_CONSIDER_RELATIVE = 100;

	/**
	 * @var TestIdorRequest
	 *
	 * reference request
	 */
	private $reference = null;

	/**
	 * @var int
	 *
	 * tolerance for output result
	 */
	private $tolerance = self::DEFAULT_TOLERANCE; // percent
	private $_tolerance = 0; // real value

	/**
	 * @var array
	 *
	 * payloads table
	 */
	private $t_payloads = null;

	/**
	 * @var string
	 *
	 * special chars used
	 */
	private $chars = null;
	private $_chars = null;

	/**
	 * @var array
	 *
	 * results table
	 */
	private $t_result = array();


	public static function help( $error='' )
	{
		echo "Usage: ".$_SERVER['argv'][0]." [OPTIONS] -p <payloads> -f <request_file>\n";
		echo "\n";
		echo "Options:\n";
		echo "\t-h\tprint this help\n";
		echo "\t-s\tforce https, default=off\n";
		echo "\t-t\tset tolerance for result output, default=5\n";
		echo "\n";
		echo "Payloads:\n";
		echo "\tThe program can deal with mutiple payloads\n";
		echo "\tThe payloads will replace orginal values in the request\n";
		echo "\tThe payloads can be strings, numerics or relative value\n";
		echo "\tA payload is represented by a special character\n";
		echo "\tEach payloads are evaluated separately\n";
		echo "\n";
		echo "\tPayloads must be separated by a ".self::PAYLOADS_DELIM."\n";
		echo "\tPayloads values must be separated by a ".self::PAYLOADS_VALUE_DELIM."\n";
		echo "\tNumeric values under ".self::PAYLOADS_CONSIDER_RELATIVE." are considered as relative\n";
		echo "\n";
		echo "\tInjection points can be URL, headers, cookies\n";
		echo "\tCheck example.txt as a request file example\n";
		echo "\tRequests can be paste from Burp Suite\n";
		echo "\n";
		echo "Examples:\n";
		echo "\t".$_SERVER['argv'][0]." -p \"§=10\" -f request.txt\n";
		echo "\t".$_SERVER['argv'][0]." -s -p \"^=bob,alice,jim\" -f request.txt\n";
		echo "\t".$_SERVER['argv'][0]." -t 10 -s -p \"§=5;^=bob,alice,jim;$=123,456,789\" -f request.txt\n";
		echo "\n";

		if( $error ) {
			echo "\nError: ".$error."!\n";
		}

		exit();
	}


	private function isRelative( $v )
	{
		if( !is_array($v) && preg_match('#[-0123456798]#',$v) && $v<self::PAYLOADS_CONSIDER_RELATIVE && $v>-self::PAYLOADS_CONSIDER_RELATIVE) {
			return true;
		} else {
			return false;
		}
	}


	public function getTolerance() {
		return $this->tolerance;
	}
	public function setTolerance( $v ) {
		$this->tolerance = (int)$v;
		return true;
	}


	public function getPayloads() {
		if( is_array($this->t_payloads) && count($this->t_payloads) ) {
			return $this->t_payloads;
		} else {
			return false;
		}
	}
	public function parsePayloads( $p )
	{
		$tmp = explode( self::PAYLOADS_DELIM, $p );
		$this->t_payloads = array();
		//var_dump( $tmp );

		foreach( $tmp as $payload ) {
			preg_match( '#(.?)=(.+)#', $payload, $matches );
			//var_dump( $matches );
			if( count($matches)==3 ) {
				$this->addPayload( $matches[1], $matches[2] );
			}
		}

	}


	public function getPayload( $p ) {
		if( isset($this->t_payloads[$p]) ) {
			return $this->t_payloads[$p];
		} else {
			return false;
		}
	}
	public function addPayload( $k, $p )
	{
		if( (int)$p && (int)$p<self::PAYLOADS_CONSIDER_RELATIVE ) {
			$this->t_payloads[$k] = array();
			for( $i=-$p ; $i<=$p ; $i++ ) {
				$this->t_payloads[$k][] = $i;
			}
		} else {
			$tmp = explode( self::PAYLOADS_VALUE_DELIM, $p );
			$this->t_payloads[ $k ] = $tmp;
		}
	}


	public function getReference() {
		return $this->reference;
	}
	public function setReference( $v ) {
		$this->reference = $v;
		return true;
	}

	public function runReference()
	{
		$this->chars = array_keys( $this->t_payloads );
		$this->_chars = '\\' . implode('\\', $this->chars);

		$this->reference->request();
		//var_dump( $this->reference );
		//exit();

		$this->_tolerance = (int)($this->reference->getResultLength() * $this->getTolerance() / 100);
		echo "\n-> Reference: RC=" . $this->reference->getResultCode() . ', RL=' . $this->reference->getResultLength() . ', T=' . $this->getTolerance() . '%, T2=' . $this->_tolerance . "\n";
	}


	public function run()
	{
		foreach( $this->t_payloads as $char=>$payloads )
		{
			$n_request = 0;
			echo "\n-> Injection: C=".$char.", P=".implode(self::PAYLOADS_VALUE_DELIM,$payloads)."\n";

			foreach( $payloads as $p )
			{
				$r = clone $this->reference;

				$n_injection = 0;
				$n_injection += $this->inject( $r, $char, $p, 'getUrl', 'setUrl' );
				foreach( $this->reference->getHeaders() as $k=>$v ) {
					$n_injection += $this->inject( $r, $char, $p, 'getHeader', 'setHeader', $k );
				}
				$n_injection += $this->inject( $r, $char, $p, 'getCookies', 'setCookies' );
				$n_injection += $this->inject( $r, $char, $p, 'getPost', 'setPost' );

				if( $n_injection ) {
					$r->request();
					//var_dump( $r );
					//$r->export();
					$this->result( $r );
					$this->t_result[] = $r;
					$n_request += $n_injection;
				}

				unset( $r );
			}

			echo $n_injection ." injection point found, ".$n_request." request performed\n";
		}

		echo "\n";
	}


	private function inject( $r, $char, $payload, $getter, $setter, $param='' )
	{
		preg_match_all('#\\' . $char . '([^' . $this->_chars . ']+)\\' . $char . '#', $this->reference->$getter($param), $matches); // original values cannot be empty
		//var_dump( $matches );
		$cnt = count($matches[0]);

		foreach( $matches[0] as $k=>$m ) {
			if( $this->isRelative($payload) ) {
				$p = (int)$matches[1][$k] + $payload;
			} else {
				$p = $payload;
			}

			$r->$setter(str_replace($m, $char . $p . $char, $r->$getter($param)), $param);
			//var_dump( $r->$getter($param) );
		}

		return $cnt;
	}


	private function result( $r )
	{
		$color = 'white';
		$diff = $r->getResultLength() - $this->reference->getResultLength();
		$text = 'U=' . $r->getUrl() . ', C=' . $r->getResultCode() . ', L=' . $r->getResultLength() . ', D=' . $diff;

		if( abs($diff) < $this->_tolerance )
		{
			// match ?!
			if( $this->isReference($r) ) {
				// this is the reference
				$color = 'dark_grey';
				$text .= ' -> REFERENCE';
			} else {
				$r->setIdor(true);
				$text .= ' -> LENGTH OK';
			}
		}
		else
		{
			// no match !!
			if( $this->isReference($r) ) {
				// this is the reference
				$color = 'red';
				$text .= ' -> ERROR';
			} else {
				//echo ' -> NORMAL';
			}
		}

		if( $r->getIdor() ) {
			if( $r->getResultCode() == $this->reference->getResultCode() ) {
				$color = 'green';
				$text .= ' AND CODE MATCH!';
			} else {
				$color = 'yellow';
				$text .= ' BUT CODE DO NOT MATCH!';
			}
		}

		Utils::_print( $text, $color );
		echo "\n";
	}


	private function isReference( $request )
	{
		if( $request->getUrl()!=$this->reference->getUrl() || $request->getHeaders()!=$this->reference->getHeaders()
			|| $request->getCookies()!=$this->reference->getCookies() || $request->getPost()!=$this->reference->getPost() ) {
			return false;
		}

		return true;
	}
}

?>
