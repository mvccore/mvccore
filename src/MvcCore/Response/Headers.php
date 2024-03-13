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
	 * @inheritDoc
	 * @return bool
	 */
	public function IsSentHeaders () {
		return headers_sent();
	}

	/**
	 * @inheritDoc
	 * @param  array<string,string|int|array<string|int>> $headers
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
	 * @inheritDoc
	 * @param  array<string,string|int|array<string|int>> $headers
	 * @param  bool                                       $cleanAllPrevious
	 * `FALSE` by default. If `TRUE`, all previous headers
	 * set by PHP `header()` or by this object will be removed.
	 * @return \MvcCore\Response
	 */
	public function AddHeaders (array $headers = [], $cleanAllPrevious = FALSE) {
		foreach ($headers as $name => $value) {
			if ($cleanAllPrevious)
				$this->RemoveHeader($name);
			$this->AddHeader($name, $value);
		}
		return $this;
	}

	/**
	 * @inheritDoc
	 * @param  string                       $name
	 * @param  string|int|array<string|int> $value
	 * @return \MvcCore\Response
	 */
	public function SetHeader ($name, $value) {
		if (isset($this->disabledHeaders[$name]))
			return $this;
		if (!isset(static::$multiplyHeaders[$name])) {
			if ($name == 'Content-Length' && $this->getOutputCompression())
				return $this;
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
	 * @inheritDoc
	 * @param  string               $name
	 * @param  string|array<string> $value
	 * @return \MvcCore\Response
	 */
	public function AddHeader ($name, $value) {
		if (isset($this->disabledHeaders[$name]))
			return $this;
		if (!isset(static::$multiplyHeaders[$name])) {
			if ($name == 'Content-Length' && $this->getOutputCompression())
				return $this;
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
	 * @inheritDoc
	 * @param  string $name
	 * @return string|array<string>|NULL
	 */
	public function GetHeader ($name) {
		$this->UpdateHeaders();
		return isset($this->headers[$name])
			? $this->headers[$name]
			: NULL;
	}

	/**
	 * @inheritDoc
	 * @param  string $name
	 * @return bool
	 */
	public function HasHeader ($name) {
		$this->UpdateHeaders();
		return isset($this->headers[$name]);
	}

	/**
	 * @inheritDoc
	 * @param  string $name
	 * @return bool
	 */
	public function RemoveHeader ($name) {
		$this->UpdateHeaders();
		$hasHeader = isset($this->headers[$name]);
		if ($hasHeader) {
			header_remove($name);
			unset($this->headers[$name]);
		}
		return $hasHeader;
	}

	/**
	 * @inheritDoc
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
	 * @inheritDoc
	 * @param  array<string> $disabledHeaders,...
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
	 * @inheritDoc
	 * @return array<string>
	 */
	public function GetDisabledHeaders () {
		return array_keys($this->disabledHeaders);
	}

	/**
	 * Detect ouput `Content-Type` and `Content-Encoding`
	 * by newly added/set http response header.
	 * @param  string               $name
	 * @param  string|array<string> $value
	 * @return \MvcCore\Response
	 */
	protected function setUpContentEncAndTypeByNew ($name, $value) {
		$nameLower = mb_strtolower($name);
		if ($nameLower === 'content-type') {
			$this->removeMisMatchHeaders(['content-type', 'Content-type', 'content-Type']);
			$this->headers['Content-Type'] = $value;
			header('Content-Type: ' . $value);
			if (strpos($value, 'text/') === 0) {
				$charsetPos = strpos($value, 'charset');
				if ($charsetPos !== FALSE) {
					$equalPos = strpos($value, '=', $charsetPos);
					if ($equalPos !== FALSE) $this->SetEncoding(
						trim(substr($value, $equalPos + 1))
					);
				}
			}
		}
		if ($nameLower === 'content-encoding')
			$this->SetEncoding($value);
		return $this;
	}

	/**
	 * Remove HTTP headers with invalid names.
	 * @param  array<string> $mismatchHeaderNames 
	 * @return \MvcCore\Response
	 */
	protected function removeMisMatchHeaders (array $mismatchHeaderNames) {
		foreach ($mismatchHeaderNames as $mismatchHeaderName) {
			if (isset($this->headers[$mismatchHeaderName])) {
				header_remove($mismatchHeaderName);
				unset($this->headers[$mismatchHeaderName]);
			}
		}
		return $this;
	}
}
