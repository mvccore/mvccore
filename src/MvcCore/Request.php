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

namespace MvcCore;

require_once(__DIR__ . '/Interfaces/IRequest.php');
require_once('Tool.php');
require_once(__DIR__.'/../MvcCore.php');

/**
 * - Linear request url parsing from `$_SERVER` global variable
 *   (as constructor argument) into local properties describing url sections.
 * - Params reading from `$_GET` and `$_POST` global variables
 *   (as constructor arguments) or readed from direct PHP input: `"php://input"` (in JSON or in query string).
 * - Params recursive cleaning by called developer rules.
 */
class Request implements Interfaces\IRequest
{
	/**
	 * Language international code, lowercase, not used by default.
	 * To use this variable - install `\MvcCore\Router` extension `\MvcCoreExt\Router\Lang` or write your own.
	 * Example: `"en"`
	 * @var string
	 */
	public $Lang		= '';

	/**
	 * Country/locale code, uppercase, not used by default.
	 * To use this variable - install `\MvcCore\Router` extension `\MvcCoreExt\Router\Locale` or write your own.
	 * Example: `"US"`
	 * @var string
	 */
	public $Locale		= '';

	/**
	 * Http protocol: `"http:" | "https:"`
	 * Example: `"http:"`
	 * @var string
	 */
	public $Protocol		= '';

	/**
	 * Application server name - domain without any port.
	 * Example: `"localhost"`
	 * @var string
	 */
	public $ServerName		= '';

	/**
	 * Application host with port if there is any.
	 * Example: `"localhost:88"`
	 * @var string
	 */
	public $Host		= '';

	/**
	 * Http port parsed by `parse_url()`.
	 * Example: `"88"`
	 * @var string
	 */
	public $Port		= '';

	/**
	 * Requested path in from application root (if `mod_rewrite` enabled), never with query string.
	 * Example: `"/products/page/2"`
	 * @var string
	 */
	public $Path		= '';

	/**
	 * Uri query string without question mark.
	 * Example: `"param-1=value-1&param-2=value-2&param-3[]=value-3-a&param-3[]=value-3-b"`
	 * @var string
	 */
	public $Query		= '';

	/**
	 * Uri fragment parsed by `parse_url()` including hash.
	 * Example: `"#any-sublink-path"`
	 * @var mixed
	 */
	public $Fragment	= '';

	/**
	 * `TRUE` if request is requested from browser by `XmlHttpRequest` object
	 * with http header: `X-Requested-With: AnyJavascriptFrameworkName`, `FALSE` otherwise.
	 * @var bool
	 */
	public $Ajax		= FALSE;

	/**
	 * Php requested script name path from application root.
	 * Example: `"/index.php"`
	 * @var string
	 */
	public $ScriptName	= '';

	/**
	 * Application root path in hard drive.
	 * Example: `"C:/www/my/development/direcotry/www"`
	 * @var string
	 */
	public $AppRoot		= '';

	/**
	 * Base app directory path after domain, if application is placed in domain subdirectory
	 * Example:
	 * - full url:  `"http://localhost:88/my/development/direcotry/www/requested/path/after/domain?with=possible&query=string"`
	 * - base path: `"/my/development/direcotry/www"`
	 * @var string
	 */
	public $BasePath	= '';

	/**
	 * Request path after domain with possible query string
	 * Example: `"/requested/path/after/app/root?with=possible&query=string"`
	 * @var string
	 */
	public $RequestPath	= '';

	/**
	 * Url to requested domain and possible port.
	 * Example: `"https://domain.com" | "http://domain:88"` if any port.
	 * @var string
	 */
	public $DomainUrl	= '';

	/**
	 * Base url to application root.
	 * Example: `"http://domain:88/my/development/direcotry/www"`
	 * @var string
	 */
	public $BaseUrl		= '';

	/**
	 * Request url including scheme, domain, port, path, without any query string
	 * Example: "`http://localhost:88/my/development/direcotry/www/requested/path/after/domain"`
	 * @var string
	 */
	public $RequestUrl	= '';

	/**
	 * Request url including scheme, domain, port, path and with query string
	 * Example: `"http://localhost:88/my/development/direcotry/www/requested/path/after/domain?with=possible&query=string"`
	 * @var string
	 */
	public $FullUrl		= '';

	/**
	 * Http method (uppercase) - `GET`, `POST`, `PUT`, `HEAD`...
	 * Example: `"GET"`
	 * @var string
	 */
	public $Method		= '';

	/**
	 * Referer url if any, safely readed by:
	 * `filter_var($_SERVER['HTTP_REFERER'], FILTER_SANITIZE_URL);`
	 * Example: `"http://foreing.domain.com/path/where/is/link/to/?my=app"`
	 * @var string
	 */
	public $Referer		= '';

