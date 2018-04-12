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

require_once(__DIR__.'/Interfaces/IRouter.php');
require_once(__DIR__.'/Application.php');
require_once('Request.php');
require_once('Route.php');
require_once('Tool.php');

/**
 * Responsibility - singleton, routes instancing, request routing and url building.
 * - Application router singleton instance managing.
 * - Global storage for all configured routes.
 *	 - Instancing all route(s) from application start
 *	   configuration somewhere in `Bootstrap` class.
 * - Global storage for currently matched route.
 * - Matching proper route object in `\MvcCore\Router::Route();`
 *   by `\MvcCore\Request::$Path`, always called from core in
 *   `\MvcCore\Application::Run();` => `\MvcCore\Application::routeRequest();`.
 * - Application url addresses completing:
 *   - Into `mod_rewrite` form by configured route instances.
 *   - Into `index.php?` + query string form, containing
 *     `controller`, `action` and all other params.
 */
class Router implements Interfaces\IRouter
{
	/**
	 * Current `\MvcCore\Router` singleton instance storage.
	 * @var \MvcCore\Router
	 */
	protected static $instance;

	/**
	 * Internally used `\MvcCore\Request` request object reference for:
	 * - Routing process in `\MvcCore\Router::Route();` and it's protected submethods.
	 * - URL addresses completing in `\MvcCore\Router::Url()` and it's protected submethods.
	 * @var \MvcCore\Request
	 */
	protected $request;

	/**
	 * Global application route instances store to match request.
	 * Keys are route(s) names, values are `\MvcCore\Route` instances.
	 * @var \MvcCore\Route[]
	 */
	protected $routes = array();

	/**
	 * Global application route instances store to complete url addresses.
	 * Keys are route(s) names and `Controller:Action` combinations,
	 * values are `\MvcCore\Route` instances.
	 * @var \MvcCore\Route[]
	 */
	protected $urlRoutes = array();

	/**
	 * Matched route by `\MvcCore\Router::Match();` processing or NULL if no match.
	 * By this route, there is created and dispatched controller lifecycle by core.
	 * @var \MvcCore\Route
	 */
	protected $currentRoute = NULL;

	/**
	 * `TRUE` if request has to be automaticly dispatched as default
	 * `Index:Index` route, if there was no route matching current request
	 * and if request was not `/` (homepage) but `/something-more`.
	 * Default value: `FALSE`.
	 * @var bool
	 */
	protected $routeToDefaultIfNotMatch = FALSE;


	/**
	 * Get singleton instance of `\MvcCore\Router` stored here.
	 * Optionaly set routes as first argument.
	 * Create proper router intance type in first time by
	 * configured class name in `\MvcCore\Application` singleton.
	 *
	 * Routes could be defined in various forms:
	 * Example:
	 *	`\MvcCore\Router::GetInstance(array(
	 *		"Products:List"	=> "/products-list/<name>/<color>",
	 *	));`
	 * or:
	 *	`\MvcCore\Router::GetInstance(array(
	 *		'products_list'	=> array(
	 *			"pattern"			=> "/products-list/<name>/<color>",
	 *			"controllerAction"	=> "Products:List",
	 *			"defaults"			=> array("name" => "default-name",	"color" => "red"),
	 *			"constraints"		=> array("name" => "[^/]*",			"color" => "[a-z]*")
	 *		)
	 *	));`
	 * or:
	 *	`\MvcCore\Router::GetInstance(array(
	 *		new Route(
	 *			"/products-list/<name>/<color>",
	 *			"Products:List",
	 *			array("name" => "default-name",	"color" => "red"),
	 *			array("name" => "[^/]*",		"color" => "[a-z]*")
	 *		)
	 *	);`
	 * or:
	 *	`\MvcCore\Router::GetInstance(array(
	 *		new Route(
	 *			"name"			=> "products_list",
	 *			"pattern"		=> "#^/products\-list/(?<name>[^/]*)/(?<color>[a-z]*)(?=/$|$)#",
	 *			"reverse"		=> "/products-list/<name>/<color>",
	 *			"controller"	=> "Products",
	 *			"action"		=> "List",
	 *			"defaults"		=> array("name" => "default-name",	"color" => "red"),
	 *		)
	 *	);`
	 * @param \MvcCore\Route[]|array $routes Keyed array with routes,
	 *										 keys are route names or route
	 *										`Controller::Action` definitions.
	 * @return \MvcCore\Router
	 */
	public static function & GetInstance ($routes = array()) {
		if (!static::$instance) {
			$routerClass = \MvcCore\Application::GetInstance()->GetRouterClass();
			static::$instance = new $routerClass($routes);
		}
		return static::$instance;
	}

