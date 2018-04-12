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
 *     `controller`, `action` and all other params.
 */
interface IRouter
{
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
	 * @param \MvcCore\Interfaces\IRoute[]|array $routes Keyed array with routes,
	 *													 keys are route names or route
	 *													 `Controller::Action` definitions.
	 * @return \MvcCore\Interfaces\IRouter
	 */
	public static function & GetInstance ($routes = array());

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
    public function & SetRoutes ($routes = array());

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
	 * @return \MvcCore\Interfaces\IRouter
	 */
    public function AddRoutes (array $routes = array(), $prepend = FALSE);

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
	 * @param \MvcCore\Interfaces\IRoute|array $route Route instance or
	 *												  route config array.
	 * @param bool $prepend
	 * @return \MvcCore\Interfaces\IRouter
	 */
	public function AddRoute ($route, $prepend = FALSE);

    /**
	 * Get all configured route(s) as `\MvcCore\Route` instances.
	 * Keys in returned array are route names, values are route objects.
	 * @return \MvcCore\Interfaces\IRoute[]
	 */
    public function GetRoutes ();

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
	public function Url ($controllerActionOrRouteName = 'Index:Index', $params = array());
}
