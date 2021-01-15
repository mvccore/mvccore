<?php

/**
 * MvcCore
 *
 * This source file is subject to the BSD 3 License
 * For the full copyright and license information, please view
 * the LICENSE.md file that are distributed with this source code.
 *
 * @copyright	Copyright (c) 2016 Tom Flidr (https://github.com/mvccore)
 * @license  https://mvccore.github.io/docs/mvccore/5.0.0/LICENCE.md
 */

namespace MvcCore\Response;

trait Headers {

	/**
	 * @inheritDocs
	 * @return bool
	 */
	public function IsSentHeaders () {
		/** @var $this \MvcCore\Response */
		return headers_sent();
	}

	/**
	 * @inheritDocs
	 * @param array $headers
	 * @param bool $cleanAllPrevious `FALSE` by default. If `TRUE`, all previous headers
	 *								 set by PHP `header()` or by this object will be removed.
	 * @return \MvcCore\Response
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
	 * @inheritDocs
	 * @param string $name
	 * @param string $value
	 * @return \MvcCore\Response
	 */
	public function SetHeader ($name, $value) {
		/** @var $this \MvcCore\Response */
		if (isset($this->disabledHeaders[$name]))
			return $this;
		header($name . ": " . $value);
		$this->headers[$name] = $value;
		$nameLower = mb_strtolower($name);
		if ($nameLower === 'content-type') {
			$charsetPos = strpos($value, 'charset');
			if ($charsetPos !== FALSE) {
				$equalPos = strpos($value, '=', $charsetPos);
				if ($equalPos !== FALSE) $this->SetEncoding(
					trim(substr($value, $equalPos + 1))
				);
			}
		}
		if ($nameLower === 'content-encoding') 
			$this->SetEncoding($value);
		return $this;
	}

	/**
	 * @inheritDocs
	 * @param string $name
	 * @return string|NULL
	 */
	public function GetHeader ($name) {
		/** @var $this \MvcCore\Response */
		$this->UpdateHeaders();
		return isset($this->headers[$name])
			? $this->headers[$name]
			: NULL;
	}

	/**
	 * @inheritDocs
	 * @param string $name
	 * @return bool
	 */
	public function HasHeader ($name) {
		/** @var $this \MvcCore\Response */
		$this->UpdateHeaders();
		return isset($this->headers[$name]);
	}

	/**
	 * @inheritDocs
	 * @return \MvcCore\Response
	 */
	public function UpdateHeaders () {
		/** @var $this \MvcCore\Response */
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
	 * @inheritDocs
	 * @param \string[] $disabledHeaders,...
	 * @return \MvcCore\Response
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
	 * @inheritDocs
	 * @return \string[]
	 */
	public function GetDisabledHeaders () {
		/** @var $this \MvcCore\Response */
		return array_keys($this->disabledHeaders);
	}
}
