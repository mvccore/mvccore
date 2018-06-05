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

namespace MvcCore\Interfaces;

//include_once('IRequest.php');
//include_once('IRoute.php');

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
interface IRouter
{
	/**
	 * Default system route name, automaticly created for requests:
	 * - For requests with explicitly defined controler and action in query string.
	 * - For requests targeting homepage with controller and action `Index:Index`.
	 * - For requests targeting any not matched path by other routes with
	 *   configured router as `$router->SetRouteToDefaultIfNotMatch();` which
	 *   target default route with controller and action `Index:Index`.
	 */
	const DEFAULT_ROUTE_NAME = 'default';

	/**
	 * Default system route name, automaticly created for error requests,
	 * where was uncatched exception in controller or template, catched by application.
	 * This route is created with controller and action `Index:Error` by default.
	 */
	const DEFAULT_ROUTE_NAME_ERROR = 'error';

	/**
	 * Default system route name, automaticly created for not matched requests,
	 * where was not possible to found requested controller or template or anything else.
	 * This route is created with controller and action `Index:NotFound` by default.
	 */
	const DEFAULT_ROUTE_NAME_NOT_FOUND = 'not_found';

	/**
	 * Always keep trailing slash in requested url or
	 * always add trailing slash into url and redirect to it.
	 */
	const TRAILING_SLASH_ALWAYS = 1;

	/**
	 * Be absolutely benevolent for trailing slash in requested url.
	 */
	const TRAILING_SLASH_BENEVOLENT = 0;

	/**
	 * Always remove trailing slash from requested url if there is any and redirect to it, except homepage.
	 */
	const TRAILING_SLASH_REMOVE = -1;

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
	 * @param \MvcCore\Interfaces\IRoute[]|array $routes Keyed array with routes,
	 *													 keys are route names or route
	 *													 `Controller::Action` definitions.
	 * @return \MvcCore\Interfaces\IRouter
	 */
	public static function & GetInstance ($routes = []);

	/**
	 * Clear all possible previously configured routes
	 * and set new given request routes again.
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
	 * @param \MvcCore\Interfaces\IRoute[]|array $routes Keyed array with routes,
	 *													 keys are route names or route
	 *													 `Controller::Action` definitions.
	 * @return \MvcCore\Interfaces\IRouter
	 */
	public function & SetRoutes ($routes = []);

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
	 * @param \MvcCore\Interfaces\IRoute[]|array $routes Keyed array with routes,
	 *											 keys are route names or route
	 *											 `Controller::Action` definitions.
	 * @param bool $prepend Optional, if `TRUE`, all given routes will
	 *						be prepended from the last to the first in
	 *						given list, not appended.
	 * @param bool $throwExceptionForDuplication `TRUE` by default. Throw an exception,
	 *											 if route `name` or route `Controller:Action`
	 *											 has been defined already. If `FALSE` old route
	 *											 is overwriten by new one.
	 * @return \MvcCore\Interfaces\IRouter
	 */
	public function & AddRoutes (array $routes = [], $prepend = FALSE, $throwExceptionForDuplication = TRUE);

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
	 * @param \MvcCore\Interfaces\IRoute|array $route Route instance or
	 *												  route config array.
	 * @param bool $prepend
	 * @param bool $throwExceptionForDuplication `TRUE` by default. Throw an exception,
	 *											 if route `name` or route `Controller:Action`
	 *											 has been defined already. If `FALSE` old route
	 *											 is overwriten by new one.
	 * @return \MvcCore\Interfaces\IRouter
	 */
	public function & AddRoute ($route, $prepend = FALSE, $throwExceptionForDuplication = TRUE);

	/**
	 * Return `TRUE` if router has any route by given route name, `FALSE` otherwise.
	 * @param string|\MvcCore\Interfaces\IRoute $routeOrRouteName
	 * @return boolean
	 */
	public function HasRoute ($routeOrRouteName);

