<?php

/**
 * MvcCore
 *
 * This source file is subject to the BSD 3 License
 * For the full copyright and license information, please view
 * the LICENSE.md file that are distributed with this source code.
 *
 * @copyright	Copyright (c) 2016 Tom Flídr (https://github.com/mvccore/mvccore)
 * @license		https://mvccore.github.io/docs/mvccore/4.0.0/LICENCE.md
 */

namespace MvcCore\Interfaces;

//include_once(__DIR__.'/../Application.php');

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
interface IRequest
{
	/**
	 * Non-secured HTTP protocol (`http:`).
	 */
	const PROTOCOL_HTTP = 'http:';

	/**
	 * Secured HTTP(s) protocol (`https:`).
	 */
	const PROTOCOL_HTTPS = 'https:';

	/**
	 * Retrieves the information or entity that is identified by the URI of the request.
	 */
	const METHOD_GET = 'GET';

	/**
	 * Posts a new entity as an addition to a URI.
	 */
	const METHOD_POST = 'POST';

	/**
	 * Replaces an entity that is identified by a URI.
	 */
	const METHOD_PUT = 'PUT';

	/**
	 * Requests that a specified URI be deleted.
	 */
	const METHOD_DELETE = 'DELETE';

	/**
	 * Retrieves the message headers for the information or entity that is identified by the URI of the request.
	 */
	const METHOD_HEAD = 'HEAD';

	/**
	 * Represents a request for information about the communication options available on the request/response chain identified by the Request-URI.
	 */
	const METHOD_OPTIONS = 'OPTIONS';

	/**
	 * Requests that a set of changes described in the request entity be applied to the resource identified by the Request- URI.
	 */
	const METHOD_PATCH = 'PATCH';


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
	 * @return \MvcCore\Interfaces\IRequest
	 */
	public static function GetInstance (
		array & $server = array(),
		array & $get = array(),
		array & $post = array(),
		array & $cookie = array(),
		array & $files = array()
	);


    /**
     * Get one of the global data collections stored as protected properties inside request object.
     * Example:
     *  // to get global `$_GET` with raw values:
     *  `$globalGet = $request->GetGlobalCollection('get');`
     * @param string $type
     * @return array
     */
    public function & GetGlobalCollection ($type);

	/**
	 * Set directly all raw http headers without any conversion at once.
	 * Header name(s) as array keys should be in standard format like:
	 * `"Content-Type" | "Content-Length" | "X-Requested-With" ...`.
	 * @param array $headers
	 * @return \MvcCore\Interfaces\IRequest
	 */
	public function & SetHeaders (array & $headers = array());

	/**
     * Get directly all raw http headers at once (with/without conversion).
     * If headers are not initialized, initialize headers by
     * `getallheaders()` or from `$_SERVER['HTTP_...']`.
     * Headers are returned as `key => value` array, headers keys are
     * in standard format like: `"Content-Type" | "Content-Length" | "X-Requested-With" ...`.
     * @param string|array $pregReplaceAllowedChars If String - list of regular expression characters to only keep, if array - `preg_replace()` pattern and reverse.
     * @return array
     */
	public function & GetHeaders ();

