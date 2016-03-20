<?php

class Idor
{
	const DELIM = '§';
	const DEFAULT_TOLERANCE = 5;

	public $method = '';
	public $http = '';

	private $url = '';
	private $_url = '';

	private $headers = '';
	private $_headers = '';

	private $cookies = '';
	private $_cookies = '';
	private $cookie_file = '';

	private $params = '';
	private $_params = '';

	private $result = '';
	private $result_length = 0;
	private $result_code = 0;

	public $idor = false;


	public function __construct()
	{
		$this->cookie_file = tempnam('/tmp', 'cook_');
	}

	public function __clone()
	{
		$this->result = '';
		$this->result_length = 0;
		$this->result_code = 0;
	}


	private function sanitize($v)
	{
		return str_replace(self::DELIM, '', $v);
	}


	public function getResultLength()
	{
		return $this->result_length;
	}

	public function getResultCode()
	{
		return $this->result_code;
	}


	public function getUrl($null = '')
	{
		return $this->url;
	}

	public function setUrl($v, $null = '')
	{
		$this->url = $v;
		$this->_url = self::sanitize($v);
	}

	public function setHeaders($array)
	{
		foreach ($array as $k => $v) {
			$this->setHeader($v, $k);
		}
	}

	public function getHeader($key)
	{
		return $this->headers[$key];
	}

	public function setHeader($v, $key)
	{
		$this->headers[$key] = $v;
		$this->_headers[$key] = self::sanitize($v);
	}


	public function getCookies($null = '')
	{
		return $this->cookies;
	}

	public function setCookies($v, $null = '')
	{
		$this->cookies = $v;
		$this->_cookies = self::sanitize($v);
	}


	public function getParams($null = '')
	{
		return $this->params;
	}

	public function setParams($v, $null = '')
	{
		$this->params = $v;
		$this->_params = self::sanitize($v);
	}


	public function request()
	{
		$c = curl_init();
		//curl_setopt($c, CURLOPT_CUSTOMREQUEST, $this->method);
		curl_setopt($c, CURLOPT_URL, $this->_url);
		//curl_setopt($c, CURLOPT_HTTP_VERSION, $this->http);
		curl_setopt($c, CURLOPT_HEADER, true);
		curl_setopt($c, CURLOPT_HTTPHEADER, $this->_headers);
		curl_setopt($c, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($c, CURLOPT_COOKIE, $this->_cookies);
		curl_setopt($c, CURLOPT_COOKIEJAR, $this->cookie_file);
		curl_setopt($c, CURLOPT_COOKIEFILE, $this->cookie_file);
		if (strlen($this->params)) {
			curl_setopt($c, CURLOPT_POST, true);
			curl_setopt($c, CURLOPT_POSTFIELDS, $this->_params);
		}
		curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
		$this->result = curl_exec($c);
		$this->result_length = strlen($this->result);
		$this->result_code = curl_getinfo($c, CURLINFO_HTTP_CODE);
	}


	public static function run( $getter, $setter, $p='' )
	{
		global $step, $tolerance;
		global $reference, $t_result;

		preg_match_all('#' . self::DELIM . '([0-9]+)' . self::DELIM . '#', $reference->$getter($p), $matches);
		//var_dump( $matches );

		if (!count($matches[0])) {
			echo "Nothing here...\n";
		}

		foreach ($matches[0] as $k => $m)
		{
			$start = $matches[1][$k] - $step;
			$end = $matches[1][$k] + $step;
			echo 'P=' . $m . ', S=' . $start . ', E=' . $end . "\n";

			for ($i = $start; $i <= $end; $i++) {
				$r = clone $reference;
				$r->$setter(str_replace($m, self::DELIM . $i . self::DELIM, $r->$getter($p)), $p);
				$r->request();
				$diff = $r->getResultLength() - $reference->getResultLength();
				$color = 'white';
				$text = 'U=' . $r->getUrl() . ', C=' . $r->getResultCode() . ', L=' . $r->getResultLength() . ', D=' . $diff;
				if (abs($diff) < $tolerance) {
					// ca match ?!
					if ($r->$getter($p) == $reference->$getter($p)) {
						// c'est la référence
						$color = 'dark_grey';
						$text .= ' -> NORMAL';
					} else {
						$r->idor = true;
						$text .= ' -> SOUNDS GOOD';
					}
				} else {
					// ca ne match pas !!
					if ($r->$getter($p) == $reference->$getter($p)) {
						// c'est la référence
						$color = 'red';
						$text .= ' -> ERROR';
					} else {
						//echo ' -> NORMAL';
					}
				}
				if ($r->idor) {
					if ($r->getResultCode() == $reference->getResultCode()) {
						$color = 'green';
						$text .= ' AND CODE MATCH!';
					} else {
						$color = 'yellow';
						$text .= ' BUT CODE DO NOT MATCH!';
					}
				}

				Utils::_print( $text, $color );
				echo "\n";
				$t_result[] = $r;
				unset( $r );
			}
		}
	}
}

?>
