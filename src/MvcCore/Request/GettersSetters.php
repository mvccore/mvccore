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
trait GettersSetters {

	/**
	 * @inheritDocs
	 * @param  \string[] $twoSegmentTlds,... List of two-segment top-level domains without leading dot.
	 * @return void
	 */
	public static function AddTwoSegmentTlds ($twoSegmentTlds) {
		$tlds = func_get_args();
		if (count($tlds) === 1 && is_array($tlds[0])) $tlds = $tlds[0];
		foreach ($tlds as $tld) self::$twoSegmentTlds[$tld] = TRUE;
	}

	/**
	 * @inheritDocs
	 * @return bool
	 */
	public function IsInternalRequest () {
		if ($this->appRequest === NULL) {
			try {
				$ctrl = $this->GetControllerName();
				$action = $this->GetActionName();
			} catch (\Exception $e) { // backward compatibility
				$ctrl = NULL;
				$action = NULL;
			} catch (\Throwable $e) {
				$ctrl = NULL;
				$action = NULL;
			}
			if ($ctrl !== NULL && $action !== NULL) {
				$this->appRequest = FALSE;
				if ($ctrl === 'controller' && $action === 'asset')
					$this->appRequest = TRUE;
			}
		}
		return $this->appRequest;
	}

	/**
	 * @inheritDocs
	 * @param  string $controllerName
	 * @return \MvcCore\Request
	 */
	public function SetControllerName ($controllerName) {
		$this->controllerName = $controllerName;
		$routerClass = self::$routerClass;
		$router = $routerClass::GetInstance();
		$this->params[$router::URL_PARAM_CONTROLLER] = $controllerName;
		return $this;
	}

	/**
	 * @inheritDocs
	 * @return string
	 */
	public function GetControllerName () {
		if ($this->controllerName === NULL) {
			$routerClass = self::$routerClass;
			$router = $routerClass::GetInstance();
			if (isset($this->globalGet[$router::URL_PARAM_CONTROLLER]))
				$this->controllerName = $this->GetParam($router::URL_PARAM_CONTROLLER, 'a-zA-Z0-9\-_/', '', 'string');
		}
		return $this->controllerName;
	}

	/**
	 * @inheritDocs
	 * @param  string $actionName
	 * @return \MvcCore\Request
	 */
	public function SetActionName ($actionName) {
		$this->actionName = $actionName;
		$routerClass = self::$routerClass;
		$router = $routerClass::GetInstance();
		$this->params[$router::URL_PARAM_ACTION] = $actionName;
		return $this;
	}

	/**
	 * @inheritDocs
	 * @return string
	 */
	public function GetActionName () {
		if ($this->actionName === NULL) {
			$routerClass = self::$routerClass;
			$router = $routerClass::GetInstance();
			if (isset($this->globalGet[$router::URL_PARAM_ACTION]))
				$this->actionName = $this->GetParam($router::URL_PARAM_ACTION, 'a-zA-Z0-9\-_', '', 'string');
		}
		return $this->actionName;
	}

	/**
	 * @inheritDocs
	 * @return bool
	 */
	public function IsCli () {
		return $this->cli;
	}

	/**
	 * @inheritDocs
	 * @param  string|NULL $lang
	 * @return \MvcCore\Request
	 */
	public function SetLang ($lang) {
		$this->lang = $lang;
		return $this;
	}

	/**
	 * @inheritDocs
	 * @return string|NULL
	 */
	public function GetLang () {
		if ($this->lang === NULL) $this->initLangAndLocale();
		return $this->lang;
	}

	/**
	 * @inheritDocs
	 * @param  string|NULL $locale
	 * @return \MvcCore\Request
	 */
	public function SetLocale ($locale) {
		$this->locale = $locale;
		return $this;
	}

	/**
	 * @inheritDocs
	 * @return string|NULL
	 */
	public function GetLocale () {
		if ($this->locale === NULL) $this->initLangAndLocale();
		return $this->locale;
	}

	/**
	 * @inheritDocs
	 * @param  string|NULL $mediaSiteVersion
	 * @return \MvcCore\Request
	 */
	public function SetMediaSiteVersion ($mediaSiteVersion) {
		$this->mediaSiteVersion = $mediaSiteVersion;
		return $this;
	}

