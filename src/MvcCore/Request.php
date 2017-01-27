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

require_once('Tool.php');
require_once(__DIR__.'/../MvcCore.php');

namespace MvcCore;

/**
 * Core request:
 * - linear request url parsing from $_SERVER into local properties describing url sections
 * - params reading from $_GET/$_POST or direct input (in JSON or in query string)
 * - params cleaning by developer rules
 */
class Request
{
	const PROTOCOL_HTTP = 'http:';
	const PROTOCOL_HTTPS = 'https:';

	const METHOD_GET = 'GET';
	const METHOD_POST = 'POST';

	/**
	 * Language international code, lowercase, not used by default.
	 * To use this variable - install \MvcCore\Router extension \MvcCoreExt\Router\Lang
	 * Example: 'en'
	 * @var string
	 */
	public $Lang		= '';

	/**
	 * Country/locale code, uppercase, not used by default.
	 * To use this variable - install \MvcCore\Router extension \MvcCoreExt\Router\Locale
	 * Example: 'US'
	 * @var string
	 */
	public $Locale		= '';
	
	/**
	 * Http protocol: 'http:' | 'https:'
	 * Example: 'http:'
	 * @var string
	 */
	public $Protocol		= '';

	/**
	 * Application server name - domain without any port.
	 * Example: 'localhost'
	 * @var string
	 */
	public $ServerName		= '';

	/**
	 * Application host with port if there is any.
	 * Example: 'localhost:88'
	 * @var string
	 */
	public $Host		= '';

	/**
	 * Http port parsed by parse_url().
	 * Example: '88'
	 * @var string
	 */
	public $Port		= '';

	/**
	 * Requested path in from application root (if mod_rewrite enabled), never with query string.
	 * Example: '/products/page/2'
	 * @var string
	 */
	public $Path		= '';

	/**
	 * Uri query string without question mark.
	 * Example: 'param-1=value-1&param-2=value-2&param-3[]=value-3-a&param-3[]=value-3-b'
	 * @var string
	 */
	public $Query		= '';

	/**
	 * Uri fragment parsed by parse_url()
	 * Example: '#any-sublink-path'
	 * @var mixed
	 */
	public $Fragment	= '';

	/**
	 * Php requested script name path from application root.
	 * Example: '/index.php'
	 * @var string
	 */
	public $ScriptName	= '';

	/**
	 * Application root path in hard drive: C:/www/my/development/direcotry/www
	 * @var string
	 */
	public $AppRoot		= '';

	/**
	 * Base app directory path after domain, if application is placed in domain subdirectory
	 * Example:
	 *  full url: 'http://localhost:88/my/development/direcotry/www/requested/path/after/domain?with=possible&query=string'
	 *  base path: '/my/development/direcotry/www'
	 * @var string
	 */
	public $BasePath	= '';

	/**
	 * Request path after domain with possible query string
	 * Example: '/requested/path/after/app/root?with=possible&query=string'
	 * @var string
	 */
	public $RequestPath	= '';

	/**
	 * Url to requested domain and possible port.
	 * Example: 'http://domain:88'
	 * @var string
	 */
	public $DomainUrl	= '';

	/**
	 * Base url to application root.
	 * Example: 'http://domain:88/my/development/direcotry/www'
	 * @var string
	 */
	public $BaseUrl		= '';

	/**
	 * Request url including scheme, domain, port, path, without any query string
	 * Example: ''http://localhost:88/my/development/direcotry/www/requested/path/after/domain'
	 * @var string
	 */
	public $RequestUrl	= '';

	/**
	 * Request url including scheme, domain, port, path and with query string
	 * Example: 'http://localhost:88/my/development/direcotry/www/requested/path/after/domain?with=possible&query=string'
	 * @var string
	 */
	public $FullUrl		= '';

	/**
	 * Http method (uppercase) - GET, POST, PUT, HEAD...
	 * Example: 'GET'
	 * @var string
	 */
	public $Method		= '';
	
	/**
	 * Referer url if any, safely readed by: 
	 * filter_var($_SERVER['HTTP_REFERER'], FILTER_SANITIZE_URL);
	 * Example: 'GET'
	 * @var string
	 */
	public $Referer		= '';

	/**
	 * Request params array, with keys defined in route or by query string, 
	 * always with controller and action keys completed by router.
	 * Example: array('controller' => 'default', 'action' => 'default', 'user' => "' OR 1=1;-- with raw danger value!");
	 * To get safe param value - use: $request->GetParam('user', 'a-zA-Z0-9');
	 * @var array
	 */
	public $Params		= array();

	/**
	 * Media site key - 'full' | 'tablet' | 'mobile'
	 * To use this variable - install \MvcCore\Router extension \MvcCoreExt\Router\Media
	 * Example: 'full'
	 * @var string
	 */
	public $MediaSiteKey = '';

	/**
	 * Content of $_SERVER global variable
	 * @var array
	 */
	protected $serverGlobals = array();

	/**
	 * Content of $_GET global variable
	 * @var array
	 */
	protected $getGlobals = array();

	/**
	 * Content of $_POST global variable
	 * @var array
	 */
	protected $postGlobals = array();
	
	/**
	 * Requested script name
	 * @var array
	 */
	protected $indexScriptName = '';

