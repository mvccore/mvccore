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

namespace MvcCore;

//include_once(__DIR__ . '/Interfaces/IRequest.php');
//include_once('Tool.php');
//include_once('Application.php');

/**
 * Responsibility - request description - url and params inputs parsing and cleaning.
 * - Linear request url parsing from referenced `$_SERVER` global variable
 *   (as constructor argument) into local properties, describing url sections.
 * - Params reading from referenced `$_GET` and `$_POST` global variables
 *   (as constructor arguments) or reading data from direct PHP
 *   input `"php://input"` (as encoded JSON data or as query string).
 * - Headers cleaning and reading by `getallheaders()` or from referenced `$_SERVER['HTTP_...']`.
 * - Cookies cleaning and reading from referenced `$_COOKIE['...']`.
 * - Uploaded files by wrapped referenced `$_FILES` global array.
 * - Primitive values cleaning or array recursive cleaning by called
 *	 developer rules from params array, headers array and cookies array.
 */
class Request implements Interfaces\IRequest
{
	/**
	 * Language international code, lowercase, not used by default.
	 * To use this variable - install  `\MvcCore\Router` extension `\MvcCore\Ext\Router\Lang`
	 * Or use this variable by your own decision.
	 * Example: `"en" | "de"`
	 * @var string|NULL
	 */
	protected $lang				= NULL;

	/**
	 * Country/locale code, uppercase, not used by default.
	 * To use this variable - install `\MvcCore\Router` extension `\MvcCore\Ext\Router\Lang`
	 * Or use this variable by your own decision.
	 * Example: `"US" | "UK"`
	 * @var string|NULL
	 */
	protected $locale			= NULL;

	/**
	 * Media site key - `"full" | "tablet" | "mobile"`.
	 * To use this variable - install `\MvcCore\Router` extension `\MvcCoreExt\Router\Media`
	 * Or use this variable by your own decision.
	 * Example: `"full" | "tablet" | "mobile"`
	 * @var string|NULL
	 */
	protected $mediaSiteKey = '';

	/**
	 * Http protocol: `"http:" | "https:"`
	 * Example: `"http:"`
	 * @var string|NULL
	 */
	protected $protocol			= NULL;

	/**
	 * `TRUE` if http protocol is `"https:"`
	 * @var bool|NULL
	 */
	protected $secure			= NULL;

	/**
	 * Application server name - domain without any port.
	 * Example: `"localhost"`
	 * @var string|NULL
	 */
	protected $serverName		= NULL;

	/**
	 * Application host with port if there is any.
	 * Example: `"localhost:88"`
	 * @var string|NULL
	 */
	protected $host				= NULL;

	/**
	 * Http port defined in requested url if any, parsed by `parse_url().
	 * Empty string if there is no port number in requested address.`.
	 * Example: `"88" | ""`
	 * @var string|NULL
	 */
	protected $port				= NULL;

	/**
	 * Requested path in from application root (if `mod_rewrite` enabled), never with query string.
	 * Example: `"/products/page/2"`
	 * @var string|NULL
	 */
	protected $path				= NULL;

	/**
	 * Uri query string without question mark.
	 * Example: `"param-1=value-1&param-2=value-2&param-3[]=value-3-a&param-3[]=value-3-b"`
	 * @var string|NULL
	 */
	protected $query			= NULL;

	/**
	 * Uri fragment parsed by `parse_url()` including hash.
	 * Example: `"#any-sublink-path"`
	 * @var string|NULL
	 */
	protected $fragment			= NULL;

	/**
	 * `TRUE` if request is requested from browser by `XmlHttpRequest` object
	 * with http header: `X-Requested-With: AnyJavascriptFrameworkName`, `FALSE` otherwise.
	 * @var bool|null
	 */
	protected $ajax				= NULL;

	/**
	 * Php requested script name path from application root.
	 * Example: `"/index.php"`
	 * @var string|NULL
	 */
	protected $scriptName		= NULL;

	/**
	 * Application root path on hard drive.
	 * Example: `"C:/www/my/development/direcotry/www"`
	 * @var string|NULL
	 */
	protected $appRoot			= NULL;