    /**
     * Create router as every time new instance,
	 * no singleton instance management here.
	 * optionaly set routes as first argument.
	 * If there is no name configured in route array configuration,
	 * set route name by given `$routes` array key, if key is not numeric.
	 *
	 * Routes could be defined in various forms:
	 * Example:
	 *	`new \MvcCore\Router(array(
	 *		"Products:List"	=> "/products-list/<name>/<color>",
	 *	));`
	 * or:
	 *	`new \MvcCore\Router(array(
	 *		'products_list'	=> array(
	 *			"pattern"			=> "/products-list/<name>/<color>",
	 *			"controllerAction"	=> "Products:List",
	 *			"defaults"			=> array("name" => "default-name",	"color" => "red"),
	 *			"constraints"		=> array("name" => "[^/]*",			"color" => "[a-z]*")
	 *		)
	 *	));`
	 * or:
	 *	`new \MvcCore\Router(array(
	 *		new Route(
	 *			"/products-list/<name>/<color>",
	 *			"Products:List",
	 *			array("name" => "default-name",	"color" => "red"),
	 *			array("name" => "[^/]*",		"color" => "[a-z]*")
	 *		)
	 *	);`
	 * or:
	 *	`new \MvcCore\Router(array(
	 *		new Route(
	 *			"name"			=> "products_list",
	 *			"pattern"		=> "#^/products\-list/(?<name>[^/]*)/(?<color>[a-z]*)(?=/$|$)#",
	 *			"reverse"		=> "/products-list/<name>/<color>",
	 *			"controller"	=> "Products",
	 *			"action"		=> "List",
	 *			"defaults"		=> array("name" => "default-name",	"color" => "red"),
	 *		)
	 *	);`
	 * @param \MvcCore\Route[]|array $routes Keyed array with routes,
	 *										 keys are route names or route
	 *										`Controller::Action` definitions.
	 * @return \MvcCore\Router
     */
    public function __construct ($routes = array()) {
		if ($routes) $this->SetRoutes($routes);
	}

    /**
	 * Clear all possible previously configured routes
	 * and set new given request routes again.
	 * If there is no name configured in route array configuration,
	 * set route name by given `$routes` array key, if key is not numeric.
	 *
	 * Routes could be defined in various forms:
	 * Example:
	 *	`\MvcCore\Router::GetInstance()->SetRoutes(array(
	 *		"Products:List"	=> "/products-list/<name>/<color>",
	 *	));`
	 * or:
	 *	`\MvcCore\Router::GetInstance()->SetRoutes(array(
	 *		'products_list'	=> array(
	 *			"pattern"			=> "/products-list/<name>/<color>",
	 *			"controllerAction"	=> "Products:List",
	 *			"defaults"			=> array("name" => "default-name",	"color" => "red"),
	 *			"constraints"		=> array("name" => "[^/]*",			"color" => "[a-z]*")
	 *		)
	 *	));`
	 * or:
	 *	`\MvcCore\Router::GetInstance()->SetRoutes(array(
	 *		new Route(
	 *			"/products-list/<name>/<color>",
	 *			"Products:List",
	 *			array("name" => "default-name",	"color" => "red"),
	 *			array("name" => "[^/]*",		"color" => "[a-z]*")
	 *		)
	 *	);`
	 * or:
	 *	`\MvcCore\Router::GetInstance()->SetRoutes(array(
	 *		new Route(
	 *			"name"			=> "products_list",
	 *			"pattern"		=> "#^/products\-list/(?<name>[^/]*)/(?<color>[a-z]*)(?=/$|$)#",
	 *			"reverse"		=> "/products-list/<name>/<color>",
	 *			"controller"	=> "Products",
	 *			"action"		=> "List",
	 *			"defaults"		=> array("name" => "default-name",	"color" => "red"),
	 *		)
	 *	);`
	 * @param \MvcCore\Route[]|array $routes Keyed array with routes,
	 *										 keys are route names or route
	 *										`Controller::Action` definitions.
	 * @return \MvcCore\Router
	 */
    public function & SetRoutes ($routes = array()) {
		$this->routes = array();
		$this->AddRoutes($routes);
		return $this;
	}

