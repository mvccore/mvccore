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
	 * @param  string $type
	 * @return array
	 */
	public function & GetGlobalCollection ($type) {
		/** @var $this \MvcCore\Request */
		$collection = 'global'.ucfirst(strtolower($type));
		return $this->{$collection};
	}

	/**
	 * @inheritDocs
	 * @param  array $headers
	 * @return \MvcCore\Request
	 */
	public function SetHeaders (array & $headers = []) {
		/** @var $this \MvcCore\Request */
		$this->headers = & $headers;
		return $this;
	}

	/**
	 * @inheritDocs
	 * @param  string|array|bool $pregReplaceAllowedChars If String - list of regular expression characters to only keep, if array - `preg_replace()` pattern and reverse, if `FALSE`, raw value is returned.
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
	 * @param  string          $name
	 * @param  string|string[] $value
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
	 * @param  string            $name                    Http header string name.
	 * @param  string|array|bool $pregReplaceAllowedChars If String - list of regular expression characters to only keep, if array - `preg_replace()` pattern and reverse, if `FALSE`, raw value is returned.
	 * @param  mixed             $ifNullValue             Default value returned if given param name is null.
	 * @param  string            $targetType              Target type to retype param value or default if-null value. If param is an array, every param item will be retyped into given target type.
	 * @throws \InvalidArgumentException                  `$name` must be a `$targetType`, not an `array`.
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
	 * @param  string $name Http header string name.
	 * @return bool
	 */
	public function HasHeader ($name = '') {
		/** @var $this \MvcCore\Request */
		if ($this->headers === NULL) $this->initHeaders();
		return isset($this->headers[$name]);
	}


	/**
	 * @inheritDocs
	 * @param  array $params
	 *               Keys are param names, values are param values.
	 * @param  int   $sourceType
	 *               Param source collection flag(s). If param has defined 
	 *               source type flag already, this given flag is used 
	 *               to overwrite already defined flag.
	 * @return \MvcCore\Request
	 */
	public function SetParams (
		array & $params = [],
		$sourceType = \MvcCore\IRequest::PARAM_TYPE_ANY
	) {
		/** @var $this \MvcCore\Request */
		$this->params = & $params;
		if ($sourceType && isset($this->paramsSources[$sourceType])) {
			$sourceTypeParams = & $this->paramsSources[$sourceType];
			$sourceTypeParamsQs = & $this->paramsSources[\MvcCore\IRequest::PARAM_TYPE_QUERY_STRING];
			$sourceTypeParamsUr = & $this->paramsSources[\MvcCore\IRequest::PARAM_TYPE_URL_REWRITE];
			foreach ($this->params as $name => $value) {
				unset($sourceTypeParamsQs[$name], $sourceTypeParamsUr[$name]);
				$sourceTypeParams[$name] = TRUE;
			}
		}
		return $this;
	}

	/**
	 * @inheritDocs
	 * @param  string|array|bool $pregReplaceAllowedChars If String - list of regular expression characters to only keep, if array - `preg_replace()` pattern and reverse, if `FALSE`, raw value is returned.
	 * @param  array             $onlyKeys                Array with keys to get only. If empty (by default), all possible params are returned.
	 * @param  int               $sourceType              Param source collection flag(s). If defined, there are returned only params from given collection types.
	 * @return array
	 */
	public function & GetParams (
		$pregReplaceAllowedChars = ['#[\<\>\'"]#' => ''], 
		$onlyKeys = [],
		$sourceType = \MvcCore\IRequest::PARAM_TYPE_ANY
	) {
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
		$sourceTypeInput = $sourceType && ($sourceType & \MvcCore\IRequest::PARAM_TYPE_INPUT) != 0;
		$sourceTypeParamsQs = $this->paramsSources[\MvcCore\IRequest::PARAM_TYPE_QUERY_STRING];
		$sourceTypeParamsUr = $this->paramsSources[\MvcCore\IRequest::PARAM_TYPE_URL_REWRITE];
		$sourceTypeParams = isset($this->paramsSources[$sourceType])
			? $this->paramsSources[$sourceType]
			: [];
		foreach ($this->params as $key => & $value) {
			if ($onlyKeys && !in_array($key, $onlyKeys, TRUE)) continue;

			if ($sourceType && (
				($sourceTypeInput && (
					isset($sourceTypeParamsQs[$key]) || isset($sourceTypeParamsUr[$key])
				)) || (
					$sourceTypeParams && !isset($sourceTypeParams[$key])
				)
			)) continue;

			$cleanedKey = $this->cleanParamValue($key, $pregReplaceAllowedChars);
			$cleanedParams[$cleanedKey] = $this->GetParam($key, $pregReplaceAllowedChars);
		}
		return $cleanedParams;
	}

	/**
	 * @inheritDocs
	 * Set directly raw parameter value without any conversion.
	 * @param  string                $name       Param raw name.
	 * @param  string|\string[]|NULL $value      Param raw value.
	 * @param  int                   $sourceType
	 *                               Param source collection flag(s). If param has defined 
	 *                               source type flag already, this given flag is used 
	 *                               to overwrite already defined flag.
	 * @return \MvcCore\Request
	 */
	public function SetParam (
		$name, 
		$value = NULL, 
		$sourceType = \MvcCore\IRequest::PARAM_TYPE_ANY
	) {
		/** @var $this \MvcCore\Request */
		if ($this->params === NULL) $this->initParams();
		$this->params[$name] = $value;
		if ($sourceType && isset($this->paramsSources[$sourceType])) {
			unset(
				$this->paramsSources[\MvcCore\IRequest::PARAM_TYPE_QUERY_STRING][$name],
				$this->paramsSources[\MvcCore\IRequest::PARAM_TYPE_URL_REWRITE][$name]
			);
			$this->paramsSources[$sourceType][$name] = TRUE;	
		}
		return $this;
	}
	
	/**
	 * @inheritDocs
	 * @param  string            $name                    Parameter string name.
	 * @param  string|array|bool $pregReplaceAllowedChars If String - list of regular expression characters to only keep, if array - `preg_replace()` pattern and reverse, if `FALSE`, raw value is returned.
	 * @param  mixed             $ifNullValue             Default value returned if given param name is null.
	 * @param  string            $targetType              Target type to retype param value or default if-null value. If param is an array, every param item will be retyped into given target type.
	 * @param  int               $sourceType              Param source collection flag(s). If defined, there is returned only param from given collection type(s).
	 * @throws \InvalidArgumentException                  `$name` must be a `$targetType`, not an `array`.
	 * @return string|\string[]|int|\int[]|bool|\bool[]|array|mixed
	 */
	public function GetParam (
		$name,
		$pregReplaceAllowedChars = "a-zA-Z0-9_;, /\-\@\:",
		$ifNullValue = NULL,
		$targetType = NULL,
		$sourceType = \MvcCore\IRequest::PARAM_TYPE_ANY
	) {
		/** @var $this \MvcCore\Request */
		if ($this->params === NULL) $this->initParams();
		if ($sourceType && (
			(($sourceType & \MvcCore\IRequest::PARAM_TYPE_INPUT) != 0 && (
				isset($this->paramsSources[\MvcCore\IRequest::PARAM_TYPE_QUERY_STRING][$name]) ||
				isset($this->paramsSources[\MvcCore\IRequest::PARAM_TYPE_URL_REWRITE][$name])
			)) || (
				isset($this->paramsSources[$sourceType]) &&
				!isset($this->paramsSources[$sourceType][$name])
			)
		)) return NULL;
		return $this->getParamFromCollection(
			$this->params, $name, $pregReplaceAllowedChars, $ifNullValue, $targetType
		);
	}

	/**
	 * @inheritDocs
	 * @param  string $name 
	 * @return int
	 */
	public function GetParamSourceType ($name) {
		if (isset($this->paramsSources[\MvcCore\IRequest::PARAM_TYPE_QUERY_STRING][$name]))
			return \MvcCore\IRequest::PARAM_TYPE_QUERY_STRING;
		if (isset($this->paramsSources[\MvcCore\IRequest::PARAM_TYPE_URL_REWRITE][$name]))
			return \MvcCore\IRequest::PARAM_TYPE_URL_REWRITE;
		return \MvcCore\IRequest::PARAM_TYPE_INPUT;
	}
	
	/**
	 * @inheritDocs
	 * @param  string $name       Parameter string name.
	 * @param  int    $sourceType Param source collection flag(s). If defined, there is returned `TRUE` only for param in given collection type(s).
	 * @return bool
	 */
	public function HasParam (
		$name, 
		$sourceType = \MvcCore\IRequest::PARAM_TYPE_ANY
	) {
		/** @var $this \MvcCore\Request */
		if ($this->params === NULL) $this->initParams();
		if ($sourceType) {
			if (($sourceType & \MvcCore\IRequest::PARAM_TYPE_INPUT) != 0) {
				return (
					isset($this->params[$name]) &&
					!isset($this->paramsSources[\MvcCore\IRequest::PARAM_TYPE_QUERY_STRING][$name]) &&
					!isset($this->paramsSources[\MvcCore\IRequest::PARAM_TYPE_URL_REWRITE][$name])
				);
			} else {
				return (
					isset($this->params[$name]) &&
					isset($this->paramsSources[$sourceType]) &&
					isset($this->paramsSources[$sourceType][$name])
				);
			}
		} else {
			return isset($this->params[$name]);
		}
	}

	/**
	 * @inheritDocs
	 * @param  string $name
	 * @return \MvcCore\Request
	 */
	public function RemoveParam ($name) {
		/** @var $this \MvcCore\Request */
		if ($this->params === NULL) $this->initParams();
		unset(
			$this->params[$name],
			$this->paramsSources[\MvcCore\IRequest::PARAM_TYPE_QUERY_STRING][$name],
			$this->paramsSources[\MvcCore\IRequest::PARAM_TYPE_URL_REWRITE][$name]
		);
		return $this;
	}


	/**
	 * @inheritDocs
	 * @param  array $files
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
	 * @param  string $file Uploaded file string name.
	 * @param  array  $data
	 * @return \MvcCore\Request
	 */
	public function SetFile ($file = '', $data = []) {
		/** @var $this \MvcCore\Request */
		$this->globalFiles[$file] = $data;
		return $this;
	}

	/**
	 * @inheritDocs
	 * @param  string $file Uploaded file string name.
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
	 * @param  string $file Uploaded file string name.
	 * @return bool
	 */
	public function HasFile ($file = '') {
		/** @var $this \MvcCore\Request */
		return isset($this->globalFiles[$file]);
	}


	/**
	 * @inheritDocs
	 * @param  array $cookies
	 * @return \MvcCore\Request
	 */
	public function SetCookies (array & $cookies = []) {
		/** @var $this \MvcCore\Request */
		$this->globalCookies = & $cookies;
		return $this;
	}

	/**
	 * @inheritDocs
	 * @param  string|array|bool $pregReplaceAllowedChars If String - list of regular expression characters to only keep, if array - `preg_replace()` pattern and reverse, if `FALSE`, raw value is returned.
	 * @param  array             $onlyKeys                Array with keys to get only. If empty (by default), all possible cookies are returned.
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
	 * @param  string           $name
	 * @param  string|\string[] $value
	 * @return \MvcCore\Request
	 */
	public function SetCookie ($name = "", $value = "") {
		/** @var $this \MvcCore\Request */
		$this->globalCookies[$name] = $value;
		return $this;
	}

	/**
	 * @inheritDocs
	 * @param  string            $name                    Cookie string name.
	 * @param  string|array|bool $pregReplaceAllowedChars If String - list of regular expression characters to only keep, if array - `preg_replace()` pattern and reverse, if `FALSE`, raw value is returned.
	 * @param  mixed             $ifNullValue             Default value returned if given param name is null.
	 * @param  string            $targetType              Target type to retype param value or default if-null value. If param is an array, every param item will be retyped into given target type.
	 * @throws \InvalidArgumentException                  `$name` must be a `$targetType`, not an `array`.
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
	 * @param  string $name Cookie string name.
	 * @return bool
	 */
	public function HasCookie ($name = '') {
		/** @var $this \MvcCore\Request */
		return isset($this->globalCookies[$name]);
	}

	/**
	 * Get filtered param or header value for characters defined as second argument to use them in `preg_replace()`.
	 * @param  string|string[]|NULL $rawValue
	 * @param  string|array|bool    $pregReplaceAllowedChars If String - list of regular expression characters to only keep, if array - `preg_replace()` pattern and reverse, if `FALSE`, raw value is returned.
	 * @param  mixed                $ifNullValue             Default value returned if given param name is null.
	 * @param  string               $targetType              Target type to retype param value or default if-null value. If param is an array, every param item will be retyped into given target type.
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
	 * @param  string            $rawValue
	 * @param  string|array|bool $pregReplaceAllowedChars If String - list of regular expression characters to only keep, if array - `preg_replace()` pattern and reverse, if `FALSE`, raw value is returned.
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
