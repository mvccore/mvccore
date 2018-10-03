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

//include_once(__DIR__.'/IRouter.php');
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
 *	 `controller`, `action` and all other params.
 */
class Router implements IRouter
{
	/**
	 * Current `\MvcCore\Router` singleton instance storage.
	 * @var \MvcCore\Router
	 */
	protected static $instance = NULL;

	/**
	 * Reference to `\MvcCore\Application::GetInstance()->GetRouteClass();`.
	 * @var string|NULL
	 */
	protected static $routeClass = NULL;

	/**
	 * Reference to `\MvcCore\Application::GetInstance()->GetToolClass();`.
	 * @var string|NULL
	 */
	protected static $toolClass = NULL;

	/**
	 * Reference to `\MvcCore\Application::GetInstance();` 
	 * to not call this very time we need app instance.
	 * @var \MvcCore\Application|\MvcCore\IApplication|NULL
	 */
	protected $application = NULL;

	/**
	 * Internally used `\MvcCore\Request` request object reference for:
	 * - Routing process in `\MvcCore\Router::Route();` and it's protected submethods.
	 * - URL addresses completing in `\MvcCore\Router::Url()` and it's protected submethods.
	 * @var \MvcCore\Request|\MvcCore\IRequest|NULL
	 */
	protected $request = NULL;

	/**
	 * Global application route instances store to match request.
	 * Keys are route(s) names, values are `\MvcCore\Route` instances.
	 * @var \MvcCore\Route[]|\MvcCore\IRoute[]
	 */
	protected $routes = [];

	/**
	 * Global application route instances store to complete url addresses.
	 * Keys are route(s) names and `Controller:Action` combinations,
	 * values are `\MvcCore\Route` instances.
	 * @var \MvcCore\Route[]
	 */
	protected $urlRoutes = [];

	/**
	 * Matched route by `\MvcCore\Router::Match();` processing or NULL if no match.
	 * By this route, there is created and dispatched controller lifecycle by core.
	 * @var \MvcCore\Route|\MvcCore\IRoute
	 */
	protected $currentRoute = NULL;

	/**
	 * Route name or route `Controller:Action` name, matched route by 
	 * `\MvcCore\Router::Match();` processing or NULL if no match.
	 * By this route name, there is completed every 'self' URL address string.
	 * This route name record is not changed by any error rendering,
	 * so in error pages, you could render 'self' links to desired page, but 
	 * not to error page itself.
	 * @var string
	 */
	protected $selfRouteName = NULL;

	/**
	 * `TRUE` if request has to be automaticly dispatched as default
	 * `Index:Index` route, if there was no route matching current request
	 * and if request was not `/` (homepage) but `/something-more`.
	 * Default value: `FALSE`.
	 * @var bool
	 */
	protected $routeToDefaultIfNotMatch = FALSE;

	/**
	 * All request params - params parsed by route and query string params.
	 * Be carefull, it could contain XSS chars. Use always `htmlspecialchars()`.
	 * @var array|NULL
	 */
	protected $requestedUrlParams = NULL;

	/**
	 * Trrailing slash behaviour - integer state about what to do with trailing
	 * slash in all requested url except homepage. Possible states are:
	 * - `-1` (`\MvcCore\IRouter::TRAILING_SLASH_REMOVE`)
	 *		Always remove trailing slash from requested url if there
	 *		is any and redirect to it, except homepage.
	 * -  `0` (`\MvcCore\IRouter::TRAILING_SLASH_BENEVOLENT`)
	 *		Be absolutely benevolent for trailing slash in requested url.
	 * -  `1` (`\MvcCore\IRouter::TRAILING_SLASH_ALWAYS`)
	 *		Always keep trailing slash in requested url or always add trailing
	 *		slash into url and redirect to it.
	 * Default value is `-1` - `\MvcCore\IRouter::TRAILING_SLASH_REMOVE`
	 * @var int
	 */
	protected $trailingSlashBehaviour = -1;

	/**
	 * Query string params separator, always initialized by configured response type.
	 * If response has no `Content-Type` header yet, query string separator is automaticly
	 * configured to `&`. That's why is very important to define response content type
	 * as the very first command in controller `Init()` method, if you want to send XML content.
	 * @var string|NULL
	 */
	protected $queryParamsSepatator = NULL;

	/**
	 * If router has any routes configured in `Route()` function call, this property is `TRUE`.
	 * If there are no routes configured in `Route()` function call moment, it's `FALSE`.
	 * @var bool
	 */
	protected $anyRoutesConfigured = FALSE;