	/**
	 * Raw request params array, with keys defined in route or by query string,
	 * always with controller and action keys completed by router.
	 * Example: `array("controller" => "default", "action" => "default", "user" => "' OR 1=1;-- with raw danger value!");`
	 * To get safe param value - use: `\MvcCore\Request::GetParam("user", "a-zA-Z0-9_");`
	 * @var array
	 */
	public $Params		= array();

	/**
	 * Media site key - `"full" | "tablet" | "mobile"`
	 * To use this variable - install `\MvcCore\Router` extension `\MvcCoreExt\Router\Media` or write your own.
	 * Example: "full"
	 * @var string
	 */
	public $MediaSiteKey = '';

	/**
	 * Cleaned input param `"controller"`, containing only chars: `"a-zA-Z0-9\-_/"`.
	 * @var string
	 */
	protected $controllerName = NULL;

	/**
	 * Cleaned input param `"action"`, containing only chars: `"a-zA-Z0-9\-_/"`.
	 * @var string
	 */
	protected $actionName = NULL;

	/**
	 * Content of $_SERVER global variable.
	 * @var array
	 */
	protected $serverGlobals = array();

	/**
	 * Content of $_GET global variable.
	 * @var array
	 */
	protected $getGlobals = array();

	/**
	 * Content of $_POST global variable.
	 * @var array
	 */
	protected $postGlobals = array();

	/**
	 * Requested script name.
	 * @var string
	 */
	protected $indexScriptName = '';

	/**
	 * Request flag if request targets internal package asset or not,
	 * - 0 => request is `Controller:Asset` call for internal package asset
	 * - 1 => request is classic application request
	 * @var mixed
	 */
	protected $appRequest = -1;

	/**
	 * Static factory to get everytime new instance of http request object.
	 * Global variables for testing or non-real request rendering should be changed
	 * and injected here to get different request object from currently called real request.
	 * @param array $server
	 * @param array $get
	 * @param array $post
	 * @return \MvcCore\Request
	 */
	public static function GetInstance (array & $server, array & $get, array & $post) {
		$requestClass = \MvcCore\Application::GetInstance()->GetRequestClass();
		return new $requestClass($server, $get, $post);
	}

    /**
	 * Creates new instance of http request object.
	 * Global variables for testing or non-real request rendering should be changed
	 * and injected here to get different request object from currently called real request.
     * @param array $server
     * @param array $get
     * @param array $post
	 * @return \MvcCore\Request
     */
    public function __construct (array & $server, array & $get, array & $post) {
		$this->serverGlobals = $server;
		$this->getGlobals = $get;
		$this->postGlobals = $post;

		$this->initScriptName();
		$this->initAppRoot();
		$this->initMethod();
		$this->initBasePath();
		$this->initProtocol();
		$this->initAjax();
		$this->initParsedUrlSegments();
		$this->initHttpParams();
		$this->initPath();
		$this->initReferer();
		$this->initUrlCompositions();

		unset($this->serverGlobals, $this->getGlobals, $this->postGlobals);
	}

	/**
	 * Return `TRUE` boolean flag if request target
	 * is anything different than `Controller:Asset`.
	 * @return bool
	 */
	public function IsAppRequest () {
		if ($this->appRequest == -1) {
			$this->appRequest = 1;
			$ctrl = 'controller';
			$action = 'action';
			if (isset($this->Params[$ctrl]) && isset($this->Params[$action])) {
				if ($this->Params[$ctrl] == $ctrl && $this->Params[$action] == 'asset') {
					$this->appRequest = 0;
				}
			}
		}
		return (bool) $this->appRequest;
	}

	/**
	 * Set directly all raw parameters without any conversion at once.
	 * @param array $params
	 * @return \MvcCore\Request
	 */
	public function & SetParams (& $params = array()) {
		$this->Params = $params;
		return $this;
	}

	/**
	 * Set directly raw parameter value without any conversion.
	 * @param string $name
	 * @param string|string[] $value
	 * @return \MvcCore\Request
	 */
	public function & SetParam ($name = "", $value = "") {
		$this->Params[$name] = $value;
		return $this;
	}

