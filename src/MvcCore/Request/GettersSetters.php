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

trait GettersSetters
{
	/**
	 * Add exceptional two-segment top-level domain like
	 * `'co.jp', 'co.uk', 'co.kr', 'co.nf' ...` to parse
	 * domain string correctly.
	 * Example: 
	 * `\MvcCore\Request::AddTwoSegmentTlds('co.uk', 'co.jp');`
	 * `\MvcCore\Request::AddTwoSegmentTlds(['co.uk', 'co.jp']);`
	 * @param \string[] $twoSegmentTlds,... List of two-segment top-level domains without leading dot.
	 * @return void
	 */
	public static function AddTwoSegmentTlds (/* ...$twoSegmentTlds */) {
		$tlds = func_get_args();
		if (count($tlds) === 1 && is_array($tlds[0])) $tlds = $tlds[0];
		foreach ($tlds as $tld) self::$twoSegmentTlds[$tld] = TRUE;
	}

	/**
	 * Return `TRUE` boolean flag if request targets `Controller:Asset`.
	 * @return bool
	 */
	public function IsInternalRequest () {
		if ($this->appRequest === NULL) {
			$ctrl = $this->GetControllerName();
			$action = $this->GetActionName();
			if ($ctrl !== NULL && $action !== NULL) {
				$this->appRequest = FALSE;
				if ($ctrl === 'controller' && $action === 'asset')
					$this->appRequest = TRUE;
			}
		}
		return $this->appRequest;
	}

	/**
	 * Set cleaned requested controller name into `\MvcCore\Request::$controllerName;`
	 * and into `\MvcCore\Request::$Params['controller'];`.
	 * @param string $controllerName
	 * @return \MvcCore\Request
	 */
	public function & SetControllerName ($controllerName) {
		$this->controllerName = $controllerName;
		$this->params['controller'] = $controllerName;
		return $this;
	}

	/**
	 * Return cleaned requested controller name from `\MvcCore\Request::$Params['controller'];`.
	 * @return string
	 */
	public function GetControllerName () {
		if ($this->controllerName === NULL) {
			if (isset($this->globalGet['controller']))
				$this->controllerName = $this->GetParam('controller', 'a-zA-Z0-9\-_/', '', 'string');
		}
		return $this->controllerName;
	}

	/**
	 * Set cleaned requested controller name into `\MvcCore\Request::$actionName;`
	 * and into `\MvcCore\Request::$Params['action'];`.
	 * @param string $actionName
	 * @return \MvcCore\Request
	 */
	public function & SetActionName ($actionName) {
		$this->actionName = $actionName;
		$this->params['action'] = $actionName;
		return $this;
	}

	/**
	 * Return cleaned requested action name from `\MvcCore\Request::$Params['action'];`.
	 * @return string
	 */
	public function GetActionName () {
		if ($this->actionName === NULL) {
			if (isset($this->globalGet['action']))
				$this->actionName = $this->GetParam('action', 'a-zA-Z0-9\-_', '', 'string');
		}
		return $this->actionName;
	}

	/**
	 * Set language international code.
	 * Use this lang storage by your own decision.
	 * Example: `"en" | "de"`
	 * @var string|NULL
	 */
	public function & SetLang ($lang) {
		$this->lang = $lang;
		return $this;
	}

	/**
	 * Get language international code, lowercase, not used by default.
	 * To use this variable - install  `\MvcCore\Router` extension `\MvcCore\Ext\Router\Lang`
	 * Or use this variable by your own decision.
	 * Example: `"en" | "de"`
	 * @var string|NULL
	 */
	public function GetLang () {
		if ($this->lang === NULL) $this->initLangAndLocale();
		return $this->lang;
	}

	/**
	 * Set country/locale code, uppercase.
	 * Use this locale storage by your own decision.
	 * Example: `"US" | "UK"`
	 * @var string|NULL
	 */
	public function & SetLocale ($locale) {
		$this->locale = $locale;
		return $this;
	}