	/**
	 * Base app directory path after domain, if application is placed in domain subdirectory
	 * Example:
	 * - full url:  `"http://localhost:88/my/development/direcotry/www/requested/path/after/domain?with=possible&query=string"`
	 * - base path: `"/my/development/direcotry/www"`
	 * @var string|NULL
	 */
	protected $basePath			= NULL;

	/**
	 * Request path after domain with possible query string
	 * Example: `"/requested/path/after/app/root?with=possible&query=string"`
	 * @var string|NULL
	 */
	protected $requestPath		= NULL;

	/**
	 * Url to requested domain and possible port.
	 * Example: `"https://domain.com" | "http://domain:88"` if any port.
	 * @var string|NULL
	 */
	protected $domainUrl		= NULL;

	/**
	 * Base url to application root.
	 * Example: `"http://domain:88/my/development/direcotry/www"`
	 * @var string|NULL
	 */
	protected $baseUrl			= NULL;

	/**
	 * Request url including scheme, domain, port, path, without any query string
	 * Example: "`http://localhost:88/my/development/direcotry/www/requested/path/after/domain"`
	 * @var string|NULL
	 */
	protected $requestUrl		= NULL;

	/**
	 * Request url including scheme, domain, port, path and with query string
	 * Example: `"http://localhost:88/my/development/direcotry/www/requested/path/after/domain?with=possible&query=string"`
	 * @var string|NULL
	 */
	protected $fullUrl			= NULL;

	/**
	 * Http method (uppercase) - `GET`, `POST`, `PUT`, `HEAD`...
	 * Example: `"GET"`
	 * @var string|NULL
	 */
	protected $method			= NULL;

	/**
	 * Referer url if any, safely readed by:
	 * `filter_var($_SERVER['HTTP_REFERER'], FILTER_SANITIZE_URL);`
	 * Example: `"http://foreing.domain.com/path/where/is/link/to/?my=app"`
	 * @var string|NULL
	 */
	protected $referer			= NULL;

	/**
	 * Server ip address string.
	 * Example: `"127.0.0.1" | "111.222.111.222"`
	 * @var string|NULL
	 */
	protected $serverIp			= NULL;

	/**
	 * Client ip address string.
	 * Example: `"127.0.0.1" | "222.111.222.111"`
	 * @var string|NULL
	 */
	protected $clientIp			= NULL;

	/**
	 * Integer value from global `$_SERVER['CONTENT_LENGTH']`,
	 * `NULL` if no value presented in global `$_SERVER` array.
	 * Example: `123456 | NULL`
	 * @var int|NULL
	 */
	protected $contentLength	= NULL;

	/**
	 * Timestamp of the start of the request, with microsecond precision.
	 * @var float
	 */
	protected $microtime		= NULL;

	/**
	 * All raw http headers without any conversion, initialized by
	 * `getallheaders()` or from `$_SERVER['HTTP_...']`.
	 * Headers are `key => value` array, headers keys are
	 * in standard format like: `"Content-Type" | "Content-Length" | "X-Requested-With" ...`.
	 * @var array|NULL
	 */
	protected $headers			= NULL;

	/**
	 * Raw request params array, with keys defined in route or by query string,
	 * always with controller and action keys completed by router.
	 * Do not read this `$Params` array directly, read it's values by:
	 * `\MvcCore\Request::GetParam($paramName, $allowedChars, $defaultValueIfNull, $targetType);`.
	 * Example:
	 *	`\MvcCore\Request:$Params = array(
	 *		"controller"	=> "default",
	 *		"action"		=> "default",
	 *		"username"		=> "' OR 1=1;-- ",	// be carefull for this content with raw (danger) value!
	 *	);`
	 *	// Do not read `$Params` array directly,
	 *	// to get safe param value use:
	 *	`\MvcCore\Request::GetParam("username", "a-zA-Z0-9_");` // return `OR` string without danger chars.
	 * @var array|NULL
	 */
	protected $params			= NULL;

	/**
	 * Request flag if request targets internal package asset or not,
	 * - 0 => Means request is `Controller:Asset` call for internal package asset.
	 * - 1 => Means request is classic application request.
	 * @var bool|NULL
	 */
	protected $appRequest		= NULL;

	/**
	 * Cleaned input param `"controller"`, containing only chars: `"a-zA-Z0-9\-_/"`.
	 * @var string
	 */
	protected $controllerName	= NULL;

