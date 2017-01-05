<?php

/**
 * MvcCore
 *
 * This source file is subject to the BSD 3 License
 * For the full copyright and license information, please view 
 * the LICENSE.md file that are distributed with this source code.
 *
 * @copyright	Copyright (c) 2016 Tom Flídr (https://github.com/mvccore/mvccore)
 * @license		https://mvccore.github.io/docs/mvccore/2.0.0/LICENCE.md
 */

class MvcCore {
	/**
	 * Determinates if application is compiled and running in single file mode or not 
	 *	'PHP'	- best speed, packed into php file (there could be any static files included as base64 content),
	 *			  only with many or large asset files could be higher memory usage, which couldn't be good.
	 *			  Application is packed into single PHP file with custom 'Packager_Php_Wrapper' class included 
	 *			  before all classes, this special class handles allowed file operations and assets base64 encoded.
	 *			  This value is initialized automaticly by MvcCore::Init();
	 *			  
	 *	'PHAR'	- lower speed, lower memory usage then first case, application is packed into single PHP file 
	 *			  with 'phar' packing system, there could be any content included but there is no speed advantages,
	 *			  because there are still operations on hard drive - loading content from phar archive, but it is
	 *			  still good way to pack your app into single file tool for any web-hosting needs:-)
	 *			  This value is initialized automaticly by MvcCore::Init();
	 *			  
	 *  'SFU'	- sfu means "single file url" - this is used sometimes in development mode, when we need 
	 *			  to check single file url generating but we don't want to pack anything yet.
	 *			  This value could be initialized only manually by developer throught MvcCore::Run(TRUE);
	 *
	 * @var string
	 */
	private static $_compiled = null;
	
	/**
	 * Application instance for current request
	 *
	 * @var MvcCore
	 */
	private static $_instance;
	
	/**
	 * Application http routes
	 *
	 * @var array
	 */
	private static $_routes = array();
	
	/**
	 * Current application http routes
	 *
	 * @var array
	 */
	private static $_currentRoute = array();
	
	/**
	 * Predispatch request custom call closure function, first param could be a referenced request object like:
	 * 
	 *	 MvcCore::SetPreRouteRequestHandler(function (& $request) {
	 *	 	$request->customVar = 'custom_value';
	 *	 });
	 *	 MvcCore::SetPreDispatchRequestHandler(function (& $request) {
	 *	 	$request->customVar = 'custom_value';
	 *	 });
	 *
	 * @var array
	 */
	private static $_preRequestHandler = array(NULL, NULL);
	
	/**
	 * Environment name - development, beta, production
	 *
	 * @var string
	 */
	private static $_environment = '';
	
	/**
	 * Time when MvcCore::Run has been called
	 *
	 * @var int
	 */
	private static $_microtime = 0;
	
	/**
	 * Application currently dispatched controller instance
	 *
	 * @var MvcCore_Controller
	 */
	private $_controller;
	
	/**
	 * Request properties - parsed url and query string
	 *
	 * @var stdClass
	 */
	private $_request;
	
