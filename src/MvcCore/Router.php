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

require_once('Request.php');
require_once('Route.php');
require_once('Tool.php');

/**
 * Core router:
 * - main store for all routes
 * - application request routing process before request dispatching in core
 * - currently matched route store
 * - application url completing
 *   - by configured routes into mod_rewrite form
 *   - or into query string form, containing controller and action params
 * - params query string building - primitive param value or array value representation possible
 */
class Router
{
	/**
	 * Current singleton instance
	 * @var \MvcCore\Router
	 */
	protected static $instance;

	/**
	 * Current application request
	 * @var \MvcCore\Request
	 */
	protected $request;
	
	/**
	 * All application routes to route request
	 * @var array
	 */
	protected $routes = array();

	/**
	 * All application routes to build url
	 * @var array
	 */
	protected $urlRoutes = array();

	/**
	 * Current application http routes
	 * @var \MvcCore\Route
	 */
	protected $currentRoute = NULL;

	/**
	 * Route request to Default:Default route if no route matches.
	 * @var bool
	 */
	protected $routeToDefaultIfNotMatch = FALSE;
	
	/**
	 * Get singleton instance by configured class In MvcCore app instance,
	 * optionaly set routes as first argument.
	 * @param array $routes 
	 * @return \MvcCore\Router
	 */
	public static function & GetInstance (array $routes = array()) {
		if (!self::$instance) {
			$routerClass = \MvcCore::GetInstance()->GetRouterClass();
			self::$instance = new $routerClass($routes);
		}
		return self::$instance;
	}

    /**
     * Create router, optionaly set routes into new instance as first argument.
     * @param array $routes 
     */
    public function __construct (array & $routes = array()) {
		if ($routes) $this->SetRoutes($routes);
	}

    /**
     * Clear and set http routes again
	 * @param array $routes 
	 * @return \MvcCore\Router
     */
    public function SetRoutes (array $routes = array()) {
		$this->routes = array();
		$this->AddRoutes($routes);
		return $this;
	}

    /**
     * Get all configured routes.
     * @return array
     */
    public function GetRoutes () {
		return $this->routes;
	}

    /**
	 * Append or prepend new request routes.
	 * Routes definition array shoud have items as array
	 * with route configuration definitions, stdClass with route
	 * configuration definitions or \MvcCore\Route instance.
	 * Keys in given array has to be route names as 'Controller:Action'
	 * strings or any custom route names with defined controller name and
	 * action name inside route array/stdClass configuration or route instance.
	 * @param array[]|\stdClass[]|\MvcCore\Route[]	$routes		keyed array with routes, keys are route names or route Controller::Action definitions
	 * @param bool									$prepend	optional
	 * @return \MvcCore\Router
	 */
    public function AddRoutes (array $routes = array(), $prepend = FALSE) {
		if ($prepend) $routes = array_reverse($routes);
		foreach ($routes as $routeName => & $route) {
			$routeType = gettype($route);
			$numericKey = is_numeric($routeName);
			if ($route instanceof \MvcCore\Route) {
				if (!$numericKey) {
					$route->Name = $routeName;
				}
			} else if ($routeType == 'array') {
				if (!$numericKey) {
					$route['name'] = $routeName;
				}
			} else if ($routeType == 'string') {
				// route name is always Controller:Action
				$route = array(
					'name'		=> $routeName,
					'pattern'	=> $route
				);
			}
			$this->AddRoute($route, $prepend);
		}
		return $this;
	}

	/**
	 * Append or prepend new request route.
	 * Route definition array shoud be array with route 
	 * configuration definition, stdClass with route configuration 
	 * definition or \MvcCore\Route instance. In configuration definition is 
	 * required route name, controller, action, pattern and if pattern contains
	 * regexp groups, its necessary also to define route reverse.
	 * Route name should be defined as 'Controller:Action' string or any custom
	 * route name, but then there is necessary to specify controller name and
	 * action name inside route array/stdClass configuration or route instance.
	 * @param array|\stdClass|\MvcCore\Route	$routeCfgOrRoute
	 * @param bool								$prepend
	 * @return \MvcCore\Router
	 */
	public function AddRoute ($routeCfgOrRoute, $prepend = FALSE) {
		if ($routeCfgOrRoute instanceof \MvcCore\Route) {
			$instance = & $routeCfgOrRoute;
		} else {
			$instance = \MvcCore\Route::GetInstance($routeCfgOrRoute);
		}
		if ($prepend) {
			$this->routes = array_merge(array($instance->Name => $instance), $this->routes);
		} else {
			$this->routes[$instance->Name] = & $instance;
		}
		$this->urlRoutes[$instance->Name] = & $instance;
		$this->urlRoutes[$instance->Controller . ':' . $instance->Action] = & $instance;
		return $this;
	}

	/**
	 * Set current route
	 * @param \MvcCore\Route $currentRoute 
	 * @return \MvcCore\Router
	 */
	public function SetCurrentRoute ($currentRoute) {
		$this->currentRoute = $currentRoute;
		return $this;
	}