    /**
	 * Append or prepend new request routes.
	 * If there is no name configured in route array configuration,
	 * set route name by given `$routes` array key, if key is not numeric.
	 *
	 * Routes could be defined in various forms:
	 * Example:
	 *	`\MvcCore\Router::GetInstance()->AddRoutes(array(
	 *		"Products:List"	=> "/products-list/<name>/<color>",
	 *	));`
	 * or:
	 *	`\MvcCore\Router::GetInstance()->AddRoutes(array(
	 *		'products_list'	=> array(
	 *			"pattern"			=> "/products-list/<name>/<color>",
	 *			"controllerAction"	=> "Products:List",
	 *			"defaults"			=> array("name" => "default-name",	"color" => "red"),
	 *			"constraints"		=> array("name" => "[^/]*",			"color" => "[a-z]*")
	 *		)
	 *	));`
	 * or:
	 *	`\MvcCore\Router::GetInstance()->AddRoutes(array(
	 *		new Route(
	 *			"/products-list/<name>/<color>",
	 *			"Products:List",
	 *			array("name" => "default-name",	"color" => "red"),
	 *			array("name" => "[^/]*",		"color" => "[a-z]*")
	 *		)
	 *	);`
	 * or:
	 *	`\MvcCore\Router::GetInstance()->AddRoutes(array(
	 *		new Route(
	 *			"name"			=> "products_list",
	 *			"pattern"		=> "#^/products\-list/(?<name>[^/]*)/(?<color>[a-z]*)(?=/$|$)#",
	 *			"reverse"		=> "/products-list/<name>/<color>",
	 *			"controller"	=> "Products",
	 *			"action"		=> "List",
	 *			"defaults"		=> array("name" => "default-name",	"color" => "red"),
	 *		)
	 *	);`
	 * @param \MvcCore\Route[]|array $routes Keyed array with routes,
	 *										 keys are route names or route
	 *										 `Controller::Action` definitions.
	 * @param bool $prepend	Optional, if `TRUE`, all given routes will
	 *						be prepended from the last to the first in
	 *						given list, not appended.
	 * @return \MvcCore\Router
	 */
    public function AddRoutes ($routes = array(), $prepend = FALSE) {
		if ($prepend) $routes = array_reverse($routes);
		$routeClass = \MvcCore\Application::GetInstance()->GetRouteClass();
		foreach ($routes as $routeName => & $route) {
			$routeType = gettype($route);
			$numericKey = is_numeric($routeName);
			if ($route instanceof \MvcCore\Interfaces\IRoute) {
				if (!$numericKey) $route->SetName($routeName);
				$this->AddRoute($route, $prepend);
			} else if ($routeType == 'array') {
				if (!$numericKey) $route['name'] = $routeName;
				$this->AddRoute($routeClass::GetInstance($route), $prepend);
			} else if ($routeType == 'string') {
				// route name is always Controller:Action
				$this->AddRoute($routeClass::GetInstance(array(
					'name'		=> $routeName,
					'pattern'	=> $route
				)), $prepend);
			} else {
				throw new \InvalidArgumentException (
					"[".__CLASS__."] Route is not possible to assign (key: \"$routeName\", value: " . json_encode($route) . ")."
				);
			}
		}
		return $this;
	}

