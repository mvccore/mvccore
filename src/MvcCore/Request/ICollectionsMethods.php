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
 * @phpstan-type CollectionItem string|int|float|bool|NULL|array<string|int|float|bool|NULL>|mixed
 * @phpstan-type CollectionFilter string|array<string,string>|bool
 */
interface ICollectionsMethods {

	/**
	 * Set exceptional two-segment top-level domain like
	 * `'co.jp', 'co.uk', 'co.kr', 'co.nf' ...` to parse
	 * domain string correctly.
	 * Example:
	 * `\MvcCore\Request::SetTwoSegmentTlds('co.uk', 'co.jp');`
	 * `\MvcCore\Request::SetTwoSegmentTlds(['co.uk', 'co.jp']);`
	 * @param  array<string> $twoSegmentTlds,...
	 * List of two-segment top-level domains without leading dot.
	 * @return void
	 */
	public static function SetTwoSegmentTlds ($twoSegmentTlds);
	
	/**
	 * Add exceptional two-segment top-level domain like
	 * `'co.jp', 'co.uk', 'co.kr', 'co.nf' ...` to parse
	 * domain string correctly.
	 * Example:
	 * `\MvcCore\Request::AddTwoSegmentTlds('co.uk', 'co.jp');`
	 * `\MvcCore\Request::AddTwoSegmentTlds(['co.uk', 'co.jp']);`
	 * @param  array<string> $twoSegmentTlds,...
	 * List of two-segment top-level domains without leading dot.
	 * @return void
	 */
	public static function AddTwoSegmentTlds ($twoSegmentTlds);

	/**
	 * Set default ports, not defined in server name by default.
	 * Example:
	 * `\MvcCore\Request::SetDefaultPorts(80, '443');`
	 * `\MvcCore\Request::SetDefaultPorts(['80', 443]);`
	 * @param  array<string>|array<int> $defaultPorts,...
	 * List of default ports, not defined in server name by default.
	 * @return void
	 */
	public static function SetDefaultPorts ($defaultPorts);

	/**
	 * Add default ports, not defined in server name by default.
	 * Example:
	 * `\MvcCore\Request::SetDefaultPorts(80, '443');`
	 * `\MvcCore\Request::SetDefaultPorts(['80', 443]);`
	 * @param  array<string>|array<int> $defaultPorts,...
	 * List of default ports, not defined in server name by default.
	 * @return void
	 */
	public static function AddDefaultPorts ($defaultPorts);
	
	/**
	 * Return `TRUE` boolean flag if request targets `Controller:Asset`.
	 * @return bool
	 */
	public function IsInternalRequest ();

	/**
	 * Set cleaned requested controller name into `\MvcCore\Request::$controllerName;`
	 * and into `\MvcCore\Request::$params['controller'];`.
	 * @param  string $controllerName
	 * @return \MvcCore\Request
	 */
	public function SetControllerName ($controllerName);

	/**
	 * Return cleaned requested controller name from `\MvcCore\Request::$params['controller'];`.
	 * @return string
	 */
	public function GetControllerName ();

	/**
	 * Set cleaned requested controller name into `\MvcCore\Request::$actionName;`
	 * and into `\MvcCore\Request::$params['action'];`.
	 * @param  string $actionName
	 * @return \MvcCore\Request
	 */
	public function SetActionName ($actionName);

	/**
	 * Return cleaned requested action name from `\MvcCore\Request::$params['action'];`.
	 * @return string
	 */
	public function GetActionName ();

	/**
	 * `TRUE` if PHP `php_sapi_name()` is `cli` and also
	 * if there is no `$_SERVER['REQUEST_URI']` defined.
	 * @return bool
	 */
	public function IsCli ();

	/**
	 * Set language international code.
	 * Use this lang storage by your own decision.
	 * Example: `"en" | "de"`
	 * @param  string|NULL $lang
	 * @return \MvcCore\Request
	 */
	public function SetLang ($lang);

	/**
	 * Get language international code, lower case, not used by default.
	 * To use this variable - install  `\MvcCore\Router` extension `\MvcCore\Ext\Router\Lang`
	 * Or use this variable by your own decision.
	 * Example: `"en" | "de"`
	 * @return string|NULL
	 */
	public function GetLang ();

