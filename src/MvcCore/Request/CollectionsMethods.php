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

namespace MvcCore\Request;

trait CollectionsMethods {

	/**
	 * @inheritDocs
	 * @param string $type
	 * @return array
	 */
	public function & GetGlobalCollection ($type) {
		/** @var $this \MvcCore\Request */
		$collection = 'global'.ucfirst(strtolower($type));
		return $this->{$collection};
	}

	/**
	 * @inheritDocs
	 * @param array $headers
	 * @return \MvcCore\Request
	 */
	public function SetHeaders (array & $headers = []) {
		/** @var $this \MvcCore\Request */
		$this->headers = & $headers;
		return $this;
	}

	/**
	 * @inheritDocs
	 * @param string|array|bool $pregReplaceAllowedChars If String - list of regular expression characters to only keep, if array - `preg_replace()` pattern and reverse, if `FALSE`, raw value is returned.
	 * @return array
	 */
	public function & GetHeaders ($pregReplaceAllowedChars = ['#[\<\>\'"]#' => '']) {
		/** @var $this \MvcCore\Request */
		if ($this->headers === NULL) $this->initHeaders();
		if ($pregReplaceAllowedChars === FALSE || $pregReplaceAllowedChars === '' || $pregReplaceAllowedChars === '.*')
			return $this->headers;
		$cleanedHeaders = [];
		foreach ($this->headers as $key => & $value) {
			$cleanedKey = $this->cleanParamValue($key, $pregReplaceAllowedChars);
			$cleanedHeaders[$cleanedKey] = $this->GetHeader($key, $pregReplaceAllowedChars);
		}
		return $cleanedHeaders;
	}

	/**
	 * @inheritDocs
	 * @param string $name
	 * @param string|string[] $value
	 * @return \MvcCore\Request
	 */
	public function SetHeader ($name = '', $value = '') {
		/** @var $this \MvcCore\Request */
		if ($this->headers === NULL) $this->initHeaders();
		$this->headers[$name] = $value;
		return $this;
	}

	/**
	 * @inheritDocs
	 * @param string $name Http header string name.
	 * @param string|array|bool $pregReplaceAllowedChars If String - list of regular expression characters to only keep, if array - `preg_replace()` pattern and reverse, if `FALSE`, raw value is returned.
	 * @param mixed $ifNullValue Default value returned if given param name is null.
	 * @param string $targetType Target type to retype param value or default if-null value. If param is an array, every param item will be retyped into given target type.
	 * @throws \InvalidArgumentException `$name` must be a `$targetType`, not an `array`.
	 * @return string|\string[]|int|\int[]|bool|\bool[]|array|mixed
	 */
	public function GetHeader (
		$name = '',
		$pregReplaceAllowedChars = "a-zA-Z0-9_;, /\-\.\@\=\+\?\!",
		$ifNullValue = NULL,
		$targetType = NULL
	) {
		/** @var $this \MvcCore\Request */
		if ($this->headers === NULL) $this->initHeaders();
		return $this->getParamFromCollection(
			$this->headers, $name, $pregReplaceAllowedChars, $ifNullValue, $targetType
		);
	}

	/**
	 * @inheritDocs
	 * @param string $name Http header string name.
	 * @return bool
	 */
	public function HasHeader ($name = '') {
		/** @var $this \MvcCore\Request */
		if ($this->headers === NULL) $this->initHeaders();
		return isset($this->headers[$name]);
	}


	/**
	 * @inheritDocs
	 * @param array $params
	 * @return \MvcCore\Request
	 */
	public function SetParams (array & $params = []) {
		/** @var $this \MvcCore\Request */
		$this->params = & $params;
		return $this;
	}

	/**
	 * @inheritDocs
	 * @param string|array|bool $pregReplaceAllowedChars If String - list of regular expression characters to only keep, if array - `preg_replace()` pattern and reverse, if `FALSE`, raw value is returned.
	 * @param array $onlyKeys Array with keys to get only. If empty (by default), all possible params are returned.
	 * @return array
	 */
	public function & GetParams ($pregReplaceAllowedChars = ['#[\<\>\'"]#' => ''], $onlyKeys = []) {
		/** @var $this \MvcCore\Request */
		if ($this->params === NULL) $this->initParams();
		if ($pregReplaceAllowedChars === FALSE || $pregReplaceAllowedChars === '' || $pregReplaceAllowedChars === '.*') {
			if ($onlyKeys) {
				$result = array_intersect_key($this->params, array_flip($onlyKeys));
			} else {
				$result = & $this->params;
			}
			return $result;
		}
		$cleanedParams = [];
		foreach ($this->params as $key => & $value) {
			if ($onlyKeys && !in_array($key, $onlyKeys, TRUE)) continue;
			$cleanedKey = $this->cleanParamValue($key, $pregReplaceAllowedChars);
			$cleanedParams[$cleanedKey] = $this->GetParam($key, $pregReplaceAllowedChars);
		}
		return $cleanedParams;
	}

