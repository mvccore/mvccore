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
trait Headers {

	/**
	 * @inheritDocs
	 * @return bool
	 */
	public function IsSentHeaders () {
		return headers_sent();
	}

	/**
	 * @inheritDocs
	 * @param  array $headers
	 * @return \MvcCore\Response
	 */
	public function SetHeaders (array $headers = []) {
		header_remove();
		$this->headers = [];
		foreach ($headers as $name => $value) {
			$this->SetHeader($name, $value);
		}
		return $this;
	}
	
	/**
	 * @inheritDocs
	 * @param  array $headers
	 * @param  bool  $cleanAllPrevious `FALSE` by default. If `TRUE`, all previous headers
	 *                                 set by PHP `header()` or by this object will be removed.
	 * @return \MvcCore\Response
	 */
	public function AddHeaders (array $headers = []) {
		foreach ($headers as $name => $value)
			$this->AddHeader($name, $value);
		return $this;
	}

	/**
	 * @inheritDocs
	 * @param  string $name
	 * @param  string|\string[] $value
	 * @return \MvcCore\Response
	 */
	public function SetHeader ($name, $value) {
		if (isset($this->disabledHeaders[$name]))
			return $this;
		if (!isset(static::$multiplyHeaders[$name])) {
			header($name . ": " . $value, TRUE);
			$this->headers[$name] = $value;
		} else {
			if (!is_array($value)) {
				header($name . ": " . $value, TRUE);
				$this->headers[$name] = [$value];
			} else {
				$this->headers[$name] = [];
				header_remove($name);
				foreach ($value as $item) {
					header($name . ": " . $item, FALSE);
					$this->headers[$name][] = $item;
				}
			}
		}
		return $this->setUpContentEncAndTypeByNew($name, $value);
	}
	
	/**
	 * @inheritDocs
	 * @param  string $name
	 * @param  string|\string[] $value
	 * @return \MvcCore\Response
	 */
	public function AddHeader ($name, $value) {
		if (isset($this->disabledHeaders[$name]))
			return $this;
		if (!isset(static::$multiplyHeaders[$name])) {
			header($name . ": " . $value);
			$this->headers[$name] = $value;
		} else {
			if (!is_array($value)) {
				header($name . ": " . $value, FALSE);
				if (isset($this->headers[$name])) {
					$this->headers[$name][] = $value;
				} else {
					$this->headers[$name] = [$value];
				}
			} else {
				foreach ($value as $item) {
					header($name . ": " . $item, FALSE);
					if (isset($this->headers[$name])) {
						$this->headers[$name][] = $item;
					} else {
						$this->headers[$name] = [$item];
					}
				}
			}
		}
		return $this->setUpContentEncAndTypeByNew($name, $value);
	}

	/**
	 * @inheritDocs
	 * @param  string $name
	 * @return string|\string[]|NULL
	 */
	public function GetHeader ($name) {
		$this->UpdateHeaders();
		return isset($this->headers[$name])
			? $this->headers[$name]
			: NULL;
	}

	/**
	 * @inheritDocs
	 * @param  string $name
	 * @return bool
	 */
	public function HasHeader ($name) {
		$this->UpdateHeaders();
		return isset($this->headers[$name]);
	}

	/**
	 * @inheritDocs
	 * @return \MvcCore\Response
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
			if (isset($this->disabledHeaders[$name])) continue;
			if (!isset(static::$multiplyHeaders[$name])) {
				$this->headers[$name] = $value;
			} else {
				if (isset($this->headers[$name])) {
					if (!in_array($value, $this->headers[$name], TRUE))
						$this->headers[$name][] = $value;
				} else {
					$this->headers[$name] = [$value];
				}
			}	
		}
		return $this;
	}

	/**
	 * @inheritDocs
	 * @param  \string[] $disabledHeaders,...
	 * @return \MvcCore\Response
	 */
	public function SetDisabledHeaders ($disabledHeaders) {
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
		return array_keys($this->disabledHeaders);
	}

	/**
	 * Detect ouput `Content-Type` and `Content-Encoding`
	 * by newly added/set http response header.
	 * @param  string $name
	 * @param  string|\string[] $value
	 * @return \MvcCore\Response
	 */
	protected function setUpContentEncAndTypeByNew ($name, $value) {
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
}