	/**
	 * Set country/locale code, upper case.
	 * Use this locale storage by your own decision.
	 * Example: `"US" | "UK"`
	 * @param  string|NULL $locale
	 * @return \MvcCore\Request
	 */
	public function SetLocale ($locale);

	/**
	 * Get country/locale code, upper case, not used by default.
	 * To use this variable - install `\MvcCore\Router` extension `\MvcCore\Ext\Router\Lang`
	 * Or use this variable by your own decision.
	 * Example: `"US" | "UK"`
	 * @return string|NULL
	 */
	public function GetLocale ();

	/**
	 * Set media site version - `"full" | "tablet" | "mobile"`.
	 * Use this media site version storage by your own decision.
	 * Example: `"full" | "tablet" | "mobile"`
	 * @param  string|NULL $mediaSiteVersion
	 * @return \MvcCore\Request
	 */
	public function SetMediaSiteVersion ($mediaSiteVersion);

	/**
	 * Get media site version - `"full" | "tablet" | "mobile"`.
	 * To use this variable - install `\MvcCore\Router` extension `\MvcCoreExt\Router\Media`
	 * Or use this variable by your own decision.
	 * Example: `"full" | "tablet" | "mobile"`
	 * @return string|NULL
	 */
	public function GetMediaSiteVersion ();
	
	/**
	 * Sets any custom property `"propertyName"` by `\MvcCore\Request::SetPropertyName("value");`,
	 * which is not necessary to define previously or gets previously defined
	 * property `"propertyName"` by `\MvcCore\Request::GetPropertyName();`.
	 * Throws exception if no property defined by get call or if virtual call
	 * begins with anything different from `Set` or `Get`.
	 * This method returns custom value for get and `\MvcCore\Request` instance for set.
	 * @param  string           $rawName
	 * @param  array<int,mixed> $arguments
	 * @throws \InvalidArgumentException
	 * @return mixed|\MvcCore\Request
	 */
	public function __call ($rawName, $arguments = []);

	/**
	 * Universal getter, if property not defined, `NULL` is returned.
	 * @param  string $name
	 * @return mixed
	 */
	public function __get ($name);

	/**
	 * Universal setter, if property not defined, it's automatically declared.
	 * @param  string $name
	 * @param  mixed  $value
	 * @return void
	 */
	public function __set ($name, $value);

	/**
	 * Get PHP requested script name path from application root.
	 * Example: `"/index.php"`
	 * @return string
	 */
	public function GetScriptName ();

	/**
	 * Set PHP requested script name path from application root.
	 * Example: `"/index.php"`
	 * @param  string $scriptName
	 * @return \MvcCore\Request
	 */
	public function SetScriptName ($scriptName);

	/**
	 * Set upper cased http method from global `$_SERVER['REQUEST_METHOD']`.
	 * Example: `$request->SetMethod("GET" | "POST" | "PUT" | "HEAD"...);`
	 * @param  string $rawMethod
	 * @return \MvcCore\Request
	 */
	public function SetMethod ($rawMethod);

	/**
	 * Get upper cased http method from global `$_SERVER['REQUEST_METHOD']`.
	 * Example: `"GET" | "POST" | "PUT" | "HEAD"...`
	 * @return string
	 */
	public function GetMethod ();

	/**
	 * Set base app directory path after domain,
	 * if application is placed in domain subdirectory.
	 * Example:
	 * - for full url:  `"http://localhost:88/my/development/direcotry/www/requested/path/after/domain?with=possible&query=string"`
	 * - set base path: `$request->SetBasePath("/my/development/directory/www");`
	 * @param  string $rawBasePath
	 * @return \MvcCore\Request
	 */
	public function SetBasePath ($rawBasePath);

	/**
	 * Get base app directory path after domain,
	 * if application is placed in domain subdirectory.
	 * Example:
	 * - full url:  `"http://localhost:88/my/development/direcotry/www/requested/path/after/domain?with=possible&query=string"`
	 * - base path: `"/my/development/directory/www"`
	 * @return string
	 */
	public function GetBasePath ();

