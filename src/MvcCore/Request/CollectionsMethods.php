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

namespace MvcCore\Request;

trait CollectionsMethods
{
	/**
	 * Get one of the global data collections stored as protected properties inside request object.
	 * Example:
	 *  // to get global `$_GET` with raw values:
	 *  `$globalGet = $request->GetGlobalCollection('get');`
	 * @param string $type
	 * @return array
	 */
	public function & GetGlobalCollection ($type) {
		$collection = 'global'.ucfirst(strtolower($type));
		return $this->{$collection};
	}

	/**
	 * Set directly all raw http headers without any conversion at once.
	 * Header name(s) as array keys should be in standard format like:
	 * `"Content-Type" | "Content-Length" | "X-Requested-With" ...`.
	 * @param array $headers
	 * @return \MvcCore\Request
	 */
	public function & SetHeaders (array & $headers = []) {
		$this->headers = & $headers;
		return $this;
	}

	/**
	 * Get directly all raw http headers at once (with/without conversion).
	 * If headers are not initialized, initialize headers by
	 * `getallheaders()` or from `$_SERVER['HTTP_...']`.
	 * Headers are returned as `key => value` array, headers keys are
	 * in standard format like: `"Content-Type" | "Content-Length" | "X-Requested-With" ...`.
	 * @param string|array|bool $pregReplaceAllowedChars If String - list of regular expression characters to only keep, if array - `preg_replace()` pattern and reverse, if `FALSE`, raw value is returned.
	 * @return array
	 */
	public function & GetHeaders ($pregReplaceAllowedChars = ['#[\<\>\'"]#' => '']) {
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
	 * Set directly raw http header value without any conversion.
	 * Header name should be in standard format like:
	 * `"Content-Type" | "Content-Length" | "X-Requested-With" ...`.
	 * @param string $name
	 * @param string|string[] $value
	 * @return \MvcCore\Request
	 */
	public function & SetHeader ($name = '', $value = '') {
		if ($this->headers === NULL) $this->initHeaders();
		$this->headers[$name] = $value;
		return $this;
	}

	/**
	 * Get http header value filtered by "rule to keep defined characters only",
	 * defined in second argument (by `preg_replace()`). Place into second argument
	 * only char groups you want to keep. Header has to be in format like:
	 * `"Content-Type" | "Content-Length" | "X-Requested-With" ...`.
	 * @param string $name Http header string name.
	 * @param string|array|bool $pregReplaceAllowedChars If String - list of regular expression characters to only keep, if array - `preg_replace()` pattern and reverse, if `FALSE`, raw value is returned.
	 * @param mixed $ifNullValue Default value returned if given param name is null.
	 * @param string $targetType Target type to retype param value or default if-null value. If param is an array, every param item will be retyped into given target type.
	 * @return string|string[]|mixed
	 */
	public function GetHeader (
		$name = '',
		$pregReplaceAllowedChars = "a-zA-Z0-9_;, /\-\.\@\=\+\?\!",
		$ifNullValue = NULL,
		$targetType = NULL
	) {
		if ($this->headers === NULL) $this->initHeaders();
		return $this->getParamFromCollection(
			$this->headers, $name, $pregReplaceAllowedChars, $ifNullValue, $targetType
		);
	}

	/**
	 * Return if reqest has any http header by given name.
	 * @param string $name Http header string name.
	 * @return bool
	 */
	public function HasHeader ($name = '') {
		if ($this->headers === NULL) $this->initHeaders();
		return isset($this->headers[$name]);
	}


	/**
	 * Set directly all raw parameters without any conversion at once.
	 * @param array $params
	 * @return \MvcCore\Request
	 */
	public function & SetParams (array & $params = []) {
		$this->params = & $params;
		return $this;
	}

	/**
	 * Get directly all raw parameters at once (with/without conversion).
	 * If any defined char groups in `$pregReplaceAllowedChars`, there will be returned
	 * all params filtered by given rule in `preg_replace()`.
	 * @param string|array|bool $pregReplaceAllowedChars If String - list of regular expression characters to only keep, if array - `preg_replace()` pattern and reverse, if `FALSE`, raw value is returned.
	 * @param array $onlyKeys Array with keys to get only. If empty (by default), all possible params are returned.
	 * @return array
	 */
	public function & GetParams ($pregReplaceAllowedChars = ['#[\<\>\'"]#' => ''], $onlyKeys = []) {
		if ($this->params === NULL) $this->initParams();
		if ($pregReplaceAllowedChars === FALSE || $pregReplaceAllowedChars === '' || $pregReplaceAllowedChars === '.*') {
			if ($onlyKeys) {
				$result = array_intersect_key($this->params, array_flip($onlyKeys));
			} else {
				$result = $this->params;
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
	 * Set directly raw parameter value without any conversion.
	 * @param string $name
	 * @param string|string[] $value
	 * @return \MvcCore\Request
	 */
	public function & SetParam ($name = '', $value = '') {
		if ($this->params === NULL) $this->initParams();
		$this->params[$name] = $value;
		return $this;
	}

	/**
	 * Remove parameter by name.
	 * @param string $name
	 * @return \MvcCore\Request
	 */
	public function & RemoveParam ($name = '') {
		if ($this->params === NULL) $this->initParams();
		unset($this->params[$name]);
		return $this;
	}

	/**
	 * Get param value from `$_GET`, `$_POST` or `php://input`, filtered by
	 * "rule to keep defined characters only", defined in second argument (by `preg_replace()`).
	 * Place into second argument only char groups you want to keep.
	 * @param string $name Parametter string name.
	 * @param string|array|bool $pregReplaceAllowedChars If String - list of regular expression characters to only keep, if array - `preg_replace()` pattern and reverse, if `FALSE`, raw value is returned.
	 * @param mixed $ifNullValue Default value returned if given param name is null.
	 * @param string $targetType Target type to retype param value or default if-null value. If param is an array, every param item will be retyped into given target type.
	 * @return string|string[]|mixed
	 */
	public function GetParam (
		$name = '',
		$pregReplaceAllowedChars = "a-zA-Z0-9_;, /\-\@\:",
		$ifNullValue = NULL,
		$targetType = NULL
	) {
		if ($this->params === NULL) $this->initParams();
		return $this->getParamFromCollection(
			$this->params, $name, $pregReplaceAllowedChars, $ifNullValue, $targetType
		);
	}

	/**
	 * Get if any param value exists in `$_GET`, `$_POST` or `php://input`
	 * @param string $name Parametter string name.
	 * @return bool
	 */
	public function HasParam ($name = '') {
		if ($this->params === NULL) $this->initParams();
		return isset($this->params[$name]);
	}


	/**
	 * Set directly whole raw global `$_FILES` without any conversion at once.
	 * @param array $files
	 * @return \MvcCore\Request
	 */
	public function & SetFiles (array & $files = []) {
		$this->globalFiles = & $files;
		return $this;
	}

	/**
	 * Return reference to configured global `$_FILES`
	 * or reference to any other testing array representing it.
	 * @return array
	 */
	public function & GetFiles () {
		return $this->globalFiles;
	}

	/**
	 * Set file item into global `$_FILES` without any conversion at once.
	 * @param string $file Uploaded file string name.
	 * @param array $data
	 * @return \MvcCore\Request
	 */
	public function & SetFile ($file = '', $data = []) {
		$this->globalFiles[$file] = $data;
		return $this;
	}

	/**
	 * Return item by file name from referenced global `$_FILES`
	 * or reference to any other testing array item representing it.
	 * @param string $file Uploaded file string name.
	 * @return array
	 */
	public function GetFile ($file = '') {
		if (isset($this->globalFiles[$file])) return $this->globalFiles[$file];
		return [];
	}

	/**
	 * Return if any item by file name exists or not in referenced global `$_FILES`.
	 * @param string $file Uploaded file string name.
	 * @return bool
	 */
	public function HasFile ($file = '') {
		return isset($this->globalFiles[$file]);
	}


	/**
	 * Set directly whole raw global `$_COOKIE` without any conversion at once.
	 * @param array $cookies
	 * @return \MvcCore\Request
	 */
	public function & SetCookies (array & $cookies = []) {
		$this->globalCookies = & $cookies;
		return $this;
	}

	/**
	 * Get directly all raw global `$_COOKIE`s at once (with/without conversion).
	 * Cookies are returned as `key => value` array.
	 * @param string|array|bool $pregReplaceAllowedChars If String - list of regular expression characters to only keep, if array - `preg_replace()` pattern and reverse, if `FALSE`, raw value is returned.
	 * @return array
	 * @return array
	 */
	public function & GetCookies ($pregReplaceAllowedChars = ['#[\<\>\'"]#' => ''], $onlyKeys = []) {
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
	 * Set raw request cookie into referenced global `$_COOKIE` without any conversion.
	 * @param string $name
	 * @param string|string[] $value
	 * @return \MvcCore\Request
	 */
	public function & SetCookie ($name = "", $value = "") {
		$this->globalCookies[$name] = $value;
		return $this;
	}

	/**
	 * Get request cookie value from referenced global `$_COOKIE` variable,
	 * filtered by characters defined in second argument through `preg_replace()`.
	 * Place into second argument only char groups you want to keep.
	 * @param string $name Cookie string name.
	 * @param string|array|bool $pregReplaceAllowedChars If String - list of regular expression characters to only keep, if array - `preg_replace()` pattern and reverse, if `FALSE`, raw value is returned.
	 * @param mixed $ifNullValue Default value returned if given param name is null.
	 * @param string $targetType Target type to retype param value or default if-null value. If param is an array, every param item will be retyped into given target type.
	 * @return string|string[]|mixed
	 */
	public function GetCookie (
		$name = '',
		$pregReplaceAllowedChars = "a-zA-Z0-9_;, /\-\.\@\=\+\?\!",
		$ifNullValue = NULL,
		$targetType = NULL
	) {
		return $this->getParamFromCollection(
			$this->globalCookies, $name, $pregReplaceAllowedChars, $ifNullValue, $targetType
		);
	}

	/**
	 * Return if any item by cookie name exists or not in referenced global `$_COOKIE`.
	 * @param string $name Cookie string name.
	 * @return bool
	 */
	public function HasCookie ($name = '') {
		return isset($this->globalCookies[$name]);
	}

	/**
	 * Get filtered param or header value for characters defined as second argument to use them in `preg_replace()`.
	 * @param string|string[]|NULL $rawValue
	 * @param string|array|bool $pregReplaceAllowedChars If String - list of regular expression characters to only keep, if array - `preg_replace()` pattern and reverse, if `FALSE`, raw value is returned.
	 * @param mixed $ifNullValue Default value returned if given param name is null.
	 * @param string $targetType Target type to retype param value or default if-null value. If param is an array, every param item will be retyped into given target type.
	 * @return string|string[]|mixed
	 */
	protected function getParamItem (
		& $rawValue = NULL,
		$pregReplaceAllowedChars = "a-zA-Z0-9_;, /\-\@\:",
		$ifNullValue = NULL,
		$targetType = NULL
	) {
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
				foreach ((array) $rawValue as $key => & $value) {
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
