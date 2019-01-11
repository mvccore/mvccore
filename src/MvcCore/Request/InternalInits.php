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

trait InternalInits
{
	/**
	 * Parse list of comma separated language tags and sort it by the
	 * quality value from `$this->globalServer['HTTP_ACCEPT_LANGUAGE']`.
	 * @param string[] $languagesList
	 * @return array
	 */
	public static function ParseHttpAcceptLang ($languagesList) {
		$languages = [];
		$languageRanges = explode(',', trim($languagesList));
		foreach ($languageRanges as $languageRange) {
			$regExpResult = preg_match(
				"/(\*|[a-zA-Z0-9]{1,8}(?:-[a-zA-Z0-9]{1,8})*)(?:\s*;\s*q\s*=\s*(0(?:\.\d{0,3})|1(?:\.0{0,3})))?/",
				trim($languageRange),
				$match
			);
			if ($regExpResult) {
				$priority = isset($match[2])
					? (string) floatval($match[2])
					: '1.0';
				if (!isset($languages[$priority])) $languages[$priority] = [];
				$langOrLangWithLocale = str_replace('-', '_', $match[1]);
				$delimiterPos = strpos($langOrLangWithLocale, '_');
				if ($delimiterPos !== FALSE) {
					$languages[$priority][] = [
						strtolower(substr($langOrLangWithLocale, 0, $delimiterPos)),
						strtoupper(substr($langOrLangWithLocale, $delimiterPos + 1))
					];
				} else {
					$languages[$priority][] = [
						strtolower($langOrLangWithLocale),
						NULL
					];
				}
			}
		}
		krsort($languages);
		reset($languages);
		return $languages;
	}

	/**
	 * If request is processed via CLI, initialize most of request properties 
	 * with empty values and parse CLI params into params array.
	 * @return void
	 */
	protected function initCli () {
		$this->phpSapi = php_sapi_name();
		$phpSapiCHasCli = FALSE;
		if (substr($this->phpSapi, 0, 3) === 'cli') {
			$this->phpSapi = 'cli';
			$phpSapiCHasCli = TRUE;
		}
		$this->cli = FALSE;
		if ($phpSapiCHasCli && !isset($this->globalServer['REQUEST_URI'])) {
			$this->cli = TRUE;
			
			$lh = 'localhost';
			$this->scheme = 'file:';
			$this->secure = FALSE;
			$this->hostName = $lh;
			$this->host = $lh;
			$this->port = '';
			$this->path = '';
			$this->query = '';
			$this->fragment = '';
			$this->ajax = FALSE;

			$this->basePath = '';
			$this->requestPath = '';
			$this->domainUrl = '';
			$this->baseUrl = '';
			$this->requestUrl = '';
			$this->fullUrl = '';
			$this->referer = '';
			$this->serverIp = '127.0.0.1';
			$this->clientIp = $this->serverIp;
			$this->contentLength = 0;
			$this->headers = [];
			$this->params = [];
			$this->appRequest = FALSE;

			$this->method = 'GET';

			if (isset($this->globalServer['SCRIPT_FILENAME'])) {
				$indexFilePath = ucfirst(str_replace(['\\', '//'], '/', $this->globalServer['SCRIPT_FILENAME']));
			} else {
				// sometimes `SCRIPT_FILENAME` is missing, when script is running in CLI
				$backtraceItems = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
				$indexFilePath = str_replace('\\', '/', $backtraceItems[count($backtraceItems) - 1]['file']);
			}
			$lastSlashPos = mb_strrpos($indexFilePath, '/');
			if ($lastSlashPos === FALSE) $lastSlashPos = 0;

			$this->appRoot = mb_substr($indexFilePath, 0, $lastSlashPos);
			$this->scriptName = mb_substr($indexFilePath, $lastSlashPos);

			$args = $this->globalServer['argv'];
			array_shift($args);
			$params = [];
			if ($args) {
				foreach ($args as $arg) {
					parse_str($arg, $paramsLocal);
					if (!$paramsLocal) continue;
					foreach ($paramsLocal as $paramName => $paramValue) {
						if (is_array($paramValue)) {
							$params = array_merge(
								$params, 
								[$paramName => array_merge(
									$params[$paramName] ?: [], $paramValue
								)]
							);
						} else {
							$params[$paramName] = $paramValue;
						}
					}
				}
			}
			$this->params = $params;
			$this->globalGet = $params;
		}
	}

