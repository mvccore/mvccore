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
	public static function AddTwoSegmentTlds ($twoSegmentTlds) {
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
			try {
				$ctrl = $this->GetControllerName();
				$action = $this->GetActionName();
			} catch (\Exception $e) {
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
	 * Set cleaned requested controller name into `\MvcCore\Request::$controllerName;`
	 * and into `\MvcCore\Request::$params['controller'];`.
	 * @param string $controllerName
	 * @return \MvcCore\Request|\MvcCore\IRequest
	 */
	public function SetControllerName ($controllerName) {
		/** @var $this \MvcCore\Request */
		$this->controllerName = $controllerName;
		$routerClass = self::$routerClass;
		$router = $routerClass::GetInstance();
		$this->params[$router::URL_PARAM_CONTROLLER] = $controllerName;
		return $this;
	}

	/**
	 * Return cleaned requested controller name from `\MvcCore\Request::$params['controller'];`.
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
	 * Set cleaned requested controller name into `\MvcCore\Request::$actionName;`
	 * and into `\MvcCore\Request::$params['action'];`.
	 * @param string $actionName
	 * @return \MvcCore\Request|\MvcCore\IRequest
	 */
	public function SetActionName ($actionName) {
		/** @var $this \MvcCore\Request */
		$this->actionName = $actionName;
		$routerClass = self::$routerClass;
		$router = $routerClass::GetInstance();
		$this->params[$router::URL_PARAM_ACTION] = $actionName;
		return $this;
	}

	/**
	 * Return cleaned requested action name from `\MvcCore\Request::$params['action'];`.
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
	 * `TRUE` if PHP `php_sapi_name()` is `cli` and also
	 * if there is no `$_SERVER['REQUEST_URI']` defined.
	 * @return bool
	 */
	public function IsCli () {
		return $this->cli;
	}

	/**
	 * Set language international code.
	 * Use this lang storage by your own decision.
	 * Example: `"en" | "de"`
	 * @param string|NULL $lang
	 * @return \MvcCore\Request|\MvcCore\IRequest
	 */
	public function SetLang ($lang) {
		/** @var $this \MvcCore\Request */
		$this->lang = $lang;
		return $this;
	}

	/**
	 * Get language international code, lower case, not used by default.
	 * To use this variable - install  `\MvcCore\Router` extension `\MvcCore\Ext\Router\Lang`
	 * Or use this variable by your own decision.
	 * Example: `"en" | "de"`
	 * @return string|NULL
	 */
	public function GetLang () {
		if ($this->lang === NULL) $this->initLangAndLocale();
		return $this->lang;
	}

	/**
	 * Set country/locale code, upper case.
	 * Use this locale storage by your own decision.
	 * Example: `"US" | "UK"`
	 * @param string|NULL $locale
	 * @return \MvcCore\Request|\MvcCore\IRequest
	 */
	public function SetLocale ($locale) {
		/** @var $this \MvcCore\Request */
		$this->locale = $locale;
		return $this;
	}

	/**
	 * Get country/locale code, upper case, not used by default.
	 * To use this variable - install `\MvcCore\Router` extension `\MvcCore\Ext\Router\Lang`
	 * Or use this variable by your own decision.
	 * Example: `"US" | "UK"`
	 * @return string|NULL
	 */
	public function GetLocale () {
		if ($this->locale === NULL) $this->initLangAndLocale();
		return $this->locale;
	}

	/**
	 * Set media site version - `"full" | "tablet" | "mobile"`.
	 * Use this media site version storage by your own decision.
	 * Example: `"full" | "tablet" | "mobile"`
	 * @param string|NULL $mediaSiteVersion
	 * @return \MvcCore\Request|\MvcCore\IRequest
	 */
	public function SetMediaSiteVersion ($mediaSiteVersion) {
		/** @var $this \MvcCore\Request */
		$this->mediaSiteVersion = $mediaSiteVersion;
		return $this;
	}

	/**
	 * Get media site version - `"full" | "tablet" | "mobile"`.
	 * To use this variable - install `\MvcCore\Router` extension `\MvcCore\Ext\Routers\Media`
	 * Or use this variable by your own decision.
	 * Example: `"full" | "tablet" | "mobile"`
	 * @return string|NULL
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
	 * @return mixed|\MvcCore\Request|\MvcCore\IRequest
	 */
	public function __call ($rawName, $arguments = []) {
		/** @var $this \MvcCore\Request */
		$nameBegin = strtolower(substr($rawName, 0, 3));
		$name = substr($rawName, 3);
		if ($nameBegin == 'get') {
			if (property_exists($this, lcfirst($name))) return $this->{lcfirst($name)};
			if (property_exists($this, $name)) return $this->$name;
			return NULL;
		} else if ($nameBegin == 'set') {
			if (property_exists($this, lcfirst($name)))
				$this->{lcfirst($name)} = isset($arguments[0]) ? $arguments[0] : NULL;
			if (property_exists($this, $name))
				$this->$name = isset($arguments[0]) ? $arguments[0] : NULL;
			return $this;
		} else {
			throw new \InvalidArgumentException("[".get_class()."] No method `{$rawName}()` defined.");
		}
	}

	/**
	 * Universal getter, if property not defined, `NULL` is returned.
	 * @param string $name
	 * @return mixed
	 */
	public function __get ($name) {
		/** @var $this \MvcCore\Request */
		if (isset($this->{lcfirst($name)}))
			return $this->{lcfirst($name)};
		if (isset($this->{$name}))
			return $this->{$name};
		return NULL;
	}

	/**
	 * Universal setter, if property not defined, it's automatically declared.
	 * @param string $name
	 * @param mixed	 $value
	 * @return \MvcCore\Request|\MvcCore\IRequest
	 */
	public function __set ($name, $value) {
		/** @var $this \MvcCore\Request */
		if (property_exists($this, lcfirst($name)))
			return $this->{lcfirst($name)} = $value;
		return $this->{$name} = $value;
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
	 * Example: `"C:/www/my/development/directory/www"`
	 * @return string
	 */
	public function GetAppRoot () {
		if ($this->appRoot === NULL) {
			// `ucfirst()` - cause IIS has lower case drive name here - different from __DIR__ value
			$indexFilePath = ucfirst(str_replace(['\\', '//'], '/', $this->globalServer['SCRIPT_FILENAME']));
			$this->appRoot = strpos(__FILE__, 'phar://') === 0
				? 'phar://' . $indexFilePath
				: dirname($indexFilePath);
		}
		return $this->appRoot;
	}

	/**
	 * Set upper cased http method from global `$_SERVER['REQUEST_METHOD']`.
	 * Example: `$request->SetMethod("GET" | "POST" | "PUT" | "HEAD"...);`
	 * @param string $rawMethod
	 * @return \MvcCore\Request|\MvcCore\IRequest
	 */
	public function SetMethod ($rawMethod) {
		/** @var $this \MvcCore\Request */
		$this->method = $rawMethod;
		return $this;
	}

	/**
	 * Get upper cased http method from global `$_SERVER['REQUEST_METHOD']`.
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
	 * - for full URI:  `"http://localhost:88/my/development/directory/www/requested/path/after/domain?with=possible&query=string"`
	 * - set base path: `$request->SetBasePath("/my/development/directory/www");`
	 * @param string $rawBasePath
	 * @return \MvcCore\Request|\MvcCore\IRequest
	 */
	public function SetBasePath ($rawBasePath) {
		/** @var $this \MvcCore\Request */
		$this->basePath = $rawBasePath;
		$this->baseUrl = NULL;
		$this->requestUrl = NULL;
		$this->fullUrl = NULL;
		return $this;
	}

	/**
	 * Get base app directory path after domain,
	 * if application is placed in domain subdirectory.
	 * Example:
	 * - full URI:  `"http://localhost:88/my/development/directory/www/requested/path/after/domain?with=possible&query=string"`
	 * - base path: `"/my/development/directory/www"`
	 * @return string
	 */
	public function GetBasePath () {
		if ($this->basePath === NULL) $this->initScriptNameAndBasePath();
		return $this->basePath;
	}

	/**
	 * Set http scheme string.
	 * Example: `$request->SetScheme("https:");`
	 * @param string $rawProtocol
	 * @return \MvcCore\Request|\MvcCore\IRequest
	 */
	public function SetScheme ($rawProtocol) {
		/** @var $this \MvcCore\Request */
		$this->scheme = $rawProtocol;
		$this->domainUrl = NULL;
		$this->baseUrl = NULL;
		$this->requestUrl = NULL;
		$this->fullUrl = NULL;
		return $this;
	}

	/**
	 * Get http scheme string.
	 * Example: `"http:" | "https:"`
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
	 * Get `TRUE` if http scheme is `"https:"`.
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
	 * Get referer URI if any, safely read by:
	 * `filter_var($_SERVER['HTTP_REFERER'], FILTER_SANITIZE_URL);`
	 * Example: `"http://foreing.domain.com/path/where/is/link/to/?my=app"`
	 * @param bool $rawInput Get raw input if `TRUE`. `FALSE` by default to get value through `htmlspecialchars($result);` without ampersand `&` escaping.
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
		return $rawInput ? $this->referer : static::HtmlSpecialChars($this->referer);
	}

	/**
	 * Get timestamp in seconds as float, when the request has been started,
	 * with microsecond precision.
	 * @return float
	 */
	public function GetStartTime () {
		if ($this->microtime === NULL) $this->microtime = $this->globalServer['REQUEST_TIME_FLOAT'];
		return $this->microtime;
	}

	/**
	 * Set TOP level domain like `com` or `co.uk`.
	 * Method also change server name and host record automatically.
	 * @param string|NULL $topLevelDomain
	 * @return \MvcCore\Request|\MvcCore\IRequest
	 */
	public function SetTopLevelDomain ($topLevelDomain) {
		/** @var $this \MvcCore\Request */
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
	 * Set top level domain like `com` from `www.example.com`.
	 * @return string|NULL
	 */
	public function GetTopLevelDomain () {
		if ($this->domainParts === NULL) $this->initDomainSegments();
		return $this->domainParts[2];
	}

	/**
	 * Set second level domain like `example` in `www.example.com`.
	 * Method also change server name and host record automatically.
	 * @param string|NULL $secondLevelDomain
	 * @return \MvcCore\Request|\MvcCore\IRequest
	 */
	public function SetSecondLevelDomain ($secondLevelDomain) {
		/** @var $this \MvcCore\Request */
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
	 * Get second level domain like `example` in `www.example.com`.
	 * @return string|NULL
	 */
	public function GetSecondLevelDomain () {
		if ($this->domainParts === NULL) $this->initDomainSegments();
		return isset($this->domainParts[1]) ? $this->domainParts[1] : NULL;
	}

	/**
	 * Set second level domain like `example` from `www.example.com`.
	 * Method also change server name and host record automatically.
	 * @param string|NULL $thirdLevelDomain
	 * @return \MvcCore\Request|\MvcCore\IRequest
	 */
	public function SetThirdLevelDomain ($thirdLevelDomain) {
		/** @var $this \MvcCore\Request */
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
	 * Get third level domain like `www` from `www.example.com`.
	 * @return string|NULL
	 */
	public function GetThirdLevelDomain () {
		if ($this->domainParts === NULL) $this->initDomainSegments();
		return isset($this->domainParts[0]) ? $this->domainParts[0] : NULL;
	}

	/**
	 * Set application server name - domain without any port.
	 * Method also change host record and domain records automatically.
	 * Example: `$request->SetHostName("localhost");`
	 * @param string $rawHostName
	 * @return \MvcCore\Request|\MvcCore\IRequest
	 */
	public function SetHostName ($rawHostName) {
		/** @var $this \MvcCore\Request */
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
	 * Method also change server name record and domain records automatically.
	 * Example: `$request->SetHost("localhost:88");`
	 * @param string $rawHost
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
	 * Get application host with port if there is any.
	 * Example: `"localhost:88"`
	 * @return string
	 */
	public function GetHost () {
		if ($this->host === NULL) $this->host = $this->globalServer['HTTP_HOST'];
		return $this->host;
	}

	/**
	 * Set http port defined in requested URI if any, parsed by `parse_url().
	 * Empty string if there is no port number in requested address.`.
	 * Example: `$request->SetPort("88")`
	 * @param string $rawPort
	 * @return \MvcCore\Request|\MvcCore\IRequest
	 */
	public function SetPort ($rawPort) {
		/** @var $this \MvcCore\Request */
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
	 * Get http port defined in requested URI if any, parsed by `parse_url().
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
	 * @return \MvcCore\Request|\MvcCore\IRequest
	 */
	public function SetPath ($rawPathValue) {
		/** @var $this \MvcCore\Request */
		$this->path = $rawPathValue;
		$this->requestUrl = NULL;
		$this->requestPath = NULL;
		$this->fullUrl = NULL;
		return $this;
	}

	/**
	 * Get requested path in from application root (if `mod_rewrite` enabled), never with query string.
	 * Example: `"/products/page/2"`
	 * @param bool $rawInput Get raw input if `TRUE`. `FALSE` by default to get value through `htmlspecialchars($result);` without ampersand `&` escaping.
	 * @return string
	 */
	public function GetPath ($rawInput = FALSE) {
		if ($this->path === NULL)
			$this->initUrlSegments();
		return $rawInput ? $this->path : static::HtmlSpecialChars($this->path);
	}

	/**
	 * Set URI query string, with or without question mark character, doesn't matter.
	 * Example: `$request->SetQuery("param-1=value-1&param-2=value-2&param-3[]=value-3-a&param-3[]=value-3-b");`
	 * @param string $rawQuery
	 * @return \MvcCore\Request|\MvcCore\IRequest
	 */
	public function SetQuery ($rawQuery) {
		/** @var $this \MvcCore\Request */
		$this->query = ltrim($rawQuery, '?');
		$this->fullUrl = NULL;
		$this->requestPath = NULL;
		return $this;
	}

	/**
	 * Get URI query string (without question mark character by default).
	 * Example: `"param-1=value-1&param-2=value-2&param-3[]=value-3-a&param-3[]=value-3-b"`
	 * @param bool $withQuestionMark If `FALSE` (by default), query string is returned always without question
	 *							   mark character at the beginning.
	 *							   If `TRUE`, and query string contains any character(s), query string is returned
	 *							   with question mark character at the beginning. But if query string contains no
	 *							   character(s), query string is returned as EMPTY STRING WITHOUT question mark character.
	 * @param bool $rawInput Get raw input if `TRUE`. `FALSE` by default to get value through `htmlspecialchars($result);` without ampersand `&` escaping.
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
	 * Get request path after domain with possible query string
	 * Example: `"/requested/path/after/app/root?with=possible&query=string"`
	 * @param bool $rawInput Get raw input if `TRUE`. `FALSE` by default to get value through `htmlspecialchars($result);` without ampersand `&` escaping.
	 * @return string
	 */
	public function GetRequestPath ($rawInput = FALSE) {
		if ($this->requestPath === NULL) {
			$this->requestPath = $this->GetPath(TRUE) . $this->GetQuery(TRUE, TRUE) . $this->GetFragment(TRUE, TRUE);
		}
		return $rawInput ? $this->requestPath : static::HtmlSpecialChars($this->requestPath);
	}

	/**
	 * Get URI to requested domain and possible port.
	 * Example: `"https://domain.com" | "http://domain:88"` if any port.
	 * @return string
	 */
	public function GetDomainUrl () {
		if ($this->domainUrl === NULL)
			$this->domainUrl = $this->GetScheme() . '//' . $this->GetHost();
		return $this->domainUrl;
	}

	/**
	 * Get base URI to application root.
	 * Example: `"http://domain:88/my/development/directory/www"`
	 * @return string
	 */
	public function GetBaseUrl () {
		if ($this->baseUrl === NULL)
			$this->baseUrl = $this->GetDomainUrl() . $this->GetBasePath();
		return $this->baseUrl;
	}

	/**
	 * Get request URI including scheme, domain, port, path, without any query string
	 * Example: "`http://localhost:88/my/development/directory/www/requested/path/after/domain"`
	 * @param bool $rawInput Get raw input if `TRUE`. `FALSE` by default to get value through `htmlspecialchars($result);` without ampersand `&` escaping.
	 * @return string
	 */
	public function GetRequestUrl ($rawInput = FALSE) {
		if ($this->requestUrl === NULL)
			$this->requestUrl = $this->GetBaseUrl() . $this->GetPath(TRUE);
		return $rawInput ? $this->requestUrl : static::HtmlSpecialChars($this->requestUrl);
	}

	/**
	 * Get request URI including scheme, domain, port, path and with query string
	 * Example: `"http://localhost:88/my/development/directory/www/requested/path/after/domain?with=possible&query=string"`
	 * @param bool $rawInput Get raw input if `TRUE`. `FALSE` by default to get value through `htmlspecialchars($result);` without ampersand `&` escaping.
	 * @return string
	 */
	public function GetFullUrl ($rawInput = FALSE) {
		if ($this->fullUrl === NULL)
			$this->fullUrl = $this->GetRequestUrl(TRUE) . $this->GetQuery(TRUE, TRUE) . $this->GetFragment(TRUE, TRUE);
		return $rawInput ? $this->fullUrl : static::HtmlSpecialChars($this->fullUrl);
	}

	/**
	 * Get URI fragment parsed by `parse_url()` (without hash character by default).
	 * Example: `"any-sublink-path"`
	 * @param bool $withHash If `FALSE` (by default), fragment is returned always without hash character
	 *					   at the beginning.
	 *					   If `TRUE`, and fragment contains any character(s), fragment is returned
	 *					   with hash character at the beginning. But if fragment contains no
	 *					   character(s), fragment is returned as EMPTY STRING WITHOUT hash character.
	 * @param bool $rawInput Get raw input if `TRUE`. `FALSE` by default to get value through `htmlspecialchars($result);` without ampersand `&` escaping.
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
			$this->clientIp = preg_replace("#[^0-9a-zA-Z\.\:\[\]]#", '', $this->clientIp);
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
			if (
				isset($this->globalServer['HTTP_X_REQUESTED_WITH']) &&
				strlen($this->globalServer['HTTP_X_REQUESTED_WITH']) > 0
			) {
				$this->ajax =TRUE;
			} else {
				$rawHeader = $this->GetHeader('X-Requested-With', '\-\. _a-zA-Z0-9', '');
				$this->ajax = strlen($rawHeader) > 0;
			}
		}
		return $this->ajax;
	}

	/**
	 * Get integer value from global `$_SERVER['CONTENT_LENGTH']`
	 * or from http header `Content-Length`, if no value, `NULL` is returned.
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
	 * Raw request body, usually from `file_get_contents('php://input');`.
	 * Use this method only for non-standard application inputs like: XML, binary data, etc...
	 * @return string
	 */
	public function GetBody () {
		if ($this->body === NULL) $this->initBody();
		return $this->body;
	}

	/**
	 * Convert special characters to HTML entities except ampersand `&`.
	 * @see http://php.net/manual/en/function.htmlspecialchars.php
	 * @param string $str
	 * @return string
	 */
	public static function HtmlSpecialChars ($str) {
		static $chars = ['"'=>'&quot;',"'"=>'&apos;','<'=>'&lt;','>'=>'&gt;',/*'&' => '&amp;',*/];
		return strtr($str, $chars);
	}
}