	/**
	 * Cleaned input param `"action"`, containing only chars: `"a-zA-Z0-9\-_/"`.
	 * @var string
	 */
	protected $actionName		= NULL;

	/**
	 * Content of referenced `$_SERVER` global variable.
	 * @var array
	 */
	protected $globalServer	= array();

	/**
	 * Content of referenced `$_GET` global variable.
	 * @var array
	 */
	protected $globalGet		= array();

	/**
	 * Content of referenced `$_POST` global variable.
	 * @var array
	 */
	protected $globalPost		= array();

	/**
	 * Content of referenced `$_COOKIE` global variable.
	 * @var array
	 */
	protected $globalCookies	= array();

	/**
	 * Content of referenced `$_FILES` global variable.
	 * @var array
	 */
	protected $globalFiles		= array();

	/**
	 * Static factory to get everytime new instance of http request object.
	 * Global variables for constructor arguments (`$_SERVER`, `$_GET`, `$_POST`...)
	 * should be changed to any arrays with any values and injected here to get
	 * different request object then currently called real request object.
	 * For example to create fake request object for testing purposes
	 * or for non-real request rendering into request output cache.
	 * @param array $server
	 * @param array $get
	 * @param array $post
	 * @param array $cookie
	 * @param array $files
	 * @return \MvcCore\Request
	 */
	public static function CreateInstance (
		array & $server = array(),
		array & $get = array(),
		array & $post = array(),
		array & $cookie = array(),
		array & $files = array()
	) {
		$requestClass = \MvcCore\Application::GetInstance()->GetRequestClass();
		return new $requestClass($server, $get, $post, $cookie, $files);
	}