    /**
	 * Append or prepend new request route.
	 *
	 * Route could be defined in various forms:
	 * Example:
	 *	`\MvcCore\Router::GetInstance()->AddRoute(array(
	 *		"name"		=> "Products:List",
	 *		"pattern"	=> "/products-list/<name>/<color>",
	 *	));`
	 * or:
	 *	`\MvcCore\Router::GetInstance()->AddRoute(array(
	 *		"name"				=> "products_list",
	 *		"pattern"			=> "/products-list/<name>/<color>",
	 *		"controllerAction"	=> "Products:List",
	 *		"defaults"			=> array("name" => "default-name",	"color" => "red"),
	 *		"constraints"		=> array("name" => "[^/]*",			"color" => "[a-z]*")
	 *	));`
	 * or:
	 *	`\MvcCore\Router::GetInstance()->AddRoute(new Route(
	 *		"/products-list/<name>/<color>",
	 *		"Products:List",
	 *		array("name" => "default-name",	"color" => "red"),
	 *		array("name" => "[^/]*",		"color" => "[a-z]*")
	 *	));`
	 * or:
	 *	`\MvcCore\Router::GetInstance()->AddRoute(new Route(
	 *		"name"			=> "products_list",
	 *		"pattern"		=> "#^/products\-list/(?<name>[^/]*)/(?<color>[a-z]*)(?=/$|$)#",
	 *		"reverse"		=> "/products-list/<name>/<color>",
	 *		"controller"	=> "Products",
	 *		"action"		=> "List",
	 *		"defaults"		=> array("name" => "default-name",	"color" => "red"),
	 *	));`
	 * @param \MvcCore\Route|\MvcCore\Interfaces\IRoute|array $route Route instance or
	 *																 route config array.
	 * @param bool $prepend	Optional, if `TRUE`, given route will
	 *						be prepended, not appended.
	 * @return \MvcCore\Router
	 */
	public function AddRoute ($route, $prepend = FALSE) {
		if ($route instanceof \MvcCore\Interfaces\IRoute) {
			$instance = & $route;
		} else {
			$routeClass = \MvcCore\Application::GetInstance()->GetRouteClass();
			$instance = $routeClass::GetInstance($route);
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
	 * Get all configured route(s) as `\MvcCore\Route` instances.
	 * Keys in returned array are route names, values are route objects.
	 * @return \MvcCore\Route[]
	 */
    public function GetRoutes () {
		return $this->routes;
	}

	/**
	 * Get `\MvcCore\Request` object as reference, used internally for:
	 * - Routing process in `\MvcCore\Router::Route();` and it's protected submethods.
	 * - URL addresses completing in `\MvcCore\Router::Url()` and it's protected submethods.
	 * @return \MvcCore\Request
	 */
	public function & GetRequest () {
		return $this->request;
	}

	/**
	 * Sets up `\MvcCore\Request` object as reference to use it internally for:
	 * - Routing process in `\MvcCore\Router::Route();` and it's protected submethods.
	 * - URL addresses completing in `\MvcCore\Router::Url()` and it's protected submethods.
	 * This is INTERNAL, not TEMPLATE method, internally called in
	 * `\MvcCore\Application::Run();` => `\MvcCore\Application::routeRequest();`.
	 * @param \MvcCore\Request $request
	 * @return \MvcCore\Router
	 */
	public function & SetRequest (& $request) {
		$this->request = & $request;
		return $this;
	}

	/**
	 * Set matched route instance for given request object
	 * into `\MvcCore\Route::Route();` method. Currently matched
	 * route is always assigned internally in that method.
	 * @param \MvcCore\Route $currentRoute
	 * @return \MvcCore\Router
	 */
	public function & SetCurrentRoute ($currentRoute) {
		$this->currentRoute = $currentRoute;
		return $this;
	}

	/**
	 * Get matched route instance reference for given request object
	 * into `\MvcCore\Route::Route($request);` method. Currently
	 * matched route is always assigned internally in that method.
	 * @return \MvcCore\Route
	 */
	public function & GetCurrentRoute () {
		return $this->currentRoute;
	}

	/**
	 * Get `TRUE` if request has to be automaticly dispatched as default
	 * `Index:Index` route, if there was no route matching current request
	 * and if request was not `/` (homepage) but `/something-more`.
	 * Default protected property value: `FALSE`.
	 * @param bool $enable
	 */
	public function GetRouteToDefaultIfNotMatch () {
		return $this->routeToDefaultIfNotMatch;
	}

	/**
	 * Set `TRUE` if request has to be automaticly dispatched as default
	 * `Index:Index` route, if there was no route matching current request
	 * and if request was not `/` (homepage) but `/something-more`.
	 * Default protected property value: `FALSE`.
	 * @param bool $enable
	 */
	public function & SetRouteToDefaultIfNotMatch ($enable = TRUE) {
		$this->routeToDefaultIfNotMatch = $enable;
		return $this;
	}

	/**
	 * Route current application request by configured routes list or by query string data.
	 * - Go throught all configured routes and try to find matching route.
	 *   - If there is founded matching route - set up `\MvcCore\Router::$currentRoute`.
	 *   - If there is catched any matching route - reset `\MvcCore\Request::$Params`
	 *     with default route params and params parsed from matching process.
	 * - If there was no route matching request, complete `\MvcCore\Router::$currentRoute`
	 *   with automaticly created `Index:Index` route by configured
	 *   `\MvcCore\Application::$routeClass` and by `\MvcCore\Router::$routeToDefaultIfNotMatch`.
	 * - If there is anything in `\MvcCore\Router::$currentRoute` and if it has
	 *   no controller or action defined, define them by request.
	 * - Return completed `\MvcCore\Router::$currentRoute` or NULL.
	 *
	 * This method is always called from core routing by:
	 * - `\MvcCore\Application::Run();` => `\MvcCore\Application::routeRequest();`.
	 * @return \MvcCore\Route
	 */
	public function & Route () {
		$request = & $this->request;
		$controllerName = $request->GetControllerName();
		$actionName = $request->GetActionName();
		if ($controllerName && $actionName) {
			$this->routeByControllerAndActionQueryString($controllerName, $actionName);
		} else {
			$this->routeByRewriteRoutes();
		}
		$requestParams = & $request->Defaults;
		$app = \MvcCore\Application::GetInstance();
		$toolClass = $app->GetToolClass();
		list($dfltCtrl, $dftlAction) = $app->GetDefaultControllerAndActionNames();
		foreach (array('controller'=>$dfltCtrl,'action'=>$dftlAction) as $mvcProp => $mvcValue) {
			if (!isset($requestParams[$mvcProp]) || (
				isset($requestParams[$mvcProp]) && strlen($requestParams[$mvcProp]) === 0
			)) {
				$requestParams[$mvcProp] = $toolClass::GetDashedFromPascalCase($mvcValue);
			}
		}
		if (!$this->currentRoute && (
			$request->GetPath() == '/' || $this->routeToDefaultIfNotMatch
		)) {
			$routeClass = $app->GetRouteClass();
			$this->currentRoute = $routeClass::GetInstance()
				->SetName("$dfltCtrl:$dftlAction")
				->SetController($dfltCtrl)
				->SetAction($dftlAction);
		}
		if ($this->currentRoute) {
			if (!$this->currentRoute->Controller) {
				$this->currentRoute->Controller = $toolClass::GetPascalCaseFromDashed(
					$request->GetControllerName()
				);
			}
			if (!$this->currentRoute->Action) {
				$this->currentRoute->Action = $toolClass::GetPascalCaseFromDashed(
					$request->GetActionName()
				);
			}
		}
		return $this->currentRoute;
	}

	/**
	 * Generates url:
	 * - By `"Controller:Action"` name and params array
	 *   (for routes configuration when routes array has keys with `"Controller:Action"` strings
	 *   and routes has not controller name and action name defined inside).
	 * - By route name and params array
	 *	 (route name is key in routes configuration array, should be any string
	 *	 but routes must have information about controller name and action name inside).
	 * Result address (url string) should have two forms:
	 * - Nice rewrited url by routes configuration
	 *   (for apps with URL rewrite support (Apache `.htaccess` or IIS URL rewrite module)
	 *   and when first param is key in routes configuration array).
	 * - For all other cases is url form like: `"index.php?controller=ctrlName&amp;action=actionName"`
	 *	 (when first param is not founded in routes configuration array).
	 * @param string $controllerActionOrRouteName	Should be `"Controller:Action"` combination or just any route name as custom specific string.
	 * @param array  $params						Optional, array with params, key is param name, value is param value.
	 * @return string
	 */
	public function Url ($controllerActionOrRouteName = 'Index:Index', $params = array()) {
		$result = '';
		$request = & $this->request;
		if (strpos($controllerActionOrRouteName, ':') !== FALSE) {
			list($ctrlPc, $actionPc) = explode(':', $controllerActionOrRouteName);
			if (!$ctrlPc) {
				$toolClass = \MvcCore\Application::GetInstance()->GetToolClass();
				$ctrlPc = $toolClass::GetPascalCaseFromDashed($request->GetControllerName());
			}
			if (!$actionPc) {
				$toolClass = \MvcCore\Application::GetInstance()->GetToolClass();
				$actionPc = $toolClass::GetPascalCaseFromDashed($request->GetActionName());
			}
			$controllerActionOrRouteName = "$ctrlPc:$actionPc";
		} else if ($controllerActionOrRouteName == 'self') {
			$controllerActionOrRouteName = $this->currentRoute
				? $this->currentRoute->Name
				: ':';
			$params = array_merge($request->Defaults, $params);
			unset($params['controller'], $params['action']);
		}
		$absolute = FALSE;
		if ($params && isset($params['absolute'])) {
			$absolute = (bool) $params['absolute'];
			unset($params['absolute']);
		}
		if (isset($this->urlRoutes[$controllerActionOrRouteName])) {
			$result = $this->urlByRoute($this->urlRoutes[$controllerActionOrRouteName], $params);
		} else {
			$result = $this->urlByQueryString($controllerActionOrRouteName, $params);
		}
		if ($absolute) $result = $request->DomainUrl . $result;
		return $result;
	}

	/**
	 * Complete url with all params in query string.
	 * Example: `"/application/base-bath/index.php?controller=ctrlName&amp;action=actionName&amp;name=cool-product-name&amp;color=blue"`
	 * @param string $controllerActionOrRouteName
	 * @param array  $params
	 * @return string
	 */
	protected function urlByQueryString ($controllerActionOrRouteName, $params) {
		$toolClass = \MvcCore\Application::GetInstance()->GetToolClass();
		list($ctrlPc, $actionPc) = explode(':', $controllerActionOrRouteName);
		$result = $this->request->BasePath . $this->request->GetScriptName()
			. '?controller=' . $toolClass::GetDashedFromPascalCase($ctrlPc)
			. '&amp;action=' . $toolClass::GetDashedFromPascalCase($actionPc);
		if ($params) $result .= '&amp;' . http_build_query($params, '', '&amp;');
		return $result;
	}

	/**
	 * Complete url by route instance reverse info.
	 * Example:
	 *	Input (`\MvcCore\Route::$Reverse`):
	 *		`"/products-list/<name>/<color>"`
	 *	Input ($params):
	 *		`array(
	 *			"name"		=> "cool-product-name",
	 *			"color"		=> "red",
	 *			"variant"	=> array("L", "XL"),
	 *		);`
	 *	Output:
	 *		`/application/base-bath/products-list/cool-product-name/blue?variant[]=L&amp;variant[]=XL"`
	 * @param \MvcCore\Route &$route
	 * @param array  $params
	 * @return string
	 */
	protected function urlByRoute (& $route, $params) {
		return $this->request->BasePath . $route->Url($params);
	}

	/**
	 * Complete current route in `\MvcCore\Router::$currentRoute`
	 * and it's params by query string data. If missing `controller`
	 * or if missing `action` param, use configured default controller or action name.
	 * @param string $controllerName
	 * @param string $actionName
	 * @return void
	 */
	protected function routeByControllerAndActionQueryString ($controllerName, $actionName) {
		$app = \MvcCore\Application::GetInstance();
		$toolClass = $app->GetToolClass();
		$routeClass = $app->GetRouteClass();
		list($ctrlDfltName, $actionDfltName) = $app->GetDefaultControllerAndActionNames();
		$controllerPc = $toolClass::GetPascalCaseFromDashed($controllerName ?: $ctrlDfltName);
		$actionPc = $toolClass::GetPascalCaseFromDashed($actionName ?: $actionDfltName);
		$this->currentRoute = $routeClass::GetInstance()
			->SetName("$controllerPc:$actionPc")
			->SetController($controllerPc)
			->SetAction($actionPc);
	}

	/**
	 * Complete `\MvcCore\Router::$currentRoute` and request params by defined routes.
	 * Go throught all configured routes and try to find matching route.
	 * If there is catched any matching route - reset `\MvcCore\Request::$Params`
	 * with default route params and params parsed from matching process.
	 * @return void
	 */
	protected function routeByRewriteRoutes () {
		$request = & $this->request;
		$requestPath = $request->GetPath();
		foreach ($this->routes as & $route) {
			if ($matchedParams = $route->Matches($requestPath)) {
				$this->currentRoute = $route;
				$routeDefaultParams = $route->Defaults ?: array();
				$request->SetParams(
					array_merge($routeDefaultParams, $request->GetParams(), $matchedParams)
				);
				break;
			}
		}
	}
}