	/**
	 * Remove route from router by given name and return removed route instance.
	 * If router has no route by given name, `NULL` is returned.
	 * @param string $routeName
	 * @return \MvcCore\Interfaces\IRoute|NULL
	 */
	public function RemoveRoute ($routeName);

	/**
	 * Get configured `\MvcCore\Route` route instances by route name, `NULL` if no route presented.
	 * @return \MvcCore\Interfaces\IRoute|NULL
	 */
	public function & GetRoute ($routeName);

	/**
	 * Get all configured route(s) as `\MvcCore\Route` instances.
	 * Keys in returned array are route names, values are route objects.
	 * @return \MvcCore\Interfaces\IRoute[]
	 */
	public function & GetRoutes ();

	/**
	 * Get `\MvcCore\Request` object as reference, used internally for:
	 * - Routing process in `\MvcCore\Router::Route();` and it's protected submethods.
	 * - URL addresses completing in `\MvcCore\Router::Url()` and it's protected submethods.
	 * @return \MvcCore\Interfaces\IRequest
	 */
	public function & GetRequest ();

	/**
	 * Sets up `\MvcCore\Request` object as reference to use it internally for:
	 * - Routing process in `\MvcCore\Router::Route();` and it's protected submethods.
	 * - URL addresses completing in `\MvcCore\Router::Url()` and it's protected submethods.
	 * This is INTERNAL, not TEMPLATE method, internally called in
	 * `\MvcCore\Application::Run();` => `\MvcCore\Application::routeRequest();`.
	 * @param \MvcCore\Interfaces\IRequest $request
	 * @return \MvcCore\Interfaces\IRouter
	 */
	public function & SetRequest (\MvcCore\Interfaces\IRequest & $request);

	/**
	 * Set matched route instance for given request object
	 * into `\MvcCore\Route::Route($request);` method. Currently
	 * matched route is always assigned internally in that method.
	 * @param \MvcCore\Interfaces\IRoute $currentRoute
	 * @return \MvcCore\Interfaces\IRouter
	 */
	public function & SetCurrentRoute (\MvcCore\Interfaces\IRoute $currentRoute);

	/**
	 * Get matched route instance reference for given request object
	 * into `\MvcCore\Route::Route($request);` method. Currently
	 * matched route is always assigned internally in that method.
	 * @return \MvcCore\Interfaces\IRoute
	 */
	public function & GetCurrentRoute ();

	/**
	 * Get `TRUE` if request has to be automaticly dispatched as default
	 * `Index:Index` route, if there was no route matching current request.
	 * Default protected property value: `FALSE`.
	 * @param bool $enable
	 * @return \MvcCore\Interfaces\IRoute
	 */
	public function GetRouteToDefaultIfNotMatch ();

	/**
	 * Set `TRUE` if request has to be automaticly dispatched as default
	 * `Index:Index` route, if there was no route matching current request.
	 * Default protected property value: `FALSE`.
	 * @param bool $enable
	 * @return \MvcCore\Interfaces\IRoute
	 */
	public function & SetRouteToDefaultIfNotMatch ($enable = TRUE);



	/**
	 * Get trrailing slash behaviour - integer state about what to do with trailing
	 * slash in all requested url except homepage. Possible states are:
	 * - `-1` (`\MvcCore\Interfaces\IRouter::TRAILING_SLASH_REMOVE`)
	 *		Always remove trailing slash from requested url if there
	 *		is any and redirect to it, except homepage.
	 * -  `0` (`\MvcCore\Interfaces\IRouter::TRAILING_SLASH_BENEVOLENT`)
	 *		Be absolutely benevolent for trailing slash in requested url.
	 * -  `1` (`\MvcCore\Interfaces\IRouter::TRAILING_SLASH_ALWAYS`)
	 *		Always keep trailing slash in requested url or always add trailing
	 *		slash into url and redirect to it.
	 * Default value is `-1` - `\MvcCore\Interfaces\IRouter::TRAILING_SLASH_REMOVE`
	 * @return int
	 */
	public function GetTrailingSlashBehaviour ();

