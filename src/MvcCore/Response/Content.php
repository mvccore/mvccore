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
trait Content {

	/**
	 * @inheritDocs
	 * @param  string $body
	 * @return \MvcCore\Response
	 */
	public function SetBody ($body) {
		$this->body = & $body;
		return $this;
	}

	/**
	 * @inheritDocs
	 * @param  string $body
	 * @return \MvcCore\Response
	 */
	public function PrependBody ($body) {
		$this->body = $body . $this->body;
		return $this;
	}

	/**
	 * @inheritDocs
	 * @param  string $body
	 * @return \MvcCore\Response
	 */
	public function AppendBody ($body) {
		$this->body .= $body;
		return $this;
	}

	/**
	 * @inheritDocs
	 * @return string|NULL
	 */
	public function & GetBody () {
		return $this->body;
	}

	/**
	 * @inheritDocs
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
	 * @inheritDocs
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
	 * @inheritDocs
	 * @return bool
	 */
	public function IsSentBody () {
		return $this->bodySent;
	}

	/**
	 * @inheritDocs
	 * @return \MvcCore\Response
	 */
	public function Send () {
		return $this
			->SendHeaders()
			->SendBody();
	}

	/**
	 * @inheritDocs
	 * @return \MvcCore\Response
	 */
	public function SendHeaders () {
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
		$outputCompression = $this->getOutputCompression();
		if ($outputCompression)
			$this->headers['Content-Encoding'] = 'gzip';
		if (!$this->request->IsCli()) {
			$app = \MvcCore\Application::GetInstance();
			$preSentHeadersHandlers = $app->__get('preSentHeadersHandlers');
			$app->ProcessCustomHandlers($preSentHeadersHandlers);
		}
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
				if (!$charsetMatched) 
					$value .= ';charset=' . $this->encoding;
			} else if ($name == 'Content-Type' && $outputCompression) {
				continue;
			}
			if (isset($this->disabledHeaders[$name])) {
				header_remove($name);
			} else {
				if (
					isset(static::$multiplyHeaders[$name]) &&
					is_array($value)
				) {
					header_remove($name);
					foreach ($value as $item)
						header($name . ": " . $item, FALSE);
				} else {
					header($name . ": " . $value, TRUE);
				}
			}
		}
		foreach ($this->disabledHeaders as $name => $b)
			header_remove($name);
		$this->addTimeAndMemoryHeader();
		return $this;
	}

	/**
	 * @inheritDocs
	 * @return \MvcCore\Response
	 */
	public function SendBody () {
		if ($this->bodySent) return $this;
		$app = \MvcCore\Application::GetInstance();
		$preSentBodyHandlers = $app->__get('preSentBodyHandlers');
		$app->ProcessCustomHandlers($preSentBodyHandlers);
		echo $this->body;
		if (!$this->getOutputCompression())
			while (ob_get_level() && @ob_end_flush());
		flush();
		$this->bodySent = TRUE;
		return $this;
	}

	/**
	 * Add CPU and RAM usage header at HTML/JSON response end.
	 * @return void
	 */
	protected function addTimeAndMemoryHeader () {
		$headerName = static::HEADER_X_MVCCORE_CPU_RAM;
		if (isset($this->disabledHeaders[$headerName])) return;
		$mtBegin = $this->request->GetStartTime();
		$time = number_format((microtime(TRUE) - $mtBegin) * 1000, 1, '.', ' ');
		$ram = function_exists('memory_get_peak_usage') ? number_format(memory_get_peak_usage() / 1000000, 2, '.', ' ') : 'n/a';
		header("{$headerName}: {$time} ms, {$ram} MB");
	}

	/**
	 * Return `TRUE` if `zlib.output_compression` is enabled.
	 * @return bool
	 */
	protected function getOutputCompression () {
		if ($this->outputCompression === NULL) {
			$zlibOutputCompression = @ini_get('zlib.output_compression');
			if ($zlibOutputCompression === FALSE) {
				$this->outputCompression = FALSE;
			} else {
				$zlibOutputCompression = mb_strtolower($zlibOutputCompression);
				$this->outputCompression = (
					$zlibOutputCompression === '1' ||
					$zlibOutputCompression === 'on'
				);
			}
		}
		return $this->outputCompression;
	}
}