	/**
	 * Get country/locale code, uppercase, not used by default.
	 * To use this variable - install `\MvcCore\Router` extension `\MvcCore\Ext\Router\Lang`
	 * Or use this variable by your own decision.
	 * Example: `"US" | "UK"`
	 * @var string|NULL
	 */
	public function GetLocale () {
		if ($this->locale === NULL) $this->initLangAndLocale();
		return $this->locale;
	}

	/**
	 * Set media site version - `"full" | "tablet" | "mobile"`.
	 * Use this media site version storage by your own decision.
	 * Example: `"full" | "tablet" | "mobile"`
	 * @var string|NULL
	 */
	public function & SetMediaSiteVersion ($mediaSiteVersion) {
		$this->mediaSiteVersion = $mediaSiteVersion;
		return $this;
	}

	/**
	 * Get media site version - `"full" | "tablet" | "mobile"`.
	 * To use this variable - install `\MvcCore\Router` extension `\MvcCore\Ext\Routers\Media`
	 * Or use this variable by your own decision.
	 * Example: `"full" | "tablet" | "mobile"`
	 * @var string|NULL
	 */
	public function GetMediaSiteVersion () {
		return $this->mediaSiteVersion;
	}


	/**
	 * Sets any custom property `"propertyName"` by `\MvcCore\Request::SetPropertyName("value");`,
	 * which is not necessary to define previously or gets previously defined
	 * property `"propertyName"` by `\MvcCore\Request::GetPropertyName();`.
	 * Throws exception if no property defined by get call or if virtual call
	 * begins with anything different from `Set` or `Get`.
	 * This method returns custom value for get and `\MvcCore\Request` instance for set.
	 * @param string $name
	 * @param array  $arguments
	 * @throws \InvalidArgumentException
	 * @return mixed|\MvcCore\Request
	 */
	public function __call ($name, $arguments = []) {
		$nameBegin = strtolower(substr($name, 0, 3));
		$prop = lcfirst(substr($name, 3));
		if ($nameBegin == 'get' && isset($this->$prop)) {
			return $this->$prop;
		} else if ($nameBegin == 'set') {
			$this->$prop = isset($arguments[0]) ? $arguments[0] : NULL;
			return $this;
		} else {
			throw new \InvalidArgumentException('['.__CLASS__."] No property with name '$prop' defined.");
		}
	}

	/**
	 * Universal getter, if property not defined, `NULL` is returned.
	 * @param string $name
	 * @return mixed
	 */
	public function __get ($name) {
		return isset($this->$name) ? $this->$name : NULL ;
	}

	/**
	 * Universal setter, if property not defined, it's automaticly declarated.
	 * @param string $name
	 * @param mixed	 $value
	 * @return \MvcCore\Request
	 */
	public function __set ($name, $value) {
		$this->$name = $value;
		return $this;
	}


	/**
	 * Php requested script name path from application root.
	 * Example: `"/index.php"`
	 * @return string
	 */
	public function GetScriptName () {
		if ($this->scriptName === NULL) $this->initScriptNameAndBasePath();
		return $this->scriptName;
	}

	/**
	 * Get application root path on hard drive.
	 * Example: `"C:/www/my/development/direcotry/www"`
	 * @return string
	 */
	public function GetAppRoot () {
		if ($this->appRoot === NULL) {
			// ucfirst - cause IIS has lower case drive name here - different from __DIR__ value
			$indexFilePath = ucfirst(str_replace(['\\', '//'], '/', $this->globalServer['SCRIPT_FILENAME']));
			if (strpos(__FILE__, 'phar://') === 0) {
				$this->appRoot = 'phar://' . $indexFilePath;
			} else {
				$this->appRoot = substr($indexFilePath, 0, mb_strrpos($indexFilePath, '/'));
			}
		}
		return $this->appRoot;
	}

	/**
	 * Set uppercased http method from global `$_SERVER['REQUEST_METHOD']`.
	 * Example: `$request->SetMethod("GET" | "POST" | "PUT" | "HEAD"...);`
	 * @param string $rawMethod
	 * @return \MvcCore\Request
	 */
	public function & SetMethod ($rawMethod) {
		$this->method = $rawMethod;
		return $this;
	}

