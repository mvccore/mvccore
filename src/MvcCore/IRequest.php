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

namespace MvcCore;

/**
 * Responsibility - request description - URL and params inputs parsing and cleaning.
 * - Linear request URL parsing from referenced `$_SERVER` global variable
 *   (as constructor argument) into local properties, describing URL sections.
 * - Params reading from referenced `$_GET` and `$_POST` global variables
 *   (as constructor arguments) or reading data from direct PHP
 *   input `"php://input"` (as encoded JSON data or as query string).
 * - Headers cleaning and reading by `getallheaders()` or from referenced `$_SERVER['HTTP_...']`.
 * - Cookies cleaning and reading from referenced `$_COOKIE['...']`.
 * - Uploaded files by wrapped referenced `$_FILES` global array.
 * - Primitive values cleaning or array recursive cleaning by called
 *   developer rules from params array, headers array and cookies array.
 */
interface IRequest extends \MvcCore\Request\IConstants {

	/**
	 * Parse list of comma separated language tags and sort it by the
	 * quality value from `$this->globalServer['HTTP_ACCEPT_LANGUAGE']`.
	 * @param  \string[] $languagesList
	 * @return array
	 */
	public static function ParseHttpAcceptLang ($languagesList);

	/**
	 * Add exceptional two-segment top-level domain like
	 * `'co.jp', 'co.uk', 'co.kr', 'co.nf' ...` to parse
	 * domain string correctly.
	 * Example:
	 * `\MvcCore\Request::AddTwoSegmentTlds('co.uk', 'co.jp');`
	 * `\MvcCore\Request::AddTwoSegmentTlds(['co.uk', 'co.jp']);`
	 * @param  \string[] $twoSegmentTlds,... List of two-segment top-level domains without leading dot.
	 * @return void
	 */
	public static function AddTwoSegmentTlds ($twoSegmentTlds);

	/**
	 * Static factory to get every time new instance of http request object.
	 * Global variables for constructor arguments (`$_SERVER`, `$_GET`, `$_POST`...)
	 * should be changed to any arrays with any values and injected here to get
	 * different request object then currently called real request object.
	 * For example to create fake request object for testing purposes
	 * or for non-real request rendering into request output cache.
	 * @param  array $server
	 * @param  array $get
	 * @param  array $post
	 * @param  array $cookie
	 * @param  array $files
	 * @return \MvcCore\Request
	 */
	public static function CreateInstance (
		array & $server = [],
		array & $get = [],
		array & $post = [],
		array & $cookie = [],
		array & $files = []
	);


	/**
	 * Get one of the global data collections stored as protected properties inside request object.
	 * Example:
	 *  // to get global `$_GET` with raw values:
	 *  `$globalGet = $request->GetGlobalCollection('get');`
	 * @param  string $type
	 * @return array
	 */
	public function & GetGlobalCollection ($type);

	/**
	 * Set directly all raw http headers without any conversion at once.
	 * Header name(s) as array keys should be in standard format like:
	 * `"Content-Type" | "Content-Length" | "X-Requested-With" ...`.
	 * @param  array $headers
	 * @return \MvcCore\Request
	 */
	public function SetHeaders (array & $headers = []);

	/**
	 * Get directly all raw http headers at once (with/without conversion).
	 * If headers are not initialized, initialize headers by
	 * `getallheaders()` or from `$_SERVER['HTTP_...']`.
	 * Headers are returned as `key => value` array, headers keys are
	 * in standard format like: `"Content-Type" | "Content-Length" | "X-Requested-With" ...`.
	 * @param  string|array|bool $pregReplaceAllowedChars If String - list of regular expression characters to only keep, if array - `preg_replace()` pattern and reverse, if `FALSE`, raw value is returned.
	 * @return array
	 */
	public function & GetHeaders ($pregReplaceAllowedChars = ['#[\<\>\'"]#', '']);

	/**
	 * Set directly raw http header value without any conversion.
	 * Header name should be in standard format like:
	 * `"Content-Type" | "Content-Length" | "X-Requested-With" ...`.
	 * @param  string          $name
	 * @param  string|string[] $value
	 * @return \MvcCore\Request
	 */
	public function SetHeader ($name = '', $value = '');