	/**
	 * Get param value from `$_GET` or `$_POST` or `php://input`,
	 * filtered by characters defined in second argument throught `preg_replace()`.
	 * Place into second argument only char groups you want to keep.
	 * @param string $name Parametter string name.
	 * @param string $pregReplaceAllowedChars List of regular expression characters to only keep.
	 * @param mixed $ifNullValue Default value returned if given param name is null.
	 * @param string $targetType Target type to retype param value or default if-null value. If param is an array, every param item will be retyped into given target type.
	 * @return string|string[]|mixed
	 */
	public function GetParam (
		$name = "",
		$pregReplaceAllowedChars = "a-zA-Z0-9_/\-\.\@",
		$ifNullValue = NULL,
		$targetType = NULL
	) {
		$params = $this->Params;
		if (!isset($params[$name])) return NULL;
		if (gettype($params[$name]) == 'array') {
			$result = array();
			$params = $params[$name];
			foreach ($params as $key => & $value) {
				$result[$key] = $this->getParamItem(
					$value, $pregReplaceAllowedChars, $ifNullValue, $targetType
				);
			}
			return $result;
		} else {
			return $this->getParamItem(
				$params[$name], $pregReplaceAllowedChars, $ifNullValue, $targetType
			);
		}
	}

	/**
	 * Return cleaned requested controller name from `\MvcCore\Request::$Params['controller'];`.
	 * @return string
	 */
	public function GetControllerName () {
		if (is_null($this->controllerName)) {
			$this->controllerName = $this->GetParam('controller', 'a-zA-Z0-9\-_/', '', 'string');
		}
		return $this->controllerName;
	}

	/**
	 * Return cleaned requested action name from `\MvcCore\Request::$Params['action'];`.
	 * @return string
	 */
	public function GetActionName () {
		if (is_null($this->actionName)) {
			$this->actionName = $this->GetParam('action', 'a-zA-Z0-9\-_/', '', 'string');
		}
		return $this->actionName;
	}

