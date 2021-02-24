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

trait InternalInits {

	/**
	 * @inheritDocs
	 * @param  \string[] $languagesList
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
		/** @var $this \MvcCore\Request */
		$hostName = gethostname();
		$this->scheme = 'file:';
		$this->secure = FALSE;
		$this->hostName = $hostName;
		$this->host = $hostName;
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

		// sometimes `$_SERVER['SCRIPT_FILENAME']` is missing, when script
		// is running in CLI or it could have relative path only
		$backtraceItems = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
		$indexFilePath = str_replace('\\', '/', $backtraceItems[count($backtraceItems) - 1]['file']);
		$lastSlashPos = mb_strrpos($indexFilePath, '/');

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

	/**
	 * Initialize URI segments: port, path, query and fragment.
	 * @see https://en.wikipedia.org/wiki/Uniform_Resource_Identifier
	 * @see https://bugs.php.net/bug.php?id=73192
	 * @return void
	 */
	protected function initUrlSegments () {
		/** @var $this \MvcCore\Request */
		$this->portDefined = FALSE;
		$this->port = '';
		$this->path = '';
		$this->query = '';
		$this->fragment = '';
		
		$uri = $this->GetScheme() . '//'
			. $this->globalServer['HTTP_HOST'];
		if (isset($this->globalServer['UNENCODED_URL'])) {
			$uri .= rawurldecode($this->globalServer['UNENCODED_URL']);
		} else {
			$uri .= rawurldecode($this->globalServer['REQUEST_URI']);
		}
		
		
		$firstColonPos = FALSE;
		if (preg_match("#^([a-z]+):#", $uri)) 
			$firstColonPos = mb_strpos($uri, ':');
		if ($firstColonPos !== FALSE)
			$uri = mb_substr($uri, $firstColonPos + 1);
		if (preg_match("#^//#", $uri)) {
			$uriLen = mb_strlen($uri);
			$nextSlashPos = mb_strpos($uri, '/', 2) ?: $uriLen;
			$nextQmPos = mb_strpos($uri, '?', 2) ?: $uriLen;
			$nextHashPos = mb_strpos($uri, '#', 2) ?: $uriLen;
			$nextDelimPos = min($nextSlashPos, $nextQmPos, $nextHashPos);
			if ($nextDelimPos === $uriLen) return;
			$authority = mb_substr($uri, 2, $nextDelimPos - 2);
			$uri = mb_substr($uri, $nextDelimPos);
			$ipv6OpenPos = mb_strpos($authority, '[');
			$ipv6ClosePos = mb_strrpos($authority, ']');
			$uriPort = NULL;
			if ($ipv6OpenPos !== FALSE && $ipv6ClosePos !== FALSE) {
				$uriPort = mb_substr($authority, $ipv6ClosePos + 1);
			} else {
				$lastColonPos = mb_strrpos($authority, ':');
				if ($lastColonPos !== FALSE) 
					$uriPort = mb_substr($authority, $lastColonPos + 1);
			}
			if ($uriPort !== NULL && $uriPort !== '' && preg_match("#^\d+$#", $uriPort)) {
				$this->port = $uriPort;
				$this->portDefined = TRUE;
			}
		} else {
			$this->path = $uri;
			return;
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
			// path or no path, query and hash
			$this->path = mb_substr($uri, 0, $questionMarkPos);
			$this->query = trim(mb_substr($uri, $questionMarkPos + 1, $hashPos - $questionMarkPos - 1), '&');
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
		/** @var $this \MvcCore\Request */
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
		/** @var $this \MvcCore\Request */
		$params = array_merge($this->globalGet);
		$method = $this->GetMethod();
		if ($method == self::METHOD_POST || $method == self::METHOD_PUT) {
			$postValues = [];
			$contentValues = [];
			$postHasBeenSerialized = FALSE;
			if (count($this->globalPost) > 0) {
				$postValues = $this->globalPost;
				$postHasBeenSerialized = TRUE;
			}
			$contentType = $this->GetHeader('Content-Type', ' \-/;_=a-zA-Z0-9', '');
			$multiPartHeader = 'multipart/form-data';
			$multiPartContent = mb_strpos($contentType, $multiPartHeader) !== FALSE;
			// @see https://stackoverflow.com/a/37046109/7032987
			if (!$postHasBeenSerialized && !$multiPartContent) {
				if ($this->body === NULL)
					$this->initBody();
				$contentValues = $this->parseBodyParams($contentType);
			}
			$params = array_merge($params, $postValues, $contentValues);
		}
		$this->params = $params;
	}

	/**
	 * Read and return direct php `POST` input from `php://input` or `php://stdin`.
	 * @return void
	 */
	protected function initBody () {
		/** @var $this \MvcCore\Request */
		$this->body = file_get_contents($this->inputStream);
	}

	/**
	 * Parse direct PHP input (`php://input`) by Content-Type header.
	 * @param  string $contentType
	 * @return array
	 */
	protected function parseBodyParams ($contentType) {
		/** @var $this \MvcCore\Request */
		$result = [];
		$app = self::$app ?: (self::$app = \MvcCore\Application::GetInstance());
		$toolClass = $app->GetToolClass();

		$urlEncType = mb_strpos($contentType, 'application/x-www-form-urlencoded') !== FALSE;
		if ($urlEncType) {
			parse_str(trim($this->body, '&='), $result);
		} else {
			$jsonType = (
				mb_strpos($contentType, 'application/json') !== FALSE ||
				mb_strpos($contentType, 'text/javascript') !== FALSE ||
				mb_strpos($contentType, 'application/ld+json') !== FALSE
			);
			if ($jsonType) {
				try {
					$result = $toolClass::DecodeJson($this->body);
				} catch (\Exception $e) { // backward compatibility
				} catch (\Throwable $e) {
				}
			} else {
				// if content type header is not recognized,
				// try JSON decoding first, then fallback to query string:
				$probablyAJsonType = !$toolClass::IsQueryString($this->body);
				if ($probablyAJsonType) {
					try {
						$result = $toolClass::DecodeJson($this->body);
					} catch (\Exception $e) { // backward compatibility
						$probablyAJsonType = FALSE; // fall back to query string parsing
					} catch (\Throwable $e) {
						$probablyAJsonType = FALSE; // fall back to query string parsing
					}
				}
				if (!$probablyAJsonType)
					parse_str(trim($this->body, '&='), $result);
			}
		}
		return $result;
	}

	/**
	 * Get param value from given collection (`$_GET`, `$_POST`, `php://input` or http headers),
	 * filtered by characters defined in second argument through `preg_replace()`.
	 * Place into second argument only char groups you want to keep.
	 * @param  array             $paramsCollection        Array with request params or array with request headers.
	 * @param  string            $name                    Parameter string name.
	 * @param  string|array|bool $pregReplaceAllowedChars If String - list of regular expression characters to only keep, if array - `preg_replace()` pattern and reverse, if `FALSE`, raw value is returned.
	 * @param  mixed             $ifNullValue             Default value returned if given param name is null.
	 * @param  string            $targetType              Target type to retype param value or default if-null value. If param is an array, every param item will be retyped into given target type.
	 * @throws \InvalidArgumentException                  `$name` must be a `$targetType`, not an `array`.
	 * @return string|\string[]|int|\int[]|bool|\bool[]|array|mixed
	 */
	protected function getParamFromCollection (
		& $paramsCollection = [],
		$name = "",
		$pregReplaceAllowedChars = "a-zA-Z0-9_;, /\-\@\:",
		$ifNullValue = NULL,
		$targetType = NULL
	) {
		/** @var $this \MvcCore\Request */
		if (!isset($paramsCollection[$name])) return $ifNullValue;
		if (is_array($paramsCollection[$name])) {
			if ($targetType !== NULL) {
				$targetTypeBracketsPos = strpos($targetType, '[]');
				$targetTypeEndsWithBrackets = $targetTypeBracketsPos !== strlen($targetType) - 3;
				$targetTypeIsArray = $targetType == 'array';
				if (!$targetTypeEndsWithBrackets && !$targetTypeIsArray) 
					throw new \InvalidArgumentException(
					"Collection member `{$name}` is not an `array`."
				);
				if ($targetTypeEndsWithBrackets)
					$targetType = substr($targetType, 0, $targetTypeBracketsPos);
			}
			$result = [];
			$paramsCollectionArr = $paramsCollection[$name];
			foreach ($paramsCollectionArr as $key => $value) {
				$cleanedKey = is_numeric($key)
					? $key
					: $this->cleanParamValue($key, $pregReplaceAllowedChars);
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
		/** @var $this \MvcCore\Request */
		$this->basePath = '';
		$this->scriptName = str_replace('\\', '/', $this->globalServer['SCRIPT_NAME']);
		$lastSlashPos = mb_strrpos($this->scriptName, '/');
		if ($lastSlashPos !== 0) {
			// request uri is always there:
			$requestUri = rawurldecode($this->globalServer['REQUEST_URI']);
			$questionMarkPos = mb_strpos($requestUri, '?');
			if ($questionMarkPos !== FALSE) 
				$requestUri = mb_substr($requestUri, 0, $questionMarkPos);

			// try to complete redirected url if any:
			$redirectPath = isset($this->globalServer['REDIRECT_REDIRECT_PATH']) 
				? $this->globalServer['REDIRECT_REDIRECT_PATH'] 
				: (isset($this->globalServer['REDIRECT_PATH']) 
					? $this->globalServer['REDIRECT_PATH'] 
					: NULL
				);
			if ($redirectPath !== NULL) {
				// usually cases with script requests paths like `/index.php?action=submit`
				$redirectPath = rawurldecode($redirectPath);
				$redirectUrl = $redirectPath . $requestUri;
			} else {
				$redirectUrl = isset($this->globalServer['REDIRECT_URL']) 
					? $this->globalServer['REDIRECT_URL'] 
					: '';
			}
			$redirectUrlLength = mb_strlen($redirectUrl);
			
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
	 * @return void
	 */
	protected function initLangAndLocale () {
		/** @var $this \MvcCore\Request */
		if (!isset($this->globalServer['HTTP_ACCEPT_LANGUAGE'])) {
			$this->lang = '';
			$this->locale = '';
			return;
		}
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
		if (!isset($langAndLocaleArr[1])) $langAndLocaleArr[1] = '';
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
		/** @var $this \MvcCore\Request */
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
