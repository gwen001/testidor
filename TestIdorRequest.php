<?php

/**
 * I don't believe in license
 * You can do want you want with this program
 * - gwen -
 */

class TestIdorRequest extends HttpRequest
{
	private $sanitizer = array();

	private $_url = '';

	private $_headers = '';

	private $_cookies = '';

	private $_params = '';

	private $idor = false;


	public function setSanitizer( $v ) {
		if( !is_array($v) ) {
			$v = array( $v );
		}
		$this->sanitizer = $v;
	}


	private function sanitize($v) {
		return str_replace( $this->sanitizer, '', $v );
	}


	public function setUrl($v, $null='' ) {
		parent::setUrl( $v );
		$this->_url = self::sanitize( $v );
	}


	public function setHeader($v, $key) {
		parent::setHeader( $v, $key );
		$this->_headers[$key] = self::sanitize($v);
	}


	public function setCookies($v, $null = '') {
		parent::setCookies( $v );
		$this->_cookies = self::sanitize( $v );
	}


	public function setPost($v, $null = '')
	{
		parent::setPost( $v );
		$this->_params = self::sanitize( $v );
	}


	public function getIdor() {
		return $this->idor;
	}
	public function setIdor($v) {
		$this->idor = (bool)$v;
	}


	private function swapData()
	{
		$this->__url = $this->url;
		$this->__headers = $this->headers;
		$this->__cookies = $this->cookies;
		$this->__params = $this->params;
	}


	private function swapDataBack()
	{
		$this->url = $this->__url;
		$this->headers = $this->__headers;
		$this->cookies = $this->__cookies;
		$this->params = $this->__params;
	}


	public function request()
	{
		$this->swapData();
		parent::request();
		$this->swapDataBack();
	}


	public function export( $echo=true )
	{
		$this->swapData();
		parent::export( $echo );
		$this->swapDataBack();
	}
}

?>