	/**
	 * Get http header value filtered by "rule to keep defined characters only",
	 * defined in second argument (by `preg_replace()`). Place into second argument
	 * only char groups you want to keep. Header has to be in format like:
	 * `"Content-Type" | "Content-Length" | "X-Requested-With" ...`.
	 * @param  string            $name                    Http header string name.
	 * @param  string|array|bool $pregReplaceAllowedChars If String - list of regular expression characters to only keep, if array - `preg_replace()` pattern and reverse, if `FALSE`, raw value is returned.
	 * @param  mixed             $ifNullValue             Default value returned if given param name is null.
	 * @param  string            $targetType              Target type to retype param value or default if-null value. If param is an array, every param item will be retyped into given target type.
	 * @throws \InvalidArgumentException                 `$name` must be a `$targetType`, not an `array`.
	 * @return string|\string[]|int|\int[]|bool|\bool[]|array|mixed
	 */
	public function GetHeader (
		$name = '',
		$pregReplaceAllowedChars = "a-zA-Z0-9_;, /\-\.\@\=\+\?\!",
		$ifNullValue = NULL,
		$targetType = NULL
	);

	/**
	 * Return if request has any http header by given name.
	 * @param  string $name Http header string name.
	 * @return bool
	 */
	public function HasHeader ($name = '');


	/**
	 * Set directly all raw parameters without any conversion at once.
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
	);

	/**
	 * Get directly all raw parameters at once (with/without conversion).
	 * If any defined char groups in `$pregReplaceAllowedChars`, there will be returned
	 * all params filtered by given rule in `preg_replace()`.
	 * @param  string|array|bool $pregReplaceAllowedChars If String - list of regular expression characters to only keep, if array - `preg_replace()` pattern and reverse, if `FALSE`, raw value is returned.
	 * @param  array             $onlyKeys                Array with keys to get only. If empty (by default), all possible params are returned.
	 * @param  int               $sourceType              Param source collection flag(s). If defined, there are returned only params from given collection types.
	 * @return array
	 */
	public function & GetParams (
		$pregReplaceAllowedChars = ['#[\<\>\'"]#', ''], 
		$onlyKeys = [], 
		$sourceType = \MvcCore\IRequest::PARAM_TYPE_ANY
	);

	/**
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
	);

	/**
	 * Get param value from `$_GET`, `$_POST` or `php://input`, filtered by
	 * "rule to keep defined characters only", defined in second argument (by `preg_replace()`).
	 * Place into second argument only char groups you want to keep.
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
	);

	/**
	 * Get param source type flag as integer:
	 * - `1` - `\MvcCore\IRequest::PARAM_TYPE_QUERY_STRING`
	 * - `2` - `\MvcCore\IRequest::PARAM_TYPE_URL_REWRITE`
	 * - `4` - `\MvcCore\IRequest::PARAM_TYPE_INPUT`
	 * @param  string $name 
	 * @return int
	 */
	public function GetParamSourceType ($name);

	/**
	 * Change existing param source type flag:
	 * - `1` - `\MvcCore\IRequest::PARAM_TYPE_QUERY_STRING`
	 * - `2` - `\MvcCore\IRequest::PARAM_TYPE_URL_REWRITE`
	 * - `4` - `\MvcCore\IRequest::PARAM_TYPE_INPUT`
	 * @param  string $name       Existing param name.
	 * @param  int    $sourceType Param source collection flag(s).
	 * @return \MvcCore\Request
	 */
	public function SetParamSourceType ($name, $sourceType = \MvcCore\IRequest::PARAM_TYPE_ANY);
	
	/**
	 * Get `TRUE` if any non `NULL` param value exists in `$_GET`, `$_POST`, `php://input` or in any other source.
	 * @param  string $name       Parameter string name.
	 * @param  int    $sourceType Param source collection flag(s). If defined, there is returned `TRUE` only for param in given collection type(s).
	 * @return bool
	 */
	public function HasParam (
		$name, 
		$sourceType = \MvcCore\IRequest::PARAM_TYPE_ANY
	);
	
	/**
	 * Remove parameter by name.
	 * @param  string $name
	 * @return \MvcCore\Request
	 */
	public function RemoveParam ($name);


	/**
	 * Set directly whole raw global `$_FILES` without any conversion at once.
	 * @param  array $files
	 * @return \MvcCore\Request
	 */
	public function SetFiles (array & $files = []);

	/**
	 * Return reference to configured global `$_FILES`
	 * or reference to any other testing array representing it.
	 * @return array
	 */
	public function & GetFiles ();

	/**
	 * Set file item into global `$_FILES` without any conversion at once.
	 * @param  string $file Uploaded file string name.
	 * @param  array  $data
	 * @return \MvcCore\Request
	 */
	public function SetFile ($file = '', $data = []);

	/**
	 * Return item by file name from referenced global `$_FILES`
	 * or reference to any other testing array item representing it.
	 * @param  string $file Uploaded file string name.
	 * @return array
	 */
	public function GetFile ($file = '');

	/**
	 * Return if any item by file name exists or not in referenced global `$_FILES`.
	 * @param  string $file Uploaded file string name.
	 * @return bool
	 */
	public function HasFile ($file = '');