	/**
	 * @inheritDocs
	 * @param string $name
	 * @param string|string[] $value
	 * @return \MvcCore\Request
	 */
	public function SetParam ($name = '', $value = '') {
		/** @var $this \MvcCore\Request */
		if ($this->params === NULL) $this->initParams();
		$this->params[$name] = $value;
		return $this;
	}

	/**
	 * @inheritDocs
	 * @param string $name
	 * @return \MvcCore\Request
	 */
	public function RemoveParam ($name = '') {
		/** @var $this \MvcCore\Request */
		if ($this->params === NULL) $this->initParams();
		unset($this->params[$name]);
		return $this;
	}

	/**
	 * @inheritDocs
	 * @param string $name Parameter string name.
	 * @param string|array|bool $pregReplaceAllowedChars If String - list of regular expression characters to only keep, if array - `preg_replace()` pattern and reverse, if `FALSE`, raw value is returned.
	 * @param mixed $ifNullValue Default value returned if given param name is null.
	 * @param string $targetType Target type to retype param value or default if-null value. If param is an array, every param item will be retyped into given target type.
	 * @throws \InvalidArgumentException `$name` must be a `$targetType`, not an `array`.
	 * @return string|\string[]|int|\int[]|bool|\bool[]|array|mixed
	 */
	public function GetParam (
		$name = '',
		$pregReplaceAllowedChars = "a-zA-Z0-9_;, /\-\@\:",
		$ifNullValue = NULL,
		$targetType = NULL
	) {
		/** @var $this \MvcCore\Request */
		if ($this->params === NULL) $this->initParams();
		return $this->getParamFromCollection(
			$this->params, $name, $pregReplaceAllowedChars, $ifNullValue, $targetType
		);
	}

	/**
	 * @inheritDocs
	 * @param string $name Parameter string name.
	 * @return bool
	 */
	public function HasParam ($name = '') {
		/** @var $this \MvcCore\Request */
		if ($this->params === NULL) $this->initParams();
		return isset($this->params[$name]);
	}


	/**
	 * @inheritDocs
	 * @param array $files
	 * @return \MvcCore\Request
	 */
	public function SetFiles (array & $files = []) {
		/** @var $this \MvcCore\Request */
		$this->globalFiles = & $files;
		return $this;
	}

	/**
	 * @inheritDocs
	 * @return array
	 */
	public function & GetFiles () {
		/** @var $this \MvcCore\Request */
		return $this->globalFiles;
	}

	/**
	 * @inheritDocs
	 * @param string $file Uploaded file string name.
	 * @param array $data
	 * @return \MvcCore\Request
	 */
	public function SetFile ($file = '', $data = []) {
		/** @var $this \MvcCore\Request */
		$this->globalFiles[$file] = $data;
		return $this;
	}

	/**
	 * @inheritDocs
	 * @param string $file Uploaded file string name.
	 * @return array
	 */
	public function GetFile ($file = '') {
		/** @var $this \MvcCore\Request */
		if (isset($this->globalFiles[$file])) {
			$file = $this->globalFiles[$file];
			if (isset($file['error']) && $file['error'] === UPLOAD_ERR_NO_FILE)
				return [];
			return $file;
		}
		return [];
	}

	/**
	 * @inheritDocs
	 * @param string $file Uploaded file string name.
	 * @return bool
	 */
	public function HasFile ($file = '') {
		/** @var $this \MvcCore\Request */
		return isset($this->globalFiles[$file]);
	}


	/**
	 * @inheritDocs
	 * @param array $cookies
	 * @return \MvcCore\Request
	 */
	public function SetCookies (array & $cookies = []) {
		/** @var $this \MvcCore\Request */
		$this->globalCookies = & $cookies;
		return $this;
	}

	/**
	 * @inheritDocs
	 * @param string|array|bool $pregReplaceAllowedChars If String - list of regular expression characters to only keep, if array - `preg_replace()` pattern and reverse, if `FALSE`, raw value is returned.
	 * @param array $onlyKeys Array with keys to get only. If empty (by default), all possible cookies are returned.
	 * @return array
	 */
	public function & GetCookies ($pregReplaceAllowedChars = ['#[\<\>\'"]#' => ''], $onlyKeys = []) {
		/** @var $this \MvcCore\Request */
		if ($pregReplaceAllowedChars === FALSE || $pregReplaceAllowedChars === '' || $pregReplaceAllowedChars === '.*') {
			if ($onlyKeys) {
				$result = array_intersect_key($this->paglobalCookiesrams, array_flip($onlyKeys));
			} else {
				$result = $this->globalCookies;
			}
			return $result;
		}
		$cleanedCookies = [];
		foreach ($this->globalCookies as $key => & $value) {
			if ($onlyKeys && !in_array($key, $onlyKeys, TRUE)) continue;
			$cleanedKey = $this->cleanParamValue($key, $pregReplaceAllowedChars);
			$cleanedCookies[$cleanedKey] = $this->GetCookie($key, $pregReplaceAllowedChars);
		}
		return $cleanedCookies;
	}

