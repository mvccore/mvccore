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
	 * Response HTTP code.
	 * Example: `200 | 301 | 404`
	 * @var int|NULL
	 */
	protected $code = NULL;

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
	 * Set HTTP response code.
	 * @param int $code
	 * @return \MvcCore\Response|\MvcCore\IResponse
	 */
	public function & SetCode ($code) {
		/** @var $this \MvcCore\Response */
		$this->code = $code;
		http_response_code($code);
		return $this;
	}

	/**
	 * Get HTTP response code.
	 * @return int
	 */
	public function GetCode () {
		if ($this->code === NULL) {
			$phpCode = http_response_code();
			$this->code = $phpCode === FALSE ? static::OK : $phpCode;
		}
		return $this->code;
	}

	/**
	 * Set HTTP response content encoding.
	 * Example: `$response->SetEncoding('utf-8');`
	 * @param string $encoding
	 * @return \MvcCore\Response|\MvcCore\IResponse
	 */
	public function & SetEncoding ($encoding = 'utf-8') {
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
		return $this->encoding;
	}

	/**
	 * Return if response has any redirect `"Location: ..."` header inside.
	 * @return bool
	 */
	public function IsRedirect () {
		return isset($this->headers['Location']);
	}
}