	/**
	 * Set directly raw http header value without any conversion.
	 * Header name should be in standard format like:
	 * `"Content-Type" | "Content-Length" | "X-Requested-With" ...`.
	 * @param string $name
	 * @param string|string[] $value
	 * @return \MvcCore\Interfaces\IRequest
	 */
	public function & SetHeader ($name = "", $value = "");

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
	);


	/**
	 * Set directly all raw parameters without any conversion at once.
	 * @param array $params
	 * @return \MvcCore\Interfaces\IRequest
	 */
	public function & SetParams (array & $params = array());

	/**
     * Get directly all raw parameters at once (with/without conversion).
     * If any defined char groups in `$pregReplaceAllowedChars`, there will be returned
     * all params filtered by given rule in `preg_replace()`.
     * @param string|array $pregReplaceAllowedChars If String - list of regular expression characters to only keep, if array - `preg_replace()` pattern and reverse.
     * @return array
     */
	public function & GetParams ($pregReplaceAllowedChars = array('#\<\>#', ''));

	/**
	 * Set directly raw parameter value without any conversion.
	 * @param string $name
	 * @param string|string[] $value
	 * @return \MvcCore\Interfaces\IRequest
	 */
	public function & SetParam ($name = "", $value = "");

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
		$pregReplaceAllowedChars = "a-zA-Z0-9_/\-\.\@",
		$ifNullValue = NULL,
		$targetType = NULL
	);


	/**
	 * Set directly whole raw global `$_FILES` without any conversion at once.
	 * @param array $files
	 * @return \MvcCore\Interfaces\IRequest
	 */
	public function & SetFiles (array & $files = array());

	/**
	 * Return reference to configured global `$_FILES`
	 * or reference to any other testing array representing it.
	 * @return array
	 */
	public function & GetFiles ();

	/**
	 * Set file item into global `$_FILES` without any conversion at once.
	 * @param string $file
	 * @param array $data
	 * @return \MvcCore\Interfaces\IRequest
	 */
	public function & SetFile ($file = '', $data = array());

	/**
	 * Return item by file name from referenced global `$_FILES`
	 * or reference to any other testing array item representing it.
	 * @return array
	 */
	public function GetFile ($file = '');


	/**
	 * Set directly whole raw global `$_COOKIE` without any conversion at once.
	 * @param array $cookies
	 * @return \MvcCore\Interfaces\IRequest
	 */
	public function & SetCookies (array & $cookies = array());

	/**
	 * Return reference to configured global `$_COOKIE`
	 * or reference to any other testing array representing it.
	 * @return array
	 */
	public function & GetCookies ();

	/**
	 * Set raw request cookie into referenced global `$_COOKIE` without any conversion.
	 * @param string $name
	 * @param string|string[] $value
	 * @return \MvcCore\Interfaces\IRequest
	 */
	public function & SetCookie ($name = "", $value = "");

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
	);


	/**
	 * Initialize all possible protected values from all globals,
	 * including all http headers, all params and application inputs.
	 * This method is not recomanded to use in production mode, it's
	 * designed mostly for development purposes, to see in one moment,
	 * what could be inside request after calling any getter method.
	 * @return \MvcCore\Interfaces\IRequest
	 */
	public function InitAll ();

	/**
	 * Return `TRUE` boolean flag if request target
	 * is anything different than `Controller:Asset`.
	 * @return bool
	 */
	public function IsAppRequest ();

	/**
     * Set cleaned requested controller name into `\MvcCore\Request::$controllerName;`
     * and into `\MvcCore\Request::$Params['controller'];`.
     * @param string $controllerName
     * @return \MvcCore\Interfaces\IRequest
     */
	public function & SetControllerName ($controllerName);

	/**
	 * Return cleaned requested controller name from `\MvcCore\Request::$Params['controller'];`.
	 * @return string
	 */
	public function GetControllerName ();

	/**
     * Set cleaned requested controller name into `\MvcCore\Request::$actionName;`
     * and into `\MvcCore\Request::$Params['action'];`.
     * @param string $actionName
     * @return \MvcCore\Interfaces\IRequest
     */
	public function & SetActionName ($actionName);

	/**
	 * Return cleaned requested action name from `\MvcCore\Request::$Params['action'];`.
	 * @return string
	 */
	public function GetActionName ();


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
	 * @return \MvcCore\Interfaces\IRequest
	 */
	public function __call ($name, $arguments = array());

	/**
	 * Universal getter, if property not defined, `NULL` is returned.
	 * @param string $name
	 * @return mixed
	 */
	public function __get ($name);

	/**
	 * Universal setter, if property not defined, it's automaticly declarated.
	 * @param string $name
	 * @param mixed	 $value
	 * @return \MvcCore\Interfaces\IRequest
	 */
	public function __set ($name, $value);


	/**
	 * Php requested script name path from application root.
	 * Example: `"/index.php"`
	 * @return string
	 */
	public function GetScriptName ();

	/**
	 * Get application root path on hard drive.
	 * Example: `"C:/www/my/development/direcotry/www"`
	 * @return string
	 */
	public function GetAppRoot ();

	/**
	 * Get uppercased http method from global `$_SERVER['REQUEST_METHOD']`.
	 * Example: `"GET" | "POST" | "PUT" | "HEAD"...`
	 * @return string
	 */
	public function GetMethod ();

	/**
	 * Get base app directory path after domain,
	 * if application is placed in domain subdirectory.
	 * Example:
	 * - full url:  `"http://localhost:88/my/development/direcotry/www/requested/path/after/domain?with=possible&query=string"`
	 * - base path: `"/my/development/direcotry/www"`
	 * @return string
	 */
	public function GetBasePath ();

	/**
	 * Get http protocol string.
	 * Example: `"http:" | "https:"`
	 * @return string
	 */
	public function GetProtocol ();

	/**
     * Get `TRUE` if http protocol is `"https:"`.
     * @return bool
     */
	public function IsSecure ();

	/**
     * Get referer url if any, safely readed by:
     * `filter_var($_SERVER['HTTP_REFERER'], FILTER_SANITIZE_URL);`
     * Example: `"http://foreing.domain.com/path/where/is/link/to/?my=app"`
	 * @return string
	 */
	public function GetReferer ();

	/**
     * Get timestamp of the start of the request, with microsecond precision.
     * @return float
     */
	public function GetMicrotime ();

	/**
	 * Get application server name - domain without any port.
	 * Example: `"localhost"`
	 * @return string
	 */
	public function GetServerName ();

	/**
	 * Get application host with port if there is any.
	 * Example: `"localhost:88"`
	 * @return string
	 */
	public function GetHost ();

	/**
	 * Get http port parsed by `parse_url()`.
	 * Example: `"88"`
	 * @return string
	 */
	public function GetPort ();

	/**
	 * Get requested path in from application root (if `mod_rewrite` enabled), never with query string.
	 * Example: `"/products/page/2"`
	 * @return string
	 */
	public function GetPath ();

	/**
	 * Get uri query string without question mark.
	 * Example: `"param-1=value-1&param-2=value-2&param-3[]=value-3-a&param-3[]=value-3-b"`
	 * @return string
	 */
	public function GetQuery ();

	/**
	 * Get request path after domain with possible query string
	 * Example: `"/requested/path/after/app/root?with=possible&query=string"`
     * @return string
	 */
	public function GetRequestPath ();

	/**
	 * Get url to requested domain and possible port.
	 * Example: `"https://domain.com" | "http://domain:88"` if any port.
     * @return string
	 */
	public function GetDomainUrl ();

	/**
	 * Get base url to application root.
	 * Example: `"http://domain:88/my/development/direcotry/www"`
     * @return string
	 */
	public function GetBaseUrl ();

	/**
	 * Get request url including scheme, domain, port, path, without any query string
	 * Example: "`http://localhost:88/my/development/direcotry/www/requested/path/after/domain"`
     * @return string
	 */
	public function GetRequestUrl ();

	/**
	 * Get request url including scheme, domain, port, path and with query string
	 * Example: `"http://localhost:88/my/development/direcotry/www/requested/path/after/domain?with=possible&query=string"`
     * @return string
	 */
	public function GetFullUrl ();

	/**
	 * Get uri fragment parsed by `parse_url()` including hash.
	 * Example: `"#any-sublink-path"`
	 * @return string
	 */
	public function GetFragment ();

	/**
	 * Get `TRUE` if request is requested on the background
	 * with usual Javascript HTTP header containing:
	 * `X-Requested-With: AnyJsFrameworkName`.
	 * @return bool
	 */
	public function IsAjax ();
}
