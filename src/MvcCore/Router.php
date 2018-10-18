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
	 * Another application route instances store to match request,
	 * where are routes stored under key, representing first founded
	 * word in requested path. Values under every first path word is array.
	 * Every array has keys as route(s) names and values as `\MvcCore\Route` 
	 * instances.
	 * @var array
	 */
	protected $routesGroups = [];

	/**
	 * Global application route instances store to complete url addresses.
	 * Keys are route(s) names and `Controller:Action` combinations,
	 * values are `\MvcCore\Route` instances.
	 * @var \MvcCore\Route[]
	 */
	protected $urlRoutes = [];

	/**
	 * TODO: dopsat
	 * @var bool|NULL
	 */
	protected $routeByQueryString = NULL;

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
	 * Those params could contain additional user params from filter function.
	 * @var array|NULL
	 */
	protected $defaultParams = NULL;

	/**
	 * All request params - params parsed by route and query string params.
	 * Be carefull, it could contain XSS chars. Use always `htmlspecialchars()`.
	 * @var array|NULL
	 */
	protected $requestedParams = NULL;

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
	 * TODO: dopsat
	 * @var bool
	 */
	protected $autoCanonizeRequests = TRUE;

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
	 * @var bool|NULL
	 */
	protected $anyRoutesConfigured = NULL;

	/**
	 * TODO: dopsat
	 * @var callable|NULL
	 */
	protected $preRouteMatchingHandler = NULL;
	
	/**
	 * TODO: dopsat
	 * @var callable|NULL
	 */
	protected $preRouteUrlBuildingHandler = NULL;

	/**
	 * TODO: dopsat
	 * @var bool
	 */
	protected $internalRequest = FALSE;


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
	 * @param bool $autoInitialize If `TRUE`, locale routes array is cleaned and 
	 *							   then all routes (or configuration arrays) are 
	 *							   sended into method `$router->AddRoutes();`, 
	 *							   where are routes auto initialized for missing 
	 *							   route names or route controller or route action
	 *							   record, completed always from array keys.
	 *							   You can you `FALSE` to set routes without any 
	 *							   change or autoinitialization, it could be usefull 
	 *							   to restore cached routes etc.
	 * @return \MvcCore\Router
	 */
	public static function & GetInstance (array $routes = [], $autoInitialize = TRUE) {
		if (!self::$instance) {
			/** @var $app \MvcCore\Application */
			$app = & \MvcCore\Application::GetInstance();
			self::$routeClass = $app->GetRouteClass();
			self::$toolClass = $app->GetToolClass();
			$routerClass = $app->GetRouterClass();
			$instance = new $routerClass($routes, $autoInitialize);
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
	 * @param bool $autoInitialize If `TRUE`, locale routes array is cleaned and 
	 *							   then all routes (or configuration arrays) are 
	 *							   sended into method `$router->AddRoutes();`, 
	 *							   where are routes auto initialized for missing 
	 *							   route names or route controller or route action
	 *							   record, completed always from array keys.
	 *							   You can you `FALSE` to set routes without any 
	 *							   change or autoinitialization, it could be usefull 
	 *							   to restore cached routes etc.
	 * @return \MvcCore\Router
	 */
	public function __construct (array $routes = [], $autoInitialize = TRUE) {
		if ($routes) $this->SetRoutes($routes, NULL, $autoInitialize);
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
	 * @param string|NULL $groupName Group name is first matched/parsed word in 
	 *								 requested path to group routes by to try to
	 *								 match only routes you realy need, not all of
	 *								 them. If `NULL` by default, routes are 
	 *								 inserted into default group.
	 * @param bool $autoInitialize If `TRUE`, locale routes array is cleaned and 
	 *							   then all routes (or configuration arrays) are 
	 *							   sended into method `$router->AddRoutes();`, 
	 *							   where are routes auto initialized for missing 
	 *							   route names or route controller or route action
	 *							   record, completed always from array keys.
	 *							   You can you `FALSE` to set routes without any 
	 *							   change or autoinitialization, it could be usefull 
	 *							   to restore cached routes etc.
	 * @return \MvcCore\Router
	 */
	public function & SetRoutes ($routes = [], $groupName = NULL, $autoInitialize = TRUE) {
		if ($autoInitialize) {
			$this->routes = [];
			$this->AddRoutes($routes, $groupName);
		} else {
			$this->routes = $routes;
			$noGroupNameDefined = $groupName === NULL;
			if ($noGroupNameDefined) {
				$this->routesGroups[''] = $routes;
			} else {
				$this->routesGroups[$groupName] = $routes;
			}
			$this->urlRoutes = [];
			foreach ($routes as $route) {
				$this->urlRoutes[$route->GetName()] = $route;
				$controllerAction = $route->GetControllerAction();
				if ($controllerAction !== ':') 
					$this->urlRoutes[$controllerAction] = $route;
				if ($noGroupNameDefined) {
					$routeGroupName = $route->GetGroupName();
					if ($routeGroupName === NULL) $routeGroupName = '';
					if (!array_key_exists($routeGroupName, $this->routesGroups))
						$this->routesGroups[$routeGroupName] = [];
					$this->routesGroups[$routeGroupName][] = $route;
				}
			}
			$this->anyRoutesConfigured = count($routes) > 0;
		}
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
	 * @param string|NULL $groupName Group name is first matched/parsed word in 
	 *								 requested path to group routes by to try to
	 *								 match only routes you realy need, not all of
	 *								 them. If `NULL` by default, routes are 
	 *								 inserted into default group.
	 * @param bool $prepend	Optional, if `TRUE`, all given routes will
	 *						be prepended from the last to the first in
	 *						given list, not appended.
	 * @param bool $throwExceptionForDuplication `TRUE` by default. Throw an exception,
	 *											 if route `name` or route `Controller:Action`
	 *											 has been defined already. If `FALSE` old route
	 *											 is overwriten by new one.
	 * @return \MvcCore\Router
	 */
	public function & AddRoutes (array $routes = [], $groupName = NULL, $prepend = FALSE, $throwExceptionForDuplication = TRUE) {
		if ($prepend) $routes = array_reverse($routes);
		$routeClass = self::$routeClass;
		foreach ($routes as $routeName => & $route) {
			$numericKey = is_numeric($routeName);
			$ctrlActionName = !$numericKey && mb_strpos($routeName, ':') !== FALSE;
			if ($route instanceof \MvcCore\IRoute) {
				if ($numericKey) {
					if (!$route->GetName()) {
						$routeAutoName = $route->GetControllerAction();
						if ($routeAutoName === ':') $routeAutoName = 'Route_' . $routeName;
						$route->SetName($routeAutoName);
					}
				} else {
					if ($ctrlActionName)
						$route->SetControllerAction($routeName);
					if ($route->GetName() === NULL) 
						$route->SetName($routeName);
				}
				$this->AddRoute(
					$route, $groupName, $prepend, $throwExceptionForDuplication
				);
			} else if (is_array($route)) {
				if (!$numericKey) 
					$route[$ctrlActionName ? 'controllerAction'  : 'name'] = $routeName;
				$this->AddRoute(
					$this->getRouteInstance($route), 
					$groupName, $prepend, $throwExceptionForDuplication
				);
			} else if (is_string($route)) {
				// route name is always Controller:Action
				$routeCfgData = ['pattern' => $route];
				$routeCfgData[$ctrlActionName ? 'controllerAction'  : 'name'] = $routeName;
				$this->AddRoute(
					$routeClass::CreateInstance($routeCfgData), 
					$groupName, $prepend, $throwExceptionForDuplication
				);
			} else {
				throw new \InvalidArgumentException (
					"[".__CLASS__."] Route is not possible to assign (key: \"$routeName\", value: " . serialize($route) . ")."
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
	 *																route config array.
	 * @param string|NULL $groupName Group name is first matched/parsed word in 
	 *								 requested path to group routes by to try to
	 *								 match only routes you realy need, not all of
	 *								 them. If `NULL` by default, routes are 
	 *								 inserted into default group.
	 * @param bool $prepend	Optional, if `TRUE`, given route will
	 *						be prepended, not appended.
	 * @param bool $throwExceptionForDuplication `TRUE` by default. Throw an exception,
	 *											 if route `name` or route `Controller:Action`
	 *											 has been defined already. If `FALSE` old route
	 *											 is overwriten by new one.
	 * @return \MvcCore\Router
	 */
	public function & AddRoute ($routeCfgOrRoute, $groupName = NULL, $prepend = FALSE, $throwExceptionForDuplication = TRUE) {
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
				//var_dump($this->routes);
				throw new \InvalidArgumentException('['.__CLASS__.'] '.implode(' ',$errorMsgs));
			}
		}
		$this->urlRoutes[$routeName] = $instance;
		if ($controllerAction !== ':') $this->urlRoutes[$controllerAction] = $instance;
		$this->addRouteToGroup ($instance, $routeName, $groupName, $prepend);
		if ($prepend) {
			$newItem = [$routeName => $instance];
			$this->routes = $newItem + $this->routes;
		} else {
			$this->routes[$routeName] = $instance;
		}
		$this->anyRoutesConfigured = TRUE;
		return $this;
	}

	/**
	 * TODO: dopsat
	 * @param \MvcCore\Route $route 
	 * @param string $routeName 
	 * @param string|NULL $groupName 
	 * @param bool $prepend 
	 */
	protected function addRouteToGroup (\MvcCore\IRoute & $route, $routeName, $groupName, $prepend) {
		if ($groupName === NULL) {
			$routesGroupsKey = '';
		} else {
			$routesGroupsKey = $groupName;
			$route->SetGroupName($groupName);
		}
		if (array_key_exists($routesGroupsKey, $this->routesGroups)) {
			$groupRoutes = & $this->routesGroups[$routesGroupsKey];
		} else {
			$groupRoutes = [];
			$this->routesGroups[$routesGroupsKey] = & $groupRoutes;
		}
		if ($prepend) {
			$newItem = [$routeName => $route];
			$groupRoutes = $newItem + $groupRoutes;
		} else {
			$groupRoutes[$routeName] = $route;
		}
	}

	/**
	 * Return `TRUE` if router has any route by given route name, `FALSE` otherwise.
	 * @param string|\MvcCore\IRoute $routeOrRouteName
	 * @return bool
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
			$this->removeRouteFromGroup($result, $routeName);
			$controllerAction = $result->GetControllerAction();
			if (isset($this->urlRoutes[$routeName])) 
				unset($this->urlRoutes[$routeName]);
			if (isset($this->urlRoutes[$controllerAction])) 
				unset($this->urlRoutes[$controllerAction]);
			if ($this->currentRoute->GetName() === $result->GetName())
				$this->currentRoute = NULL;
		}
		if (!$this->routes && $this->preRouteMatchingHandler === NULL) 
			$this->anyRoutesConfigured = FALSE;
		return $result;
	}

	protected function removeRouteFromGroup (\MvcCore\IRoute & $route, $routeName) {
		$routeGroup = $route->GetGroupName();
		$groupRoutesKey = $routeGroup ?: '';
		if (isset($this->routesGroups[$groupRoutesKey])) 
			unset($this->routesGroups[$groupRoutesKey][$routeName]);
	}

	/**
	 * Get all configured route(s) as `\MvcCore\Route` instances.
	 * Keys in returned array are route names, values are route objects.
	 * @param string|NULL $groupName Group name is first matched/parsed word in 
	 *								 requested path to group routes by to try to
	 *								 match only routes you realy need, not all of
	 *								 them. If `NULL` by default, there are 
	 *								 returned all routes from all groups.
	 * @return \MvcCore\Route[]
	 */
	public function & GetRoutes ($groupName = NULL) {
		if ($groupName !== NULL) 
			return $this->routesGroups[$groupName];
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
	 * TODO: dopsat
	 * @param bool|NULL $routeByQueryString 
	 * @return \MvcCore\Router
	 */
	public function & SetRouteByQueryString ($routeByQueryString = TRUE) {
		$this->routeByQueryString = $routeByQueryString;
		return $this;
	}

	/**
	 * TODO: dopsat
	 * @return bool|NULL
	 */
	public function GetRouteByQueryString () {
		return $this->routeByQueryString;
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
	 * Get default request params - default params to build url with possibility
	 * to define custom records for filter functions.
	 * Be carefull, it could contain XSS chars. Use always `htmlspecialchars()`.
	 * @return array
	 */
	public function & GetDefaultParams () {
		return $this->defaultParams;
	}

	/**
	 * Get all request params - params parsed by route and query string params.
	 * Be carefull, it could contain XSS chars. Use always `htmlspecialchars()`.
	 * @return array
	 */
	public function & GetRequestedParams () {
		return $this->requestedParams;
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
	 * TODO: dopsat
	 * @return bool
	 */
	public function GetAutoCanonizeRequests () {
		return $this->autoCanonizeRequests;
	}

	/**
	 * TODO: dopsat
	 * @param bool $autoCanonizeRequests 
	 * @return \MvcCore\Router
	 */
	public function & SetAutoCanonizeRequests ($autoCanonizeRequests = TRUE) {
		$this->autoCanonizeRequests = $autoCanonizeRequests;
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
	 * - Return `TRUE` if routing has no redirection or `FALSE` for redirection.
	 *
	 * This method is always called from core routing by:
	 * - `\MvcCore\Application::Run();` => `\MvcCore\Application::routeRequest();`.
	 * @return bool
	 */
	public function Route () {
		$this->internalRequest = $this->request->IsInternalRequest();
		if (!$this->internalRequest && !$this->routeByQueryString) 
			if (!$this->redirectToProperTrailingSlashIfNecessary()) return FALSE;
		list($requestCtrlName, $requestActionName) = $this->routeDetectStrategy();
		if ($this->routeByQueryString) {
			$this->routeByControllerAndActionQueryString($requestCtrlName, $requestActionName);
		} else {
			$this->routeByRewriteRoutes($requestCtrlName, $requestActionName);
		}
		if (!$this->routeProcessRouteRedirectionIfAny()) return FALSE;
		return $this->routeSetUpDefaultForHomeIfNoMatch()
					->routeSetUpSelfRouteNameIfAny()
					->routeRedirect2CanonicalIfAny();
	}

	/**
	 * TODO: neaktualni
	 * @return array
	 */
	protected function routeDetectStrategy () {
		$request = & $this->request;
		$requestCtrlName = $request->GetControllerName();
		$requestActionName = $request->GetActionName();
		$this->anyRoutesConfigured = (
			$this->preRouteMatchingHandler !== NULL || count($this->routes) > 0
		);
		if ($this->routeByQueryString === NULL) {
			list($reqScriptName, $reqPath) = [$request->GetScriptName(), $request->GetPath(TRUE)];
			$requestCtrlNameNotNull = $requestCtrlName !== NULL;
			$requestActionNameNotNull = $requestActionName !== NULL;
			$requestCtrlAndAlsoAction = $requestCtrlNameNotNull && $requestActionNameNotNull;
			$requestCtrlOrAction = $requestCtrlNameNotNull || $requestActionNameNotNull;
			$this->routeByQueryString = (
				$requestCtrlAndAlsoAction ||
				($requestCtrlOrAction && (
					$reqScriptName === $reqPath || 
					trim($reqPath, '/') === ''
				))
			);
		}
		return [$requestCtrlName, $requestActionName];
	}

	protected function routeProcessRouteRedirectionIfAny () {
		if ($this->currentRoute instanceof \MvcCore\IRoute) {
			$redirectRouteName = $this->currentRoute->GetRedirect();
			if ($redirectRouteName !== NULL) {
				$redirectUrl = $this->Url($redirectRouteName, $this->requestedParams);
				$this->redirect($redirectUrl, \MvcCore\IResponse::MOVED_PERMANENTLY);
				return FALSE;
			}
		}
		return TRUE;
	}

	/**
	 * After routing is done, check if there is any current route and if not,
	 * check if request is homepage or if router is configured to route
	 * request to default controller and action if no match and set up new 
	 * route as current route for default controller and action if necessary.
	 * @return \MvcCore\Router
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
				// set up requested params from query string if there are any (and path if there is path from previous fn)
				$requestParams = array_merge([], $this->request->GetParams(FALSE));
				unset($requestParams['controller'], $requestParams['action']);
				$this->requestedParams = & $requestParams;
			}
		}
		return $this;
	}

	/**
	 * After routing is done, check if there is any current route and set up
	 * property `$this->selfRouteName` with currently matched route name.
	 * @return \MvcCore\Router
	 */
	protected function routeSetUpSelfRouteNameIfAny () {
		if ($this->currentRoute instanceof \MvcCore\IRoute) 
			$this->selfRouteName = $this->anyRoutesConfigured
				? $this->currentRoute->GetName()
				: $this->currentRoute->GetControllerAction();
		return $this;
	}

	/**
	 * TODO:
	 * Return `TRUE` if current route is route instance or `FALSE` otherwise.
	 * @return bool
	 */
	protected function routeRedirect2CanonicalIfAny () {
		if (
			$this->internalRequest || !$this->autoCanonizeRequests || 
			$this->request->GetMethod() !== \MvcCore\IRequest::METHOD_GET
		) return TRUE;
		if ($this->routeByQueryString) {
			// self url could be completed only by query string strategy
			return $this->routeRedirect2CanonicalQueryStringStrategy();
		} else if ($this->selfRouteName !== NULL) {
			// self url could be completed by rewrite routes strategy
			return $this->routeRedirect2CanonicalRewriteRoutesStrategy();
		}
		return TRUE;
	}

	protected function routeRedirect2CanonicalQueryStringStrategy () {
		$req = & $this->request;
		$redirectToCanonicalUrl = FALSE;
		$requestGlobalGet = & $req->GetGlobalCollection('get');
		$requestedCtrlDc = isset($requestGlobalGet['controller']) ? $requestGlobalGet['controller'] : NULL;
		$requestedActionDc = isset($requestGlobalGet['action']) ? $requestGlobalGet['action'] : NULL;
		$toolClass = self::$toolClass;
		list($dfltCtrlPc, $dftlActionPc) = $this->application->GetDefaultControllerAndActionNames();
		$dfltCtrlDc = $toolClass::GetDashedFromPascalCase($dfltCtrlPc);
		$dftlActionDc = $toolClass::GetDashedFromPascalCase($dftlActionPc);
		$requestedParamsClone = array_merge([], $this->requestedParams);
		if ($requestedCtrlDc === NULL && $requestedParamsClone['controller'] === $dfltCtrlDc) {
			unset($requestedParamsClone['controller']);
			$redirectToCanonicalUrl = TRUE;
		} else if ($requestedCtrlDc !== NULL && $requestedCtrlDc === $dfltCtrlDc) {
			unset($requestedParamsClone['controller']);
			$redirectToCanonicalUrl = TRUE;
		}
		if ($requestedActionDc === NULL && $requestedParamsClone['action'] === $dftlActionDc) {
			unset($requestedParamsClone['action']);
			$redirectToCanonicalUrl = TRUE;
		} else if ($requestedActionDc !== NULL && $requestedActionDc === $dftlActionDc) {
			unset($requestedParamsClone['action']);
			$redirectToCanonicalUrl = TRUE;
		}
		if ($redirectToCanonicalUrl) {
			$selfCanonicalUrl = $this->UrlByQueryString($this->selfRouteName, $requestedParamsClone);	
			$this->redirect($selfCanonicalUrl, \MvcCore\IResponse::MOVED_PERMANENTLY);
			return FALSE;
		}
		return TRUE;
	}
	
	protected function routeRedirect2CanonicalRewriteRoutesStrategy () {
		$req = & $this->request;
		$redirectToCanonicalUrl = FALSE;
		$defaultParams =  $this->GetDefaultParams() ?: [];
		list($selfUrlDomainAndBasePart, $selfUrlPathAndQueryPart) = $this->urlRoutes[$this->selfRouteName]->Url(
			$req, $this->requestedParams, $defaultParams, $this->getQueryStringParamsSepatator()
		);
		if (mb_strlen($selfUrlDomainAndBasePart) > 0 && $selfUrlDomainAndBasePart !== $req->GetBaseUrl()) 
			$redirectToCanonicalUrl = TRUE;
		if (mb_strlen($selfUrlPathAndQueryPart) > 0) {
			$path = $req->GetPath(TRUE);
			$path = $path === '' ? '/' : $path ;
			$requestedUrl = $req->GetBasePath() . $path;
			if (mb_strpos($selfUrlPathAndQueryPart, '?') !== FALSE) {
				$selfUrlPathAndQueryPart = rawurldecode($selfUrlPathAndQueryPart);
				$requestedUrl .= $req->GetQuery(TRUE, TRUE);
			}
			if ($selfUrlPathAndQueryPart !== $requestedUrl) 
				$redirectToCanonicalUrl = TRUE;
		}
		if ($redirectToCanonicalUrl) {
			$selfCanonicalUrl = $this->Url($this->selfRouteName, $this->requestedParams);
			$this->redirect($selfCanonicalUrl, \MvcCore\IResponse::MOVED_PERMANENTLY);
			return FALSE;
		}
		return TRUE;
	}

	/**
	 * Here you can redefine target controller and action and it doesn't matter,
	 * what has been routed before. This method is only possible to use and it 
	 * make sence to use it only in any application post route handler, after 
	 * `Route()` method has been called and before controller is created by 
	 * application and dispatched. This method is very advanced. you have to 
	 * know what you are doing. There is no missing template or controller or 
	 * action checking!
	 * @param string $controllerNamePc Pascal case clasic controller name definition.
	 * @param string $actionNamePc Pascal case action name without `Action` suffix.
	 * @param bool $changeSelfRoute \FALSE` by default to change self route to generate self urls.
	 * @return bool
	 */
	public function RedefineRoutedTarget ($controllerNamePc = NULL, $actionNamePc = NULL, $changeSelfRoute = FALSE) {
		$toolClass = self::$toolClass;
		$ctrlNameDc = NULL;
		$actionNameDc = NULL;
		$currentRoute = & $this->currentRoute;
		$currentRouteMatched = $currentRoute instanceof \MvcCore\IRoute;
		$matchedParams = $currentRouteMatched ? $currentRoute->GetMatchedParams() : [];
		if ($controllerNamePc !== NULL) {
			$ctrlNameDc = str_replace(['\\', '_'], '/', $toolClass::GetDashedFromPascalCase($controllerNamePc));
			$matchedParams['controller'] = $ctrlNameDc;
			$this->request->SetControllerName($ctrlNameDc)->SetParam('controller', $ctrlNameDc);
		}
		if ($actionNamePc !== NULL) {
			$actionNameDc = $toolClass::GetDashedFromPascalCase($actionNamePc);
			$matchedParams['action'] = $actionNameDc;
			$this->request->SetActionName($actionNameDc)->SetParam('action', $ctrlNameDc);
			if (isset($this->requestedParams['action'])) $this->requestedParams['action'] = $actionNameDc;
		}
		if ($currentRouteMatched) {
			$currentRoute->SetMatchedParams($matchedParams);
			if (strpos($currentRoute->GetName(), ':') !== FALSE && $controllerNamePc !== NULL && $actionNamePc !== NULL) {
				$currentRoute->SetName($controllerNamePc . ':' . $actionNamePc);
			}
		}
		if ($currentRouteMatched && $changeSelfRoute) {
			$this->selfRouteName = $this->anyRoutesConfigured
				? $currentRoute->GetName()
				: $currentRoute->GetControllerAction();
			if ($controllerNamePc !== NULL) 
				if (isset($this->requestedParams['controller'])) 
					$this->requestedParams['controller'] = $ctrlNameDc;
			if ($actionNamePc !== NULL)
				if (isset($this->requestedParams['action'])) 
					$this->requestedParams['action'] = $actionNameDc;
		}
		return TRUE;
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
	public function Url ($controllerActionOrRouteName = 'Index:Index', array $params = []) {
		$result = '';
		$ctrlActionOrRouteNameKey = $this->urlGetCompletedCtrlActionKey(
			$controllerActionOrRouteName
		);
		if ($this->anyRoutesConfigure) {
			if (isset($this->urlRoutes[$ctrlActionOrRouteNameKey])) {
				$result = $this->UrlByRoute(
					$this->urlRoutes[$ctrlActionOrRouteNameKey], 
					$params, $controllerActionOrRouteName
				);
			} else {
				// TODO: tady je místo, kde bych se měl zkusit zeptat do databáze, 
				// zda tam něco je nebo ne, to, aby se to ptalo jak zplašený, to si
				// asi musim pořešit tak, že nevim, budu si asi taky v routeru ukládat, 
				// na co už se to ptalo?
				$result = $this->UrlByQueryString(
					$ctrlActionOrRouteNameKey, 
					$params, $controllerActionOrRouteName
				);
			}
		} else {
			$result = $this->UrlByQueryString(
				$ctrlActionOrRouteNameKey, 
				$params, $controllerActionOrRouteName
			);
		}
		return $result;
	}

	protected function urlGetCompletedCtrlActionKey ($controllerAction) {
		$result = $controllerAction;
		if (strpos($controllerAction, ':') !== FALSE) {
			list($ctrlPc, $actionPc) = explode(':', $controllerAction);
			if (!$ctrlPc) {
				$toolClass = self::$toolClass;
				$ctrlPc = $toolClass::GetPascalCaseFromDashed($this->request->GetControllerName());
			}
			if (!$actionPc) {
				$toolClass = self::$toolClass;
				$actionPc = $toolClass::GetPascalCaseFromDashed($this->request->GetActionName());
			}
			$result = "$ctrlPc:$actionPc";
		} else if ($controllerAction == 'self') {
			$result = $this->selfRouteName;
		}
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
			$this->AddRoute($defaultRoute, NULL, TRUE, FALSE);
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
	 * Complete optionally absolute, non-localized url with all params in query string.
	 * Example: `"/application/base-bath/index.php?controller=ctrlName&amp;action=actionName&amp;name=cool-product-name&amp;color=blue"`
	 * @param string $controllerActionOrRouteName
	 * @param array  $params
	 * @param string $givenRouteName
	 * @return string
	 */
	public function UrlByQueryString ($controllerActionOrRouteName = 'Index:Index', array & $params = [], $givenRouteName = NULL) {
		if ($givenRouteName == 'self') 
			$params = array_merge($this->requestedParams ?: [], $params);
		$toolClass = self::$toolClass;
		list($ctrlPc, $actionPc) = explode(':', $controllerActionOrRouteName);
		if (isset($params['controller'])) {
			$ctrlPc = $params['controller'];
			unset($params['controller']);
		}
		if (isset($params['action'])) {
			$actionPc = $params['action'];
			unset($params['action']);
		}
		$amp = $this->getQueryStringParamsSepatator();
		list($dfltCtrlPc, $dftlActionPc) = $this->application->GetDefaultControllerAndActionNames();
		$absolute = $this->urlGetAbsoluteParam($params);
		$result = '';
		$ctrlIsNotDefault = $ctrlPc !== $dfltCtrlPc;
		$actionIsNotDefault = $actionPc !== $dftlActionPc;
		$sep = '?';
		if ($params || $ctrlIsNotDefault || $actionIsNotDefault) {
			$result .= $this->request->GetScriptName();
		}
		if ($ctrlIsNotDefault) {
			$result .= $sep . 'controller=' . $toolClass::GetDashedFromPascalCase($ctrlPc);
			$sep = $amp;
		}
		if ($actionIsNotDefault) {
			$result .= $sep . 'action=' . $toolClass::GetDashedFromPascalCase($actionPc);
			$sep = $amp;
		}
		if ($params) {
			// `http_build_query()` automaticly converts all XSS chars to entities (`< > & " ' &`):
			$result .= $sep . str_replace('%2F', '/', http_build_query($params, '', $amp, PHP_QUERY_RFC3986));
		}
		if ($result == '') $result = '/';
		$result = $this->request->GetBasePath() . $result;
		if ($absolute) 
			$result = $this->request->GetDomainUrl() . $result;
		return $result;
	}

	/**
	 * Complete optionally absolute, non-localized url by route instance reverse info.
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
	 * @param \MvcCore\Route $route
	 * @param array $params
	 * @param string $urlParamRouteName
	 * @return string
	 */
	public function UrlByRoute (\MvcCore\IRoute & $route, array & $params = [], $urlParamRouteName = NULL) {
		if ($urlParamRouteName == 'self') 
			$params = array_merge($this->requestedParams ?: [], $params);
		$defaultParams = $this->GetDefaultParams() ?: [];
		return implode('', $route->Url(
			$this->request, $params, $defaultParams, $this->getQueryStringParamsSepatator()
		));
	}

	/**
	 * Get `TRUE` if given `array $params` contains `boolean` record under 
	 * `"absolute"` array key and if the record is `TRUE`. Unset the absolute 
	 * flag from `$params` in any case.
	 * @param array $params 
	 * @return boolean
	 */
	protected function urlGetAbsoluteParam (array & $params = []) {
		$absolute = FALSE;
		$absoluteParamName = static::URL_PARAM_ABSOLUTE;
		if ($params && isset($params[$absoluteParamName])) {
			$absolute = (bool) $params[$absoluteParamName];
			unset($params[$absoluteParamName]);
		}
		return $absolute;
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
			return $routeCfgOrRoute->SetRouter($this);
		$routeClass = self::$routeClass;
		return $routeClass::CreateInstance($routeCfgOrRoute)->SetRouter($this);
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
		$this->requestedParams = array_merge([], $this->request->GetParams(FALSE));
		$this->defaultParams = array_merge([], $this->requestedParams);
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
		$requestMethod = $request->GetMethod();
		$routes = & $this->routeByRRGetRoutesToMatch();
		$allMatchedParams = [];
		foreach ($routes as & $route) {
			/** @var $route \MvcCore\Route */
			$routeMethod = $route->GetMethod();
			if ($routeMethod !== NULL && $routeMethod !== $requestMethod) continue;
			if ($allMatchedParams = $route->Matches($request)) {
				$this->currentRoute = clone $route;
				$this->currentRoute->SetMatchedParams($allMatchedParams);
				$requestParams = $this->routeByRRSetRequestedAndDefaultParams(
					$allMatchedParams
				);
				$break = $this->routeByRRSetRequestParams($allMatchedParams, $requestParams);
				if ($break) break;
			}
		}
		if ($this->currentRoute !== NULL) 
			$this->routeByRRSetUpRequestByCurrentRoute(
				$allMatchedParams['controller'], $allMatchedParams['action']
			);
	}

	protected function & routeByRRGetRoutesToMatch () {
		$requestedPath = ltrim($this->request->GetPath(), '/');
		$nextSlashPos = mb_strpos($requestedPath, '/');
		if ($nextSlashPos === FALSE) $nextSlashPos = mb_strlen($requestedPath);
		$firstPathWord = mb_substr($requestedPath, 0, $nextSlashPos);
		if (array_key_exists($firstPathWord, $this->routesGroups)) {
			$routes = & $this->routesGroups[$firstPathWord];
		} else {
			$routes = & $this->routesGroups[''];
		}
		x($routes);
		reset($routes);
		return $routes;
	}

	protected function & routeByRRSetRequestedAndDefaultParams (& $allMatchedParams) {
		$request = & $this->request;
		$routeDefaults = & $this->currentRoute->GetDefaults();
		$rawQueryParams = $request->GetParams(FALSE);
		// redirect route with strictly defined match regexp and not defined reverse could have `NULL` method result:
		$routeReverseParams = $this->currentRoute->GetReverseParams() ?: [];
		// complete realy matched params from path
		$pathMatchedParams = array_merge([], $allMatchedParams);
		$controllerInReverse	= in_array('controller', $routeReverseParams, TRUE);
		$actionInReverse		= in_array('action', $routeReverseParams, TRUE);
		if (!$controllerInReverse)	unset($pathMatchedParams['controller']);
		if (!$actionInReverse)		unset($pathMatchedParams['action']);
		// complete params for request object
		$requestParams = array_merge(
			$routeDefaults, $pathMatchedParams, $rawQueryParams
		);
		// complete default params - default params to build url with user 
		// defined records possibility from filtering functions
		$this->defaultParams = array_merge(
			$routeDefaults, $allMatchedParams, $rawQueryParams
		);
		// requested params - all realy requested params for self URL addresses
		// parsed from path and merged with query params
		$this->requestedParams = array_merge([], $allMatchedParams);
		if (!$controllerInReverse)	unset($this->requestedParams['controller']);
		if (!$actionInReverse)		unset($this->requestedParams['action']);
		$this->requestedParams = array_merge($this->requestedParams, $rawQueryParams);
		return $requestParams;
	}

	protected function routeByRRSetRequestParams (& $allMatchedParams, & $requestParams) {
		$request = & $this->request;
		// filter request params
		list($success, $requestParamsFiltered) = $this->currentRoute->Filter(
			$requestParams, $this->defaultParams, \MvcCore\IRoute::CONFIG_FILTER_IN
		);
		if ($success === FALSE) {
			$this->currentRoute = NULL;
			$allMatchedParams = [];
			return FALSE;
		}
		$requestParamsFiltered['controller'] = $allMatchedParams['controller'];
		$requestParamsFiltered['action'] = $allMatchedParams['action'];
		$request->SetParams($requestParamsFiltered);
		return TRUE;
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
	protected function routeByRRSetUpRequestByCurrentRoute ($requestCtrlName, $requestActionName) {
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
