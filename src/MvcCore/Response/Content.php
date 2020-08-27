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

trait Content
{
	/**
	 * Set HTTP response body.
	 * @param string $body
	 * @return \MvcCore\Response|\MvcCore\IResponse
	 */
	public function SetBody ($body) {
		/** @var $this \MvcCore\Response */
		$this->body = & $body;
		return $this;
	}

	/**
	 * Prepend HTTP response body.
	 * @param string $body
	 * @return \MvcCore\Response|\MvcCore\IResponse
	 */
	public function PrependBody ($body) {
		/** @var $this \MvcCore\Response */
		$this->body = $body . $this->body;
		return $this;
	}

	/**
	 * Append HTTP response body.
	 * @param string $body
	 * @return \MvcCore\Response|\MvcCore\IResponse
	 */
	public function AppendBody ($body) {
		/** @var $this \MvcCore\Response */
		$this->body .= $body;
		return $this;
	}

	/**
	 * Get HTTP response body.
	 * @return string|NULL
	 */
	public function & GetBody () {
		/** @var $this \MvcCore\Response */
		return $this->body;
	}

	/**
	 * Returns if response has any `text/html` or `application/xhtml+xml`
	 * substring in `Content-Type` header.
	 * @return bool
	 */
	public function IsHtmlOutput () {
		/** @var $this \MvcCore\Response */
		if (isset($this->headers['Content-Type'])) {
			$value = $this->headers['Content-Type'];
			return strpos($value, 'text/html') !== FALSE || strpos($value, 'application/xhtml+xml') !== FALSE;
		}
		return FALSE;
	}

	/**
	 * Returns if response has any `xml` substring in `Content-Type` header.
	 * @return bool
	 */
	public function IsXmlOutput () {
		/** @var $this \MvcCore\Response */
		if (isset($this->headers['Content-Type'])) {
			$value = $this->headers['Content-Type'];
			return strpos($value, 'xml') !== FALSE;
		}
		return FALSE;
	}

	/**
	 * `TRUE` if body has been sent.
	 * @return bool
	 */
	public function IsSentBody () {
		/** @var $this \MvcCore\Response */
		return $this->bodySent;
	}

	/**
	 * Send all HTTP headers and send response body.
	 * @return \MvcCore\Response|\MvcCore\IResponse
	 */
	public function Send () {
		/** @var $this \MvcCore\Response */
		return $this
			->SendHeaders()
			->SendBody();
	}

	/**
	 * Send all HTTP headers.
	 * @return \MvcCore\Response|\MvcCore\IResponse
	 */
	public function SendHeaders () {
		/** @var $this \MvcCore\Response */
		if (headers_sent()) return $this;
		$httpVersion = $this->GetHttpVersion();
		$code = $this->GetCode();
		$status = $this->codeMessage !== NULL
			? ' '.$this->codeMessage
			: (isset(static::$codeMessages[$code])
				? ' '.static::$codeMessages[$code]
				: '');
		$this->UpdateHeaders();
		if (!isset($this->headers['Content-Encoding']))
			$this->headers['Content-Encoding'] = $this->GetEncoding();
		//http_response_code($code);
		header($httpVersion . ' ' . $code . $status);
		header('Host: ' . $this->request->GetHost());
		foreach ($this->headers as $name => $value) {
			if ($name == 'Content-Type') {
				$charsetMatched = FALSE;
				$charsetPos = strpos($value, 'charset');
				if ($charsetPos !== FALSE) {
					$equalPos = strpos($value, '=', $charsetPos);
					if ($equalPos !== FALSE) $charsetMatched = TRUE;
				}
				if (!$charsetMatched) $value .= ';charset=' . $this->encoding;
			}
			if (isset($this->disabledHeaders[$name])) {
				header_remove($name);
			} else {
				header($name . ": " . $value);
			}
		}
		foreach ($this->disabledHeaders as $name => $b)
			header_remove($name);
		$this->addTimeAndMemoryHeader();
		return $this;
	}

	/**
	 * Send response body.
	 * @return \MvcCore\Response|\MvcCore\IResponse
	 */
	public function SendBody () {
		/** @var $this \MvcCore\Response */
		if ($this->bodySent) return $this;
		echo $this->body;
		if (ob_get_level())
			ob_end_flush();
		flush();
		$this->bodySent = TRUE;
		return $this;
	}

	/**
	 * Add CPU and RAM usage header at HTML/JSON response end.
	 * @return void
	 */
	protected function addTimeAndMemoryHeader () {
		/** @var $this \MvcCore\Response */
		$headerName = static::HEADER_X_MVCCORE_CPU_RAM;
		if (isset($this->disabledHeaders[$headerName])) return;
		$mtBegin = $this->request->GetStartTime();
		$time = number_format((microtime(TRUE) - $mtBegin) * 1000, 1, '.', ' ');
		$ram = function_exists('memory_get_peak_usage') ? number_format(memory_get_peak_usage() / 1000000, 2, '.', ' ') : 'n/a';
		header("$headerName: $time ms, $ram MB");
	}
}
