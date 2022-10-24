<?php

/**
 * MvcCore
 *
 * This source file is subject to the BSD 3 License
 * For the full copyright and license information, please view
 * the LICENSE.md file that are distributed with this source code.
 *
 * @copyright	Copyright (c) 2016 Tom Flidr (https://github.com/mvccore)
 * @license		https://mvccore.github.io/docs/mvccore/5.0.0/LICENSE.md
 */

namespace MvcCore\Response;

/**
 * @mixin \MvcCore\Response
 */
trait PropsGettersSetters {

	/**
	 * Response codes and text messages.
	 * @var array<int, string>
	 */
	protected static $codeMessages = [
		\MvcCore\IResponse::OK						=> 'OK',
		\MvcCore\IResponse::MOVED_PERMANENTLY		=> 'Moved Permanently',
		\MvcCore\IResponse::SEE_OTHER				=> 'See Other',
		\MvcCore\IResponse::NOT_FOUND				=> 'Not Found',
		\MvcCore\IResponse::INTERNAL_SERVER_ERROR	=> 'Internal Server Error',
	];

	protected static $multiplyHeaders = [
		'Set-Cookie'				=> TRUE,
		'Content-Security-Policy'	=> TRUE,
	];
	
	/**
	 * CSRF protection cookie name. `__MCP` by default.
	 * @var string
	 */
	protected static $csrfProtectionCookieName = \MvcCore\IResponse::COOKIE_CSRF_DEFAULT_NAME;

	/**
	 * Response HTTP protocol version by `$_SERVER['SERVER_PROTOCOL']`.
	 * Example: `HTTP/1.0 | HTTP/1.1 | HTTP/2 | SPDY`
	 * @var string|NULL
	 */
	protected $httpVersion = NULL;

	/**
	 * Response HTTP code.
	 * Example: `200 | 301 | 404`
	 * @var int|NULL
	 */
	protected $code = NULL;

	/**
	 * Optional response HTTP code message.
	 * @var string|NULL
	 */
	protected $codeMessage = NULL;

	/**
	 * Response HTTP headers as `key => value` array.
	 * Example:
	 * ````
	 *   [
	 *       'Content-Type'     => 'text/html',
	 *       'Content-Encoding' => 'utf-8'
	 *   ];
	 * ````
	 * @var \string[]
	 */
	protected $headers = [];

	/**
	 * Response content encoding.
	 * Example: `"utf-8" | "windows-1250" | "ISO-8859-2"`
	 * @var \string|NULL
	 */
	protected $encoding = NULL;

	/**
	 * Response HTTP body.
	 * Example: `"<!DOCTYPE html><html lang="en"><head><meta ..."`
	 * @var \string|NULL
	 */
	protected $body = NULL;

	/**
	 * `TRUE` if headers or body has been sent.
	 * @var bool
	 */
	protected $bodySent = FALSE;

	/**
	 * Disabled headers, never sent except if there is
	 * rendered exception in development environment.
	 * @var array
	 */
	protected $disabledHeaders = [];

	/**
	 * Reference to current application request object.
	 * @var \MvcCore\Request
	 */
	protected $request = NULL;

	
	/**
	 * @inheritDocs
	 * @return string
	 */
	public static function GetCsrfProtectionCookieName () {
		return static::$csrfProtectionCookieName;
	}
	
	/**
	 * @inheritDocs
	 * @param  string $csrfProtectionCookieName 
	 * @return string
	 */
	public static function SetCsrfProtectionCookieName ($csrfProtectionCookieName) {
		return static::$csrfProtectionCookieName = $csrfProtectionCookieName;
	}
	
	/**
	 * @inheritDocs
	 * @return \string[]
	 */
	public static function GetMultiplyHeaders () {
		return array_keys(static::$multiplyHeaders);
	}
	
	/**
	 * @inheritDocs
	 * @param  \string[] $multiplyHeaders 
	 * @return \string[]
	 */
	public static function SetMultiplyHeaders ($multiplyHeaders) {
		static::$multiplyHeaders = [];
		foreach ($multiplyHeaders as $multiplyHeader)
			static::$multiplyHeaders[$multiplyHeader] = TRUE;
		return $multiplyHeaders;
		
	}

	/**
	 * @inheritDocs
	 * @return string
	 */
	public function GetHttpVersion () {
		if ($this->httpVersion === NULL) {
			$server = & $this->request->GetGlobalCollection('server');
			$this->httpVersion = isset($server['SERVER_PROTOCOL'])
				? $server['SERVER_PROTOCOL']
				: 'HTTP/1.1';
		}
		return $this->httpVersion;
	}

	/**
	 * @inheritDocs
	 * @param  string $httpVersion
	 * @return \MvcCore\Response
	 */
	public function SetHttpVersion ($httpVersion) {
		$this->httpVersion = $httpVersion;
		return $this;
	}

	/**
	 * @inheritDocs
	 * @param  int         $code
	 * @param  string|NULL $codeMessage
	 * @return \MvcCore\Response
	 */
	public function SetCode ($code, $codeMessage = NULL) {
		$this->code = $code;
		if ($codeMessage !== NULL) $this->codeMessage = $codeMessage;
		http_response_code($code);
		return $this;
	}

	/**
	 * @inheritDocs
	 * @return int
	 */
	public function GetCode () {
		if ($this->code === NULL) {
			$phpCode = http_response_code();
			$this->code = $phpCode === FALSE ? \MvcCore\IResponse::OK : $phpCode;
		}
		return $this->code;
	}

	/**
	 * @inheritDocs
	 * @param  string $encoding
	 * @return \MvcCore\Response
	 */
	public function SetEncoding ($encoding = 'utf-8') {
		$this->encoding = $encoding;
		unset(
			$this->headers['content-encoding'],
			$this->headers['Content-encoding'],
			$this->headers['content-Encoding']
		);
		$this->headers['Content-Encoding'] = $encoding;
		header('Content-Encoding: ' . $encoding);
		if (isset($this->headers['Content-Type'])) 
			header('Content-Type: ' . $this->headers['Content-Type'] . '; charset=' . $encoding);
		return $this;
	}

	/**
	 * @inheritDocs
	 * @return string|NULL
	 */
	public function GetEncoding () {
		if ($this->encoding === NULL) {
			if (isset($this->headers['Content-Encoding'])) {
				$this->encoding = $this->headers['Content-Encoding'];
			} else if (isset($this->headers['Content-Type'])) {
				$value = $this->headers['Content-Type'];
				$charsetPos = strpos($value, 'charset');
				if ($charsetPos !== FALSE) {
					$equalPos = strpos($value, '=', $charsetPos);
					if ($equalPos !== FALSE)
						$this->encoding = trim(substr($value, $equalPos + 1));
				}
			}
			if (!$this->encoding)
				$this->encoding = 'utf-8';
		}
		return $this->encoding;
	}

	/**
	 * @inheritDocs
	 * @return bool
	 */
	public function IsRedirect () {
		return isset($this->headers['Location']);
	}

	/**
	 * @inheritDocs
	 * @return bool
	 */
	public function IsSent () {
		return $this->bodySent && headers_sent();
	}
}
