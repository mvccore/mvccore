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
	 * @return \MvcCore\Response
	 */
	public function & SetBody ($body) {
		$this->body = & $body;
		return $this;
	}

	/**
	 * Prepend HTTP response body.
	 * @param string $body
	 * @return \MvcCore\Response
	 */
	public function & PrependBody ($body) {
		$this->body = $body . $this->body;
		return $this;
	}

	/**
	 * Append HTTP response body.
	 * @param string $body
	 * @return \MvcCore\Response
	 */
	public function & AppendBody ($body) {
		$this->body .= $body;
		return $this;
	}

	/**
	 * Get HTTP response body.
	 * @return string|NULL
	 */
	public function & GetBody () {
		return $this->body;
	}

	/**
	 * Returns if response has any `text/html` or `application/xhtml+xml`
	 * substring in `Content-Type` header.
	 * @return bool
	 */
	public function IsHtmlOutput () {
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
		if (isset($this->headers['Content-Type'])) {
			$value = $this->headers['Content-Type'];
			return strpos($value, 'xml') !== FALSE;
		}
		return FALSE;
	}

	/**
	 * `TRUE` if headers or body has been sent.
	 * @return bool
	 */
	public function IsSent () {
		return $this->sent || headers_sent();
	}

	/**
	 * Send all HTTP headers and send response body.
	 * @return void
	 */
	public function Send () {
		if ($this->IsSent()) return;
		$code = $this->GetCode();
		$status = isset(static::$codeMessages[$code]) ? ' ' . static::$codeMessages[$code] : '';
		if (!isset($this->headers['Content-Encoding'])) {
			if (!$this->encoding) $this->encoding = 'utf-8';
			$this->headers['Content-Encoding'] = $this->encoding;
		}
		$this->UpdateHeaders();
		header("HTTP/1.0 $code $status");
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
		echo $this->body;
		if (ob_get_level()) echo ob_get_clean();
		flush();
		$this->sent = TRUE;
	}

	/**
	 * Add CPU and RAM usage header at HTML/JSON response end.
	 * @return void
	 */
	protected function addTimeAndMemoryHeader () {
		$headerName = static::HEADER_X_MVCCORE_CPU_RAM;
		if (isset($this->disabledHeaders[$headerName])) return;
		$mtBegin = \MvcCore\Application::GetInstance()->GetRequest()->GetMicrotime();
		$time = number_format((microtime(TRUE) - $mtBegin) * 1000, 1, '.', ' ');
		$ram = function_exists('memory_get_peak_usage') ? number_format(memory_get_peak_usage() / 1000000, 2, '.', ' ') : 'n/a';
		header("$headerName: $time ms, $ram MB");
	}
}