	/**
	 * Return routed route by http request.
	 * @return \MvcCore\Route
	 */
	public function & GetCurrentRoute () {
		return $this->currentRoute;
	}

	/**
	 * Get state about request routing to 'Default:Default' route if no route matches.
	 * @param bool $enable 
	 */
	public function GetRouteToDefaultIfNotMatch () {
		return $this->routeToDefaultIfNotMatch;
	}

	/**
	 * Set route request to 'Default:Default' route if no route matches.
	 * @param bool $enable
	 */
	public function SetRouteToDefaultIfNotMatch ($enable = TRUE) {
		$this->routeToDefaultIfNotMatch = $enable;
		return $this;
	}

	/**
	 * Route application request by configured routes list.
	 * To route request - custom complete currentRoute property 
	 * and Params property in referenced application request by 
	 * current request url. Return routed route as current route 
	 * as reference.
	 * This method is always called from \MvcCore app instance 
	 * to dispatch controller by result route.
	 * @param \MvcCore\Request $request 
	 * @return \MvcCore\Route
	 */
	public function & Route (\MvcCore\Request & $request) {
		$this->request = $request;
		$chars = "a-zA-Z0-9\-_/";
		$controllerName = $request->GetParam('controller', $chars);
		$actionName = $request->GetParam('action', $chars);
		if ($controllerName && $actionName) {
			$this->routeByControllerAndActionQueryString($controllerName, $actionName);
		} else {
			$this->routeByRewriteRoutes();
		}
		$requestParams = & $this->request->Params;
		list($defaultCtrl, $defaultAction) = \MvcCore::GetInstance()->GetDefaultControllerAndActionNames();
		foreach (array('controller' => $defaultCtrl, 'action' => $defaultAction) as $mvcProperty => $mvcValue) {
			if (!isset($requestParams[$mvcProperty]) || (isset($requestParams[$mvcProperty])  && strlen($requestParams[$mvcProperty]) === 0)) {
				$requestParams[$mvcProperty] = \MvcCore\Tool::GetDashedFromPascalCase($mvcValue);
			}
		}
		if (!$this->currentRoute && ($this->request->Path == '/' || $this->routeToDefaultIfNotMatch)) {
			$this->currentRoute = \MvcCore\Route::GetInstance(array(
				'name'			=> "$defaultCtrl:$defaultAction",
				'controller'	=> $defaultCtrl,
				'action'		=> $defaultAction,
			));
		}
		if ($this->currentRoute) {
			if (!$this->currentRoute->Controller) {
				$this->currentRoute->Controller = \MvcCore\Tool::GetPascalCaseFromDashed(
					$requestParams['controller']
				);
			}
			if (!$this->currentRoute->Action) {
				$this->currentRoute->Action = \MvcCore\Tool::GetPascalCaseFromDashed(
					$requestParams['action']
				);
			}
		}
		return $this->currentRoute;
	}

	/**
	 * Generates url by:
	 * - 'Controller:Action' name and params array
	 *   (for routes configuration when routes array has keys with 'Controller:Action' strings
	 *   and routes has not controller name and action name defined inside)
	 * - route name and params array
	 *	 (route name is key in routes configuration array, should be any string
	 *	 but routes must have information about controller name and action name inside)
	 * Result address should have two forms:
	 * - nice rewrited url by routes configuration
	 *   (for apps with .htaccess supporting url_rewrite and when first param is key in routes configuration array)
	 * - for all other cases is url form: index.php?controller=ctrlName&action=actionName
	 *	 (when first param is not founded in routes configuration array)
	 * @param string $controllerActionOrRouteName	Should be 'Controller:Action' combination or just any route name as custom specific string
	 * @param array  $params						optional
	 * @return string
	 */
	public function Url ($controllerActionOrRouteName = 'Index:Index', $params = array()) {
		$result = '';
		if (strpos($controllerActionOrRouteName, ':') !== FALSE) {
			list($ctrlPc, $actionPc) = explode(':', $controllerActionOrRouteName);
			$requestParams = $this->request->Params;
			if (!$ctrlPc) $ctrlPc = \MvcCore\Tool::GetPascalCaseFromDashed($requestParams['controller']);
			if (!$actionPc) $actionPc = \MvcCore\Tool::GetPascalCaseFromDashed($requestParams['action']);
			$controllerActionOrRouteName = "$ctrlPc:$actionPc";
		} else if ($controllerActionOrRouteName == 'self') {
			$controllerActionOrRouteName = $this->currentRoute ? $this->currentRoute->Name : ':';
			$params = array_merge($this->request->Params, $params);
			unset($params['controller'], $params['action']);
		}
		$absolute = FALSE;
		if ($params && isset($params['absolute'])) {
			$absolute = (bool) $params['absolute'];
			unset($params['absolute']);
		}
		if (isset($this->urlRoutes[$controllerActionOrRouteName])) {
			$result = $this->urlByRoute($controllerActionOrRouteName, $params);
		} else {
			$result = $this->urlByQueryString($controllerActionOrRouteName, $params);
		}
		if ($absolute) $result = $this->request->DomainUrl . $result;
		return $result;
	}