	/**
	 * Sets any custom property `"PropertyName"` by `\MvcCore\Request::SetPropertyName("value")`,
	 * which is not necessary to define previously or gets previously defined
	 * property `"PropertyName"` by `\MvcCore\Request::GetPropertyName();`.
	 * Throws exception if no property defined by get call or if virtual call
	 * begins with anything different from 'Set' or 'Get'.
	 * This method returns custom value for get and `\MvcCore\Request` instance for set.
	 * @param string $rawName
	 * @param array  $arguments
	 * @throws \Exception
	 * @return mixed|\MvcCore\Request
	 */
	public function __call ($rawName, $arguments = array()) {
		$nameBegin = strtolower(substr($rawName, 0, 3));
		$name = substr($rawName, 3);
		if ($nameBegin == 'get' && isset($this->$name)) {
			return $this->$name;
		} else if ($nameBegin == 'set') {
			$this->$name = isset($arguments[0]) ? $arguments[0] : NULL;
			return $this;
		} else {
			throw new \InvalidArgumentException('['.__CLASS__."] No property with name '$name' defined.");
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
	 * Get filtered param value for characters defined as second argument to use them in `preg_replace()`.
	 * @param string|string[]|NULL $rawValue
	 * @param string $pregReplaceAllowedChars List of regular expression characters to only keep.
	 * @param mixed $ifNullValue Default value returned if given param name is null.
	 * @param string $targetType Target type to retype param value or default if-null value. If param is an array, every param item will be retyped into given target type.
	 * @return string|string[]|mixed
	 */
	protected function getParamItem (
		& $rawValue = NULL,
		$pregReplaceAllowedChars = "a-zA-Z0-9_/\-\.\@",
		$ifNullValue = NULL,
		$targetType = NULL
	) {
		if (is_null($rawValue)) {
			if (is_null($targetType)) return $ifNullValue;
			return settype($ifNullValue, $targetType);
		} else {
			$rawValue = trim($rawValue);
			if (mb_strlen($rawValue) === 0) return "";
			if (mb_strlen($pregReplaceAllowedChars) > 0 || $pregReplaceAllowedChars == ".*") {
				if (is_null($targetType)) return $rawValue;
				return settype($rawValue, $targetType);
			} else if (gettype($rawValue) == 'array') {
				$result = array();
				foreach ((array) $rawValue as $key => & $value) {
					$result[$key] = $this->getParamItem(
						$value, $pregReplaceAllowedChars, $ifNullValue, $targetType
					);
				}
				return $result;
			} else {
				$pattern = "#[^" . $pregReplaceAllowedChars . "]#";
				$result = preg_replace($pattern, "", $rawValue);
				if (is_null($targetType)) return $result;
				return settype($result, $targetType);
			}
		}
	}

	/**
	 * Initialize `index.php` script name.
	 * @return void
	 */
	protected function initScriptName () {
		$this->indexScriptName = str_replace('\\', '/', $this->serverGlobals['SCRIPT_NAME']);
		$this->ScriptName = '/' . substr($this->indexScriptName, strrpos($this->indexScriptName, '/') + 1);
	}

	/**
	 * Initialize application root directory.
	 * @return void
	 */
	protected function initAppRoot () {
		// $appRootRelativePath = mb_substr($this->indexScriptName, 0, strrpos($this->indexScriptName, '/') + 1);
		// ucfirst - cause IIS has lower case drive name here - different from __DIR__ value
		$indexFilePath = ucfirst(str_replace('\\', '/', $this->serverGlobals['SCRIPT_FILENAME']));
		if (strpos(__FILE__, 'phar://') === 0) {
			$appRootFullPath = 'phar://' . $indexFilePath;
		} else {
			$appRootFullPath = substr($indexFilePath, 0, mb_strrpos($indexFilePath, '/'));
		}
		$this->AppRoot = str_replace(array('\\', '//'), '/', $appRootFullPath);
	}

	/**
	 * Initialize http method.
	 * @return void
	 */
	protected function initMethod () {
		$this->Method = strtoupper($this->serverGlobals['REQUEST_METHOD']);
	}

	/**
	 * Complete base application path like:
	 * request url:	`"http://localhost/my/development/direcotry/www"`
	 * base path:	`"/my/development/direcotry/www"`
	 * @return void
	 */
	protected function initBasePath () {
		$lastSlashPos = mb_strrpos($this->indexScriptName, '/');
		if ($lastSlashPos !== FALSE) {
			$this->BasePath = mb_substr($this->indexScriptName, 0, $lastSlashPos);
		} else {
			$this->BasePath = '';
		}
	}

	/**
	 * Initialize HTTP protocol.
	 * @return void
	 */
	protected function initProtocol () {
		$this->Protocol = static::PROTOCOL_HTTP;
		if (
			isset($this->serverGlobals['HTTPS']) &&
			strtolower($this->serverGlobals['HTTPS']) == 'on'
		) {
			$this->Protocol = static::PROTOCOL_HTTPS;
		}
	}

	/**
	 * Initialize if request is requested on the background or not
	 * with usual Javascript HTTP header containing: `X-Requested-With: AnyJsFrameworkName`.
	 */
	protected function initAjax () {
		$this->Ajax = (
			isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
			strlen($_SERVER['HTTP_X_REQUESTED_WITH']) > 0
		);
	}

	/**
	 * Initialize url segments parsed by `parse_url()` php method.
	 * @return void
	 */
	protected function initParsedUrlSegments () {
		$absoluteUrl = $this->Protocol . '//'
			. $this->serverGlobals['HTTP_HOST']
			. $this->serverGlobals['REQUEST_URI'];
		$parsedUrl = parse_url($absoluteUrl);
		$keyUc = '';
		foreach ($parsedUrl as $key => $value) {
			$keyUc = ucfirst($key);
			if (isset($this->$keyUc)) {
				$this->$keyUc = (string) $value;
			}
		}
		$this->ServerName = $this->serverGlobals['SERVER_NAME'];
		$this->Host = $this->serverGlobals['HTTP_HOST'];
	}

	/**
	 * Initialize params from global `$_GET` and (global `$_POST` or direct `php://input`).
	 * @return void
	 */
	protected function initHttpParams () {
		$params = array_merge($this->getGlobals);
		if ($this->Method == self::METHOD_POST) {
			$postValues = array();
			if (count($this->postGlobals) > 0) {
				$postValues = $this->postGlobals;
			} else {
				$postValues = $this->initParamsCompletePostData();
			}
			$params = array_merge($params, $postValues);
		}
		$this->Params = $params;
	}

	/**
	 * Read and return direct php `POST` input from `php://input`.
	 * @return array
	 */
	private function initParamsCompletePostData () {
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
	 * Initialize request path.
	 * @return void
	 */
	protected function initPath () {
		$requestUrl = $this->serverGlobals['REQUEST_URI'];
		$path = '/' . ltrim(mb_substr($requestUrl, mb_strlen($this->BasePath)), '/');
		if (mb_strpos($path, '?') !== FALSE) $path = mb_substr($path, 0, mb_strpos($path, '?'));
		$this->Path = $path;
	}

	/**
	 * Initialize referer safely if any.
	 * @return void
	 */
	protected function initReferer () {
		$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
		if ($referer) {
			$referer = filter_var($referer, FILTER_SANITIZE_URL);
			$this->Referer = $referer ? $referer : '';
		}
	}

	/**
	 * Initialize url compositions.
	 * @return void
	 */
	protected function initUrlCompositions () {
		$this->RequestPath = $this->Path . (($this->Query) ? '?' . $this->Query : '') . $this->Fragment;
		$this->DomainUrl = $this->Protocol . '//' . $this->Host;
		$this->BaseUrl = $this->DomainUrl . $this->BasePath;
		$this->RequestUrl = $this->BaseUrl . $this->Path;
		$this->FullUrl = $this->RequestUrl . (($this->Query) ? '?' . $this->Query : '');
	}
}
