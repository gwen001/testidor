<?php

/**
 * I don't believe in license
 * You can do want you want with this program
 * - gwen -
 */

class TestIdorRequest
{
	private $sanitizer = array();

	private $request_file = null;

	private $host = '';

	private $ssl = false;

	private $redirect = true;

	private $method = '';

	private $http = '';

	private $url = '';
	private $_url = '';

	private $headers = '';
	private $_headers = '';

	private $cookies = '';
	private $_cookies = '';
	private $cookie_file = '';

	private $post = '';
	private $_post = '';

	private $result = '';
	private $result_length = 0;
	private $result_code = 0;

	private $idor = false;


	public function __construct() {
		$this->cookie_file = tempnam('/tmp', 'cook_');
	}

	public function __clone() {
		$this->result = '';
		$this->result_length = 0;
		$this->result_code = 0;
	}


	public function setSanitizer( $v ) {
		if( !is_array($v) ) {
			$v = array( $v );
		}
		$this->sanitizer = $v;
	}
	private function sanitize($v) {
		return str_replace( $this->sanitizer, '', $v );
	}


	public function getResultLength() {
		return $this->result_length;
	}

	public function getResultCode() {
		return $this->result_code;
	}


	public function getRequestFile() {
		return $this->request_file;
	}
	public function setRequestFile( $v ) {
		if( is_file($v) ) {
			$this->request_file = $v;
			return true;
		} else {
			return false;
		}
	}


	public function getHost() {
		return $this->host;
	}
	public function setHost( $v ) {
		$this->host = $v;
		return true;
	}


	public function getRedirect() {
		return $this->redirect;
	}
	public function setRedirect( $v ) {
		$this->redirect = (bool)$v;
		return true;
	}


	public function getSsl() {
		return $this->ssl;
	}
	public function setSsl( $v ) {
		$this->ssl = (bool)$v;
		return true;
	}


	public function getUrl( $null='' ) {
		return $this->url;
	}
	public function setUrl($v, $null='' ) {
		$this->url = $v;
		$this->_url = self::sanitize($v);
	}
	public function getFullUrl() {
		return $this->url;
	}


	public function getMethod() {
		return $this->method;
	}
	public function setMethod($v) {
		$this->method = strtoupper($v);
	}


	public function getHttp() {
		return $this->http;
	}
	public function setHttp($v) {
		$this->http = $v;
	}


	public function getHeaders() {
		return $this->headers;
	}
	public function setHeaders($array) {
		foreach ($array as $k => $v) {
			$this->setHeader($v, $k);
		}
	}

	public function getHeader($key) {
		return $this->headers[$key];
	}
	public function setHeader($v, $key) {
		$this->headers[$key] = $v;
		$this->_headers[$key] = self::sanitize($v);
	}


	public function getCookies($null = '') {
		return $this->cookies;
	}
	public function setCookies($v, $null = '') {
		$this->cookies = $v;
		$this->_cookies = self::sanitize($v);
	}


	public function getPost($null = '')
	{
		return $this->post;
	}
	public function setPost($v, $null = '')
	{
		$this->post = $v;
		$this->_post = self::sanitize($v);
	}


	public function getIdor() {
		return $this->idor;
	}
	public function setIdor($v) {
		$this->idor = (bool)$v;
	}


	public function request()
	{
		$surplace = array();

		$c = curl_init();
		//curl_setopt($c, CURLOPT_CUSTOMREQUEST, $this->method);
		curl_setopt($c, CURLOPT_URL, ($this->ssl?'https://':'http://').$this->host.$this->_url);
		//curl_setopt($c, CURLOPT_HTTP_VERSION, $this->http);
		curl_setopt($c, CURLOPT_HEADER, true);
		if( $this->redirect ) {
			curl_setopt($c, CURLOPT_FOLLOWLOCATION, true);
		}
		curl_setopt($c, CURLOPT_COOKIE, $this->_cookies);
		curl_setopt($c, CURLOPT_COOKIEJAR, $this->cookie_file);
		curl_setopt($c, CURLOPT_COOKIEFILE, $this->cookie_file);
		if( strlen($this->post) ) {
			// this header seems to fuck the request...
			//$surplace['Content-Length'] = 'Content-Length: '.strlen( $this->_post );
			curl_setopt($c, CURLOPT_POST, true);
			curl_setopt($c, CURLOPT_POSTFIELDS, $this->_post);
		}
		curl_setopt($c, CURLOPT_HTTPHEADER, array_merge($this->_headers,$surplace));
		curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
		$this->result = curl_exec($c);
		$this->result_length = strlen($this->result);
		$this->result_code = curl_getinfo($c, CURLINFO_HTTP_CODE);
	}


	public function loadFile( $file )
	{
		if( !$this->setRequestFile($file) ) {
			return false;
		}

		$request = trim( file_get_contents($file) ); // the full request
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

		$this->setHost( $host );
		$this->setUrl( $url );
		$this->setMethod( $method );
		$this->setHttp( $http );
		$this->setHeaders( $h_replay );
		$this->setCookies( $cookies );
		$this->setPost( $post );


		return true;
	}

	public function export()
	{
		echo $this->method.' '.preg_replace('#http[s?]://#','',$this->_url).' '.$this->http."\n";
		echo 'Host: '.$this->host."\n";
		foreach( $this->_headers as $h ) {
			echo $h."\n";
		}
		echo $this->_cookies."\n\n";
		echo $this->_post."\n";
	}
}

?>