	/**
	 * Get uppercased http method from global `$_SERVER['REQUEST_METHOD']`.
	 * Example: `"GET" | "POST" | "PUT" | "HEAD"...`
	 * @return string
	 */
	public function GetMethod () {
		if ($this->method === NULL) {
			$this->method = strtoupper($this->globalServer['REQUEST_METHOD']);
		}
		return $this->method;
	}

	/**
	 * Set base app directory path after domain,
	 * if application is placed in domain subdirectory.
	 * Example:
	 * - for full url:  `"http://localhost:88/my/development/direcotry/www/requested/path/after/domain?with=possible&query=string"`
	 * - set base path: `$request->SetBasePath("/my/development/direcotry/www");`
	 * @param string $rawBasePath
	 * @return \MvcCore\Request
	 */
	public function & SetBasePath ($rawBasePath) {
		$this->basePath = $rawBasePath;
		return $this;
	}

	/**
	 * Get base app directory path after domain,
	 * if application is placed in domain subdirectory.
	 * Example:
	 * - full url:  `"http://localhost:88/my/development/direcotry/www/requested/path/after/domain?with=possible&query=string"`
	 * - base path: `"/my/development/direcotry/www"`
	 * @return string
	 */
	public function GetBasePath () {
		if ($this->basePath === NULL) $this->initScriptNameAndBasePath();
		return $this->basePath;
	}

	/**
	 * Set http protocol string.
	 * Example: `$request->SetProtocol("https:");`
	 * @param string $rawProtocol
	 * @return \MvcCore\Request
	 */
	public function & SetProtocol ($rawProtocol) {
		$this->protocol = $rawProtocol;
		return $this;
	}

	/**
	 * Get http protocol string.
	 * Example: `"http:" | "https:"`
	 * @return string
	 */
	public function GetProtocol () {
		if ($this->protocol === NULL) {
			$this->protocol = (
				isset($this->globalServer['HTTPS']) &&
				strtolower($this->globalServer['HTTPS']) == 'on'
			)
				? static::PROTOCOL_HTTPS
				: static::PROTOCOL_HTTP;
		}
		return $this->protocol;
	}

	/**
	 * Get `TRUE` if http protocol is `"https:"`.
	 * @return bool
	 */
	public function IsSecure () {
		if ($this->secure === NULL)
			$this->secure = $this->GetProtocol() == static::PROTOCOL_HTTPS;
		return $this->secure;
	}

	/**
	 * Get referer url if any, safely readed by:
	 * `filter_var($_SERVER['HTTP_REFERER'], FILTER_SANITIZE_URL);`
	 * Example: `"http://foreing.domain.com/path/where/is/link/to/?my=app"`
	 * @param bool $rawInput Get raw input if `TRUE`. `FALSE` by default to get value throught `htmlspecialchars($result);` without amersand `&` escaping.
	 * @return string
	 */
	public function GetReferer ($rawInput = FALSE) {
		if ($this->referer === NULL) {
			$referer = isset($this->globalServer['HTTP_REFERER'])
				? $this->globalServer['HTTP_REFERER']
				: '';
			if ($referer) {
				while (mb_strpos($referer, '%') !== FALSE)
					$referer = rawurldecode($referer);
				$referer = filter_var($referer, FILTER_SANITIZE_URL) ?: '';
			}
			$this->referer = $referer;
		}
		return $rawInput ? $this->referer : static::htmlSpecialChars($this->referer);
	}

	/**
	 * Get timestamp of the start of the request, with microsecond precision.
	 * @return float
	 */
	public function GetMicrotime () {
		if ($this->microtime === NULL) $this->microtime = $this->globalServer['REQUEST_TIME_FLOAT'];
		return $this->microtime;
	}

	/**
	 * Set TOP level domain like `com` or `co.uk`.
	 * Method also change server name and host record automaticly.
	 * @param string|NULL $topLevelDomain 
	 * @return \MvcCore\Request
	 */
	public function & SetTopLevelDomain ($topLevelDomain) {
		if ($this->domainParts === NULL) $this->initDomainSegments();
		$this->domainParts[2] = $topLevelDomain;
		$this->hostName = trim(implode('.', $this->domainParts), '.');
		if ($this->hostName && $this->portDefined) 
			$this->host = $this->hostName . ':' . $this->port;
		return $this;
	}
	