	/**
	 * Request flag if request targets internal package asset or not,
	 * - 0 - request is Controller:Asset call for internal package asset
	 * - 1 - request is classic application request
	 * @var mixed
	 */
	protected $appRequest = -1;

	/**
	 * Get everytime new instance of http request, 
	 * global variables should be changed and injected here 
	 * to get different request object from currently called real request.
	 * @param array $server 
	 * @param array $get 
	 * @param array $post 
	 * @return \MvcCore\Request
	 */
	public static function GetInstance (array & $server, array & $get, array & $post) {
		$requestClass = \MvcCore::GetInstance()->GetRequestClass();
		return new $requestClass($server, $get, $post);
	}

    /**
	 * Get everytime new instance of http request, 
	 * global variables should be changed and injected here 
	 * to get different request object from currently called real request.
	 * 
     * @param array $server 
     * @param array $get 
     * @param array $post 
     */
    public function __construct (array & $server, array & $get, array & $post) { 
		$this->serverGlobals = $server;
		$this->getGlobals = $get;
		$this->postGlobals = $post;
		
		$this->initMethod();
		$this->initScriptName();
		$this->initBasePath();
		$this->initProtocol();
		$this->initParsedUrlSegments();
		$this->initParams();
		$this->initAppRoot();
		$this->initPath();
		$this->initReferer();
		$this->initUrlCompositions();
		
		unset($this->serverGlobals, $this->getGlobals, $this->postGlobals);
	}

	/**
	 * Sets any custom property ('PropertyName') by $request->SetPropertyName('value'),
	 * which is not necessary to define previously or gets previously defined
	 * property ('PropertyName') by $request->GetPropertyName(); Throws exception
	 * if no property defined by get call or if virtual call begins with anything
	 * different from 'set' or 'get'.
	 * This method returns custom value for get and $request instance for set.
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
			throw new \Exception('['.__CLASS__."] No property with name '$name' defined.");
		}
	}

	/**
	 * Universal getter, if property not defined, NULL is returned.
	 * @param string $name 
	 * @return mixed
	 */
	public function __get ($name) {
		return isset($this->$name) ? $this->$name : NULL ;
	}

	/**
	 * Universal setter, if property not defined, it's automaticly declarated.
	 * @param string	$name 
	 * @param mixed		$value
	 * @return void
	 */
	public function __set ($name, $value) {
		$this->$name = $value;
	}

	/**
	 * Set directly raw param value without any change
	 * @param string $name 
	 * @param string $value 
	 * @return \MvcCore\Request
	 */
	public function SetParam ($name = "", $value = "") {
		$this->Params[$name] = $value;
		return $this;
	}

	/**
	 * Get param value, filtered for characters defined as second argument to use them in preg_replace().
	 * @param string $name 
	 * @param string $pregReplaceAllowedChars 
	 * @return string
	 */
	public function GetParam ($name = "", $pregReplaceAllowedChars = "a-zA-Z0-9_/\-\.\@") {
		$result = '';
		$params = $this->Params;
		if (isset($params[$name])) {
			$rawValue = trim($params[$name]);
			if (mb_strlen($rawValue) > 0) {
				if (!$pregReplaceAllowedChars || $pregReplaceAllowedChars == ".*") {
					$result = $rawValue;
				} else {
					$pattern = "#[^" . $pregReplaceAllowedChars . "]#";
					$result = preg_replace($pattern, "", $rawValue);
				}
			}
		}
		return $result;
	}

	/**
	 * Return boolean flag if request target 
	 * is anything different than 'Controller:Asset'
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
	 * Initialize http method.
	 * @return void
	 */
	protected function initMethod () {
		$this->Method = strtoupper($this->serverGlobals['REQUEST_METHOD']);
	}

	/**
	 * Initialize index.php script name.
	 * @return void
	 */
	protected function initScriptName () {
		$this->indexScriptName = str_replace('\\', '/', $this->serverGlobals['SCRIPT_NAME']);
		$this->ScriptName = '/' . substr($this->indexScriptName, strrpos($this->indexScriptName, '/') + 1);
	}

	/**
	 * Complete base application path like: /localhost/my/development/direcotry/www
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
	 * Initialize http protocol.
	 * @return void
	 */
	protected function initProtocol () {
		$this->Protocol = static::PROTOCOL_HTTP;
		if (isset($this->serverGlobals['HTTPS']) && strtolower($this->serverGlobals['HTTPS']) == 'on') {
			$this->Protocol = static::PROTOCOL_HTTPS;
		}
	}
	
	/**
	 * Initialize url segments parsed by parse_url() php method.
	 * @return void
	 */
	protected function initParsedUrlSegments () {
		$absoluteUrl = $this->Protocol . '//' . $this->serverGlobals['HTTP_HOST'] . $this->serverGlobals['REQUEST_URI'];
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
	 * Initialize params from global $_GET and (global $_POST or direct 'php://input').
	 * @return void
	 */
	protected function initParams () {
		$params = array_merge($this->getGlobals);
		if (strtoupper($this->serverGlobals['REQUEST_METHOD']) == 'POST') {
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
	 * Read and return direct php post input from 'php://input'.
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
		$this->AppRoot = str_replace('\\', '/', $appRootFullPath);
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