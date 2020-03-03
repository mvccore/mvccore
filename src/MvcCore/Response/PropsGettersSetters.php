<?php

/**
 * MvcCore
 *
 * This source file is subject to the BSD 3 License
 * For the full copyright and license information, please view
 * the LICENSE.md file that are distributed with this source code.
 *
 * @copyright	Copyright (c) 2016 Tom Flídr (https://github.com/mvccore/mvccore)
 * @license  https://mvccore.github.io/docs/mvccore/5.0.0/LICENCE.md
 */

namespace MvcCore\Response;

trait PropsGettersSetters
{
	protected static $codeMessages = [
		\MvcCore\IResponse::OK						=> 'OK',
		\MvcCore\IResponse::MOVED_PERMANENTLY		=> 'Moved Permanently',
		\MvcCore\IResponse::SEE_OTHER				=> 'See Other',
		\MvcCore\IResponse::NOT_FOUND				=> 'Not Found',
		\MvcCore\IResponse::INTERNAL_SERVER_ERROR	=> 'Internal Server Error',
	];

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
	 *	`array(
	 *		'Content-Type'		=> 'text/html',
	 *		'Content-Encoding'	=> 'utf-8'
	 *	);`
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
	protected $sent = FALSE;

	/**
	 * Disabled headers, never sent except if there is 
	 * rendered exception in development environment.
	 * @var array
	 */
	protected $disabledHeaders = [];

	/**
	 * Reference to current application request object.
	 * @var \MvcCore\IRequest
	 */
	protected $request = NULL;

	
	/**
	 * Get response protocol HTTP version by `$_SERVER['SERVER_PROTOCOL']`, 
	 * `HTTP/1.1` by default.
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
	 * Set response protocol HTTP version - `HTTP/1.1 | HTTP/2.0`...
	 * @param string $httpVersion
	 * @return \MvcCore\Response|\MvcCore\IResponse
	 */
	public function SetHttpVersion ($httpVersion) {
		/** @var $this \MvcCore\Response */
		$this->httpVersion = $httpVersion;
		return $this;
	}

	/**
	 * Set HTTP response code.
	 * @param int $code
	 * @param string|NULL $codeMessage
	 * @return \MvcCore\Response|\MvcCore\IResponse
	 */
	public function SetCode ($code, $codeMessage = NULL) {
		/** @var $this \MvcCore\Response */
		$this->code = $code;
		if ($codeMessage !== NULL) $this->codeMessage = $codeMessage;
		http_response_code($code);
		return $this;
	}

	/**
	 * Get HTTP response code.
	 * @return int
	 */
	public function GetCode () {
		/** @var $this \MvcCore\Response */
		if ($this->code === NULL) {
			$phpCode = http_response_code();
			$this->code = $phpCode === FALSE ? \MvcCore\IResponse::OK : $phpCode;
		}
		return $this->code;
	}

	/**
	 * Set HTTP response content encoding.
	 * Example: `$response->SetEncoding('utf-8');`
	 * @param string $encoding
	 * @return \MvcCore\Response|\MvcCore\IResponse
	 */
	public function SetEncoding ($encoding = 'utf-8') {
		/** @var $this \MvcCore\Response */
		$this->encoding = $encoding;
		$this->headers['Content-Encoding'] = $encoding;
		return $this;
	}

	/**
	 * Get HTTP response content encoding.
	 * Example: `$response->GetEncoding(); // returns 'utf-8'`
	 * @return string|NULL
	 */
	public function GetEncoding () {
		/** @var $this \MvcCore\Response */
		return $this->encoding;
	}

	/**
	 * Return if response has any redirect `"Location: ..."` header inside.
	 * @return bool
	 */
	public function IsRedirect () {
		/** @var $this \MvcCore\Response */
		return isset($this->headers['Location']);
	}
}
