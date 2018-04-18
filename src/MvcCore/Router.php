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

//include_once(__DIR__.'/Interfaces/IRouter.php');
//include_once(__DIR__.'/Application.php');
//include_once('Request.php');
//include_once('Route.php');
//include_once('Tool.php');

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
	 * @var \MvcCore\Request|\MvcCore\Interfaces\IRequest|NULL
	 */
	protected $request = NULL;

	/**
	 * Global application route instances store to match request.
	 * Keys are route(s) names, values are `\MvcCore\Route` instances.
	 * @var \MvcCore\Route[]|\MvcCore\Interfaces\IRoute[]
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
	 * @var \MvcCore\Route|\MvcCore\Interfaces\IRoute
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
	 * All cleaned request params for chars to prevent XSS atacks.
	 * @var array|NULL
	 */
	protected $cleanedRequestParams = NULL;

	/**
	 * Reference to singleton instance in `\MvcCore\Application::GetInstance();`.
	 * @var \MvcCore\Application|NULL
	 */
	private static $_app = NULL;

	/**
	 * Reference to `\MvcCore\Application::GetInstance()->GetRouterClass();`.
	 * @var string|NULL
	 */
	private static $_routerClass = NULL;

	/**
	 * Reference to `\MvcCore\Application::GetInstance()->GetRouteClass();`.
	 * @var string|NULL
	 */
	private static $_routeClass = NULL;

	/**
	 * Reference to `\MvcCore\Application::GetInstance()->GetToolClass();`.
	 * @var string|NULL
	 */
	private static $_toolClass = NULL;


	/**
	 * Get singleton instance of `\MvcCore\Router` stored always here.
	 * Optionaly set routes as first argument.
	 * Create proper router instance type at first time by
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
			/** @var $app \MvcCore\Application */
			$app = & \MvcCore\Application::GetInstance();
			self::$_routeClass = $app->GetRouteClass();
			self::$_toolClass = $app->GetToolClass();
			$routerClass = $app->GetRouterClass();
			self::$_routerClass = & $routerClass;
			self::$_app = & $app;
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
	public function & AddRoutes (array $routes = array(), $prepend = FALSE) {
		if ($prepend) $routes = array_reverse($routes);
		$routeClass = self::$_routeClass;
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
	 * Set up route by route name into `\MvcCore\Router::$routes` array
	 * to route incoming request and also set up route by route name and
	 * by `Controller:Action` combination into `\MvcCore\Router::$urlRoutes`
	 * array to build url addresses.
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
	public function & AddRoute ($route, $prepend = FALSE) {
		if ($route instanceof \MvcCore\Interfaces\IRoute) {
			$instance = & $route;
		} else {
			$routeClass = self::$_routeClass;
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
	public function & GetRoutes () {
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
	public function & SetRequest (\MvcCore\Interfaces\IRequest & $request) {
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
	public function & SetCurrentRoute (\MvcCore\Interfaces\IRoute $currentRoute) {
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
	 * - If there is strictly defined `controller` and `action` value in query string,
	 *   route request by given values, add new route and complete new empty
	 *   `\MvcCore\Router::$currentRoute` route with `controller` and `action` values from query string.
	 * - If there is no strictly defined `controller` and `action` value in query string,
	 *   go throught all configured routes and try to find matching route:
	 *   - If there is catched any matching route:
	 *     - Set up `\MvcCore\Router::$currentRoute`.
	 *     - Reset `\MvcCore\Request::$params` again with with default route params,
	 *       with request params itself and with params parsed from matching process.
	 * - If there is no route matching the request and also if the request is targeting homepage
	 *   or there is no route matching the request and also if the request is targeting something
	 *   else and also router is configured to route to default controller and action if no route
	 *   founded, complete `\MvcCore\Router::$currentRoute` with new empty automaticly created route
	 *   targeting default controller and action by configuration in application instance (`Index:Index`)
	 *   and route type create by configured `\MvcCore\Application::$routeClass` class name.
	 * - Return completed `\MvcCore\Router::$currentRoute` or NULL.
	 *
	 * This method is always called from core routing by:
	 * - `\MvcCore\Application::Run();` => `\MvcCore\Application::routeRequest();`.
	 * @return \MvcCore\Route
	 */
	public function & Route () {
		$request = & $this->request;
		$requestCtrlName = $request->GetControllerName();
		$requestActionName = $request->GetActionName();
		if ($requestCtrlName && $requestActionName) {
			$this->routeByControllerAndActionQueryString($requestCtrlName, $requestActionName);
		} else {
			$this->routeByRewriteRoutes($requestCtrlName, $requestActionName);
		}
		if ($this->currentRoute === NULL && (
			$request->GetPath() == '/' || $this->routeToDefaultIfNotMatch
		)) {
			$routeClass = self::$_routeClass;
			list($dfltCtrl, $dftlAction) = self::$_app->GetDefaultControllerAndActionNames();
			$this->currentRoute = $routeClass::GetInstance()
				->SetName("$dfltCtrl:$dftlAction")
				->SetController($dfltCtrl)
				->SetAction($dftlAction);
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
		if ($this->cleanedRequestParams == NULL) $this->initCleanedRequestParams();
		if (strpos($controllerActionOrRouteName, ':') !== FALSE) {
			list($ctrlPc, $actionPc) = explode(':', $controllerActionOrRouteName);
			if (!$ctrlPc) {
				$toolClass = self::$_toolClass;
				$ctrlPc = $toolClass::GetPascalCaseFromDashed($request->GetControllerName());
			}
			if (!$actionPc) {
				$toolClass = self::$_toolClass;
				$actionPc = $toolClass::GetPascalCaseFromDashed($request->GetActionName());
			}
			$controllerActionOrRouteName = "$ctrlPc:$actionPc";
		} else if ($controllerActionOrRouteName == 'self') {
			$controllerActionOrRouteName = $this->currentRoute
				? $this->currentRoute->Name
				: ':';
			$params = array_merge($this->cleanedRequestParams, $params);
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
		if ($absolute) $result = $request->GetDomainUrl() . $result;
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
		$toolClass = self::$_toolClass;
		list($ctrlPc, $actionPc) = explode(':', $controllerActionOrRouteName);
		$result = $this->request->GetBasePath() . $this->request->GetScriptName()
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
		return $this->request->GetBasePath() . $route->Url($params, $this->cleanedRequestParams);
	}

	/**
	 * Complete current route in `\MvcCore\Router::$currentRoute`
	 * and it's params by query string data. If missing `controller`
	 * or if missing `action` param, use configured default controller and default action name.
	 * @param string $controllerName
	 * @param string $actionName
	 * @return void
	 */
	protected function routeByControllerAndActionQueryString ($requestCtrlName, $requestActionName) {
		$toolClass = self::$_toolClass;
		$routeClass = self::$_routeClass;
		list($ctrlDfltName, $actionDfltName) = self::$_app->GetDefaultControllerAndActionNames();
		$controllerPc = $toolClass::GetPascalCaseFromDashed($requestCtrlName ?: $ctrlDfltName);
		$actionPc = $toolClass::GetPascalCaseFromDashed($requestActionName ?: $actionDfltName);
		$this->currentRoute = $routeClass::GetInstance()
			->SetName('default')
			->SetController($controllerPc)
			->SetAction($actionPc);
		$this->AddRoute($this->currentRoute, TRUE);
		$this->request->SetControllerName($toolClass::GetDashedFromPascalCase($controllerPc));
		$this->request->SetActionName($toolClass::GetDashedFromPascalCase($actionPc));
	}

	/**
	 * Complete `\MvcCore\Router::$currentRoute` and request params by defined routes.
	 * Go throught all configured routes and try to find matching route.
	 * If there is catched any matching route - reset `\MvcCore\Request::$params`
	 * with default route params, with params itself and with params parsed from matching process.
	 * @param string $controllerName
	 * @param string $actionName
	 * @return void
	 */
	protected function routeByRewriteRoutes ($requestCtrlName, $requestActionName) {
		$request = & $this->request;
		$requestPath = $request->GetPath();
		/** @var $route \MvcCore\Route */
		foreach ($this->routes as & $route) {
			if ($matchedParams = $route->Matches($requestPath)) {
				$this->currentRoute = & $route;
				$routeDefaultParams = $route->Defaults ?: array();
				$newParams = array_merge($routeDefaultParams, $request->GetParams(''), $matchedParams);
				$request->SetParams($newParams);
				break;
			}
		}
		if ($this->currentRoute !== NULL && (!$route->Controller || !$route->Action)) {
			$toolClass = self::$_toolClass;
			list($ctrlDfltName, $actionDfltName) = self::$_app->GetDefaultControllerAndActionNames();
			if (!$route->Controller) {
				$route->Controller = $requestCtrlName ?: $ctrlDfltName;
				$request->SetControllerName($toolClass::GetDashedFromPascalCase($route->Controller));
			}
			if (!$route->Action) {
				$route->Action = $requestActionName ?: $actionDfltName;
				$request->SetActionName($toolClass::GetDashedFromPascalCase($route->Action));
			}
		}
	}

	/**
	 * Go throught all query string params and prepare, escape all chars (`<` and `>`)
	 * to prevent any XSS attacks, when there is used request params to automaticly complete
	 * remaining param values in url address building process.
	 * @return void
	 */
	protected function initCleanedRequestParams () {
		$cleanedRequestParams = array();
		$request = & $this->request;
		$charsToReplace = array('<' => '&lt;', '>' => '&gt;');
		$globalGet = & $request->GetGlobalCollection('get');
		foreach ($globalGet as $rawName => $rawValue) {
			$paramName = strtr($rawName, $charsToReplace);
			$cleanedRequestParams[$paramName] = strtr($rawValue, $charsToReplace);
		}
		$this->cleanedRequestParams = $cleanedRequestParams;
	}
}
