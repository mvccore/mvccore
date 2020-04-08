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

trait Headers
{
	/**
	 * `TRUE` if headers has been sent.
	 * @return bool
	 */
	public function IsSentHeaders () {
		/** @var $this \MvcCore\Response */
		return headers_sent();
	}

	/**
	 * Set multiple HTTP response headers as `key => value` array.
	 * All given headers are automatically merged with previously setted headers.
	 * If you change second argument to true, all previous request object and PHP
	 * headers are removed and given headers will be only headers for output.
	 * There is automatically set response encoding from value for
	 * `Content-Type` header, if contains any `charset=...`.
	 * There is automatically set response encoding from value for
	 * `Content-Encoding` header.
	 * Example: `$response->SetHeader(array('Content-Type' => 'text/plain; charset=utf-8'));`
	 * @param array $headers
	 * @param bool $cleanAllPrevious `FALSE` by default. If `TRUE`, all previous headers
	 *								 set by PHP `header()` or by this object will be removed.
	 * @return \MvcCore\Response|\MvcCore\IResponse
	 */
	public function SetHeaders (array $headers = [], $cleanAllPrevious = FALSE) {
		/** @var $this \MvcCore\Response */
		if ($cleanAllPrevious) {
			header_remove();
			$this->headers = [];
		}
		foreach ($headers as $name => $value) {
			$this->SetHeader($name, $value);
		}
		return $this;
	}

	/**
	 * Set HTTP response header.
	 * There is automatically set response encoding from value for
	 * `Content-Type` header, if contains any `charset=...`.
	 * There is automatically set response encoding from value for
	 * `Content-Encoding` header.
	 * Example: `$response->SetHeader('Content-Type', 'text/plain; charset=utf-8');`
	 * @param string $name
	 * @param string $value
	 * @return \MvcCore\Response|\MvcCore\IResponse
	 */
	public function SetHeader ($name, $value) {
		/** @var $this \MvcCore\Response */
		if (isset($this->disabledHeaders[$name]))
			return $this;
		header($name . ": " . $value);
		$this->headers[$name] = $value;
		if ($name === 'Content-Type') {
			$charsetPos = strpos($value, 'charset');
			if ($charsetPos !== FALSE) {
				$equalPos = strpos($value, '=', $charsetPos);
				if ($equalPos !== FALSE) $this->SetEncoding(
					trim(substr($value, $equalPos + 1))
				);
			}
		}
		if ($name === 'Content-Encoding') $this->SetEncoding($value);
		return $this;
	}

	/**
	 * Get HTTP response header by name. If header doesn't exists, null is returned.
	 * Example: `$response->GetHeader('Content-Type'); // returns 'text/plain; charset=utf-8'`
	 * @param string $name
	 * @return string|NULL
	 */
	public function GetHeader ($name) {
		$this->UpdateHeaders();
		return isset($this->headers[$name])
			? $this->headers[$name]
			: NULL;
	}

	/**
	 * Get if response has any HTTP response header by given `$name`.
	 * Example:
	 *	`$response->GetHeader('Content-Type'); // returns TRUE if there is header 'Content-Type'
	 *	`$response->GetHeader('content-type'); // returns FALSE if there is header 'Content-Type'
	 * @param string $name
	 * @return bool
	 */
	public function HasHeader ($name) {
		$this->UpdateHeaders();
		return isset($this->headers[$name]);
	}

	/**
	 * Consolidate all headers from PHP response
	 * by calling `headers_list()` into local headers list.
	 * @return \MvcCore\Response|\MvcCore\IResponse
	 */
	public function UpdateHeaders () {
		$rawHeaders = headers_list();
		$name = '';
		$value = '';
		foreach ($rawHeaders as $rawHeader) {
			$doubleDotPos = strpos($rawHeader, ':');
			if ($doubleDotPos !== FALSE) {
				$name = trim(substr($rawHeader, 0, $doubleDotPos));
				$value = trim(substr($rawHeader, $doubleDotPos + 1));
			} else {
				$name = $rawHeader;
				$value = '';
			}
			if (!isset($this->disabledHeaders[$name]))
  				$this->headers[$name] = $value;
		}
		return $this;
	}

	/**
	 * Set disabled headers, never sent except if there is
	 * rendered exception in development environment.
	 * @param \string[] $disabledHeaders,...
	 * @return \MvcCore\Response|\MvcCore\IResponse
	 */
	public function SetDisabledHeaders ($disabledHeaders) {
		/** @var $this \MvcCore\Response */
		$this->disabledHeaders = [];
		$args = func_get_args();
		if (count($args) === 1 && is_array($args[0])) $args = $args[0];
		foreach ($args as $arg)
			$this->disabledHeaders[$arg] = TRUE;
		return $this;
	}

	/**
	 * Get disabled headers, never sent except if there is
	 * rendered exception in development environment.
	 * @return \string[]
	 */
	public function GetDisabledHeaders () {
		return array_keys($this->disabledHeaders);
	}
}