	/**
	 * Set directly whole raw global `$_COOKIE` without any conversion at once.
	 * @param  array $cookies
	 * @return \MvcCore\Request
	 */
	public function SetCookies (array & $cookies = []);

	/**
	 * Get directly all raw global `$_COOKIE`s at once (with/without conversion).
	 * Cookies are returned as `key => value` array.
	 * @param  string|array|bool $pregReplaceAllowedChars If String - list of regular expression characters to only keep, if array - `preg_replace()` pattern and reverse, if `FALSE`, raw value is returned.
	 * @param  array             $onlyKeys                Array with keys to get only. If empty (by default), all possible cookies are returned.
	 * @return array
	 */
	public function & GetCookies ($pregReplaceAllowedChars = ['#[\<\>\'"]#', ''], $onlyKeys = []);

	/**
	 * Set raw request cookie into referenced global `$_COOKIE` without any conversion.
	 * @param  string          $name
	 * @param  string|string[] $value
	 * @return \MvcCore\Request
	 */
	public function SetCookie ($name = '', $value = '');

	/**
	 * Get request cookie value from referenced global `$_COOKIE` variable,
	 * filtered by characters defined in second argument through `preg_replace()`.
	 * Place into second argument only char groups you want to keep.
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
	);

	/**
	 * Return if any item by cookie name exists or not in referenced global `$_COOKIE`.
	 * @param  string $name Cookie string name.
	 * @return bool
	 */
	public function HasCookie ($name = '');


	/**
	 * Initialize all possible protected values from all global variables,
	 * including all http headers, all params and application inputs.
	 * This method is not recommended to use in production mode, it's
	 * designed mostly for development purposes, to see in one moment,
	 * what could be inside request after calling any getter method.
	 * @return \MvcCore\Request
	 */
	public function InitAll ();

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
	 * @param  string $rawName
	 * @param  array  $arguments
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
	 * @return \MvcCore\Request
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
	 * Get application root path.
	 * This value is always the same with webserver document root for single file projects.
	 * Example: `"C:/www/my/development/project"`
	 * @return string
	 */
	public function GetAppRoot ();
	
	/**
	 * Set application root path.
	 * This value has to be the same with webserver document root for single file projects.
	 * Example: `"C:/www/my/development/project"`
	 * @param  string $appRoot
	 * @return \MvcCore\Request
	 */
	public function SetAppRoot ($appRoot);

	/**
	 * Get webserver document root path, where is `/index.php` located.
	 * This value is always the same with application root for single file projects. 
	 * Example: `"C:/www/my/development/project/www"`
	 * @return string
	 */
	public function GetDocumentRoot ();
	
	/**
	 * Set webserver document root path, where is `/index.php` located.
	 * This value is always the same with application root for single file projects. 
	 * Example: `"C:/www/my/development/project/www"`
	 * @param  string $documentRoot
	 * @return \MvcCore\Request
	 */
	public function SetDocumentRoot ($documentRoot);

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
	 * @return string
	 */
	public function GetHost ();

	/**
	 * Set http port defined in requested URL if any.
	 * Empty string if there is no port number in requested address.`.
	 * Example: `$request->SetPort("88")`
	 * @param  string $rawPort
	 * @return \MvcCore\Request
	 */
	public function SetPort ($rawPort);

	/**
	 * Get http port defined in requested URL if any.
	 * Empty string if there is no port number in requested address.`.
	 * Example: `"88" | ""`
	 * @return string
	 */
	public function GetPort ();

	/**
	 * Set requested path in from application root (if `mod_rewrite` enabled), never with query string.
	 * Example: `$request->SetPort("/products/page/2");`
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
	 * @return string
	 */
	public function GetDomainUrl ();

	/**
	 * Get base URL to application root.
	 * Example: `"http://domain:88/my/development/direcotry/www"`
	 * @return string
	 */
	public function GetBaseUrl ();

	/**
	 * Get request URL including scheme, domain, port, path, without any query string
	 * Example: "`http://localhost:88/my/development/direcotry/www/requested/path/after/domain"`
	 * @param  bool $rawInput Get raw input if `TRUE`. `FALSE` by default to get value through `htmlspecialchars($result);` without ampersand `&` escaping.
	 * @return string
	 */
	public function GetRequestUrl ($rawInput = FALSE);

	/**
	 * Get request URL including scheme, domain, port, path and with query string
	 * Example: `"http://localhost:88/my/development/direcotry/www/requested/path/after/domain?with=possible&query=string"`
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
	 * Convert special characters to HTML entities except ampersand `&`.
	 * Remove all ASCII characters from 0 to 31 except `\r`,`\n` and `\t`.
	 * @see http://php.net/manual/en/function.htmlspecialchars.php
	 * @param  string $str
	 * @return string
	 */
	public static function HtmlSpecialChars ($str);
}