	/**
	 * Set top level domain like `com` from `www.example.com`.
	 * @return string|NULL
	 */
	public function GetTopLevelDomain () {
		if ($this->domainParts === NULL) $this->initDomainSegments();
		return $this->domainParts[2];
	}
	
	/**
	 * Set second level domain like `example` in `www.example.com`.
	 * Method also change server name and host record automaticly.
	 * @param string|NULL $secondLevelDomain 
	 * @return \MvcCore\Request
	 */
	public function & SetSecondLevelDomain ($secondLevelDomain) {
		if ($this->domainParts === NULL) $this->initDomainSegments();
		$this->domainParts[1] = $secondLevelDomain;
		$this->hostName = trim(implode('.', $this->domainParts), '.');
		if ($this->hostName && $this->portDefined) 
			$this->host = $this->hostName . ':' . $this->port;
		return $this;
	}
	
	/**
	 * Get third level domain like `www` in `www.example.com`.
	 * @return string|NULL
	 */
	public function GetSecondLevelDomain () {
		if ($this->domainParts === NULL) $this->initDomainSegments();
		return isset($this->domainParts[1]) ? $this->domainParts[1] : NULL;
	}
	
	/**
	 * Set second level domain like `example` from `www.example.com`.
	 * Method also change server name and host record automaticly.
	 * @param string|NULL $thirdLevelDomain 
	 * @return \MvcCore\Request
	 */
	public function & SetThirdLevelDomain ($thirdLevelDomain) {
		if ($this->domainParts === NULL) $this->initDomainSegments();
		$this->domainParts[0] = $thirdLevelDomain;
		$this->hostName = trim(implode('.', $this->domainParts), '.');
		if ($this->hostName && $this->portDefined) 
			$this->host = $this->hostName . ':' . $this->port;
		return $this;
	}
	
	/**
	 * Get third level domain like `www` from `www.example.com`.
	 * @return string|NULL
	 */
	public function GetThirdLevelDomain () {
		if ($this->domainParts === NULL) $this->initDomainSegments();
		return isset($this->domainParts[0]) ? $this->domainParts[0] : NULL;
	}

	/**
	 * Set application server name - domain without any port.
	 * Method also change host record and domain records automaticly.
	 * Example: `$request->SetHostName("localhost");`
	 * @param string $rawHostName
	 * @return \MvcCore\Request
	 */
	public function & SetHostName ($rawHostName) {
		if ($this->hostName !== $rawHostName) $this->domainParts = NULL;
		$this->hostName = $rawHostName;
		if ($rawHostName && $this->portDefined) 
			$this->host = $rawHostName . ':' . $this->port;
		return $this;
	}

	/**
	 * Get application server name - domain without any port.
	 * Example: `"localhost"`
	 * @return string
	 */
	public function GetHostName () {
		if ($this->hostName === NULL) 
			$this->hostName = $this->globalServer['SERVER_NAME'];
		return $this->hostName;
	}