	/**
	 * Set http scheme string.
	 * Example: `$request->SetScheme("https:");`
	 * @param  string $rawProtocol
	 * @return \MvcCore\Request
	 */
	public function SetScheme ($rawProtocol);

	/**
	 * Get http scheme string.
	 * Example: `"http:" | "https:"`
	 * @return string
	 */
	public function GetScheme ();

	/**
	 * Get `TRUE` if http scheme is `"https:"`.
	 * @return bool
	 */
	public function IsSecure ();

	/**
	 * Get referer URL if any.
	 * Example: `"http://foreing.domain.com/path/where/is/link/to/?my=app"`
	 * Be carefull, value from `$_SERVER['HTTP_REFERER']` is generally unsafe.
	 * @param  bool $rawInput Get raw input if `TRUE`. `FALSE` by default to get value through `htmlspecialchars($result);` without ampersand `&` escaping.
	 * @return string
	 */
	public function GetReferer ($rawInput = FALSE);

	/**
	 * Get timestamp in seconds as float, when the request has been started,
	 * with microsecond precision.
	 * @return float
	 */
	public function GetStartTime ();

	/**
	 * Set TOP level domain like `com` or `co.uk`.
	 * Method also change server name and host record automatically.
	 * @param  string|NULL $topLevelDomain
	 * @return \MvcCore\Request
	 */
	public function SetTopLevelDomain ($topLevelDomain);

	/**
	 * Set top level domain like `com` from `www.example.com`.
	 * Be carefull on Apache web server, `$_SERVER['SERVER_NAME']`
	 * could be spoofed without `UseCanonicalName On` configuration.
	 * @see https://www.php.net/manual/en/reserved.variables.server.php#refsect1-reserved.variables.server-indices
	 * @return string|NULL
	 */
	public function GetTopLevelDomain ();

	/**
	 * Set second level domain like `example` in `www.example.com`.
	 * Method also change server name and host record automatically.
	 * @param  string|NULL $secondLevelDomain
	 * @return \MvcCore\Request
	 */
	public function SetSecondLevelDomain ($secondLevelDomain);

	/**
	 * Get second level domain like `example` in `www.example.com`.
	 * Be carefull on Apache web server, `$_SERVER['SERVER_NAME']`
	 * could be spoofed without `UseCanonicalName On` configuration.
	 * @see https://www.php.net/manual/en/reserved.variables.server.php#refsect1-reserved.variables.server-indices
	 * @return string|NULL
	 */
	public function GetSecondLevelDomain ();

	/**
	 * Set second level domain like `example` from `www.example.com`.
	 * Method also change server name and host record automatically.
	 * @param  string|NULL $thirdLevelDomain
	 * @return \MvcCore\Request
	 */
	public function SetThirdLevelDomain ($thirdLevelDomain);

	/**
	 * Get third level domain like `www` from `www.example.com`.
	 * Be carefull on Apache web server, `$_SERVER['SERVER_NAME']`
	 * could be spoofed without `UseCanonicalName On` configuration.
	 * @see https://www.php.net/manual/en/reserved.variables.server.php#refsect1-reserved.variables.server-indices
	 * @return string|NULL
	 */
	public function GetThirdLevelDomain ();

	/**
	 * Set application server name - domain without any port.
	 * Method also change host record and domain records automatically.
	 * Example: `$request->SetHostName("localhost");`
	 * @param  string $rawHostName
	 * @return \MvcCore\Request
	 */
	public function SetHostName ($rawHostName);

	/**
	 * Get application server name - domain without any port.
	 * Example: `"localhost"`
	 * Be carefull on Apache web server, `$_SERVER['SERVER_NAME']`
	 * could be spoofed without `UseCanonicalName On` configuration.
	 * @see https://www.php.net/manual/en/reserved.variables.server.php#refsect1-reserved.variables.server-indices
	 * @return string
	 */
	public function GetHostName ();

	/**
	 * Set application host with port if there is any.
	 * Method also change server name record and domain records automatically.
	 * Example: `$request->SetHost("localhost:88");`
	 * @param  string $rawHost
	 * @return \MvcCore\Request
	 */
	public function SetHost ($rawHost);