	/**
	 * @inheritDocs
	 * @return string|NULL
	 */
	public function GetMediaSiteVersion () {
		return $this->mediaSiteVersion;
	}


	/**
	 * @inheritDocs
	 * @param  string $rawName
	 * @param  array  $arguments
	 * @throws \InvalidArgumentException
	 * @return mixed|\MvcCore\Request
	 */
	public function __call ($rawName, $arguments = []) {
		$nameBegin = strtolower(substr($rawName, 0, 3));
		$name = substr($rawName, 3);
		$lcName = lcfirst($name);
		if ($nameBegin == 'get') {
			if (property_exists($this, $lcName)) 
				return $this->{$lcName};
			if (property_exists($this, $name)) 
				return $this->$name;
			return NULL;
		} else if ($nameBegin == 'set') {
			$value = isset($arguments[0]) ? $arguments[0] : NULL;
			if (property_exists($this, $name)) {
				$this->{$name} = $value;
			} else {
				$this->{$lcName} = $value;
			}
			return $this;
		} else {
			throw new \InvalidArgumentException("[".get_class()."] No method `{$rawName}()` defined.");
		}
	}

	/**
	 * @inheritDocs
	 * @param  string $name
	 * @return mixed
	 */
	public function __get ($name) {
		$lcPropName = lcfirst($name);
		if (isset($this->{$lcPropName}))
			return $this->{$lcPropName};
		if (isset($this->{$name}))
			return $this->{$name};
		return NULL;
	}

	/**
	 * @inheritDocs
	 * @param  string $name
	 * @param  mixed  $value
	 * @return \MvcCore\Request
	 */
	public function __set ($name, $value) {
		$lcPropName = lcfirst($name);
		if (property_exists($this, $lcPropName))
			return $this->{$lcPropName} = $value;
		return $this->{$name} = $value;
	}

	
	/**
	 * @inheritDocs
	 * @return string
	 */
	public function GetScriptName () {
		if ($this->scriptName === NULL) $this->initScriptNameAndBasePath();
		return $this->scriptName;
	}
	
	/**
	 * @inheritDocs
	 * @param  string $scriptName
	 * @return \MvcCore\Request
	 */
	public function SetScriptName ($scriptName) {
		$this->scriptName = $scriptName;
		return $this;
	}
	
	/**
	 * @inheritDocs
	 * @return string
	 */
	public function GetAppRoot () {
		if ($this->appRoot === NULL) 
			$this->appRoot = defined('MVCCORE_APP_ROOT')
				? constant('MVCCORE_APP_ROOT')
				: $this->GetDocumentRoot();
		return $this->appRoot;
	}
	
	/**
	 * @inheritDocs
	 * @param  string $appRoot
	 * @return \MvcCore\Request
	 */
	public function SetAppRoot ($appRoot) {
		$this->appRoot = $appRoot;
		return $this;
	}
	
	/**
	 * @inheritDocs
	 * @return string
	 */
	public function GetDocumentRoot () {
		if ($this->documentRoot === NULL) {
			if (defined('MVCCORE_DOCUMENT_ROOT')) {
				$this->documentRoot = constant('MVCCORE_DOCUMENT_ROOT');
			} else {
				// `ucfirst()` - cause IIS has lower case drive name here - different from __DIR__ value
				$indexFilePath = ucfirst(str_replace(
					['\\', '//'], '/', 
					$this->globalServer['SCRIPT_FILENAME']
				));
				$this->documentRoot = strlen(\Phar::running()) > 0 
					? 'phar://' . $indexFilePath
					: dirname($indexFilePath);
			}
		}
		return $this->documentRoot;
	}
	
	/**
	 * @inheritDocs
	 * @param  string $documentRoot
	 * @return \MvcCore\Request
	 */
	public function SetDocumentRoot ($documentRoot) {
		$this->documentRoot = $documentRoot;
		return $this;
	}

	/**
	 * @inheritDocs
	 * @param  string $rawMethod
	 * @return \MvcCore\Request
	 */
	public function SetMethod ($rawMethod) {
		$this->method = $rawMethod;
		return $this;
	}

	/**
	 * @inheritDocs
	 * @return string
	 */
	public function GetMethod () {
		if ($this->method === NULL) {
			$this->method = strtoupper($this->globalServer['REQUEST_METHOD']);
		}
		return $this->method;
	}