	/**
	 * Complete url with all data in query string
	 * @param string $controllerActionOrRouteName 
	 * @param array  $params 
	 * @return string
	 */
	protected function urlByQueryString ($controllerActionOrRouteName, $params) {
		list($contollerPascalCase, $actionPascalCase) = explode(':', $controllerActionOrRouteName);
		$controllerDashed = \MvcCore\Tool::GetDashedFromPascalCase($contollerPascalCase);
		$actionDashed = \MvcCore\Tool::GetDashedFromPascalCase($actionPascalCase);
		$result = $this->request->BasePath . $this->request->ScriptName 
			. "?controller=$controllerDashed&action=$actionDashed";
		if ($params) {
			$result .= "&" . http_build_query($params, "", "&");
		}
		return $result;
	}

	/**
	 * Complete url by route instance reverse info
	 * @param string $controllerActionOrRouteName
	 * @param array  $params
	 * @return string
	 */
	protected function urlByRoute ($controllerActionOrRouteName, $params) {
		$route = $this->urlRoutes[$controllerActionOrRouteName];
		$result = $this->request->BasePath . rtrim($route->Reverse, '?&');
		$allParams = array_merge(
			is_array($route->Params) ? $route->Params : array(), $params
		);
		foreach ($allParams as $key => $value) {
			$paramKeyReplacement = "{%$key}";
			if (mb_strpos($result, $paramKeyReplacement) === FALSE) {
				$glue = (mb_strpos($result, '?') === FALSE) ? '?' : '&';
				$result .= $glue . http_build_query(array($key => $value));
			} else {
				$result = str_replace($paramKeyReplacement, $value, $result);
			}
		}
		return $result;
	}

	/**
	 * Complete current route and request params by query string
	 * @param string $controllerName 
	 * @param string $actionName 
	 * @return void
	 */
	protected function routeByControllerAndActionQueryString ($controllerName, $actionName) {
		list ($controllerDashed, $controllerPascalCase) = static::completeControllerActionParam($controllerName);
		list ($actionDashed, $actionPascalCase) = static::completeControllerActionParam($actionName);
		$this->currentRoute = \MvcCore\Route::GetInstance(array(
			'name'			=> "$controllerPascalCase:$actionPascalCase",
			'controller'	=> $controllerPascalCase,
			'action'		=> $actionPascalCase
		));
		$this->request->Params['controller'] = $controllerDashed;
		$this->request->Params['action'] = $actionDashed;
	}

	/**
	 * Complete current route and request params by defined routes
	 * @return void
	 */
	protected function routeByRewriteRoutes () {
		$requestPath = $this->request->Path;
		foreach ($this->routes as & $route) {
			preg_match_all($route->Pattern, $requestPath, $patternMatches);
			if (count($patternMatches) > 0 && count($patternMatches[0]) > 0) {
				$this->currentRoute = $route;
				$controllerName = isset($route->Controller)? $route->Controller: '';
				$routeParams = array(
					'controller'	=>	\MvcCore\Tool::GetDashedFromPascalCase(str_replace(array('_', '\\'), '/', $controllerName)),
					'action'		=>	\MvcCore\Tool::GetDashedFromPascalCase(isset($route->Action)	? $route->Action	: ''),
				);
				preg_match_all("#{%([a-zA-Z0-9]*)}#", $route->Reverse, $reverseMatches);
				if (isset($reverseMatches[1]) && $reverseMatches[1]) {
					$reverseMatchesNames = $reverseMatches[1];
					array_shift($patternMatches);
					foreach ($reverseMatchesNames as $key => $reverseKey) {
						if (isset($patternMatches[$key]) && count($patternMatches[$key])) {
							// 1 line bellow is only for route debug panel, only for cases when you
							// forget to define current rewrite param, this defines null value by default
							if (!isset($route->Params[$reverseKey])) $route->Params[$reverseKey] = NULL;
							$routeParams[$reverseKey] = $patternMatches[$key][0];
						} else {
							break;	
						}
					}
				}
				$routeDefaultParams = isset($route->Params) ? $route->Params : array();
				$this->request->Params = array_merge($routeDefaultParams, $routeParams, $this->request->Params);
				break;
			}
		}
	}

	/**
	 * Complete controller and action names in both forms - dashed and pascal case
	 * @param string $dashed 
	 * @return string[]
	 */
	protected static function completeControllerActionParam ($dashed = '') {
		$pascalCase = '';
		$dashed = strlen($dashed) > 0 ? strtolower($dashed) : 'default';
		$pascalCase = preg_replace_callback("#(\-[a-z])#", function ($m) {return strtoupper(substr($m[0], 1));}, $dashed);
		$pascalCase = preg_replace_callback("#(_[a-z])#", function ($m) {return strtoupper($m[0]);}, $pascalCase);
		$pascalCase = ucfirst($pascalCase);
		return array($dashed, $pascalCase);
	}
}