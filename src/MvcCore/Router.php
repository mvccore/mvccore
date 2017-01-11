<?php

/**
 * MvcCore
 *
 * This source file is subject to the BSD 3 License
 * For the full copyright and license information, please view 
 * the LICENSE.md file that are distributed with this source code.
 *
 * @copyright	Copyright (c) 2016 Tom Flídr (https://github.com/mvccore/mvccore)
 * @license		https://mvccore.github.io/docs/mvccore/3.0.0/LICENCE.md
 */

require_once('Request.php');
require_once('Route.php');
require_once('Tool.php');

class MvcCore_Router
{
	/**
	 * Current singleton instance
	 * @var MvcCore_Router
	 */
	protected static $instance;

	/**
	 * Current application request
	 * @var MvcCore_Request
	 */
	protected $request;
	
	/**
	 * All application http routes
	 * @var array
	 */
	protected $routes = array();
	
	/**
	 * Current application http routes
	 * @var MvcCore_Route
	 */
	protected $currentRoute = NULL;

	/**
	 * Route request to Default::Default route if no route matches.
	 * @var bool
	 */
	protected $routeToDefaultIfNotMatch = FALSE;
	
	/**
	 * Get singleton instance by configured class In MvcCore app instance,
	 * optionaly set routes as first argument.
	 * @param array $routes 
	 * @return MvcCore_Router
	 */
	public static function & GetInstance (array & $routes = array()) {
		if (!self::$instance) {
			$routerClass = MvcCore::GetInstance()->GetRouterClass();
			self::$instance = new $routerClass($routes);
		}
		return self::$instance;
	}

    /**
     * Create router, optionaly set routes as first argument.
     * @param array $routes 
     */
    public function __construct (array & $routes = array()) {
		if ($routes) {
			$this->SetRoutes($routes);
		}
		$appCompiled = MvcCore::GetInstance()->GetCompiled();
		if (substr($appCompiled, 0, 3) == 'PHP' || $appCompiled == 'SFU') {
			$this->AddRoute(array(
				'name'			=> 'Controller::Asset',
				'pattern'		=> "#^/((static|Var/Tmp)+(.*))#",
				'reverse'		=> '{%path}',
				'params'		=> array('path' => ''),
			));
		}
	}

    /**
     * Clear and set http routes again
	 * @param array $routes 
	 * @return MvcCore_Router
     */
    public function SetRoutes (array $routes = array()) {
		$this->routes = array();
		$this->AddRoutes($routes);
		return $this;
	}

    /**
	 * Prepend or append new http routes to the end of keyed array with routes
	 *
	 * @param array	$routes		keyed array with routes, keys are route names or route Controller::Action definitions
	 * @param bool	$prepend	optional
	 * @return MvcCore_Router
	 */
    public function AddRoutes (array $routes = array(), $prepend = FALSE) {
		foreach ($routes as $routeName => $route) {
			if (strpos($routeName, '::') !== FALSE ) {
				$routeType = gettype($route);
				if ($route instanceof MvcCore_Route) {
					$route->Name = $routeName;
				} else if ($routeType == 'array') {
					$route['name'] = $routeName;
				} else if ($routeType == 'string') {
					$route = array(
						'name'		=> $routeName, 
						'pattern'	=> $route
					);
				}
			}
			$this->AddRoute($route, $prepend);
		}
		return $this;
	}

	/**
	 * Add http route
	 * @param array|stdClass|MvcCore_Route	$routeOrRouteCfgData
	 * @param bool							$prepend
	 * @return MvcCore_Router
	 */
	public function AddRoute ($routeCfgDataOrRoute, $prepend = FALSE) {
		if ($routeCfgDataOrRoute instanceof MvcCore_Route) {
			$instance = $routeCfgDataOrRoute;
		} else {
			$instance = MvcCore_Route::GetInstance($routeCfgDataOrRoute);
		}
		if ($prepend) {
			$this->routes = array_merge(array($instance->Name => $instance), $this->routes);
		} else {
			$this->routes[$instance->Name] = $instance;
		}
		return $this;
	}

	/**
	 * Return routed route by http request.
	 * @return MvcCore_Route
	 */
	public function & GetCurrentRoute () {
		return $this->currentRoute;
	}

	/**
	 * Set route request to Default::Default route if no route matches.
	 * @param bool $enable 
	 */
	public function SetRouteToDefaultIfNotMatch ($enable = TRUE) {
		$this->routeToDefaultIfNotMatch = $enable;
		return $this;
	}