	/**
	 * @inheritDocs
	 * @param  string $rawBasePath
	 * @return \MvcCore\Request
	 */
	public function SetBasePath ($rawBasePath) {
		$this->basePath = $rawBasePath;
		$this->baseUrl = NULL;
		$this->requestUrl = NULL;
		$this->fullUrl = NULL;
		return $this;
	}

	/**
	 * @inheritDocs
	 * @return string
	 */
	public function GetBasePath () {
		if ($this->basePath === NULL) $this->initScriptNameAndBasePath();
		return $this->basePath;
	}

	/**
	 * @inheritDocs
	 * @param  string $rawProtocol
	 * @return \MvcCore\Request
	 */
	public function SetScheme ($rawProtocol) {
		$this->scheme = $rawProtocol;
		$this->domainUrl = NULL;
		$this->baseUrl = NULL;
		$this->requestUrl = NULL;
		$this->fullUrl = NULL;
		return $this;
	}

	/**
	 * @inheritDocs
	 * @return string
	 */
	public function GetScheme () {
		if ($this->scheme === NULL) {
			$this->scheme = (
				(isset($this->globalServer['HTTPS']) && strtolower($this->globalServer['HTTPS']) == 'on') ||
				$this->globalServer['SERVER_PORT'] == 443
			)
				? static::SCHEME_HTTPS
				: static::SCHEME_HTTP;
		}
		return $this->scheme;
	}

	/**
	 * @inheritDocs
	 * @return bool
	 */
	public function IsSecure () {
		if ($this->secure === NULL)
			$this->secure = in_array($this->GetScheme(), [
				static::SCHEME_HTTPS,
				static::SCHEME_FTPS,
				static::SCHEME_IRCS,
				static::SCHEME_SSH
			], TRUE);
		return $this->secure;
	}

	/**
	 * @inheritDocs
	 * @param  bool $rawInput Get raw input if `TRUE`. `FALSE` by default to get value through `htmlspecialchars($result);` without ampersand `&` escaping.
	 * @return string
	 */
	public function GetReferer ($rawInput = FALSE) {
		if ($this->referer === NULL) {
			$referer = isset($this->globalServer['HTTP_REFERER'])
				? $this->globalServer['HTTP_REFERER']
				: '';
			if ($referer) 
				while (preg_match("#%([0-9a-zA-Z]{2})#", $referer))
					$referer = rawurldecode($referer);
				$referer = str_replace('%', '%25', $referer);
			$this->referer = $referer;
		}
		return $rawInput ? $this->referer : static::HtmlSpecialChars($this->referer);
	}

	/**
	 * @inheritDocs
	 * @return float
	 */
	public function GetStartTime () {
		if ($this->microtime === NULL) $this->microtime = $this->globalServer['REQUEST_TIME_FLOAT'];
		return $this->microtime;
	}

	/**
	 * @inheritDocs
	 * @param  string|NULL $topLevelDomain
	 * @return \MvcCore\Request
	 */
	public function SetTopLevelDomain ($topLevelDomain) {
		if ($this->domainParts === NULL) $this->initDomainSegments();
		$this->domainParts[2] = $topLevelDomain;
		$this->hostName = trim(implode('.', $this->domainParts), '.');
		if ($this->hostName && $this->portDefined)
			$this->host = $this->hostName . ':' . $this->port;
		$this->domainUrl = NULL;
		$this->baseUrl = NULL;
		$this->requestUrl = NULL;
		$this->fullUrl = NULL;
		return $this;
	}

	/**
	 * @inheritDocs
	 * @return string|NULL
	 */
	public function GetTopLevelDomain () {
		if ($this->domainParts === NULL) $this->initDomainSegments();
		return $this->domainParts[2];
	}

	/**
	 * @inheritDocs
	 * @param  string|NULL $secondLevelDomain
	 * @return \MvcCore\Request
	 */
	public function SetSecondLevelDomain ($secondLevelDomain) {
		if ($this->domainParts === NULL) $this->initDomainSegments();
		$this->domainParts[1] = $secondLevelDomain;
		$this->hostName = trim(implode('.', $this->domainParts), '.');
		if ($this->hostName && $this->portDefined)
			$this->host = $this->hostName . ':' . $this->port;
		$this->domainUrl = NULL;
		$this->baseUrl = NULL;
		$this->requestUrl = NULL;
		$this->fullUrl = NULL;
		return $this;
	}