	/**
	 * Initialize URI segments parsed by `parse_url()`
	 * php method: port, path, query and fragment.
	 * @return void
	 */
	protected function initUrlSegments () {
		$this->portDefined = FALSE;
		$this->port = '';
		$this->path = '';
		$this->query = '';
		$this->fragment = '';

		$uri = $this->GetScheme() . '//'
			. $this->globalServer['HTTP_HOST'];
		if (isset($this->globalServer['UNENCODED_URL'])) {
			$uri .= rawurldecode($this->globalServer['UNENCODED_URL']);
		} else if (isset($this->globalServer['UNENCODED_URL'])) {
			$uri .= rawurldecode($this->globalServer['UNENCODED_URL']);
		} else {
			$uri .= rawurldecode($this->globalServer['REQUEST_URI']);
		}

		$firstColonPos = mb_strpos($uri, ':');
		if ($firstColonPos !== FALSE) 
			$uri = mb_substr($uri, $firstColonPos + 1);
		
		if (mb_substr($uri, 0, 2) === '//') {
			$nextSlashPos = mb_strpos($uri, '/', 2);
			if ($nextSlashPos !== FALSE) {
				$authority = mb_substr($uri, 2, $nextSlashPos - 2);
				$uri = mb_substr($uri, $nextSlashPos);
				$colonsCount = mb_substr_count($authority, ':');
				if ($colonsCount === 1) {
					$colonPos = mb_strpos($authority, ':');
					$this->port = mb_substr($authority, $colonPos + 1);
					if ($this->port !== '') 
						$this->portDefined = TRUE;
				}
			} else {
				return;
			}
		}

		$basePath = $this->GetBasePath();
		$uri = mb_substr($uri, mb_strlen($basePath));
		
		$questionMarkPos = mb_strpos($uri, '?');
		$hashPos = mb_strpos($uri, '#');
		$questionMarkContained = $questionMarkPos !== FALSE;
		$hashContained = $hashPos !== FALSE;
		if (!$questionMarkContained && !$hashContained) {
			// path, no query, no hash
			$this->path = $uri;
		} else if ($questionMarkContained && !$hashContained) {
			// path, query and no hash
			$this->path = mb_substr($uri, 0, $questionMarkPos);
			$this->query = trim(mb_substr($uri, $questionMarkPos + 1), '&');
		} else if (!$questionMarkContained && $hashContained) {
			// path, no query and hash
			$this->path = mb_substr($uri, 0, $hashPos);
			$this->fragment = mb_substr($uri, $hashPos + 1);
		} else if ($questionMarkContained && $hashContained && $questionMarkPos < $hashPos) {
			// path, query and hash
			$this->path = mb_substr($uri, 0, $questionMarkPos);
			$this->query = trim(mb_substr($uri, $questionMarkPos + 1, $questionMarkPos + 1 - $hashPos), '&');
			$this->fragment = mb_substr($uri, $hashPos + 1);
		} else {
			// path, no query and hash containing question mark
			$this->path = mb_substr($uri, 0, $questionMarkPos);
			$this->fragment = mb_substr($uri, $hashPos + 1);
		}
	}