	/**
	 * Complete currentRoute property and request params by url.
	 * Always called from MvcCore app instance to dispatch controller by result route.
	 * @param MvcCore_Request $request 
	 * @return MvcCore_Route
	 */
	public function & Route (MvcCore_Request & $request) {
		//var_dump($this->routes);
		$this->request = $request;
		$chars = "a-zA-Z0-9\-_";
		$controllerName = $request->GetParam('controller', $chars);
		$actionName = $request->GetParam('action', $chars);
		if ($controllerName && $actionName) {
			$this->routeByControllerAndActionQueryString($controllerName, $actionName);
		} else {
			$this->routeByRewriteRoutes();
		}
		$requestParams = & $this->request->Params;
		list($defaultCtrl, $defaultAction) = MvcCore::GetInstance()->GetDefaultControllerAndActionNames();
		foreach (array('controller' => $defaultCtrl, 'action' => $defaultAction) as $mvcProperty => $mvcValue) {
			if (!isset($requestParams[$mvcProperty]) || (isset($requestParams[$mvcProperty])  && strlen($requestParams[$mvcProperty]) === 0)) {
				$requestParams[$mvcProperty] = MvcCore_Tool::GetDashedFromPascalCase($mvcValue);
			}
		}
		if (!$this->currentRoute && $this->routeToDefaultIfNotMatch) {
			$this->currentRoute = MvcCore_Route::GetInstance(array(
				'name'			=> "$defaultCtrl::$defaultAction",
				'controller'	=> $defaultCtrl,
				'action'		=> $defaultAction,
			));
		}
		if ($this->currentRoute) {
			if (!$this->currentRoute->Controller) {
				$this->currentRoute->Controller = MvcCore_Tool::GetPascalCaseFromDashed(
					$requestParams['controller']
				);
			}
			if (!$this->currentRoute->Action) {
				$this->currentRoute->Action = MvcCore_Tool::GetPascalCaseFromDashed(
					$requestParams['action']
				);
			}
		}
		//var_dump($this->currentRoute);
		return $this->currentRoute;
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
		$this->currentRoute = MvcCore_Route::GetInstance(array(
			'name'			=> "$controllerPascalCase::$actionPascalCase",
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
		foreach ($this->routes as $route) {
			preg_match_all($route->Pattern, $requestPath, $patternMatches);
			if (count($patternMatches) > 0 && count($patternMatches[0]) > 0) {
				$this->currentRoute = $route;
				$routeParams = array(
					'controller'	=>	MvcCore_Tool::GetDashedFromPascalCase(isset($route->Controller)? $route->Controller: ''),
					'action'		=>	MvcCore_Tool::GetDashedFromPascalCase(isset($route->Action)	? $route->Action	: ''),
				);
				preg_match_all("#{%([a-zA-Z0-9]*)}#", $route->Reverse, $reverseMatches);
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
				$routeDefaultParams = isset($route->Params) ? $route->Params : array();
				$this->request->Params = array_merge($routeDefaultParams, $this->request->Params, $routeParams);
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

	/**
	 * Generates url by:
	 * - Controller::Action name and params array
	 *   (for routes configuration when routes array has keys with Controller::Action strings
	 *   and routes has not controller name and action name defined inside)
	 * - route name and params array
	 *	 (route name is key in routes configuration array, should be any string
	 *	 but routes must have information about controller name and action name inside)
	 * Result address should have two forms:
	 * - nice rewrited url by routes configuration
	 *   (for apps with .htaccess supporting url_rewrite and when first param is key in routes configuration array)
	 * - for all other cases is url form: index.php?controller=ctrlName&action=actionName
	 *	 (when first param is not founded in routes configuration array)
	 * @param string $controllerActionOrRouteName	Should be Controller::Action combination or just any route name as custom specific string
	 * @param array  $params						optional
	 * @return string
	 */
	public function Url ($controllerActionOrRouteName = 'Default::Default', $params = array()) {
		$result = '';
		if ($controllerActionOrRouteName == 'self') {
			$controllerActionOrRouteName = $this->currentRoute ? $this->currentRoute->Name : '::';
			if (!$params) {
				$params = array_merge(array(), $this->request->Params);
				unset($params['controller'], $params['action']);
			}
		}
		if (!isset($this->routes[$controllerActionOrRouteName])) {
			list($contollerPascalCase, $actionPascalCase) = explode('::', $controllerActionOrRouteName);
			$controllerDashed = MvcCore_Tool::GetDashedFromPascalCase($contollerPascalCase);
			$actionDashed = MvcCore_Tool::GetDashedFromPascalCase($actionPascalCase);
			$result = $this->request->ScriptName . "?controller=$controllerDashed&action=$actionDashed";
			if ($params) $result .= "&" . http_build_query($params, "", "&");
		} else {
			$route = $this->routes[$controllerActionOrRouteName];
			$result = $this->request->BasePath . rtrim($route->Reverse, '?&');
			$allParams = array_merge($route->Params, $params);
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
}