	/**
	 * @inheritDocs
	 * @param string $name
	 * @param string|string[] $value
	 * @return \MvcCore\Request
	 */
	public function SetCookie ($name = "", $value = "") {
		/** @var $this \MvcCore\Request */
		$this->globalCookies[$name] = $value;
		return $this;
	}

	/**
	 * @inheritDocs
	 * @param string $name Cookie string name.
	 * @param string|array|bool $pregReplaceAllowedChars If String - list of regular expression characters to only keep, if array - `preg_replace()` pattern and reverse, if `FALSE`, raw value is returned.
	 * @param mixed $ifNullValue Default value returned if given param name is null.
	 * @param string $targetType Target type to retype param value or default if-null value. If param is an array, every param item will be retyped into given target type.
	 * @throws \InvalidArgumentException `$name` must be a `$targetType`, not an `array`.
	 * @return string|\string[]|int|\int[]|bool|\bool[]|array|mixed
	 */
	public function GetCookie (
		$name = '',
		$pregReplaceAllowedChars = "a-zA-Z0-9_;, /\-\.\@\=\+\?\!",
		$ifNullValue = NULL,
		$targetType = NULL
	) {
		/** @var $this \MvcCore\Request */
		return $this->getParamFromCollection(
			$this->globalCookies, $name, $pregReplaceAllowedChars, $ifNullValue, $targetType
		);
	}

	/**
	 * @inheritDocs
	 * @param string $name Cookie string name.
	 * @return bool
	 */
	public function HasCookie ($name = '') {
		/** @var $this \MvcCore\Request */
		return isset($this->globalCookies[$name]);
	}

	/**
	 * Get filtered param or header value for characters defined as second argument to use them in `preg_replace()`.
	 * @param string|string[]|NULL $rawValue
	 * @param string|array|bool $pregReplaceAllowedChars If String - list of regular expression characters to only keep, if array - `preg_replace()` pattern and reverse, if `FALSE`, raw value is returned.
	 * @param mixed $ifNullValue Default value returned if given param name is null.
	 * @param string $targetType Target type to retype param value or default if-null value. If param is an array, every param item will be retyped into given target type.
	 * @return string|\string[]|int|\int[]|bool|\bool[]|array|mixed
	 */
	protected function getParamItem (
		& $rawValue = NULL,
		$pregReplaceAllowedChars = "a-zA-Z0-9_;, /\-\@\:",
		$ifNullValue = NULL,
		$targetType = NULL
	) {
		/** @var $this \MvcCore\Request */
		if ($rawValue === NULL) {
			// if there is NULL in target collection
			if ($targetType === NULL) return $ifNullValue;
			$result = is_scalar($ifNullValue) ? $ifNullValue : clone $ifNullValue;
			settype($result, $targetType);
			return $result;
		} else {
			// if there is not NULL in target collection
			if (is_string($rawValue) && mb_strlen(trim($rawValue)) === 0) {
				// if value after trim is empty string, return empty string (retyped if necessary)
				$result = "";
				if ($targetType === NULL) return $result;
				$result = is_scalar($ifNullValue) ? $ifNullValue : clone $ifNullValue;
				settype($result, $targetType);
				return $result;
			} else if ($pregReplaceAllowedChars === FALSE || $pregReplaceAllowedChars === '.*') {
				// if there is something in target collection and all chars are allowed
				$result = $rawValue;
				if ($targetType === NULL) return $result;
				settype($result, $targetType);
				return $result;
			} else if (is_array($rawValue)) {
				// if there is something in target collection and it's an array
				$result = [];
				foreach ((array) $rawValue as $key => $value) {
					$cleanedKey = $this->cleanParamValue($key, $pregReplaceAllowedChars);
					$result[$cleanedKey] = $this->getParamItem(
						$value, $pregReplaceAllowedChars, $ifNullValue, $targetType
					);
				}
				return $result;
			} else {
				// if there is something in target collection and it's not an array
				$result = $this->cleanParamValue($rawValue, $pregReplaceAllowedChars);
				if ($targetType === NULL) return $result;
				settype($result, $targetType);
				return $result;
			}
		}
	}

	/**
	 * Clean param value by given list of allowed chars or by given `preg_replace()` pattern and reverse.
	 * @param string $rawValue
	 * @param string|array|bool $pregReplaceAllowedChars If String - list of regular expression characters to only keep, if array - `preg_replace()` pattern and reverse, if `FALSE`, raw value is returned.
	 * @return string
	 */
	protected function cleanParamValue ($rawValue, $pregReplaceAllowedChars = "a-zA-Z0-9_;, /\-\@\:") {
		/** @var $this \MvcCore\Request */
		if ($pregReplaceAllowedChars === FALSE) {
			return $rawValue;
		} else if (is_array($pregReplaceAllowedChars)) {
			foreach ($pregReplaceAllowedChars as $pattern => $replace) {
				$replaceFn = mb_substr($pattern, 0, 1) === '#' ? 'preg_replace' : 'str_replace';
				$rawValue = $replaceFn($pattern, $replace, $rawValue);
			}
			return $rawValue;
		} else {
			return preg_replace("#[^" . $pregReplaceAllowedChars . "]#", "", (string) $rawValue);
		}
	}
}