	/**
	 * @inheritDocs
	 * @return string|NULL
	 */
	public function GetSecondLevelDomain () {
		if ($this->domainParts === NULL) $this->initDomainSegments();
		return isset($this->domainParts[1]) ? $this->domainParts[1] : NULL;
	}

	/**
	 * @inheritDocs
	 * @param  string|NULL $thirdLevelDomain
	 * @return \MvcCore\Request
	 */
	public function SetThirdLevelDomain ($thirdLevelDomain) {
		if ($this->domainParts === NULL) $this->initDomainSegments();
		$this->domainParts[0] = $thirdLevelDomain;
		$this->hostName = trim(implode('.', $this->domainParts), '.');
		if ($this->hostName && $this->portDefined)
			$this->host = $this->hostName . ':' . $this->port;
		$this->domainUrl = NULL;
		$this->baseUrl = NULL;
		$this->requestUrl = NULL;
		$this->fullUrl = NULL;
		return $this;
	}

	/**
	 * @inheritDocs
	 * @return string|NULL
	 */
	public function GetThirdLevelDomain () {
		if ($this->domainParts === NULL) $this->initDomainSegments();
		return isset($this->domainParts[0]) ? $this->domainParts[0] : NULL;
	}

	/**
	 * @inheritDocs
	 * @param  string $rawHostName
	 * @return \MvcCore\Request
	 */
	public function SetHostName ($rawHostName) {
		if ($this->hostName !== $rawHostName) $this->domainParts = NULL;
		$this->hostName = $rawHostName;
		$this->domainUrl = NULL;
		$this->baseUrl = NULL;
		$this->requestUrl = NULL;
		$this->fullUrl = NULL;
		if ($rawHostName && $this->portDefined)
			$this->host = $rawHostName . ':' . $this->port;
		return $this;
	}

	/**
	 * @inheritDocs
	 * @return string
	 */
	public function GetHostName () {
		if ($this->hostName === NULL)
			$this->hostName = $this->globalServer['SERVER_NAME'];
		return $this->hostName;
	}

	/**
	 * @inheritDocs
	 * @param  string $rawHost
	 * @return \MvcCore\Request
	 */
	public function SetHost ($rawHost) {
		$this->host = $rawHost;
		$this->domainUrl = NULL;
		$this->baseUrl = NULL;
		$this->requestUrl = NULL;
		$this->fullUrl = NULL;
		$doubleDotPos = mb_strpos($rawHost, ':');
		if ($doubleDotPos !== FALSE) {
			$hostName = mb_substr($rawHost, 0, $doubleDotPos);
			$this->SetPort(mb_substr($rawHost, $doubleDotPos + 1));
		} else {
			$hostName = $rawHost;
			$this->port = '';
			$this->portDefined = FALSE;
		}
		return $this->SetHostName($hostName);
	}

	/**
	 * @inheritDocs
	 * @return string
	 */
	public function GetHost () {
		if ($this->host === NULL) $this->host = $this->globalServer['HTTP_HOST'];
		return $this->host;
	}

	/**
	 * @inheritDocs
	 * @param  string $rawPort
	 * @return \MvcCore\Request
	 */
	public function SetPort ($rawPort) {
		$this->port = $rawPort;
		$this->domainUrl = NULL;
		$this->baseUrl = NULL;
		$this->requestUrl = NULL;
		$this->fullUrl = NULL;
		if (strlen($rawPort) > 0) {
			$this->host = $this->hostName . ':' . $rawPort;
			$this->portDefined = TRUE;
		} else {
			$this->host = $this->hostName;
			$this->portDefined = FALSE;
		}
		return $this;
	}

	/**
	 * @inheritDocs
	 * @return string
	 */
	public function GetPort () {
		if ($this->port === NULL) $this->initUrlSegments();
		return $this->port;
	}

	/**
	 * @inheritDocs
	 * @param  string $rawPathValue
	 * @return \MvcCore\Request
	 */
	public function SetPath ($rawPathValue) {
		$this->path = $rawPathValue;
		$this->requestUrl = NULL;
		$this->requestPath = NULL;
		$this->fullUrl = NULL;
		return $this;
	}

