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

/**
 * @mixin \MvcCore\Request
 */
trait CollectionsMethods {

	/**
	 * @inheritDocs
	 * @param  string $type
	 * @return array
	 */
	public function & GetGlobalCollection ($type) {
		$collection = 'global'.ucfirst(strtolower($type));
		return $this->{$collection};
	}

	/**
	 * @inheritDocs
	 * @param  array $headers
	 * @return \MvcCore\Request
	 */
	public function SetHeaders (array & $headers = []) {
		$this->headers = & $headers;
		return $this;
	}

	/**
	 * @inheritDocs
	 * @param  string|array|bool $pregReplaceAllowedChars If String - list of regular expression characters to only keep, if array - `preg_replace()` pattern and reverse, if `FALSE`, raw value is returned.
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
	 * @inheritDocs
	 * @param  string          $name
	 * @param  string|string[] $value
	 * @return \MvcCore\Request
	 */
	public function SetHeader ($name = '', $value = '') {
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
		$this->params = & $params;
		if ($sourceType) {
			$qsFlag = ($sourceType & \MvcCore\IRequest::PARAM_TYPE_QUERY_STRING) != 0;
			$urFlag = ($sourceType & \MvcCore\IRequest::PARAM_TYPE_URL_REWRITE) != 0;
			$sourceTypeParamsQs = & $this->paramsSources[\MvcCore\IRequest::PARAM_TYPE_QUERY_STRING];
			$sourceTypeParamsUr = & $this->paramsSources[\MvcCore\IRequest::PARAM_TYPE_URL_REWRITE];
			foreach ($this->params as $name => $value) {
				unset($sourceTypeParamsQs[$name], $sourceTypeParamsUr[$name]);
				if ($qsFlag) $sourceTypeParamsQs[$name] = TRUE;	
				if ($urFlag) $sourceTypeParamsUr[$name] = TRUE;	
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
		if ($this->params === NULL) $this->initParams();

		if ($sourceType) {
			$qsFlag = $sourceType && ($sourceType & \MvcCore\IRequest::PARAM_TYPE_QUERY_STRING) != 0;
			$urFlag = $sourceType && ($sourceType & \MvcCore\IRequest::PARAM_TYPE_URL_REWRITE) != 0;
			$inFlag = $sourceType && ($sourceType & \MvcCore\IRequest::PARAM_TYPE_INPUT) != 0;
			$flagsCount = ($qsFlag ? 1 : 0) + ($urFlag ? 1 : 0) + ($inFlag ? 1 : 0);
			$sourceTypeParamsQs = $this->paramsSources[\MvcCore\IRequest::PARAM_TYPE_QUERY_STRING];
			$sourceTypeParamsUr = $this->paramsSources[\MvcCore\IRequest::PARAM_TYPE_URL_REWRITE];
		}

		if ($pregReplaceAllowedChars === FALSE || $pregReplaceAllowedChars === '' || $pregReplaceAllowedChars === '.*') {
			if ($onlyKeys) {
				$result = array_intersect_key($this->params, array_flip($onlyKeys));
			} else {
				$result = & $this->params;
			}
			if (!$sourceType) return $result;
			$sourceTypesResult = [];
			foreach ($result as $key => $value) {
				$notFoundFlagsCount = 0;
				if ($qsFlag && !isset($sourceTypeParamsQs[$key])) $notFoundFlagsCount++;
				if ($urFlag && !isset($sourceTypeParamsUr[$key])) $notFoundFlagsCount++;
				if ($inFlag && !$qsFlag && isset($sourceTypeParamsQs[$key])) $notFoundFlagsCount++;
				if ($inFlag && !$urFlag && isset($sourceTypeParamsUr[$key])) $notFoundFlagsCount++;
				if ($notFoundFlagsCount >= $flagsCount) continue;
				$sourceTypesResult[$key] = $value;
			}
			return $sourceTypesResult;
		}

		$cleanedParams = [];
		if ($sourceType) {
			foreach ($this->params as $key => $value) {
				if ($onlyKeys && !in_array($key, $onlyKeys, TRUE)) continue;

				$notFoundFlagsCount = 0;
				if ($qsFlag && !isset($sourceTypeParamsQs[$key])) $notFoundFlagsCount++;
				if ($urFlag && !isset($sourceTypeParamsUr[$key])) $notFoundFlagsCount++;
				if ($inFlag && !$qsFlag && isset($sourceTypeParamsQs[$key])) $notFoundFlagsCount++;
				if ($inFlag && !$urFlag && isset($sourceTypeParamsUr[$key])) $notFoundFlagsCount++;
				if ($notFoundFlagsCount >= $flagsCount) continue;
			
				$cleanedKey = $this->cleanParamValue($key, $pregReplaceAllowedChars);
				$cleanedParams[$cleanedKey] = $this->GetParam($key, $pregReplaceAllowedChars);
			}
		} else {
			foreach ($this->params as $key => $value) {
				if ($onlyKeys && !in_array($key, $onlyKeys, TRUE)) continue;
				$cleanedKey = $this->cleanParamValue($key, $pregReplaceAllowedChars);
				$cleanedParams[$cleanedKey] = $this->GetParam($key, $pregReplaceAllowedChars);
			}
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
		if ($this->params === NULL) $this->initParams();
		$this->params[$name] = $value;
		if ($sourceType) {
			unset(
				$this->paramsSources[\MvcCore\IRequest::PARAM_TYPE_QUERY_STRING][$name],
				$this->paramsSources[\MvcCore\IRequest::PARAM_TYPE_URL_REWRITE][$name]
			);
			if (($sourceType & \MvcCore\IRequest::PARAM_TYPE_QUERY_STRING) != 0)
				$this->paramsSources[\MvcCore\IRequest::PARAM_TYPE_QUERY_STRING][$name] = TRUE;	
			if (($sourceType & \MvcCore\IRequest::PARAM_TYPE_URL_REWRITE) != 0)
				$this->paramsSources[\MvcCore\IRequest::PARAM_TYPE_URL_REWRITE][$name] = TRUE;	
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
		if ($this->params === NULL) $this->initParams();
		if ($sourceType && !$this->HasParam($name, $sourceType)) return NULL;
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
		if (isset($this->params[$name]))
			return \MvcCore\IRequest::PARAM_TYPE_INPUT;
		return \MvcCore\IRequest::PARAM_TYPE_ANY;
	}
	
	/**
	 * @inheritDocs
	 * @param  string $name 
	 * @return int
	 */
	public function SetParamSourceType ($name, $sourceType = \MvcCore\IRequest::PARAM_TYPE_ANY) {
		if (!isset($this->params[$name])) return $this;
		unset(
			$this->paramsSources[\MvcCore\IRequest::PARAM_TYPE_QUERY_STRING][$name],
			$this->paramsSources[\MvcCore\IRequest::PARAM_TYPE_URL_REWRITE][$name]
		);
		if (($sourceType & \MvcCore\IRequest::PARAM_TYPE_QUERY_STRING) != 0)
			$this->paramsSources[\MvcCore\IRequest::PARAM_TYPE_QUERY_STRING][$name] = TRUE;
		if (($sourceType & \MvcCore\IRequest::PARAM_TYPE_URL_REWRITE) != 0)
			$this->paramsSources[\MvcCore\IRequest::PARAM_TYPE_URL_REWRITE][$name] = TRUE;
		return $this;
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
		if ($this->params === NULL) $this->initParams();
		// if there is no param - return false:
		if (!isset($this->params[$name])) return FALSE;
		// if there is not defined source type and param value exists, return true:
		if (!$sourceType) return TRUE;
		// if source type has query string flag and there is query string param type, return true:
		if (
			($sourceType & \MvcCore\IRequest::PARAM_TYPE_QUERY_STRING) != 0 &&
			isset($this->paramsSources[\MvcCore\IRequest::PARAM_TYPE_QUERY_STRING][$name])
		) return TRUE;
		// if source type has url rewrite flag and there is url rewrite param type, return true:
		if (
			($sourceType & \MvcCore\IRequest::PARAM_TYPE_URL_REWRITE) != 0 &&
			isset($this->paramsSources[\MvcCore\IRequest::PARAM_TYPE_URL_REWRITE][$name])
		) return TRUE;
		// if source type has input param flag and there is input param type, return true:
		if (
			($sourceType & \MvcCore\IRequest::PARAM_TYPE_INPUT) != 0 &&
			!isset($this->paramsSources[\MvcCore\IRequest::PARAM_TYPE_QUERY_STRING][$name]) &&
			!isset($this->paramsSources[\MvcCore\IRequest::PARAM_TYPE_URL_REWRITE][$name])
		) return TRUE;
		// if there is defined param sorce type and key is not set in proper collections, return false:
		return FALSE;
	}

	/**
	 * @inheritDocs
	 * @param  string $name
	 * @return \MvcCore\Request
	 */
	public function RemoveParam ($name) {
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
		$this->globalFiles = & $files;
		return $this;
	}

	/**
	 * @inheritDocs
	 * @return array
	 */
	public function & GetFiles () {
		return $this->globalFiles;
	}

	/**
	 * @inheritDocs
	 * @param  string $file Uploaded file string name.
	 * @param  array  $data
	 * @return \MvcCore\Request
	 */
	public function SetFile ($file = '', $data = []) {
		$this->globalFiles[$file] = $data;
		return $this;
	}

	/**
	 * @inheritDocs
	 * @param  string $file Uploaded file string name.
	 * @return array
	 */
	public function GetFile ($file = '') {
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
		return isset($this->globalFiles[$file]);
	}


	/**
	 * @inheritDocs
	 * @param  array $cookies
	 * @return \MvcCore\Request
	 */
	public function SetCookies (array & $cookies = []) {
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
		if ($rawValue === NULL) {
			// if there is NULL in target collection
			if ($targetType === NULL || $ifNullValue === NULL) return $ifNullValue;
			$result = is_scalar($ifNullValue) 
				? $ifNullValue 
				: clone $ifNullValue;
			settype($result, $targetType);
			return $result;
		} else {
			// if there is not NULL in target collection
			if (is_string($rawValue) && mb_strlen(trim($rawValue)) === 0) {
				// if value after trim is empty string, return NULL 
				// (or retyped if null value if necessary)
				$result = "";
				if ($targetType === NULL || $ifNullValue === NULL) return NULL;
				$result = is_scalar($ifNullValue) 
					? $ifNullValue 
					: clone $ifNullValue;
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