	/**
	 * Set trrailing slash behaviour - integer state about what to do with trailing
	 * slash in all requested url except homepage. Possible states are:
	 * - `-1` (`\MvcCore\Interfaces\IRouter::TRAILING_SLASH_REMOVE`)
	 *		Always remove trailing slash from requested url if there
	 *		is any and redirect to it, except homepage.
	 * -  `0` (`\MvcCore\Interfaces\IRouter::TRAILING_SLASH_BENEVOLENT`)
	 *		Be absolutely benevolent for trailing slash in requested url.
	 * -  `1` (`\MvcCore\Interfaces\IRouter::TRAILING_SLASH_ALWAYS`)
	 *		Always keep trailing slash in requested url or always add trailing
	 *		slash into url and redirect to it.
	 * Default value is `-1` - `\MvcCore\Interfaces\IRouter::TRAILING_SLASH_REMOVE`
	 * @param int $trailingSlashBehaviour `-1` (`\MvcCore\Interfaces\IRouter::TRAILING_SLASH_REMOVE`)
	 *										 Always remove trailing slash from requested url if there
	 *										 is any and redirect to it, except homepage.
	 *									 `0` (`\MvcCore\Interfaces\IRouter::TRAILING_SLASH_BENEVOLENT`)
	 *										 Be absolutely benevolent for trailing slash in requested url.
	 *									 `1` (`\MvcCore\Interfaces\IRouter::TRAILING_SLASH_ALWAYS`)
	 *										 Always keep trailing slash in requested url or always add trailing
	 *										 slash into url and redirect to it.
	 * @return \MvcCore\Interfaces\IRouter
	 */
	public function & SetTrailingSlashBehaviour ($trailingSlashBehaviour = -1);

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
	 * - Return completed `\MvcCore\Router::$currentRoute` or NULL.
	 *
	 * This method is always called from core routing by:
	 * - `\MvcCore\Application::Run();` => `\MvcCore\Application::routeRequest();`.
	 * @return \MvcCore\Interfaces\IRoute
	 */
	public function & Route ();

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
	public function Url ($controllerActionOrRouteName = 'Index:Index', $params = []);

	/**
	 * Complete non-absolute, non-localized url with all params in query string.
	 * Example: `"/application/base-bath/index.php?controller=ctrlName&amp;action=actionName&amp;name=cool-product-name&amp;color=blue"`
	 * @param string $controllerActionOrRouteName
	 * @param array  $params
	 * @return string
	 */
	public function UrlByQueryString ($controllerActionOrRouteName = 'Index:Index', & $params = []);

	/**
	 * Complete non-absolute, non-localized url by route instance reverse info.
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
	 * @param \MvcCore\Interfaces\IRoute &$route
	 * @param array $params
	 * @return string
	 */
	public function UrlByRoute (\MvcCore\Interfaces\IRoute & $route, & $params = []);

	/**
	 * Get all request params - params parsed by route and query string params.
	 * Be carefull, it could contain XSS chars. Use always `htmlspecialchars()`.
	 * @return array
	 */
	public function & GetRequestedUrlParams ();

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
	 *						 `\MvcCore\Interfaces\IRouter::DEFAULT_ROUTE_NAME`
	 *						 `\MvcCore\Interfaces\IRouter::DEFAULT_ROUTE_NAME_ERROR`
	 *						 `\MvcCore\Interfaces\IRouter::DEFAULT_ROUTE_NAME_NOT_FOUND`
	 * @param string $controllerPc Controller name in pascal case.
	 * @param string $actionPc Action name with pascal case without ending `Action` substring.
	 * @return \MvcCore\Interfaces\IRoute
	 */
	public function & SetOrCreateDefaultRouteAsCurrent ($routeName, $controllerPc, $actionPc);
}
