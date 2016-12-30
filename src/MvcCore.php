<?php

/**
 * MvcCore
 *
 * This source file is subject to the BSD 3 License
 * For the full copyright and license information, please view 
 * the LICENSE.md file that are distributed with this source code.
 *
 * @copyright	Copyright (c) 2016 Tom Flídr (https://github.com/mvccore/mvccore)
 * @license		https://mvccore.github.io/docs/mvccore/1.0.0/LICENCE.md
 */

class MvcCore {
	/**
	 * Determinates if application is compilled and running in single file or not
	 * @var bool
	 */
	private static $_compiled = FALSE;
	/**
	 * Application start microtime
	 * @var float
	 */
	private static $_microtime = 0;
	/**
	 * Summary of $_instance
	 * @var mixed
	 */
	private static $_instance;
	/**
	 * Application http routes
	 * @var array
	 */
	private static $_routes = array();
	/**
	 * Current application http routes
	 * @var array
	 */
	private static $_currentRoute = array();
	
	/**
	 * Predispatch request custom call closure function, first param could be a referenced request object like:
	 *	 MvcCore::SetPreRouteRequestHandler(function (& $request) {
	 *	 	$request->customVar = 'custom_value';
	 *	 });
	 *	 MvcCore::SetPreDispatchRequestHandler(function (& $request) {
	 *	 	$request->customVar = 'custom_value';
	 *	 });
	 * @var stdClass
	 */
	private static $_preRequestHandler = array(NULL, NULL);
	/**
	 * Application currently dispatched controller instance
	 * @var App_Controller_<ControllerName> extends MvcCore
	 */
	private $_controller;
	/**
	 * Request properties - parsed url and query string
	 * @var stdClass
	 */
	private $_request;
	/******************************************************************************************************
	 *                                           static getters
	******************************************************************************************************/
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
	public static function GetCurrentRoute () {
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
	public static function GetMicrotime () {
		return self::$_microtime;
	}
	public static function GetInstance () {
		return self::$_instance;
	}
	public static function GetRequest () {
		return self::$_instance->_request;
	}
	public function Url ($routeName = 'Default::Default', $params = array()) {
		$result = '';
		if ($routeName == 'self') $routeName = self::GetCurrentRoute()->name;
		if (!self::$_compiled && isset(self::$_routes[$routeName])) {
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
		} else {
			list($contollerPascalCase, $actionPascalCase) = explode('::', $routeName);
			$controllerDashed = self::_getDashedFromPascalCase($contollerPascalCase);
			$actionDashed = self::_getDashedFromPascalCase($actionPascalCase);
			$scriptName = $this->_request->scriptName;
			$result = $scriptName . "?controller=$controllerDashed&action=$actionDashed";
			if ($params) $result .= "&" . http_build_query($params, "", "&");
		}
		return $result;
	}
	public static function DecodeJson ($jsonStr) {
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
	public static function StaticInit () {
		self::$_microtime = microtime(TRUE);
	}
	public static function Run ($compiled = FALSE) {
		self::$_compiled = $compiled;
		self::$_instance = new self($compiled);
		self::$_instance->_process();
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
		$scriptName = $_SERVER['SCRIPT_NAME'];
		$lastSlashPos = mb_strrpos($scriptName, '/');
		if ($lastSlashPos !== false) {
			$basePath = mb_substr($scriptName, 0, $lastSlashPos);
		} else {
			$basePath = '';
		}
		$protocol = (isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on') ? 'https:' : 'http:';
		$requestUri = $_SERVER['REQUEST_URI'];
		$absoluteUri = $protocol . '//' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		$parsedUri = parse_url($absoluteUri);
		$requestArr = array_merge($requestDefault, $parsedUri);
		$requestArr['params'] = array_merge($_GET, count($_POST) > 0 ? $_POST : self::_completePostData());
		$indexScriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
		$appRootRelPath = mb_substr($indexScriptName, 0, strrpos($indexScriptName, '/') + 1);
		$indexFilePath = ucfirst(str_replace('\\', '/', $_SERVER['SCRIPT_FILENAME'])); // ucfirst - cause IIS has lowercase drive name here - different from __DIR__ value
		if (strpos(__FILE__, 'phar://') === 0) {
			$appRootFullPath = 'phar://' . $indexFilePath;
		} else {
			$appRootFullPath = substr($indexFilePath, 0, mb_strrpos($indexFilePath, '/'));
		}
		$basePath = mb_substr($scriptName, 0, $lastSlashPos);
		$requestArr['scriptName'] = substr($indexScriptName, strrpos($indexScriptName, '/') + 1);
		$requestArr['appRoot'] = str_replace('\\', '/', $appRootFullPath);
		$requestArr['basePath'] = $basePath;
		$path = '/' . mb_substr($requestUri, mb_strlen($appRootRelPath));
		if (mb_strpos($path, '?') !== FALSE) $path = mb_substr($path, 0, mb_strpos($path, '?'));
		$requestArr['path'] = $path;
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
					'controller'	=>	self::_getDashedFromPascalCase(isset($route->controller)? $route->controller: ''),
					'action'		=>	self::_getDashedFromPascalCase(isset($route->action)	? $route->action	: ''),
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
		$controllerClass = 'App_Controllers_' . $controllerNamePascalCase;
		$actionName = $actionNamePascalCase . 'Action';
		$controllerFullPath = implode('/', array(
			$this->_request->appRoot, str_replace('_', '/', $controllerClass) . '.php'
		));
		if (!file_exists($controllerFullPath)) {
			self::_dispatchException(new Exception("[MvcCore] Controller file '$controllerFullPath' not found."));
		}
		try {
			$this->_controller = new $controllerClass($this->_request);
		} catch (Exception $e) {
			if (class_exists('Debug')) {
				Debug::_exceptionHandler($e);
			} else {
				throw $e;
			}
			exit;
		}
		if (!method_exists($this->_controller, $actionName)) {
			self::_dispatchException(new Exception("[MvcCore] Controller '$controllerClass' has not method '$actionName'."));
		}
		list($controllerNameDashed, $actionNameDashed) = array($this->_request->params['controller'], $this->_request->params['action']);
		try {
			$this->_controller->PreDispatch();
			$this->_controller->$actionName();
			$this->_controller->Render($controllerNameDashed, $actionNameDashed);
		} catch (Exception $e) {
			if (class_exists('Debug')) {
				Debug::_exceptionHandler($e);
			} else {
				throw $e;
			}
		}
	}
	/******************************************************************************************************
	 *                                           helper methods
	******************************************************************************************************/
	private function _callPreRequestHandler ($index = 0) {
		$handler = MvcCore::$_preRequestHandler[$index];
		if ($handler instanceof Closure) {
			try {
				$handler($this->_request);
			} catch (exception $e) {
				Debug::_exceptionHandler($e);
			}
		}
	}
	private static function _dispatchException ($e) {
		if (class_exists('Debug') && Debug::$productionMode) {
			MvcCore_Controller::redirect(
				$this->Url('Default::NotFound'), 
				404
			);
		} else if (class_exists('Debug')) {
			Debug::_exceptionHandler($e);
		} else {
			throw $e;
		}
	}
	private static function _completeControllerActionParam ($dashed = '') {
		$pascalCase = '';
		$dashed = strlen($dashed) > 0 ? strtolower($dashed) : 'default';
		$pascalCase = preg_replace_callback("#(\-[a-z])#", function ($m) {return strtoupper(substr($m[0], 1));}, $dashed);
		$pascalCase = preg_replace_callback("#(_[a-z])#", function ($m) {return strtoupper($m[0]);}, $pascalCase);
		$pascalCase = ucfirst($pascalCase);
		return array($dashed, $pascalCase);
	}
	private static function _getDashedFromPascalCase ($pascalCase = '') {
		return strtolower(preg_replace("#([A-Z])#", "-$1", lcfirst($pascalCase)));
	}
}
MvcCore::StaticInit();