	/**
	 * Create new instance of http request object.
	 * Global variables for constructor arguments (`$_SERVER`, `$_GET`, `$_POST`...)
	 * should be changed to any arrays with any values and injected here to get
	 * different request object then currently called real request object.
	 * For example to create fake request object for testing purposes
	 * or for non-real request rendering into request output cache.
	 * @param array $server
	 * @param array $get
	 * @param array $post
	 * @param array $cookie
	 * @param array $files
	 * @return \MvcCore\Request
	 */
	public function __construct (
		array & $server = array(),
		array & $get = array(),
		array & $post = array(),
		array & $cookie = array(),
		array & $files = array()
	) {
		$this->globalServer = & $server;
		$this->globalGet = & $get;
		$this->globalPost = & $post;
		$this->globalCookies = & $cookie;
		$this->globalFiles = & $files;
	}

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
		return $this->$collection;
	}

	/**
	 * Set directly all raw http headers without any conversion at once.
	 * Header name(s) as array keys should be in standard format like:
	 * `"Content-Type" | "Content-Length" | "X-Requested-With" ...`.
	 * @param array $headers
	 * @return \MvcCore\Request
	 */
	public function & SetHeaders (array & $headers = array()) {
		$this->headers = & $headers;
		return $this;
	}

	/**
	 * Get directly all raw http headers at once (with/without conversion).
	 * If headers are not initialized, initialize headers by
	 * `getallheaders()` or from `$_SERVER['HTTP_...']`.
	 * Headers are returned as `key => value` array, headers keys are
	 * in standard format like: `"Content-Type" | "Content-Length" | "X-Requested-With" ...`.
	 * @param string|array $pregReplaceAllowedChars If String - list of regular expression characters to only keep, if array - `preg_replace()` pattern and reverse.
	 * @return array
	 */
	public function & GetHeaders ($pregReplaceAllowedChars = array('#[\<\>]#', '')) {
		if ($this->headers === NULL) $this->initHeaders();
		if ($pregReplaceAllowedChars === '' || $pregReplaceAllowedChars === '.*') return $this->headers;
		$cleanedHeaders = array();
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
	public function & SetHeader ($name = "", $value = "") {
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
	 * @param string|array $pregReplaceAllowedChars If String - list of regular expression characters to only keep, if array - `preg_replace()` pattern and reverse.
	 * @param mixed $ifNullValue Default value returned if given param name is null.
	 * @param string $targetType Target type to retype param value or default if-null value. If param is an array, every param item will be retyped into given target type.
	 * @return string|string[]|mixed
	 */
	public function GetHeader (
		$name = "",
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
	 * Set directly all raw parameters without any conversion at once.
	 * @param array $params
	 * @return \MvcCore\Request
	 */
	public function & SetParams (array & $params = array()) {
		$this->params = & $params;
		return $this;
	}

	/**
	 * Get directly all raw parameters at once (with/without conversion).
	 * If any defined char groups in `$pregReplaceAllowedChars`, there will be returned
	 * all params filtered by given rule in `preg_replace()`.
	 * @param string|array $pregReplaceAllowedChars If String - list of regular expression characters to only keep, if array - `preg_replace()` pattern and reverse.
	 * @param array $onlyKeys Array with keys to get only. If empty (by default), all possible params are returned.
	 * @return array
	 */
	public function & GetParams ($pregReplaceAllowedChars = array('#[\<\>]#', ''), $onlyKeys = array()) {
		if ($this->params === NULL) $this->initParams();
		if ($pregReplaceAllowedChars === '' || $pregReplaceAllowedChars === '.*') {
			$result = $onlyKeys ? array_intersect_key($this->params, array_flip($onlyKeys)) : $this->params;
			return $result;
		}
		$cleanedParams = array();
		foreach ($this->params as $key => & $value) {
			if ($onlyKeys && !in_array($key, $onlyKeys)) continue;
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
	public function & SetParam ($name = "", $value = "") {
		if ($this->params === NULL) $this->initParams();
		$this->params[$name] = $value;
		return $this;
	}

	/**
	 * Get param value from `$_GET`, `$_POST` or `php://input`, filtered by
	 * "rule to keep defined characters only", defined in second argument (by `preg_replace()`).
	 * Place into second argument only char groups you want to keep.
	 * @param string $name Parametter string name.
	 * @param string|array $pregReplaceAllowedChars If String - list of regular expression characters to only keep, if array - `preg_replace()` pattern and reverse.
	 * @param mixed $ifNullValue Default value returned if given param name is null.
	 * @param string $targetType Target type to retype param value or default if-null value. If param is an array, every param item will be retyped into given target type.
	 * @return string|string[]|mixed
	 */
	public function GetParam (
		$name = "",
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
	 * Set directly whole raw global `$_FILES` without any conversion at once.
	 * @param array $files
	 * @return \MvcCore\Request
	 */
	public function & SetFiles (array & $files = array()) {
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
	 * @param string $file
	 * @param array $data
	 * @return \MvcCore\Request
	 */
	public function & SetFile ($file = '', $data = array()) {
		$this->globalFiles[$file] = $data;
		return $this;
	}

	/**
	 * Return item by file name from referenced global `$_FILES`
	 * or reference to any other testing array item representing it.
	 * @return array
	 */
	public function GetFile ($file = '') {
		if (isset($this->globalFiles[$file])) return $this->globalFiles[$file];
		return array();
	}


	/**
	 * Set directly whole raw global `$_COOKIE` without any conversion at once.
	 * @param array $cookies
	 * @return \MvcCore\Request
	 */
	public function & SetCookies (array & $cookies = array()) {
		$this->globalCookies = & $cookies;
		return $this;
	}

	/**
	 * Return reference to configured global `$_COOKIE`
	 * or reference to any other testing array representing it.
	 * @return array
	 */
	public function & GetCookies () {
		return $this->globalCookies;
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
	 * filtered by characters defined in second argument throught `preg_replace()`.
	 * Place into second argument only char groups you want to keep.
	 * @param string $name Cookie string name.
	 * @param string|array $pregReplaceAllowedChars If String - list of regular expression characters to only keep, if array - `preg_replace()` pattern and reverse.
	 * @param mixed $ifNullValue Default value returned if given param name is null.
	 * @param string $targetType Target type to retype param value or default if-null value. If param is an array, every param item will be retyped into given target type.
	 * @return string|string[]|mixed
	 */
	public function GetCookie (
		$name = "",
		$pregReplaceAllowedChars = "a-zA-Z0-9_;, /\-\.\@\=\+\?\!",
		$ifNullValue = NULL,
		$targetType = NULL
	) {
		return $this->getParamFromCollection(
			$this->globalCookies, $name, $pregReplaceAllowedChars, $ifNullValue, $targetType
		);
	}


	/**
	 * Initialize all possible protected values from all globals,
	 * including all http headers, all params and application inputs.
	 * This method is not recomanded to use in production mode, it's
	 * designed mostly for development purposes, to see in one moment,
	 * what could be inside request after calling any getter method.
	 * @return \MvcCore\Request
	 */
	public function & InitAll () {
		$this->GetScriptName();
		$this->GetAppRoot();
		$this->GetMethod();
		$this->GetBasePath();
		$this->GetProtocol();
		$this->IsSecure();
		$this->GetServerName();
		$this->GetHost();
		$this->GetRequestPath();
		$this->GetFullUrl();
		$this->GetReferer();
		$this->GetMicrotime();
		$this->IsAjax();
		if ($this->port === NULL) $this->initUrlSegments();
		if ($this->headers === NULL) $this->initHeaders();
		if ($this->params === NULL) $this->initParams();
		$this->GetServerIp();
		$this->GetClientIp();
		$this->GetContentLength();
		return $this;
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
	 * Set media site key - `"full" | "tablet" | "mobile"`.
	 * Use this media site key storage by your own decision.
	 * Example: `"full" | "tablet" | "mobile"`
	 * @var string|NULL
	 */
	public function & SetMediaSiteKey ($mediaSiteKey) {
		$this->mediaSiteKey = $mediaSiteKey;
		return $this;
	}

	/**
	 * Get media site key - `"full" | "tablet" | "mobile"`.
	 * To use this variable - install `\MvcCore\Router` extension `\MvcCoreExt\Router\Media`
	 * Or use this variable by your own decision.
	 * Example: `"full" | "tablet" | "mobile"`
	 * @var string|NULL
	 */
	public function GetMediaSiteKey () {
		return $this->mediaSiteKey;
	}


	/**
	 * Sets any custom property `"PropertyName"` by `\MvcCore\Request::SetPropertyName("value")`,
	 * which is not necessary to define previously or gets previously defined
	 * property `"PropertyName"` by `\MvcCore\Request::GetPropertyName();`.
	 * Throws exception if no property defined by get call or if virtual call
	 * begins with anything different from 'Set' or 'Get'.
	 * This method returns custom value for get and `\MvcCore\Request` instance for set.
	 * @param string $name
	 * @param array  $arguments
	 * @throws \InvalidArgumentException
	 * @return mixed|\MvcCore\Request
	 */
	public function __call ($name, $arguments = array()) {
		$nameBegin = strtolower(substr($name, 0, 3));
		$prop = substr($name, 3);
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
			$indexFilePath = ucfirst(str_replace(array('\\', '//'), '/', $this->globalServer['SCRIPT_FILENAME']));
			if (strpos(__FILE__, 'phar://') === 0) {
				$this->appRoot = 'phar://' . $indexFilePath;
			} else {
				$this->appRoot = substr($indexFilePath, 0, mb_strrpos($indexFilePath, '/'));
			}
		}
		return $this->appRoot;
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
	 * @return string
	 */
	public function GetReferer () {
		if ($this->referer === NULL) {
			$referer = isset($this->globalServer['HTTP_REFERER'])
				? $this->globalServer['HTTP_REFERER']
				: '';
			if ($referer) $referer = filter_var($referer, FILTER_SANITIZE_URL) ?: '';
			$this->referer = $referer;
		}
		return $this->referer;
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
	 * Get application server name - domain without any port.
	 * Example: `"localhost"`
	 * @return string
	 */
	public function GetServerName () {
		if ($this->serverName === NULL) $this->serverName = $this->globalServer['SERVER_NAME'];
		return $this->serverName;
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
	 * Http port defined in requested url if any, parsed by `parse_url().
	 * Empty string if there is no port number in requested address.`.
	 * Example: `"88" | ""`
	 * @return string
	 */
	public function GetPort () {
		if ($this->port === NULL) $this->initUrlSegments();
		return $this->port;
	}

	/**
	 * Get requested path in from application root (if `mod_rewrite` enabled), never with query string.
	 * Example: `"/products/page/2"`
	 * @return string
	 */
	public function GetPath () {
		if ($this->path === NULL) $this->initUrlSegments();
		return $this->path;
	}

	/**
	 * Get uri query string (without question mark character by default).
	 * Example: `"param-1=value-1&param-2=value-2&param-3[]=value-3-a&param-3[]=value-3-b"`
	 * @param bool $withQuestionMark If `FALSE` (by default), query string is returned always without question
	 *                               mark character at the beginning.
	 *                               If `TRUE`, and query string contains any character(s), query string is returned
	 *                               with question mark character at the beginning. But if query string contains no
	 *                               character(s), query string is returned as EMPTY STRING WITHOUT question mark character.
	 * @return string
	 */
	public function GetQuery ($withQuestionMark = FALSE) {
		if ($this->query === NULL) $this->initUrlSegments();
		return ($withQuestionMark && mb_strlen($this->query) > 0)
			? '?' . $this->query
			: $this->query;
	}

	/**
	 * Get request path after domain with possible query string
	 * Example: `"/requested/path/after/app/root?with=possible&query=string"`
	 * @return string
	 */
	public function GetRequestPath () {
		if ($this->requestPath === NULL) {
			$this->requestPath = $this->GetPath() . $this->GetQuery(TRUE) . $this->GetFragment(TRUE);
		}
		return $this->requestPath;
	}

	/**
	 * Get url to requested domain and possible port.
	 * Example: `"https://domain.com" | "http://domain:88"` if any port.
	 * @return string
	 */
	public function GetDomainUrl () {
		if ($this->domainUrl === NULL) $this->domainUrl = $this->GetProtocol() . '//' . $this->GetHost();
		return $this->domainUrl;
	}

	/**
	 * Get base url to application root.
	 * Example: `"http://domain:88/my/development/direcotry/www"`
	 * @return string
	 */
	public function GetBaseUrl () {
		if ($this->baseUrl === NULL) $this->baseUrl = $this->GetDomainUrl() . $this->GetBasePath();
		return $this->baseUrl;
	}

	/**
	 * Get request url including scheme, domain, port, path, without any query string
	 * Example: "`http://localhost:88/my/development/direcotry/www/requested/path/after/domain"`
	 * @return string
	 */
	public function GetRequestUrl () {
		if ($this->requestUrl === NULL) $this->requestUrl = $this->GetBaseUrl() . $this->GetPath();
		return $this->requestUrl;
	}

	/**
	 * Get request url including scheme, domain, port, path and with query string
	 * Example: `"http://localhost:88/my/development/direcotry/www/requested/path/after/domain?with=possible&query=string"`
	 * @return string
	 */
	public function GetFullUrl () {
		if ($this->fullUrl === NULL) {
			$this->fullUrl = $this->GetRequestUrl() . $this->GetQuery(TRUE) . $this->GetFragment(TRUE);
		}
		return $this->fullUrl;
	}

	/**
	 * Get uri fragment parsed by `parse_url()` (without hash character by default).
	 * Example: `"any-sublink-path"`
	 * @param bool $withHash If `FALSE` (by default), fragment is returned always without hash character
	 *                       at the beginning.
	 *                       If `TRUE`, and fragment contains any character(s), fragment is returned
	 *                       with hash character at the beginning. But if fragment contains no
	 *                       character(s), fragment is returned as EMPTY STRING WITHOUT hash character.
	 * @return string
	 */
	public function GetFragment ($withHash = FALSE) {
		if ($this->fragment === NULL) $this->initUrlSegments();
		return ($withHash && mb_strlen($this->fragment) > 0)
			? '?' . $this->fragment
			: $this->fragment;
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
				is_int($this->globalServer['CONTENT_LENGTH'])
			) {
				$this->contentLength = intval($this->globalServer['CONTENT_LENGTH']);
			}
		}
		return $this->contentLength;
	}

	/**
	 * Parse list of comma separated language tags and sort it by the
	 * quality value from `$this->globalServer['HTTP_ACCEPT_LANGUAGE']`.
	 * @param string[] $languagesList
	 * @return array
	 */
	public static function ParseHttpAcceptLang ($languagesList) {
		$languages = array();
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
				if (!isset($languages[$priority])) $languages[$priority] = array();
				$langOrLangWithLocale = str_replace('-', '_', $match[1]);
				$delimiterPos = strpos($langOrLangWithLocale, '_');
				if ($delimiterPos !== FALSE) {
					$languages[$priority][] = array(
						strtolower(substr($langOrLangWithLocale, 0, $delimiterPos)),
						strtoupper(substr($langOrLangWithLocale, $delimiterPos + 1))
					);
				} else {
					$languages[$priority][] = array(
						strtolower($langOrLangWithLocale),
						NULL
					);
				}
			}
		}
		krsort($languages);
		reset($languages);
		return $languages;
	}


	/**
	 * Initialize url segments parsed by `parse_url()`
	 * php method: port, path, query and fragment.
	 * @return void
	 */
	protected function initUrlSegments () {
		$absoluteUrl = $this->GetProtocol() . '//'
			. $this->globalServer['HTTP_HOST']
			. $this->globalServer['REQUEST_URI'];
		$parsedUrl = parse_url($absoluteUrl);
		$this->port = isset($parsedUrl['port']) ? $parsedUrl['port'] : '';
		$this->path = isset($parsedUrl['path']) ? $parsedUrl['path'] : '';
		$this->path = mb_substr($this->path, mb_strlen($this->GetBasePath()));
		$this->query = isset($parsedUrl['query']) ? $parsedUrl['query'] : '';
		$this->fragment = isset($parsedUrl['fragment']) ? $parsedUrl['fragment'] : '';
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
			$headers = array();
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
			$postValues = array();
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
		$result = array();
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
	 * filtered by characters defined in second argument throught `preg_replace()`.
	 * Place into second argument only char groups you want to keep.
	 * @param array $collection Array with request params or array with request headers.
	 * @param string $name Parametter string name.
	 * @param string|array $pregReplaceAllowedChars If String - list of regular expression characters to only keep, if array - `preg_replace()` pattern and reverse.
	 * @param mixed $ifNullValue Default value returned if given param name is null.
	 * @param string $targetType Target type to retype param value or default if-null value. If param is an array, every param item will be retyped into given target type.
	 * @return string|string[]|mixed
	 */
	protected function getParamFromCollection (
		& $paramsCollection = array(),
		$name = "",
		$pregReplaceAllowedChars = "a-zA-Z0-9_;, /\-\@\:",
		$ifNullValue = NULL,
		$targetType = NULL
	) {
		if (!isset($paramsCollection[$name])) return NULL;
		if (gettype($paramsCollection[$name]) == 'array') {
			$result = array();
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
	 * Get filtered param or header value for characters defined as second argument to use them in `preg_replace()`.
	 * @param string|string[]|NULL $rawValue
	 * @param string|array $pregReplaceAllowedChars If String - list of regular expression characters to only keep, if array - `preg_replace()` pattern and reverse.
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
			$rawValue = trim($rawValue);
			if (mb_strlen($rawValue) === 0) {
				// if value after trim is empty string, return empty string (retyped if necessary)
				$result = "";
				if ($targetType === NULL) return $result;
				settype($result, $targetType);
				return $result;
			} else if ($pregReplaceAllowedChars == '.*') {
				// if there is something in target collection and all chars are allowed
				$result = $rawValue;
				if ($targetType === NULL) return $result;
				settype($result, $targetType);
				return $result;
			} else if (gettype($rawValue) == 'array') {
				// if there is something in target collection and it's an array
				$result = array();
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
	 * @param string|array $pregReplaceAllowedChars If String - list of regular expression characters to only keep, if array - `preg_replace()` pattern and reverse.
	 * @return string
	 */
	protected function cleanParamValue ($rawValue, $pregReplaceAllowedChars = "a-zA-Z0-9_;, /\-\@\:") {
		if (is_array($pregReplaceAllowedChars)) {
			return preg_replace($pregReplaceAllowedChars[0], $pregReplaceAllowedChars[1], $rawValue);
		} else {
			return preg_replace("#[^" . $pregReplaceAllowedChars . "]#", "", $rawValue);
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
			$redirectUrl = isset($this->globalServer['REDIRECT_URL']) ? $this->globalServer['REDIRECT_URL'] : '';
			$redirectUrlLength = mb_strlen($redirectUrl);
			$requestUri = $this->globalServer['REQUEST_URI'];
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
				: array(NULL, NULL);
		} else {
			$languagesAndLocales = static::ParseHttpAcceptLang($rawUaLanguages);
			$langAndLocaleArr = current($languagesAndLocales);
			if (gettype($langAndLocaleArr) == 'array') $langAndLocaleArr = current($langAndLocaleArr);
		}
		list($this->lang, $this->locale) = $langAndLocaleArr;
	}
}