	/**
	 * Get singleton instance of `\MvcCore\Router` stored always here.
	 * Optionaly set routes as first argument.
	 * Create proper router instance type at first time by
	 * configured class name in `\MvcCore\Application` singleton.
	 *
	 * Routes could be defined in various forms:
	 * Example:
	 *	`\MvcCore\Router::GetInstance([
	 *		"Products:List"	=> "/products-list/<name>/<color>",
	 *	]);`
	 * or:
	 *	`\MvcCore\Router::GetInstance([
	 *		'products_list'	=> [
	 *			"pattern"			=> "/products-list/<name>/<color>",
	 *			"controllerAction"	=> "Products:List",
	 *			"defaults"			=> array("name" => "default-name",	"color" => "red"),
	 *			"constraints"		=> array("name" => "[^/]*",			"color" => "[a-z]*")
	 *		]
	 *	]);`
	 * or:
	 *	`\MvcCore\Router::GetInstance([
	 *		new Route(
	 *			"/products-list/<name>/<color>",
	 *			"Products:List",
	 *			array("name" => "default-name",	"color" => "red"),
	 *			array("name" => "[^/]*",		"color" => "[a-z]*")
	 *		)
	 *	]);`
	 * or:
	 *	`\MvcCore\Router::GetInstance([
	 *		new Route(
	 *			"name"			=> "products_list",
	 *			"pattern"		=> "#^/products\-list/(?<name>[^/]*)/(?<color>[a-z]*)(?=/$|$)#",
	 *			"reverse"		=> "/products-list/<name>/<color>",
	 *			"controller"	=> "Products",
	 *			"action"		=> "List",
	 *			"defaults"		=> array("name" => "default-name",	"color" => "red"),
	 *		)
	 *	]);`
	 * @param \MvcCore\Route[]|array $routes Keyed array with routes,
	 *										 keys are route names or route
	 *										`Controller::Action` definitions.
	 * @return \MvcCore\Router
	 */
	public static function & GetInstance (array $routes = []) {
		if (!self::$instance) {
			/** @var $app \MvcCore\Application */
			$app = & \MvcCore\Application::GetInstance();
			self::$routeClass = $app->GetRouteClass();
			self::$toolClass = $app->GetToolClass();
			$routerClass = $app->GetRouterClass();
			$instance = new $routerClass($routes);
			$instance->application = & $app;
			self::$instance = & $instance;
		}
		return self::$instance;
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
	 *	`new \MvcCore\Router([
	 *		"Products:List"	=> "/products-list/<name>/<color>",
	 *	]);`
	 * or:
	 *	`new \MvcCore\Router([
	 *		'products_list'	=> [
	 *			"pattern"			=> "/products-list/<name>/<color>",
	 *			"controllerAction"	=> "Products:List",
	 *			"defaults"			=> ["name" => "default-name",	"color" => "red"],
	 *			"constraints"		=> ["name" => "[^/]*",			"color" => "[a-z]*"]
	 *		]
	 *	]);`
	 * or:
	 *	`new \MvcCore\Router(array(
	 *		new Route(
	 *			"/products-list/<name>/<color>",
	 *			"Products:List",
	 *			["name" => "default-name",	"color" => "red"],
	 *			["name" => "[^/]*",		"color" => "[a-z]*"]
	 *		)
	 *	);`
	 * or:
	 *	`new \MvcCore\Router([
	 *		new Route(
	 *			"name"			=> "products_list",
	 *			"pattern"		=> "#^/products\-list/(?<name>[^/]*)/(?<color>[a-z]*)(?=/$|$)#",
	 *			"reverse"		=> "/products-list/<name>/<color>",
	 *			"controller"	=> "Products",
	 *			"action"		=> "List",
	 *			"defaults"		=> ["name" => "default-name",	"color" => "red"],
	 *		)
	 *	]);`
	 * @param \MvcCore\Route[]|array $routes Keyed array with routes,
	 *										 keys are route names or route
	 *										`Controller::Action` definitions.
	 * @return \MvcCore\Router
	 */
	public function __construct (array $routes = []) {
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
	 *	`\MvcCore\Router::GetInstance()->SetRoutes([
	 *		'products_list'	=> array(
	 *			"pattern"			=> "/products-list/<name>/<color>",
	 *			"controllerAction"	=> "Products:List",
	 *			"defaults"			=> ["name" => "default-name",	"color" => "red"],
	 *			"constraints"		=> ["name" => "[^/]*",			"color" => "[a-z]*"]
	 *		)
	 *	]);`
	 * or:
	 *	`\MvcCore\Router::GetInstance()->SetRoutes([
	 *		new Route(
	 *			"/products-list/<name>/<color>",
	 *			"Products:List",
	 *			["name" => "default-name",	"color" => "red"],
	 *			["name" => "[^/]*",		"color" => "[a-z]*"]
	 *		)
	 *	]);`
	 * or:
	 *	`\MvcCore\Router::GetInstance()->SetRoutes([
	 *		new Route(
	 *			"name"			=> "products_list",
	 *			"pattern"		=> "#^/products\-list/(?<name>[^/]*)/(?<color>[a-z]*)(?=/$|$)#",
	 *			"reverse"		=> "/products-list/<name>/<color>",
	 *			"controller"	=> "Products",
	 *			"action"		=> "List",
	 *			"defaults"		=> ["name" => "default-name",	"color" => "red"],
	 *		)
	 *	]);`
	 * @param \MvcCore\Route[]|array $routes Keyed array with routes,
	 *										 keys are route names or route
	 *										`Controller::Action` definitions.
	 * @return \MvcCore\Router
	 */
	public function & SetRoutes ($routes = []) {
		$this->routes = [];
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
	 *	`\MvcCore\Router::GetInstance()->AddRoutes([
	 *		"Products:List"	=> "/products-list/<name>/<color>",
	 *	]);`
	 * or:
	 *	`\MvcCore\Router::GetInstance()->AddRoutes([
	 *		'products_list'	=> [
	 *			"pattern"			=> "/products-list/<name>/<color>",
	 *			"controllerAction"	=> "Products:List",
	 *			"defaults"			=> ["name" => "default-name",	"color" => "red"],
	 *			"constraints"		=> ["name" => "[^/]*",			"color" => "[a-z]*"]
	 *		]
	 *	]);`
	 * or:
	 *	`\MvcCore\Router::GetInstance()->AddRoutes([
	 *		new Route(
	 *			"/products-list/<name>/<color>",
	 *			"Products:List",
	 *			["name" => "default-name",	"color" => "red"],
	 *			["name" => "[^/]*",		"color" => "[a-z]*"]
	 *		)
	 *	]);`
	 * or:
	 *	`\MvcCore\Router::GetInstance()->AddRoutes([
	 *		new Route(
	 *			"name"			=> "products_list",
	 *			"pattern"		=> "#^/products\-list/(?<name>[^/]*)/(?<color>[a-z]*)(?=/$|$)#",
	 *			"reverse"		=> "/products-list/<name>/<color>",
	 *			"controller"	=> "Products",
	 *			"action"		=> "List",
	 *			"defaults"		=> ["name" => "default-name",	"color" => "red"],
	 *		)
	 *	]);`
	 * @param \MvcCore\Route[]|array $routes Keyed array with routes,
	 *										 keys are route names or route
	 *										 `Controller::Action` definitions.
	 * @param bool $prepend	Optional, if `TRUE`, all given routes will
	 *						be prepended from the last to the first in
	 *						given list, not appended.
	 * @param bool $throwExceptionForDuplication `TRUE` by default. Throw an exception,
	 *											 if route `name` or route `Controller:Action`
	 *											 has been defined already. If `FALSE` old route
	 *											 is overwriten by new one.
	 * @return \MvcCore\Router
	 */
	public function & AddRoutes (array $routes = [], $prepend = FALSE, $throwExceptionForDuplication = TRUE) {
		if ($prepend) $routes = array_reverse($routes);
		$routeClass = self::$routeClass;
		foreach ($routes as $routeName => & $route) {
			$ctrlActionName = mb_strpos($routeName, ':') !== FALSE;
			$numericKey = is_numeric($routeName);
			if ($route instanceof \MvcCore\IRoute) {
				if (!$numericKey) {
					if ($ctrlActionName)
						$route->SetControllerAction($routeName);
					if ($route->GetName() === NULL) 
						$route->SetName($routeName);
				}
				$this->AddRoute(
					$route, $prepend, $throwExceptionForDuplication
				);
			} else if (is_array($route)) {
				if (!$numericKey) 
					$route[$ctrlActionName ? 'controllerAction'  : 'name'] = $routeName;
				$this->AddRoute(
					$this->getRouteInstance($route), 
					$prepend, $throwExceptionForDuplication
				);
			} else if (is_string($route)) {
				// route name is always Controller:Action
				$routeCfgData = ['pattern' => $route];
				$routeCfgData[$ctrlActionName ? 'controllerAction'  : 'name'] = $routeName;
				$this->AddRoute(
					$routeClass::CreateInstance($routeCfgData), 
					$prepend, $throwExceptionForDuplication
				);
			} else {
				throw new \InvalidArgumentException (
					"[".__CLASS__."] Route is not possible to assign (key: \"$routeName\", value: " . json_encode($route) . ")."
				);
			}
		}
		$this->anyRoutesConfigured = count($routes) > 0;
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
	 *	`\MvcCore\Router::GetInstance()->AddRoute([
	 *		"name"		=> "Products:List",
	 *		"pattern"	=> "/products-list/<name>/<color>",
	 *	]);`
	 * or:
	 *	`\MvcCore\Router::GetInstance()->AddRoute([
	 *		"name"				=> "products_list",
	 *		"pattern"			=> "/products-list/<name>/<color>",
	 *		"controllerAction"	=> "Products:List",
	 *		"defaults"			=> ["name" => "default-name",	"color" => "red"],
	 *		"constraints"		=> ["name" => "[^/]*",			"color" => "[a-z]*"]
	 *	]);`
	 * or:
	 *	`\MvcCore\Router::GetInstance()->AddRoute(new Route(
	 *		"/products-list/<name>/<color>",
	 *		"Products:List",
	 *		["name" => "default-name",	"color" => "red"],
	 *		["name" => "[^/]*",		"color" => "[a-z]*"]
	 *	));`
	 * or:
	 *	`\MvcCore\Router::GetInstance()->AddRoute(new Route(
	 *		"name"			=> "products_list",
	 *		"pattern"		=> "#^/products\-list/(?<name>[^/]*)/(?<color>[a-z]*)(?=/$|$)#",
	 *		"reverse"		=> "/products-list/<name>/<color>",
	 *		"controller"	=> "Products",
	 *		"action"		=> "List",
	 *		"defaults"		=> ["name" => "default-name",	"color" => "red"],
	 *	));`
	 * @param \MvcCore\Route|\MvcCore\IRoute|array $routeCfgOrRoute Route instance or
	 *																		   route config array.
	 * @param bool $prepend	Optional, if `TRUE`, given route will
	 *						be prepended, not appended.
	 * @param bool $throwExceptionForDuplication `TRUE` by default. Throw an exception,
	 *											 if route `name` or route `Controller:Action`
	 *											 has been defined already. If `FALSE` old route
	 *											 is overwriten by new one.
	 * @return \MvcCore\Router
	 */
	public function & AddRoute ($routeCfgOrRoute, $prepend = FALSE, $throwExceptionForDuplication = TRUE) {
		$instance = & $this->getRouteInstance($routeCfgOrRoute);
		$routeName = $instance->GetName();
		$controllerAction = $instance->GetControllerAction();
		if ($throwExceptionForDuplication) {
			$errorMsgs = [];
			if (isset($this->routes[$routeName]))
				$errorMsgs[] = 'Route with name `'.$routeName.'` has already been defined between router routes.';
			if (isset($this->urlRoutes[$controllerAction]))
				$errorMsgs[] = 'Route with `Controller:Action` combination: `'.$controllerAction.'` has already been defined between router routes.';
			if ($errorMsgs) {
				var_dump($this->routes);
				throw new \InvalidArgumentException('['.__CLASS__.'] '.implode(' ',$errorMsgs));
			}
		}
		$this->urlRoutes[$routeName] = $instance;
		$this->urlRoutes[$controllerAction] = $instance;
		if ($prepend) {
			$newRoutes = [];
			$newRoutes[$routeName] = $instance; 
			foreach ($this->routes as $routeName => $route)
				$newRoutes[$routeName] = $route;
			$this->routes = & $newRoutes;
		} else {
			$this->routes[$routeName] = $instance;
		}
		$this->anyRoutesConfigured = TRUE;
		return $this;
	}

	/**
	 * Return `TRUE` if router has any route by given route name, `FALSE` otherwise.
	 * @param string|\MvcCore\IRoute $routeOrRouteName
	 * @return boolean
	 */
	public function HasRoute ($routeOrRouteName) {
		if (is_string($routeOrRouteName)) {
			return isset($this->routes[$routeOrRouteName]);
		} else /*if ($routeOrRouteName instanceof \MvcCore\IRoute)*/ {
			return (
				isset($this->routes[$routeOrRouteName->GetName()]) || 
				isset($this->routes[$routeOrRouteName->GetControllerAction()])
			);
		}
		//return FALSE;
	}

	/**
	 * Remove route from router by given name and return removed route instance.
	 * If router has no route by given name, `NULL` is returned.
	 * @param string $routeName
	 * @return \MvcCore\Route|\MvcCore\IRoute|NULL
	 */
	public function RemoveRoute ($routeName) {
		$result = NULL;
		if (isset($this->routes[$routeName])) {
			$result = $this->routes[$routeName];
			unset($this->routes[$routeName]);
			$controllerAction = $result->GetControllerAction();
			if (isset($this->urlRoutes[$routeName])) unset($this->urlRoutes[$routeName]);
			if (isset($this->urlRoutes[$controllerAction])) unset($this->urlRoutes[$controllerAction]);
			if ($this->currentRoute->GetName() === $result->GetName())
				$this->currentRoute = NULL;
		}
		if (!$this->routes) $this->anyRoutesConfigured = FALSE;
		return $result;
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
	 * Get configured `\MvcCore\Route` route instances by route name, `NULL` if no route presented.
	 * @return \MvcCore\Route|NULL
	 */
	public function & GetRoute ($routeName) {
		if (isset($this->routes[$routeName]))
			return $this->routes[$routeName];
		return NULL;
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
	public function & SetRequest (\MvcCore\IRequest & $request) {
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
	public function & SetCurrentRoute (\MvcCore\IRoute $currentRoute) {
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
	 * Get trrailing slash behaviour - integer state about what to do with trailing
	 * slash in all requested url except homepage. Possible states are:
	 * - `-1` (`\MvcCore\IRouter::TRAILING_SLASH_REMOVE`)
	 *		Always remove trailing slash from requested url if there
	 *		is any and redirect to it, except homepage.
	 * -  `0` (`\MvcCore\IRouter::TRAILING_SLASH_BENEVOLENT`)
	 *		Be absolutely benevolent for trailing slash in requested url.
	 * -  `1` (`\MvcCore\IRouter::TRAILING_SLASH_ALWAYS`)
	 *		Always keep trailing slash in requested url or always add trailing
	 *		slash into url and redirect to it.
	 * Default value is `-1` - `\MvcCore\IRouter::TRAILING_SLASH_REMOVE`
	 * @return int
	 */
	public function GetTrailingSlashBehaviour () {
		return $this->trailingSlashBehaviour;
	}

	/**
	 * Set trrailing slash behaviour - integer state about what to do with trailing
	 * slash in all requested url except homepage. Possible states are:
	 * - `-1` (`\MvcCore\IRouter::TRAILING_SLASH_REMOVE`)
	 *		Always remove trailing slash from requested url if there
	 *		is any and redirect to it, except homepage.
	 * -  `0` (`\MvcCore\IRouter::TRAILING_SLASH_BENEVOLENT`)
	 *		Be absolutely benevolent for trailing slash in requested url.
	 * -  `1` (`\MvcCore\IRouter::TRAILING_SLASH_ALWAYS`)
	 *		Always keep trailing slash in requested url or always add trailing
	 *		slash into url and redirect to it.
	 * Default value is `-1` - `\MvcCore\IRouter::TRAILING_SLASH_REMOVE`
	 * @param int $trailingSlashBehaviour `-1` (`\MvcCore\IRouter::TRAILING_SLASH_REMOVE`)
	 *										 Always remove trailing slash from requested url if there
	 *										 is any and redirect to it, except homepage.
	 *									 `0` (`\MvcCore\IRouter::TRAILING_SLASH_BENEVOLENT`)
	 *										 Be absolutely benevolent for trailing slash in requested url.
	 *									 `1` (`\MvcCore\IRouter::TRAILING_SLASH_ALWAYS`)
	 *										 Always keep trailing slash in requested url or always add trailing
	 *										 slash into url and redirect to it.
	 * @return \MvcCore\Router|\MvcCore\IRouter
	 */
	public function & SetTrailingSlashBehaviour ($trailingSlashBehaviour = -1) {
		$this->trailingSlashBehaviour = $trailingSlashBehaviour;
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
	 *	 - Set up `\MvcCore\Router::$currentRoute`.
	 *	 - Reset `\MvcCore\Request::$params` again with with default route params,
	 *	   with request params itself and with params parsed from matching process.
	 * - If there is no route matching the request and also if the request is targeting homepage
	 *   or there is no route matching the request and also if the request is targeting something
	 *   else and also router is configured to route to default controller and action if no route
	 *   founded, complete `\MvcCore\Router::$currentRoute` with new empty automaticly created route
	 *   targeting default controller and action by configuration in application instance (`Index:Index`)
	 *   and route type create by configured `\MvcCore\Application::$routeClass` class name.
	 * - Return `TRUE` if `\MvcCore\Router::$currentRoute` is route instance or `FALSE` for redirection.
	 *
	 * This method is always called from core routing by:
	 * - `\MvcCore\Application::Run();` => `\MvcCore\Application::routeRequest();`.
	 * @return bool
	 */
	public function Route () {
		if (!$this->redirectToProperTrailingSlashIfNecessary()) return FALSE;
		$request = & $this->request;
		$requestCtrlName = $request->GetControllerName();
		$requestActionName = $request->GetActionName();
		$this->anyRoutesConfigured = count($this->routes) > 0;
		if ($requestCtrlName && $requestActionName) {
			$this->routeByControllerAndActionQueryString($requestCtrlName, $requestActionName);
		} else {
			$this->routeByRewriteRoutes($requestCtrlName, $requestActionName);
		}
		$this->routeSetUpDefaultForHomeIfNoMatch();
		return $this->routeSetUpSelfRouteName();
	}

	/**
	 * After routing is done, check if there is any current route and if not,
	 * check if request is homepage or if router is configured to route
	 * request to default controller and action if no match and set up new 
	 * route as current route for default controller and action if necessary.
	 * @return void
	 */
	protected function routeSetUpDefaultForHomeIfNoMatch () {
		if ($this->currentRoute === NULL) {
			$request = & $this->request;
			$requestIsHome = (
				trim($request->GetPath(), '/') == '' || 
				$request->GetPath() == $request->GetScriptName()
			);
			if ($requestIsHome || $this->routeToDefaultIfNotMatch) {
				list($dfltCtrl, $dftlAction) = $this->application->GetDefaultControllerAndActionNames();
				$this->SetOrCreateDefaultRouteAsCurrent(
					\MvcCore\IRouter::DEFAULT_ROUTE_NAME, $dfltCtrl, $dftlAction
				);
			}
		}
	}

	/**
	 * After routing is done, check if there is any current route and set up
	 * property `$this->selfRouteName` with currently matched route name.
	 * Return `TRUE` if current route is route instance or `FALSE` otherwise.
	 * @return bool
	 */
	protected function routeSetUpSelfRouteName () {
		$result = $this->currentRoute instanceof \MvcCore\IRoute;
		if ($result) 
			$this->selfRouteName = $this->anyRoutesConfigured
				? $this->currentRoute->GetName()
				: $this->currentRoute->GetControllerAction();
		return $result;
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
	 * @throws \InvalidArgumentException
	 * @return string
	 */
	public function Url ($controllerActionOrRouteName = 'Index:Index', $params = []) {
		$result = '';
		$request = & $this->request;
		
		if (strpos($controllerActionOrRouteName, ':') !== FALSE) {
			list($ctrlPc, $actionPc) = explode(':', $controllerActionOrRouteName);
			if (!$ctrlPc) {
				$toolClass = self::$toolClass;
				$ctrlPc = $toolClass::GetPascalCaseFromDashed($request->GetControllerName());
			}
			if (!$actionPc) {
				$toolClass = self::$toolClass;
				$actionPc = $toolClass::GetPascalCaseFromDashed($request->GetActionName());
			}
			$controllerActionOrRouteName = "$ctrlPc:$actionPc";
		} else if ($controllerActionOrRouteName == 'self') {
			$controllerActionOrRouteName = $this->selfRouteName;
			$params = array_merge($this->GetRequestedUrlParams(), $params);
			unset($params['controller'], $params['action']);
		}

		$absolute = FALSE;
		if ($params && isset($params['absolute'])) {
			$absolute = (bool) $params['absolute'];
			unset($params['absolute']);
		}
		
		if ($this->anyRoutesConfigured && isset($this->urlRoutes[$controllerActionOrRouteName])) {
			$result = $this->UrlByRoute($this->urlRoutes[$controllerActionOrRouteName], $params);
		} else if ($this->anyRoutesConfigured && isset($this->routes[$controllerActionOrRouteName])) {
			$result = $this->UrlByRoute($this->routes[$controllerActionOrRouteName], $params);
		} else {
			$result = $this->UrlByQueryString($controllerActionOrRouteName, $params);
		}

		if ($absolute) $result = $request->GetDomainUrl() . $result;
		
		return $result;
	}

	/**
	 * Try to found any existing route by `$routeName` argument
	 * or try to find any existing route by `$controllerPc:$actionPc` arguments
	 * combination and set this founded route instance as current route object.
	 *
	 * Target request object reference to this newly configured current route object.
	 *
	 * If no route by name or controller and action combination found,
	 * create new empty route by configured route class from application core
	 * and set up this new route by given `$routeName`, `$controllerPc`, `$actionPc`
	 * with route match pattern to match any request `#/(?<path>.*)#` and with reverse
	 * pattern `/<path>` to create url by single `path` param only. Add this newly
	 * created route into routes and set this new route as current route object.
	 *
	 * This method is always called internaly for following cases:
	 * - When router has no routes configured and request is necessary
	 *   to route by query string arguments only (controller and action).
	 * - When no route matched and when is necessary to create
	 *   default route object for homepage, handled by `Index:Index` by default.
	 * - When no route matched and when router is configured to route
	 *   requests to default route if no route matched by
	 *   `$router->SetRouteToDefaultIfNotMatch();`.
	 * - When is necessary to create not found route or error route
	 *   when there was not possible to route the request or when
	 *   there was any uncatched exception in controller or template
	 *   catched later by application.
	 *
	 * @param string $routeName Always as `default`, `error` or `not_found`, by constants:
	 *						 `\MvcCore\IRouter::DEFAULT_ROUTE_NAME`
	 *						 `\MvcCore\IRouter::DEFAULT_ROUTE_NAME_ERROR`
	 *						 `\MvcCore\IRouter::DEFAULT_ROUTE_NAME_NOT_FOUND`
	 * @param string $controllerPc Controller name in pascal case.
	 * @param string $actionPc Action name with pascal case without ending `Action` substring.
	 * @param bool $fallbackCall `FALSE` by default. If `TRUE`, this function is called from error rendering fallback, self route name is not changed.
	 * @return \MvcCore\Route|\MvcCore\IRoute
	 */
	public function & SetOrCreateDefaultRouteAsCurrent ($routeName, $controllerPc, $actionPc, $fallbackCall = FALSE) {
		$controllerPc = strtr($controllerPc, '/', '\\');
		$ctrlActionRouteName = $controllerPc.':'. $actionPc;
		$request = & $this->request;
		if (isset($this->routes[$ctrlActionRouteName])) {
			$defaultRoute = $this->routes[$ctrlActionRouteName];
		} else if (isset($this->routes[$routeName])) {
			$defaultRoute = $this->routes[$routeName];
		} else {
			$routeClass = self::$routeClass;
			$defaultRoute = $routeClass::CreateInstance()
				->SetMatch('#/(?<path>.*)#')
				->SetReverse('/<path>')
				->SetName($routeName)
				->SetController($controllerPc)
				->SetAction($actionPc)
				->SetDefaults([
					'path'		=> NULL,
					'controller'=> NULL,
					'action'	=> NULL,
				]);
			$anyRoutesConfigured = $this->anyRoutesConfigured;
			$this->AddRoute($defaultRoute, TRUE, FALSE);
			$this->anyRoutesConfigured = $anyRoutesConfigured;
			if (!$request->IsInternalRequest()) 
				$request->SetParam('path', ($request->HasParam('path')
					? $request->GetParam('path', '.*')
					: $request->GetPath())
				);
		}
		$toolClass = self::$toolClass;
		$request
			->SetControllerName($toolClass::GetDashedFromPascalCase($defaultRoute->GetController()))
			->SetActionName($toolClass::GetDashedFromPascalCase($defaultRoute->GetAction()));
		$this->currentRoute = $defaultRoute;
		if (!$fallbackCall) $this->selfRouteName = $routeName;
		return $defaultRoute;
	}

	/**
	 * Complete non-absolute, non-localized url with all params in query string.
	 * Example: `"/application/base-bath/index.php?controller=ctrlName&amp;action=actionName&amp;name=cool-product-name&amp;color=blue"`
	 * @param string $controllerActionOrRouteName
	 * @param array  $params
	 * @return string
	 */
	public function UrlByQueryString ($controllerActionOrRouteName = 'Index:Index', & $params = []) {
		$toolClass = self::$toolClass;
		list($ctrlPc, $actionPc) = explode(':', $controllerActionOrRouteName);
		$amp = $this->getQueryStringParamsSepatator();
		list($dfltCtrl, $dftlAction) = $this->application->GetDefaultControllerAndActionNames();
		$result = $this->request->GetBasePath();
		if ($params || $ctrlPc !== $dfltCtrl || $actionPc !== $dftlAction) {
			$result .= $this->request->GetScriptName()
				. '?controller=' . $toolClass::GetDashedFromPascalCase($ctrlPc)
				. $amp . 'action=' . $toolClass::GetDashedFromPascalCase($actionPc);
			if ($params) 
				// `http_build_query()` automaticly converts all XSS chars to entities (`< > & " ' &`):
				$result .= $amp . str_replace('%2F', '/', http_build_query($params, '', $amp));
		}
		return $result;
	}

	/**
	 * Complete non-absolute, non-localized url by route instance reverse info.
	 * Example:
	 *	Input (`\MvcCore\Route::$reverse`):
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
	 * @param array $params
	 * @return string
	 */
	public function UrlByRoute (\MvcCore\IRoute & $route, & $params = []) {
		return $this->request->GetBasePath() . $route->Url(
			$params, $this->GetRequestedUrlParams(), $this->getQueryStringParamsSepatator()
		);
	}

	/**
	 * Get all request params - params parsed by route and query string params.
	 * Be carefull, it could contain XSS chars. Use always `htmlspecialchars()`.
	 * @return array
	 */
	public function & GetRequestedUrlParams () {
		if ($this->requestedUrlParams === NULL) {
			// create global `$_GET` array clone:
			$this->requestedUrlParams = array_merge([], $this->request->GetGlobalCollection('get'));
		}
		return $this->requestedUrlParams;
	}

	/**
	 * Get always route instance from given route configuration data or return
	 * already created given instance.
	 * @param \MvcCore\Route|\MvcCore\IRoute|array $routeCfgOrRoute Route instance or
	 *																		   route config array.
	 * @return \MvcCore\Route|\MvcCore\IRoute
	 */
	protected function & getRouteInstance (& $routeCfgOrRoute) {
		if ($routeCfgOrRoute instanceof \MvcCore\IRoute) 
			return $routeCfgOrRoute;
		$routeClass = self::$routeClass;
		$instance = $routeClass::CreateInstance($routeCfgOrRoute);
		return $instance;
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
		$toolClass = self::$toolClass;
		list($ctrlDfltName, $actionDfltName) = $this->application->GetDefaultControllerAndActionNames();
		$this->SetOrCreateDefaultRouteAsCurrent(
			\MvcCore\IRouter::DEFAULT_ROUTE_NAME,
			$toolClass::GetPascalCaseFromDashed($requestCtrlName ?: $ctrlDfltName),
			$toolClass::GetPascalCaseFromDashed($requestActionName ?: $actionDfltName)
		);
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
		$requestMethod = $request->GetMethod();
		/** @var $route \MvcCore\Route */
		reset($this->routes);
		foreach ($this->routes as & $route) {
			if ($matchedParams = $route->Matches($requestPath, $requestMethod, NULL)) {
				$this->currentRoute = & $route;
				$routeDefaultParams = $route->GetDefaults() ?: [];
				$newParams = array_merge($routeDefaultParams, $matchedParams, $request->GetParams('.*'));
				$request->SetParams($newParams);
				$matchedParamsClone = array_merge([], $matchedParams);
				unset($matchedParamsClone['controller'], $matchedParamsClone['action']);
				if ($matchedParamsClone) {
					if ($this->requestedUrlParams) {
						$requestedUrlParamsToMerge = $this->requestedUrlParams;
					} else {
						$requestedUrlParamsToMerge = [];
					}
					$this->requestedUrlParams = array_merge(
						$requestedUrlParamsToMerge, $matchedParamsClone
					);
				}
				break;
			}
		}
		if ($this->currentRoute !== NULL) 
			$this->routeByRewriteRoutesSetUpRequestByCurrentRoute(
				$requestCtrlName, $requestActionName
			);
	}

	/**
	 * Set up request object controller and action by current route (routing 
	 * result) routed by method `$this->routeByRewriteRoutes();`. If there is no
	 * controller name and only action name or if there is no action name and only 
	 * controller name, complete those missing record by default core values for 
	 * controller name and action name.
	 * @param string $controllerName
	 * @param string $actionName
	 * @return void
	 */
	protected function routeByRewriteRoutesSetUpRequestByCurrentRoute ($requestCtrlName, $requestActionName) {
		$route = $this->currentRoute;
		$request = & $this->request;
		$toolClass = self::$toolClass;
		$routeCtrl = $route->GetController();
		$routeAction = $route->GetAction();
		if (!$routeCtrl || !$routeAction) {
			list($ctrlDfltName, $actionDfltName) = $this->application->GetDefaultControllerAndActionNames();
			if (!$routeCtrl)
				$route->SetController(
					$requestCtrlName
						? $toolClass::GetPascalCaseFromDashed($requestCtrlName)
						: $ctrlDfltName
				);
			if (!$routeAction)
				$route->SetAction(
					$requestActionName
						? $toolClass::GetPascalCaseFromDashed($requestActionName)
						: $actionDfltName
					);
		}
		$request
			->SetControllerName($toolClass::GetDashedFromPascalCase($route->GetController()))
			->SetActionName($toolClass::GetDashedFromPascalCase($route->GetAction()));
	}

	/**
	 * Redirect to proper trailing slash url version only
	 * if it is necessary by `\MvcCore\Router::$trailingSlashBehaviour`
	 * and if it is necessary by last character in request path.
	 * @return bool
	 */
	protected function redirectToProperTrailingSlashIfNecessary () {
		if (!$this->trailingSlashBehaviour) return TRUE;
		$path = $this->request->GetPath();
		if ($path == '/')
			return TRUE; // do not redirect for homepage with trailing slash
		if ($path == '') {
			// add homepage trailing slash and redirect
			$this->redirect(
				$this->request->GetBaseUrl()
				. '/'
				. $this->request->GetQuery(TRUE)
				. $this->request->GetFragment(TRUE)
			);
		}
		$lastPathChar = mb_substr($path, mb_strlen($path) - 1);
		if ($lastPathChar == '/' && $this->trailingSlashBehaviour == \MvcCore\IRouter::TRAILING_SLASH_REMOVE) {
			// remove trailing slash and redirect
			$this->redirect(
				$this->request->GetBaseUrl()
				. rtrim($path, '/')
				. $this->request->GetQuery(TRUE)
				. $this->request->GetFragment(TRUE)
			);
			return FALSE;
		} else if ($lastPathChar != '/' && $this->trailingSlashBehaviour == \MvcCore\IRouter::TRAILING_SLASH_ALWAYS) {
			// add trailing slash and redirect
			$this->redirect(
				$this->request->GetBaseUrl()
				. $path . '/'
				. $this->request->GetQuery(TRUE)
				. $this->request->GetFragment(TRUE)
			);
			return FALSE;
		}
		return TRUE;
	}

	/**
	 * Redirect request to given url with optional code and terminate application.
	 * @param string	$url New location url.
	 * @param int		$code Http status code, 301 by default.
	 */
	protected function redirect ($url, $code = 301) {
		$app = \MvcCore\Application::GetInstance();
		$app->GetResponse()
			->SetCode($code)
			->SetHeader('Location', $url);
		$app->Terminate();
	}

	/**
	 * Return XML query string separator `&amp;`, if response has any `Content-Type` header with `xml` substring inside
	 * or return XML query string separator `&amp;` if `\MvcCore\View::GetDoctype()` is has any `XML` or any `XHTML` substring inside.
	 * Otherwise return HTML query string separator `&`.
	 * @return string
	 */
	protected function getQueryStringParamsSepatator () {
		if ($this->queryParamsSepatator === NULL) {
			$response = \MvcCore\Application::GetInstance()->GetResponse();
			if ($response->HasHeader('Content-Type')) {
				$this->queryParamsSepatator = $response->IsXmlOutput() ? '&amp;' : '&';
			} else {
				$viewDocType = \MvcCore\View::GetDoctype();
				$this->queryParamsSepatator = (
					strpos($viewDocType, \MvcCore\View::DOCTYPE_XML) !== FALSE ||
					strpos($viewDocType, \MvcCore\View::DOCTYPE_XHTML) !== FALSE
				) ? '&amp;' : '&';
			}
		}
		return $this->queryParamsSepatator;
	}
}