	/**
	 * Init raw http headers by `getallheaders()` or from `$_SERVER['HTTP_...']`.
	 * Headers has to be `key => value` array, headers keys in standard format
	 * like: `"Content-Type" | "Content-Length" | "X-Requested-With" ...`.
	 * @return void
	 */
	protected function initHeaders () {
		if (function_exists('getallheaders')) {
			$headers = getallheaders();
		} else {
			$headers = [];
			foreach ($this->globalServer as $name => $value) {
				if (substr($name, 0, 5) == 'HTTP_') {
					$headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
				} else if ($name == "CONTENT_TYPE") {
					$headers["Content-Type"] = $value;
				} else if ($name == "CONTENT_LENGTH") {
					$headers["Content-Length"] = $value;
				}
			}
		}
		$this->headers = $headers;
	}

	/**
	 * Initialize params from global `$_GET` and (global `$_POST` or direct `php://input`).
	 * @return void
	 */
	protected function initParams () {
		$params = array_merge($this->globalGet);
		if ($this->GetMethod() == self::METHOD_POST) {
			$postValues = [];
			if (count($this->globalPost) > 0) {
				$postValues = $this->globalPost;
			} else {
				$postValues = $this->initParamsCompletePostData();
			}
			$params = array_merge($params, $postValues);
		}
		$this->params = $params;
	}

	/**
	 * Read and return direct php `POST` input from `php://input`.
	 * @return array
	 */
	protected function initParamsCompletePostData () {
		$result = [];
		$rawPhpInput = file_get_contents('php://input');
		$decodedJsonResult = \MvcCore\Tool::DecodeJson($rawPhpInput);
		if ($decodedJsonResult->success) {
			$result = (array) $decodedJsonResult->data;
		} else {
			$rows = explode('&', $rawPhpInput);
			foreach ($rows as $row) {
				list($key, $value) = explode('=', $row);
				$result[$key] = $value;
			}
		}
		return $result;
	}

	/**
	 * Get param value from given collection (`$_GET`, `$_POST`, `php://input` or http headers),
	 * filtered by characters defined in second argument through `preg_replace()`.
	 * Place into second argument only char groups you want to keep.
	 * @param array $collection Array with request params or array with request headers.
	 * @param string $name Parameter string name.
	 * @param string|array|bool $pregReplaceAllowedChars If String - list of regular expression characters to only keep, if array - `preg_replace()` pattern and reverse, if `FALSE`, raw value is returned.
	 * @param mixed $ifNullValue Default value returned if given param name is null.
	 * @param string $targetType Target type to retype param value or default if-null value. If param is an array, every param item will be retyped into given target type.
	 * @return string|string[]|mixed
	 */
	protected function getParamFromCollection (
		& $paramsCollection = [],
		$name = "",
		$pregReplaceAllowedChars = "a-zA-Z0-9_;, /\-\@\:",
		$ifNullValue = NULL,
		$targetType = NULL
	) {
		if (!isset($paramsCollection[$name])) return $ifNullValue;
		if (is_array($paramsCollection[$name])) {
			$result = [];
			$paramsCollection = $paramsCollection[$name];
			foreach ($paramsCollection as $key => & $value) {
				$cleanedKey = $this->cleanParamValue($key, $pregReplaceAllowedChars);
				$result[$cleanedKey] = $this->getParamItem(
					$value, $pregReplaceAllowedChars, $ifNullValue, $targetType
				);
			}
			return $result;
		} else {
			return $this->getParamItem(
				$paramsCollection[$name], $pregReplaceAllowedChars, $ifNullValue, $targetType
			);
		}
	}