	/**
	 * Get application host with port if there is any.
	 * Example: `"localhost:88"`
	 * Be carefull on Apache web server, `$_SERVER['SERVER_NAME']`
	 * and `$_SERVER['SERVER_PORT']` could be spoofed without 
	 * `UseCanonicalName On` and `UseCanonicalPhysicalPort On` configuration.
	 * @see https://www.php.net/manual/en/reserved.variables.server.php#refsect1-reserved.variables.server-indices
	 * @return string
	 */
	public function GetHost ();

	/**
	 * Set http port defined in requested URL if any.
	 * Empty string if there is no port number in requested address.`.
	 * Examples: 
	 * `$request->SetPort(88)`
	 * `$request->SetPort("88")`
	 * @param  string|int $rawPort
	 * @return \MvcCore\Request
	 */
	public function SetPort ($rawPort);

	/**
	 * Get http port defined in requested URL if any.
	 * Empty string if there is no port number in requested address.`.
	 * Example: `"88" | ""`
	 * Be carefull on Apache web server, `$_SERVER['SERVER_PORT']` could be spoofed
	 * without `UseCanonicalName On` and `UseCanonicalPhysicalPort On` configuration.
	 * @see https://www.php.net/manual/en/reserved.variables.server.php#refsect1-reserved.variables.server-indices
	 * @return string
	 */
	public function GetPort ();

	/**
	 * Set requested path in from application root (if `mod_rewrite` enabled), never with query string.
	 * Example: `$request->SetPath("/products/page/2");`
	 * @param  string $rawPathValue
	 * @return \MvcCore\Request
	 */
	public function SetPath ($rawPathValue);

	/**
	 * Get requested path in from application root (if `mod_rewrite` enabled), never with query string.
	 * Example: `"/products/page/2"`
	 * @param  bool $rawInput Get raw input if `TRUE`. `FALSE` by default to get value through `htmlspecialchars($result);` without ampersand `&` escaping.
	 * @return string
	 */
	public function GetPath ($rawInput = FALSE);

	/**
	 * Set URL query string, with or without question mark character, doesn't matter.
	 * Example: `$request->SetQuery("param-1=value-1&param-2=value-2&param-3[]=value-3-a&param-3[]=value-3-b");`
	 * @param  string $rawQuery
	 * @return \MvcCore\Request
	 */
	public function SetQuery ($rawQuery);

	/**
	 * Get URL query string (without question mark character by default).
	 * Example: `"param-1=value-1&param-2=value-2&param-3[]=value-3-a&param-3[]=value-3-b"`
	 * @param  bool   $withQuestionMark If `FALSE` (by default), query string is returned always without question
	 *                                  mark character at the beginning.
	 *                                  If `TRUE`, and query string contains any character(s), query string is returned
	 *                                  with question mark character at the beginning. But if query string contains no
	 *                                  character(s), query string is returned as EMPTY STRING WITHOUT question mark character.
	 * @param  bool   $rawInput         Get raw input if `TRUE`. `FALSE` by default to get value through `htmlspecialchars($result);` without ampersand `&` escaping.
	 * @return string
	 */
	public function GetQuery ($withQuestionMark = FALSE, $rawInput = FALSE);

	/**
	 * Get request path after domain with possible query string
	 * Example: `"/requested/path/after/app/root?with=possible&query=string"`
	 * @param  bool $rawInput Get raw input if `TRUE`. `FALSE` by default to get value through `htmlspecialchars($result);` without ampersand `&` escaping.
	 * @return string
	 */
	public function GetRequestPath ($rawInput = FALSE);

	/**
	 * Get URL to requested domain and possible port.
	 * Example: `"https://domain.com" | "http://domain:88"` if any port.
	 * Be carefull on Apache web server, `$_SERVER['SERVER_NAME']`
	 * and `$_SERVER['SERVER_PORT']` could be spoofed without 
	 * `UseCanonicalName On` and `UseCanonicalPhysicalPort On` configuration.
	 * @see https://www.php.net/manual/en/reserved.variables.server.php#refsect1-reserved.variables.server-indices
	 * @return string
	 */
	public function GetDomainUrl ();