	/**
	 * Set application host with port if there is any.
	 * Method also change server name record and domain records automaticly.
	 * Example: `$request->SetHost("localhost:88");`
	 * @param string $rawHost
	 * @return \MvcCore\Request
	 */
	public function & SetHost ($rawHost) {
		$this->host = $rawHost;
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
	 * Get application host with port if there is any.
	 * Example: `"localhost:88"`
	 * @return string
	 */
	public function GetHost () {
		if ($this->host === NULL) $this->host = $this->globalServer['HTTP_HOST'];
		return $this->host;
	}

	/**
	 * Set http port defined in requested url if any, parsed by `parse_url().
	 * Empty string if there is no port number in requested address.`.
	 * Example: `$request->SetPort("88")`
	 * @param string $rawPort
	 * @return \MvcCore\Request
	 */
	public function & SetPort ($rawPort) {
		$this->port = $rawPort;
		$this->portDefined = strlen($rawPort) > 0;
		return $this;
	}

	/**
	 * Get http port defined in requested url if any, parsed by `parse_url().
	 * Empty string if there is no port number in requested address.`.
	 * Example: `"88" | ""`
	 * @return string
	 */
	public function GetPort () {
		if ($this->port === NULL) $this->initUrlSegments();
		return $this->port;
	}

	/**
	 * Set requested path in from application root (if `mod_rewrite` enabled), never with query string.
	 * Example: `$request->SetPort("/products/page/2");`
	 * @param string $rawPathValue
	 * @return \MvcCore\Request
	 */
	public function & SetPath ($rawPathValue) {
		$this->path = $rawPathValue;
		return $this;
	}

	/**
	 * Get requested path in from application root (if `mod_rewrite` enabled), never with query string.
	 * Example: `"/products/page/2"`
	 * @param bool $rawInput Get raw input if `TRUE`. `FALSE` by default to get value throught `htmlspecialchars($result);` without amersand `&` escaping.
	 * @return string
	 */
	public function GetPath ($rawInput = FALSE) {
		if ($this->path === NULL) 
			$this->initUrlSegments();
		return $rawInput ? $this->path : static::htmlSpecialChars($this->path);
	}

	/**
	 * Set uri query string, with or without question mark character, doesn't matter.
	 * Example: `$request->SetQuery("param-1=value-1&param-2=value-2&param-3[]=value-3-a&param-3[]=value-3-b");`
	 * @param string $rawQuery
	 * @return \MvcCore\Request
	 */
	public function & SetQuery ($rawQuery) {
		$this->query = ltrim($rawQuery, '?');
		return $this;
	}

	/**
	 * Get uri query string (without question mark character by default).
	 * Example: `"param-1=value-1&param-2=value-2&param-3[]=value-3-a&param-3[]=value-3-b"`
	 * @param bool $withQuestionMark If `FALSE` (by default), query string is returned always without question
	 *							   mark character at the beginning.
	 *							   If `TRUE`, and query string contains any character(s), query string is returned
	 *							   with question mark character at the beginning. But if query string contains no
	 *							   character(s), query string is returned as EMPTY STRING WITHOUT question mark character.
	 * @param bool $rawInput Get raw input if `TRUE`. `FALSE` by default to get value throught `htmlspecialchars($result);` without amersand `&` escaping.
	 * @return string
	 */
	public function GetQuery ($withQuestionMark = FALSE, $rawInput = FALSE) {
		if ($this->query === NULL) 
			$this->initUrlSegments();
		$result = ($withQuestionMark && mb_strlen($this->query) > 0)
			? '?' . $this->query
			: $this->query;
		return $rawInput ? $result : static::htmlSpecialChars($result);
	}

	/**
	 * Get request path after domain with possible query string
	 * Example: `"/requested/path/after/app/root?with=possible&query=string"`
	 * @param bool $rawInput Get raw input if `TRUE`. `FALSE` by default to get value throught `htmlspecialchars($result);` without amersand `&` escaping.
	 * @return string
	 */
	public function GetRequestPath ($rawInput = FALSE) {
		if ($this->requestPath === NULL) {
			$this->requestPath = $this->GetPath(TRUE) . $this->GetQuery(TRUE, TRUE) . $this->GetFragment(TRUE, TRUE);
		}
		return $rawInput ? $this->requestPath : static::htmlSpecialChars($this->requestPath);
	}

	/**
	 * Get url to requested domain and possible port.
	 * Example: `"https://domain.com" | "http://domain:88"` if any port.
	 * @return string
	 */
	public function GetDomainUrl () {
		if ($this->domainUrl === NULL) 
			$this->domainUrl = $this->GetProtocol() . '//' . $this->GetHost();
		return $this->domainUrl;
	}

	/**
	 * Get base url to application root.
	 * Example: `"http://domain:88/my/development/direcotry/www"`
	 * @return string
	 */
	public function GetBaseUrl () {
		if ($this->baseUrl === NULL) 
			$this->baseUrl = $this->GetDomainUrl() . $this->GetBasePath();
		return $this->baseUrl;
	}

	/**
	 * Get request url including scheme, domain, port, path, without any query string
	 * Example: "`http://localhost:88/my/development/direcotry/www/requested/path/after/domain"`
	 * @param bool $rawInput Get raw input if `TRUE`. `FALSE` by default to get value throught `htmlspecialchars($result);` without amersand `&` escaping.
	 * @return string
	 */
	public function GetRequestUrl ($rawInput = FALSE) {
		if ($this->requestUrl === NULL) 
			$this->requestUrl = $this->GetBaseUrl() . $this->GetPath(TRUE);
		return $rawInput ? $this->requestUrl : $this->htmlSpecialChars($this->requestUrl);
	}

	/**
	 * Get request url including scheme, domain, port, path and with query string
	 * Example: `"http://localhost:88/my/development/direcotry/www/requested/path/after/domain?with=possible&query=string"`
	 * @param bool $rawInput Get raw input if `TRUE`. `FALSE` by default to get value throught `htmlspecialchars($result);` without amersand `&` escaping.
	 * @return string
	 */
	public function GetFullUrl ($rawInput = FALSE) {
		if ($this->fullUrl === NULL) 
			$this->fullUrl = $this->GetRequestUrl(TRUE) . $this->GetQuery(TRUE, TRUE) . $this->GetFragment(TRUE, TRUE);
		return $rawInput ? $this->fullUrl : static::htmlSpecialChars($this->fullUrl);
	}

	/**
	 * Get uri fragment parsed by `parse_url()` (without hash character by default).
	 * Example: `"any-sublink-path"`
	 * @param bool $withHash If `FALSE` (by default), fragment is returned always without hash character
	 *					   at the beginning.
	 *					   If `TRUE`, and fragment contains any character(s), fragment is returned
	 *					   with hash character at the beginning. But if fragment contains no
	 *					   character(s), fragment is returned as EMPTY STRING WITHOUT hash character.
	 * @param bool $rawInput Get raw input if `TRUE`. `FALSE` by default to get value throught `htmlspecialchars($result);` without amersand `&` escaping.
	 * @return string
	 */
	public function GetFragment ($withHash = FALSE, $rawInput = FALSE) {
		if ($this->fragment === NULL) 
			$this->initUrlSegments();
		$result = ($withHash && mb_strlen($this->fragment) > 0)
			? '?' . $this->fragment
			: $this->fragment;
		return $rawInput ? $result : static::htmlSpecialChars($result);
	}

	/**
	 * Get server IP from `$_SERVER` global variable.
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
	 * Get client IP from `$_SERVER` global variable.
	 * @return string
	 */
	public function GetClientIp () {
		if ($this->clientIp === NULL) {
			$this->clientIp = (isset($this->globalServer['REMOTE_ADDR'])
				? $this->globalServer['REMOTE_ADDR']
				: (isset($this->globalServer['HTTP_X_CLIENT_IP'])
					? $this->globalServer['HTTP_X_CLIENT_IP']
					: ''));
		}
		return $this->clientIp;
	}

	/**
	 * Get `TRUE` if request is requested on the background
	 * with usual Javascript HTTP header containing:
	 * `X-Requested-With: AnyJsFrameworkName`.
	 * @return bool
	 */
	public function IsAjax () {
		if ($this->ajax === NULL) {
			$this->ajax = (
				isset($this->globalServer['HTTP_X_REQUESTED_WITH']) &&
				strlen($this->globalServer['HTTP_X_REQUESTED_WITH']) > 0
			);
		}
		return $this->ajax;
	}

	/**
	 * Get integer value from global `$_SERVER['CONTENT_LENGTH']`,
	 * If no value, `NULL` is returned.
	 * @return int|NULL
	 */
	public function GetContentLength () {
		if ($this->contentLength === NULL) {
			if (
				isset($this->globalServer['CONTENT_LENGTH']) &&
				ctype_digit($this->globalServer['CONTENT_LENGTH'])
			) $this->contentLength = intval($this->globalServer['CONTENT_LENGTH']);
		}
		return $this->contentLength;
	}

	/**
	 * Convert special characters to HTML entities except ampersand `&`.
	 * @see http://php.net/manual/en/function.htmlspecialchars.php
	 * @param string $str 
	 * @return string
	 */
	protected static function htmlSpecialChars ($str) {
		static $chars = ['"'=>'&quot;',"'"=>'&apos;','<'=>'&lt;','>'=>'&gt;',/*'&' => '&amp;',*/];
		return strtr($str, $chars);
	}
}