	/**
	 * Init script name from `$_SERVER['SCRIPT_NAME']` and request base path.
	 * @return void
	 */
	protected function initScriptNameAndBasePath () {
		$this->basePath = '';
		$this->scriptName = str_replace('\\', '/', $this->globalServer['SCRIPT_NAME']);
		$lastSlashPos = mb_strrpos($this->scriptName, '/');
		if ($lastSlashPos !== 0) {
			$redirectUrl = rawurldecode(isset($this->globalServer['REDIRECT_URL']) ? $this->globalServer['REDIRECT_URL'] : '');
			$redirectUrlLength = mb_strlen($redirectUrl);
			$requestUri = rawurldecode($this->globalServer['REQUEST_URI']);
			$questionMarkPos = mb_strpos($requestUri, '?');
			if ($questionMarkPos !== FALSE) $requestUri = mb_substr($requestUri, 0, $questionMarkPos);
			if ($redirectUrlLength === 0 || ($redirectUrlLength > 0 && $redirectUrl === $requestUri)) {
				$this->basePath = mb_substr($this->scriptName, 0, $lastSlashPos);
				$this->scriptName = '/' . mb_substr($this->scriptName, $lastSlashPos + 1);
			} else {
				// request was redirected by Apache `mod_rewrite` with `DPI` flag:
				$requestUriPosInRedirectUri = mb_strrpos($redirectUrl, $requestUri);
				$apacheRedirectedPath = mb_substr($redirectUrl, 0, $requestUriPosInRedirectUri);
				$this->scriptName = mb_substr($this->scriptName, mb_strlen($apacheRedirectedPath));
				$lastSlashPos = mb_strrpos($this->scriptName, '/');
				$this->basePath = mb_substr($this->scriptName, 0, $lastSlashPos);
			}
		} else {
			$this->scriptName = '/' . mb_substr($this->scriptName, $lastSlashPos + 1);
		}
	}

	/**
	 * Initialize language code and locale code from global `$_SERVER['HTTP_ACCEPT_LANGUAGE']`
	 * if any, by `Intl` extension function `locale_accept_from_http()` or by custom parsing.
	 */
	protected function initLangAndLocale () {
		$rawUaLanguages = $this->globalServer['HTTP_ACCEPT_LANGUAGE'];
		if (extension_loaded('Intl')) {
			$langAndLocaleStr = \locale_accept_from_http($rawUaLanguages);
			$langAndLocaleArr = $langAndLocaleStr !== NULL
				? explode('_', $langAndLocaleStr)
				: [NULL, NULL];
		} else {
			$languagesAndLocales = static::ParseHttpAcceptLang($rawUaLanguages);
			$langAndLocaleArr = current($languagesAndLocales);
			if (is_array($langAndLocaleArr)) 
				$langAndLocaleArr = current($langAndLocaleArr);
		}
		if ($langAndLocaleArr[0] === NULL) $langAndLocaleArr[0] = '';
		if (count($langAndLocaleArr) > 1 && $langAndLocaleArr[1] === NULL) $langAndLocaleArr[1] = '';
		list($this->lang, $this->locale) = $langAndLocaleArr;
	}

	/**
	 * Initialize domain parts from server name property.
	 * If you need to add exceptional top-level domain names, use method
	 * `\MvcCore\Request::AddTwoSegmentTlds('co.uk');`
	 * Example: 
	 * `'any.content.example.co.uk' => ['any.content', 'example', 'co.uk']`
	 * @return void
	 */
	protected function initDomainSegments () {
		$hostName = $this->GetHostName();
		$this->domainParts = [];
		$lastDotPos = mb_strrpos($hostName, '.');
		if ($lastDotPos === FALSE) {
			$this->domainParts = [NULL, NULL, $hostName];
		} else {
			$first = mb_substr($hostName, $lastDotPos + 1);
			$second = mb_substr($hostName, 0, $lastDotPos);
			// check co.uk and other...
			if (self::$twoSegmentTlds) {
				$lastDotPos = mb_strrpos($second, '.');
				if ($lastDotPos !== FALSE) {
					$firstTmp = mb_substr($second, $lastDotPos + 1) . '.' . $first;
					if (isset(self::$twoSegmentTlds[$firstTmp])) {
						$first = $firstTmp;
						$second = $firstTmp = mb_substr($second, 0, $lastDotPos);
					}
				}
			}
			$lastDotPos = mb_strrpos($second, '.');
			if ($lastDotPos === FALSE) {
				$this->domainParts = [NULL, $second, $first];
			} else {
				$third = mb_substr($second, 0, $lastDotPos);
				$second = mb_substr($second, $lastDotPos + 1);
				$this->domainParts = [$third, $second, $first];
			}
		}
	}
}