	/**
	 * Get base URL to application root.
	 * Example: `"http://domain:88/my/development/direcotry/www"`
	 * Be carefull on Apache web server, `$_SERVER['SERVER_NAME']`
	 * and `$_SERVER['SERVER_PORT']` could be spoofed without 
	 * `UseCanonicalName On` and `UseCanonicalPhysicalPort On` configuration.
	 * @see https://www.php.net/manual/en/reserved.variables.server.php#refsect1-reserved.variables.server-indices
	 * @return string
	 */
	public function GetBaseUrl ();

	/**
	 * Get request URL including scheme, domain, port, path, without any query string
	 * Example: "`http://localhost:88/my/development/direcotry/www/requested/path/after/domain"`
	 * Be carefull on Apache web server, `$_SERVER['SERVER_NAME']`
	 * and `$_SERVER['SERVER_PORT']` could be spoofed without 
	 * `UseCanonicalName On` and `UseCanonicalPhysicalPort On` configuration.
	 * @see https://www.php.net/manual/en/reserved.variables.server.php#refsect1-reserved.variables.server-indices
	 * @param  bool $rawInput Get raw input if `TRUE`. `FALSE` by default to get value through `htmlspecialchars($result);` without ampersand `&` escaping.
	 * @return string
	 */
	public function GetRequestUrl ($rawInput = FALSE);

	/**
	 * Get request URL including scheme, domain, port, path and with query string
	 * Example: `"http://localhost:88/my/development/direcotry/www/requested/path/after/domain?with=possible&query=string"`
	 * Be carefull on Apache web server, `$_SERVER['SERVER_NAME']`
	 * and `$_SERVER['SERVER_PORT']` could be spoofed without 
	 * `UseCanonicalName On` and `UseCanonicalPhysicalPort On` configuration.
	 * @see https://www.php.net/manual/en/reserved.variables.server.php#refsect1-reserved.variables.server-indices
	 * @param  bool $rawInput Get raw input if `TRUE`. `FALSE` by default to get value through `htmlspecialchars($result);` without ampersand `&` escaping.
	 * @return string
	 */
	public function GetFullUrl ($rawInput = FALSE);

	/**
	 * Get URI fragment (without hash character by default).
	 * Example: `"any-sublink-path"`
	 * @param  bool $withHash If `FALSE` (by default), fragment is returned always without hash character
	 *                        at the beginning.
	 *                        If `TRUE`, and fragment contains any character(s), fragment is returned
	 *                        with hash character at the beginning. But if fragment contains no
	 *                        character(s), fragment is returned as EMPTY STRING WITHOUT hash character.
	 * @param  bool $rawInput Get raw input if `TRUE`. `FALSE` by default to get value through `htmlspecialchars($result);` without ampersand `&` escaping.
	 * @return string
	 */
	public function GetFragment ($withHash = FALSE, $rawInput = FALSE);

	/**
	 * Get server IP from `$_SERVER` global variable.
	 * @return string
	 */
	public function GetServerIp ();

	/**
	 * Get client IP from `$_SERVER` global variable.
	 * @return string
	 */
	public function GetClientIp ();

	/**
	 * Get `TRUE` if request is requested on the background
	 * with usual Javascript HTTP header containing:
	 * `X-Requested-With: AnyJsFrameworkName`.
	 * @return bool
	 */
	public function IsAjax ();

	/**
	 * Get integer value from global `$_SERVER['CONTENT_LENGTH']`
	 * or from http header `Content-Length`, if no value, `NULL` is returned.
	 * @return int|NULL
	 */
	public function GetContentLength ();

	/**
	 * Raw request body, usually from `file_get_contents('php://input');`.
	 * Use this method only for non-standard application inputs like: XML, binary data, etc...
	 * @return string
	 */
	public function GetBody ();

	/**
	 * Convert HTML control characters `"`, `'`, `<`, `>`, `\` to entities, 
	 * except ampersand `&` and `=`, which are used in url query string. 
	 * Convert also all possible ASCII non-visible characters from decimal 
	 * index 0 to 32 also to html entities.
	 * @see http://php.net/manual/en/function.htmlspecialchars.php
	 * @see https://www.ascii-code.com/
	 * @param  string $str
	 * @return string
	 */
	public static function HtmlSpecialChars ($str);
}