	/**
	 * @inheritDocs
	 * @param  bool $rawInput Get raw input if `TRUE`. `FALSE` by default to get value through `htmlspecialchars($result);` without ampersand `&` escaping.
	 * @return string
	 */
	public function GetPath ($rawInput = FALSE) {
		if ($this->path === NULL)
			$this->initUrlSegments();
		return $rawInput ? $this->path : static::HtmlSpecialChars($this->path);
	}

	/**
	 * @inheritDocs
	 * @param  string $rawQuery
	 * @return \MvcCore\Request
	 */
	public function SetQuery ($rawQuery) {
		$this->query = ltrim($rawQuery, '?');
		$this->fullUrl = NULL;
		$this->requestPath = NULL;
		return $this;
	}

	/**
	 * @inheritDocs
	 * @param  bool   $withQuestionMark
	 *                If `FALSE` (by default), query string is returned always without 
	 *                question mark character at the beginning. If `TRUE`, and query 
	 *                string contains any character(s), query string is returned with 
	 *                question mark character at the beginning. But if query string 
	 *                contains no character(s), query string is returned as EMPTY STRING 
	 *                WITHOUT question mark character.
	 * @param  bool   $rawInput 
	 *                Get raw input if `TRUE`. `FALSE` by default to get value 
	 *                through `htmlspecialchars($result);` without ampersand `&` escaping.
	 * @return string
	 */
	public function GetQuery ($withQuestionMark = FALSE, $rawInput = FALSE) {
		if ($this->query === NULL)
			$this->initUrlSegments();
		$result = ($withQuestionMark && mb_strlen($this->query) > 0)
			? '?' . $this->query
			: $this->query;
		return $rawInput ? $result : static::HtmlSpecialChars($result);
	}

	/**
	 * @inheritDocs
	 * @param  bool $rawInput Get raw input if `TRUE`. `FALSE` by default to get value through `htmlspecialchars($result);` without ampersand `&` escaping.
	 * @return string
	 */
	public function GetRequestPath ($rawInput = FALSE) {
		if ($this->requestPath === NULL) {
			$this->requestPath = $this->GetPath(TRUE) . $this->GetQuery(TRUE, TRUE) . $this->GetFragment(TRUE, TRUE);
		}
		return $rawInput ? $this->requestPath : static::HtmlSpecialChars($this->requestPath);
	}

	/**
	 * @inheritDocs
	 * @return string
	 */
	public function GetDomainUrl () {
		if ($this->domainUrl === NULL)
			$this->domainUrl = $this->GetScheme() . '//' . $this->GetHost();
		return $this->domainUrl;
	}

	/**
	 * @inheritDocs
	 * @return string
	 */
	public function GetBaseUrl () {
		if ($this->baseUrl === NULL)
			$this->baseUrl = $this->GetDomainUrl() . $this->GetBasePath();
		return $this->baseUrl;
	}

	/**
	 * @inheritDocs
	 * @param  bool $rawInput Get raw input if `TRUE`. `FALSE` by default to get value through `htmlspecialchars($result);` without ampersand `&` escaping.
	 * @return string
	 */
	public function GetRequestUrl ($rawInput = FALSE) {
		if ($this->requestUrl === NULL)
			$this->requestUrl = $this->GetBaseUrl() . $this->GetPath(TRUE);
		return $rawInput ? $this->requestUrl : static::HtmlSpecialChars($this->requestUrl);
	}

	/**
	 * @inheritDocs
	 * @param  bool $rawInput Get raw input if `TRUE`. `FALSE` by default to get value through `htmlspecialchars($result);` without ampersand `&` escaping.
	 * @return string
	 */
	public function GetFullUrl ($rawInput = FALSE) {
		if ($this->fullUrl === NULL)
			$this->fullUrl = $this->GetRequestUrl(TRUE) . $this->GetQuery(TRUE, TRUE) . $this->GetFragment(TRUE, TRUE);
		return $rawInput ? $this->fullUrl : static::HtmlSpecialChars($this->fullUrl);
	}

	/**
	 * @inheritDocs
	 * @param  bool $withHash 
	 *              If `FALSE` (by default), fragment is returned always without 
	 *              hash character at the beginning. If `TRUE`, and fragment 
	 *              contains any character(s), fragment is returned with hash 
	 *              character at the beginning. But if fragment contains no
	 *              character(s), fragment is returned as EMPTY STRING WITHOUT hash character.
	 * @param  bool $rawInput 
	 *              Get raw input if `TRUE`. `FALSE` by default to get value 
	 *              through `htmlspecialchars($result);` without ampersand `&` escaping.
	 * @return string
	 */
	public function GetFragment ($withHash = FALSE, $rawInput = FALSE) {
		if ($this->fragment === NULL)
			$this->initUrlSegments();
		$result = ($withHash && mb_strlen($this->fragment) > 0)
			? '?' . $this->fragment
			: $this->fragment;
		return $rawInput ? $result : static::HtmlSpecialChars($result);
	}

