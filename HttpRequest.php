<?php

/**
 * I don't believe in license
 * You can do want you want with this program
 * - gwen -
 */

class HttpRequest
{
	const METHOD_GET = 'GET';
	const METHOD_POST = 'POST';
	const METHOD_PUT = 'PUT';
	const METHOD_DELETE = 'DELETE';
	const METHOD_HEAD = 'HEAD';
	const METHOD_OPTIONS = 'OPTIONS';

	protected $request_file = null;

	protected $host = '';

	protected $ssl = false;

	protected $redirect = true;

	protected $method = '';

	protected $http = '';

	protected $url = '';

	protected $headers = '';

	protected $cookies = '';
	protected $cookie_file = '';

	protected $params = '';

	protected $multipart = false;

	protected $content_length = false;

	protected $result = '';
	protected $result_length = 0;
	protected $result_code = 0;


	public function __construct() {
		$this->cookie_file = tempnam('/tmp', 'cook_');
	}

	public function __clone() {
		$this->result = '';
		$this->result_length = 0;
		$this->result_code = 0;
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


	public function isMultipart() {
		return $this->multipart;
	}
	public function setMultipart( $v ) {
		$this->multipart = (bool)$v;
		return true;
	}


	public function getContentLength() {
		return $this->content_length;
	}
	public function setContentLength( $v ) {
		$this->content_length = (bool)$v;
		return true;
	}


	public function getUrl( $base64=false ) {
		$v = $this->url;
		if( $base64 ) {
			$v = base64_encode( serialize($v) );
		}
		return $v;
	}
	public function setUrl($v) {
		$this->url = $v;
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


	public function getHeaders( $base64=false ) {
		$v = $this->headers;
		if( $base64 ) {
			$v = base64_encode( serialize($v) );
		}
		return $v;
	}
	public function setHeaders($array) {
		foreach ($array as $k => $v) {
			$this->setHeader($v, $k);
		}
	}

	public function getHeader( $key, $base64=false ) {
		$v = $this->headers[$key];
		if( $base64 ) {
			$v = base64_encode( $v );
		}
		return $v;
	}
	public function setHeader($v, $key) {
		$this->headers[$key] = $v;
	}


	public function getCookies( $base64=false ) {
		$v = $this->cookies;
		if( $base64 ) {
			$v = base64_encode( $v );
		}
		return $v;
	}
	public function setCookies($v) {
		$this->cookies = $v;
	}


	public function getParams( $base64=false )
	{
		$v = $this->params;
		if( $base64 ) {
			$v = base64_encode( $v );
		}
		return $v;
	}
	public function setParams($v)
	{
		$this->params = $v;
	}


	public function isPost() {
		return ($this->method==self::METHOD_POST);
	}


	public function request()
	{
		$surplace = array();

		$c = curl_init();
		curl_setopt($c, CURLOPT_CUSTOMREQUEST, $this->method);
		curl_setopt($c, CURLOPT_URL, ($this->ssl?'https://':'http://').$this->host.$this->url);
		curl_setopt($c, CURLOPT_HTTP_VERSION, $this->http);
		curl_setopt($c, CURLOPT_HEADER, true);
		if( $this->redirect ) {
			curl_setopt($c, CURLOPT_FOLLOWLOCATION, true);
		}
		curl_setopt($c, CURLOPT_COOKIE, $this->cookies);
		curl_setopt($c, CURLOPT_COOKIEJAR, $this->cookie_file);
		curl_setopt($c, CURLOPT_COOKIEFILE, $this->cookie_file);
		if( strlen($this->params) ) {
			if( $this->content_length ) {
				// this header seems to fuck the request...
				//$surplace['Content-Length'] = 'Content-Length: '.strlen( $this->params );
				// but this works great!
				$surplace['Content-Length'] = 'Content-Length: 0';
			}
			if( $this->isPost() ) {
				curl_setopt($c, CURLOPT_POST, true);
				curl_setopt($c, CURLOPT_POSTFIELDS, $this->params);
			}
		}
		curl_setopt($c, CURLOPT_HTTPHEADER, array_merge($this->headers,$surplace));
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
		$t_headers = explode( "\n", array_shift($t_request) ); // headers
		$h_request = array_map( function($str){return explode(':',trim($str));}, $t_headers ); // splited headers
		array_shift( $h_request );

		$first = array_shift( $t_headers ); // first ligne is: method, url, http version
		list($method,$url,$http) = explode( ' ', $first );

		$params = ''; // post parameters
		if( count($t_request) ) {
			$params = implode( "\n\n", $t_request );
		}

		$host = '';
		$cookies = '';
		$h_replay = array(); // headers kept in the replay request

		foreach( $h_request as $header )
		{
			$h = trim( array_shift($header) );
			$v = trim( implode(':',$header) );

			switch( $h )
			{
				case 'Accept-Encoding':
				case 'Content-Length':
					break;

				case 'Cookie':
					$cookies = $h.': '.$v;
					break;

				case 'Host':
					$host = $v;
					break;

				/*case 'Accept':
				case 'Accept-Language':
				case 'Connection':
				case 'Referer':
				case 'User-Agent':
				case 'x-ajax-replace':
				case 'X-Requested-With':*/
				case 'Content-Type':
					if( stristr($v,'multipart') !== false ) {
						$this->setMultipart( true );
					}
				default:
					$h_replay[ $h ] = $h.': '.$v;
					break;
			}
		}

		$this->setHost( $host );
		$this->setUrl( $url );
		$this->setMethod( $method );
		$this->setHttp( $http );
		$this->setHeaders( $h_replay );
		$this->setCookies( $cookies );
		$this->setParams( $params );

		return true;
	}


	public function export( $echo=true )
	{
		$output = '';
		$output .= $this->method.' '.preg_replace('#http[s?]://#','',$this->url).' '.$this->http."\n";
		$output .= 'Host: '.$this->host."\n";
		foreach( $this->headers as $h ) {
			$output .= $h."\n";
		}
		$output .= $this->cookies."\n\n";
		$output .= $this->params."\n";

		if( $echo ) {
			echo $output;
		} else {
			return $output;
		}
	}
}

?>