	/******************************************************************************************************
	 *                                           static getters
	******************************************************************************************************/
	public static function Run ($singleFileUrl = FALSE) {
		self::$_microtime = microtime(TRUE);
		if ($singleFileUrl) self::$_compiled = 'SFU';
		self::$_instance = new self();
		self::$_instance->_process();
	}
	public static function GetEnvironment () {
		if (!self::$_environment) {
			$serverAddress = isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : $_SERVER['LOCAL_ADDR'] ;
			$remoteAddress = $_SERVER['REMOTE_ADDR'];
			if ($serverAddress == $remoteAddress) {
				self::$_environment = 'development';
			} else {
				self::$_environment = 'production';	
			}
		}
		return self::$_environment;
	}
	public static function SetEnvironment ($environment = 'production') {
		self::$_environment = $environment;
	}
	public static function SetRoutes ($routes = array()) {
		foreach ($routes as $key => $values) {
			$route = (object) $values;
			$route->name = $key;
			if (strpos($key, '::') !== FALSE) {
				$contAndAct = explode('::', $key);
				$route->controller = $contAndAct[0] ? $contAndAct[0] : 'Default';
				$route->action = $contAndAct[1] ? $contAndAct[1] : 'Default';
				if (!isset($route->params)) $route->params = array();
			}
			self::$_routes[$key] = $route;
		}
	}
	public static function GetMicrotime () {
		return self::$_microtime;
	}
	public static function & GetCurrentRoute () {
		return self::$_currentRoute;
	}
	public static function SetPreRouteRequestHandler ($handler = null) {
		self::$_preRequestHandler[0] = $handler;
	}
	public static function SetPreDispatchRequestHandler ($handler = null) {
		self::$_preRequestHandler[1] = $handler;
	}
	public static function GetCompiled () {
		return self::$_compiled;
	}
	public static function & GetInstance () {
		return self::$_instance;
	}
	public static function & GetController () {
		return self::$_instance->_controller;
	}
	public static function & GetRequest () {
		return self::$_instance->_request;
	}
	public static function DecodeJson (& $jsonStr) {
		$result = (object) array(
			'success'	=> TRUE,
			'data'		=> null,
		);
		$jsonData = json_decode($jsonStr);
		if (json_last_error() == JSON_ERROR_NONE) {
			$result->data = $jsonData;
		} else {
			$result->success = FALSE;
		}
		return $result;
	}
	public static function Init () {
		if (is_null(self::$_compiled)) {
			$compiled = '';
			if (strpos(__FILE__, 'phar://') === 0) {
				$compiled = 'PHAR';
			} else if (class_exists('Packager_Php_Wrapper')) {
				$compiled = Packager_Php_Wrapper::FS_MODE;
			}
			self::$_compiled = $compiled;
		}
	}
	public static function SessionStart () {
		$sessionNotStarted = function_exists('session_status') ? session_status() == PHP_SESSION_NONE : session_id() == '' ;
		if ($sessionNotStarted) {
			if (class_exists('Zend_Session')) {
				Zend_Session::start();
			} else {
				session_start();
			}
		}
	}
	public static function Terminate () {
		if (class_exists('Zend_Session')) {
			if (Zend_Session::isStarted()) Zend_Session::writeClose();
		} else {
			@session_write_close();
		}
		exit;
	}
	private static function _completePostData () {
		$result = array();
		$rawPhpInput = file_get_contents('php://input');
		$decodedJsonResult = self::DecodeJson($rawPhpInput);
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
	/******************************************************************************************************
	 *                                           application run
	******************************************************************************************************/
	public function Url ($routeName = 'Default::Default', $params = array()) {
		$result = '';
		if ($routeName == 'self') {
			$routeName = self::GetCurrentRoute()->name;
			if (!$params) {
				$params = array_merge(array(), $this->_request->params);
				unset($params['controller'], $params['action']);
			}
		}
		if (/*self::$_compiled == 'SFU' || */!isset(self::$_routes[$routeName])) {
			list($contollerPascalCase, $actionPascalCase) = explode('::', $routeName);
			$controllerDashed = self::GetDashedFromPascalCase($contollerPascalCase);
			$actionDashed = self::GetDashedFromPascalCase($actionPascalCase);
			$scriptName = $this->_request->scriptName;
			$result = $scriptName . "?controller=$controllerDashed&action=$actionDashed";
			if ($params) $result .= "&" . http_build_query($params, "", "&");
		} else {
			$route = (object) self::$_routes[$routeName];
			$result = $this->_request->basePath . rtrim($route->reverse, '?&');
			$allParams = array_merge($route->params, $params);
			foreach ($allParams as $key => $value) {
				$paramKeyReplacement = "{%$key}";
				if (mb_strpos($result, $paramKeyReplacement) === FALSE) {
					$glue = (mb_strpos($result, '?') === FALSE) ? '?' : '&';
					$result .= "$glue$key=$value";
				} else {
					$result = str_replace($paramKeyReplacement, $value, $result);
				}
			}
		}
		return $result;
	}
	private function _process () {
		$this->_setUpRequest();
		$this->_callPreRequestHandler(0);
		$this->_routeRequest();
		$this->_callPreRequestHandler(1);
		$this->_dispatchMvcRequest();
	}
	private function _setUpRequest () {
		$requestDefault = array(
			'scheme'	=> '',
			'host'		=> '',
			'port'		=> '',
			'path'		=> '',
			'query'		=> '',
			'fragment'	=> '',
			'scriptName'=> '',
			'appRoot'	=> '',
			'method'	=> strtoupper($_SERVER['REQUEST_METHOD']),
			'params'	=> array(),
		);
		// script name and base path
		$indexScriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
		$lastSlashPos = mb_strrpos($indexScriptName, '/');
		if ($lastSlashPos !== false) {
			$basePath = mb_substr($indexScriptName, 0, $lastSlashPos);
		} else {
			$basePath = '';
		}
		// protocol, requestUrl and absoluteUrl to complete and merge whole request object
		$protocol = (isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on') ? 'https:' : 'http:';
		$requestUrl = $_SERVER['REQUEST_URI'];
		$absoluteUrl = $protocol . '//' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		// merge request default and parse_url() result
		$parsedUrl = parse_url($absoluteUrl);
		$requestArr = array_merge($requestDefault, $parsedUrl);
		// complete get, post or php://input data
		$params = array_merge($_GET);
		if (strtoupper($_SERVER['REQUEST_METHOD']) == 'POST') $params = array_merge($params, count($_POST) > 0 ? $_POST : self::_completePostData());
		$requestArr['params'] = $params;
		// app root full path
		$appRootRelativePath = mb_substr($indexScriptName, 0, strrpos($indexScriptName, '/') + 1);
		$indexFilePath = ucfirst(str_replace('\\', '/', $_SERVER['SCRIPT_FILENAME'])); // ucfirst - cause IIS has lowercase drive name here - different from __DIR__ value
		if (strpos(__FILE__, 'phar://') === 0) {
			$appRootFullPath = 'phar://' . $indexFilePath;
		} else {
			$appRootFullPath = substr($indexFilePath, 0, mb_strrpos($indexFilePath, '/'));
		}
		// complete all paths
		$requestArr['scriptName'] = substr($indexScriptName, strrpos($indexScriptName, '/') + 1);
		$requestArr['appRoot'] = str_replace('\\', '/', $appRootFullPath);
		$requestArr['basePath'] = $basePath;
		$path = '/' . mb_substr($requestUrl, mb_strlen($appRootRelativePath));
		if (mb_strpos($path, '?') !== FALSE) $path = mb_substr($path, 0, mb_strpos($path, '?'));
		$requestArr['path'] = $path;
		// retype array to stdClass to make life easier
		$this->_request = (object) $requestArr;
	}
	private function _routeRequest () {
		$chars = "a-zA-Z0-9\-_";
		$controllerName = $this->_routeRequestCompleteParam('controller', $chars);
		$actionName = $this->_routeRequestCompleteParam('action', $chars);
		if ($controllerName && $actionName) {
			$this->_routeRequestByControllerAndActionQueryString($controllerName, $actionName);
		} else {
			$this->_routeRequestByRewriteRoutes();
		}
		$requestParams = & $this->_request->params;
		foreach (array('controller', 'action') as $mvcProperty) {
			if (!isset($requestParams[$mvcProperty]) || (isset($requestParams[$mvcProperty])  && strlen($requestParams[$mvcProperty]) === 0)) {
				$requestParams[$mvcProperty] = 'default';
			}
		}
		if (!self::$_currentRoute) {
			self::$_currentRoute = (object) array(
				'name'			=> "Default::Default",
				'controller'	=> "Default",
				'action'		=> "Default",
			);
		}
	}
	private function _routeRequestCompleteParam ($name = "", $pregReplaceAllowedChars = "a-zA-Z0-9\-") {
		$result = '';
		$params = $this->_request->params;
		if (isset($params[$name])) {
			$rawValue = trim($params[$name]);
			if (mb_strlen($rawValue) > 0) {
				$pattern = "#[^" . $pregReplaceAllowedChars . "]#";
				$result = preg_replace($pattern, "", $rawValue);
			}
		}
		return $result;
	}
	private function _routeRequestByControllerAndActionQueryString ($controllerName, $actionName) {
		list ($controllerDashed, $controllerPascalCase) = self::_completeControllerActionParam($controllerName);
		list ($actionDashed, $actionPascalCase) = self::_completeControllerActionParam($actionName);
		self::$_currentRoute = (object) array(
			'name'			=> "$controllerPascalCase::$actionPascalCase",
			'controller'	=> $controllerPascalCase,
			'action'		=> $actionPascalCase,
		);
		$this->_request->params['controller'] = $controllerDashed;
		$this->_request->params['action'] = $actionDashed;
	}
	private function _routeRequestByRewriteRoutes () {
		$requestPath = $this->_request->path;
		foreach (self::$_routes as $routeName => $route) {
			preg_match_all($route->pattern, $requestPath, $patternMatches);
			if (count($patternMatches) > 0 && count($patternMatches[0]) > 0) {
				self::$_currentRoute = $route;
				$routeParams = array(
					'controller'	=>	self::GetDashedFromPascalCase(isset($route->controller)? $route->controller: ''),
					'action'		=>	self::GetDashedFromPascalCase(isset($route->action)	? $route->action	: ''),
				);
				preg_match_all("#{%([a-zA-Z0-9]*)}#", $route->reverse, $reverseMatches);
				if (isset($reverseMatches[1]) && $reverseMatches[1]) {
					$reverseMatchesNames = $reverseMatches[1];
					array_shift($patternMatches);
					foreach ($reverseMatchesNames as $key => $reverseKey) {
						if (isset($patternMatches[$key]) && count($patternMatches[$key])) {
							$routeParams[$reverseKey] = $patternMatches[$key][0];
						} else {
							break;	
						}
					}
				}
				$routeDefaultParams = isset($route->params) ? $route->params : array();
				$this->_request->params = array_merge($routeDefaultParams, $this->_request->params, $routeParams);
				break;
			}
		}
	}
	private function _dispatchMvcRequest () {
		list ($controllerNamePascalCase, $actionNamePascalCase) = array(self::$_currentRoute->controller, self::$_currentRoute->action);
		$actionName = $actionNamePascalCase . 'Action';
		if ($controllerNamePascalCase == 'Controller') {
			$controllerClass = 'MvcCore_Controller';
		} else {
			$controllerClass = 'App_Controllers_' . $controllerNamePascalCase;
			$controllerFullPath = implode('/', array(
				$this->_request->appRoot, str_replace('_', '/', $controllerClass) . '.php'
			));
			if (!self::$_compiled && !file_exists($controllerFullPath)) {
				return self::_dispatchException(new Exception("[MvcCore] Controller file '$controllerFullPath' not found.")); // development purposes
			}
		}
		try {
			$this->_controller = new $controllerClass($this->_request);
		} catch (Exception $e) {
			return self::_dispatchException($e);
		}
		if (!method_exists($this->_controller, $actionName)) {
			return self::_dispatchException(new Exception("[MvcCore] Controller '$controllerClass' has not method '$actionName'."));
		}
		list($controllerNameDashed, $actionNameDashed) = array($this->_request->params['controller'], $this->_request->params['action']);
		try {
			$this->_controller->PreDispatch();
			$this->_controller->$actionName();
			$this->_controller->Render($controllerNameDashed, $actionNameDashed);
		} catch (Exception $e) {
			self::_dispatchException($e);
		}
	}
	/******************************************************************************************************
	 *                                           helper methods
	 ******************************************************************************************************/
	public static function GetDashedFromPascalCase ($pascalCase = '') {
		return strtolower(preg_replace("#([A-Z])#", "-$1", lcfirst($pascalCase)));
	}
	public static function GetPascalCaseFromDashed ($dashed = '') {
		return ucfirst(str_replace('-', '', ucwords($dashed, '-')));
	}
	private function _callPreRequestHandler ($index = 0) {
		$handler = MvcCore::$_preRequestHandler[$index];
		if ($handler instanceof Closure) {
			try {
				$handler($this->_request);
			} catch (exception $e) {
				self::_dispatchException($e);
			}
		}
	}
	private static function _dispatchException ($e) {
		if (class_exists('Packager_Php')) return;
		$production = MvcCore::GetEnvironment() == 'production';
		if (class_exists('Debug')) {
			if ($production) {
				Debug::log($e);
				self::_renderError($e->getMessage());
			} else {
				Debug::_exceptionHandler($e);
			}
		} else {
			if ($production) {
				self::_renderError($e->getMessage());
			} else {
				throw $e;
			}
		}
		exit;
	}
	private static function _renderError ($exceptionMessage = '') {
		if (self::_checkIfDefaultErrorControllerActionExists()) {
			$ctrl = new App_Controllers_Default(self::$_instance->_request);
			try {
				$ctrl->PreDispatch();
				$ctrl->ErrorAction();
				$ctrl->Render('default', 'error');
			} catch (Exception $e) {
				if (class_exists('Debug')) {
					Debug::_exceptionHandler($e);
				}
				self::_renderErrorPlainText($exceptionMessage . PHP_EOL . $e->getMessage());
			}
		} else {
			self::_renderErrorPlainText($exceptionMessage);
		}
	}
	private static function _checkIfDefaultErrorControllerActionExists () {
		$controllerName = 'App_Controllers_Default';
		return (bool) class_exists($controllerName) && method_exists($controllerName, 'ErrorAction');
	}
	private static function _renderErrorPlainText ($text = '') {
		header('HTTP/1.0 500 Internal Server Error');
		header('Content-Type: text/plain');
		if (!$text) $text = 'Internal Server Error.';
		echo "Error 500 - $text";
		self::Terminate();
	}
	private static function _completeControllerActionParam ($dashed = '') {
		$pascalCase = '';
		$dashed = strlen($dashed) > 0 ? strtolower($dashed) : 'default';
		$pascalCase = preg_replace_callback("#(\-[a-z])#", function ($m) {return strtoupper(substr($m[0], 1));}, $dashed);
		$pascalCase = preg_replace_callback("#(_[a-z])#", function ($m) {return strtoupper($m[0]);}, $pascalCase);
		$pascalCase = ucfirst($pascalCase);
		return array($dashed, $pascalCase);
	}
}
MvcCore::Init();