	/**
	 * @inheritDocs
	 * @return string
	 */
	public function GetServerIp () {
		if ($this->serverIp === NULL) {
			$this->serverIp = (isset($this->globalServer['SERVER_ADDR'])
				? $this->globalServer['SERVER_ADDR']
				: (isset($this->globalServer['LOCAL_ADDR'])
					? $this->globalServer['LOCAL_ADDR']
					: ''));
		}
		return $this->serverIp;
	}

	/**
	 * @inheritDocs
	 * @return string
	 */
	public function GetClientIp () {
		if ($this->clientIp === NULL) {
			$this->clientIp = (isset($this->globalServer['REMOTE_ADDR'])
				? $this->globalServer['REMOTE_ADDR']
				: (isset($this->globalServer['HTTP_X_CLIENT_IP'])
					? $this->globalServer['HTTP_X_CLIENT_IP']
					: ''));
			$this->clientIp = preg_replace("#[^0-9a-zA-Z\.\:\[\]]#", '', $this->clientIp);
		}
		return $this->clientIp;
	}

	/**
	 * @inheritDocs
	 * @return bool
	 */
	public function IsAjax () {
		if ($this->ajax === NULL) {
			$rawHeader = isset($this->globalServer['HTTP_X_REQUESTED_WITH'])
				? $this->globalServer['HTTP_X_REQUESTED_WITH']
				: $this->GetHeader('X-Requested-With', FALSE);
			$this->ajax = mb_strtolower($rawHeader) === 'xmlhttprequest';
		}
		return $this->ajax;
	}

	/**
	 * @inheritDocs
	 * @return int|NULL
	 */
	public function GetContentLength () {
		if ($this->contentLength === NULL) {
			if (
				isset($this->globalServer['CONTENT_LENGTH']) &&
				ctype_digit($this->globalServer['CONTENT_LENGTH'])
			) {
				$this->contentLength = intval($this->globalServer['CONTENT_LENGTH']);
			} else {
				$rawHeader = $this->GetHeader('Content-Length', '0-9', '');
				if ($rawHeader)
					$this->contentLength = intval($rawHeader);
			}
		}
		return $this->contentLength;
	}

	/**
	 * @inheritDocs
	 * @return string
	 */
	public function GetBody () {
		if ($this->body === NULL) $this->initBody();
		return $this->body;
	}

	/**
	 * @inheritDocs
	 * @see http://php.net/manual/en/function.htmlspecialchars.php
	 * @param  string $str
	 * @return string
	 */
	public static function HtmlSpecialChars ($str) {
		static $chars = [
			// Base ASCII chars from 0 to 31:
			"\x00"	=> '',	"\x08"	=> '',			"\x10"	=> '',	"\x18"	=> '',
			"\x01"	=> '',	/*"\x09"	=> "\t",*/	"\x11"	=> '',	"\x19"	=> '',
			"\x02"	=> '',	/*"\x0A"	=> "\n",*/	"\x12"	=> '',	"\x1A"	=> '',
			"\x03"	=> '',	"\x0B"	=> '',			"\x13"	=> '',	"\x1B"	=> '',
			"\x04"	=> '',	"\x0C"	=> '',			"\x14"	=> '',	"\x1C"	=> '',
			"\x05"	=> '',	/*"\x0D"	=> "\r",*/	"\x15"	=> '',	"\x1D"	=> '',
			"\x06"	=> '',	"\x0E"	=> '',			"\x16"	=> '',	"\x1E"	=> '',
			"\x07"	=> '',	"\x0F"	=> '',			"\x17"	=> '',	"\x1F"	=> '',
			// HTML special chars except `&`:
			'"'=>'&quot;',
			"'"=>'&apos;',
			'<'=>'&lt;',
			'>'=>'&gt;',
			/*'&' => '&amp;',*/
		];
		return strtr($str, $chars);
	}
}
