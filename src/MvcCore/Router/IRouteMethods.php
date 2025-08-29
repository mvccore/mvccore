<?php

/**
 * MvcCore
 *
 * This source file is subject to the BSD 3 License
 * For the full copyright and license information, please view
 * the LICENSE.md file that are distributed with this source code.
 *
 * @copyright	Copyright (c) 2016 Tom Flidr (https://github.com/mvccore)
 * @license		https://mvccore.github.io/docs/mvccore/5.0.0/LICENSE.md
 */

namespace MvcCore\Router;

interface IRouteMethods {
	
	/**
	 * Clear all possible previously configured routes
	 * and set new given request routes again.
	 * If there is no name configured in route array configuration,
	 * set route name by given `$routes` array key, if key is not numeric.
	 *
	 * Routes could be defined in various forms:
	 * Example:
	 * ````
	 *   \MvcCore\Router::GetInstance()->SetRoutes([
	 *       "Products:List" => "/products-list/<name>/<color>",
	 *   ]);
	 * ````
	 * or:
	 * ````
	 *   \MvcCore\Router::GetInstance()->SetRoutes([
	 *       'products_list'        => [
	 *           "pattern"          => "/products-list/<name>/<color>",
	 *           "controllerAction" => "Products:List",
	 *           "defaults"         => ["name" => "default-name", "color" => "red"],
	 *           "constraints"      => ["name" => "[^/]*",        "color" => "[a-z]*"]
	 *       ]
	 *   ]);
	 * ````
	 * or:
	 * ````
	 *   \MvcCore\Router::GetInstance()->SetRoutes([
	 *       new Route(
	 *           "/products-list/<name>/<color>",
	 *           "Products:List",
	 *           ["name" => "default-name", "color" => "red"],
	 *           ["name" => "[^/]*",        "color" => "[a-z]*"]
	 *       )
	 *   ]);
	 * ````
	 * or:
	 * ````
	 *   \MvcCore\Router::GetInstance()->SetRoutes([
	 *       new Route(
	 *           "name"       => "products_list",
	 *           "pattern"    => "#^/products\-list/(?<name>[^/]*)/(?<color>[a-z]*)(?=/$|$)#",
	 *           "reverse"    => "/products-list/<name>/<color>",
	 *           "controller" => "Products",
	 *           "action"     => "List",
	 *           "defaults"   => ["name" => "default-name", "color" => "red"],
	 *       )
	 *   ]);
	 * ````
	 * @param  \MvcCore\Route[]|array $routes
	 *                                Keyed array with routes, keys are route names or route
	 *                                `Controller::Action` definitions.
	 * @param  ?string                $groupName 
	 *                                Group name is first matched/parsed word in requested path to 
	 *                                group routes by to try to match only routes you really need, 
	 *                                not all of them. If `NULL` by default, routes are inserted 
	 *                                into default group.
	 * @param  bool                   $autoInitialize 
	 *                                If `TRUE`, locale routes array is cleaned and then all 
	 *                                routes (or configuration arrays) are sent into method 
	 *                                `$router->AddRoutes();`, where are routes auto initialized 
	 *                                for missing route names or route controller or route action
	 *                                record, completed always from array keys. You can you `FALSE` 
	 *                                to set routes without any change or auto-initialization, it 
	 *                                could be useful to restore cached routes etc.
	 * @return \MvcCore\Router
	 */
	public function SetRoutes ($routes = [], $groupName = NULL, $autoInitialize = TRUE);

	/**
	 * Append or prepend new request routes.
	 * If there is no name configured in route array configuration,
	 * set route name by given `$routes` array key, if key is not numeric.
	 *
	 * Routes could be defined in various forms:
	 * Example:
	 * ````
	 *   \MvcCore\Router::GetInstance()->AddRoutes([
	 *       "Products:List" => "/products-list/<name>/<color>",
	 *   ]);
	 * ````
	 * or:
	 * ````
	 *   \MvcCore\Router::GetInstance()->AddRoutes([
	 *       'products_list'        => [
	 *           "pattern"          => "/products-list/<name>/<color>",
	 *           "controllerAction" => "Products:List",
	 *           "defaults"         => ["name" => "default-name", "color" => "red"],
	 *           "constraints"      => ["name" => "[^/]*",        "color" => "[a-z]*"]
	 *       ]
	 *   ]);
	 * ````
	 * or:
	 * ````
	 *   \MvcCore\Router::GetInstance()->AddRoutes([
	 *       new Route(
	 *           "/products-list/<name>/<color>",
	 *           "Products:List",
	 *           ["name" => "default-name", "color" => "red"],
	 *           ["name" => "[^/]*",        "color" => "[a-z]*"]
	 *       )
	 *   ]);
	 * ````
	 * or:
	 * ````
	 *   \MvcCore\Router::GetInstance()->AddRoutes([
	 *       new Route(
	 *           "name"       => "products_list",
	 *           "pattern"    => "#^/products\-list/(?<name>[^/]*)/(?<color>[a-z]*)(?=/$|$)#",
	 *           "reverse"    => "/products-list/<name>/<color>",
	 *           "controller" => "Products",
	 *           "action"     => "List",
	 *           "defaults"   => ["name" => "default-name", "color" => "red"],
	 *       )
	 *   ]);
	 * ````
	 * @param  array<int|string,\MvcCore\Route|array<string,mixed>|string> $routes
	 * Keyed array with routes, keys are route names or route
	 * `Controller::Action` definitions.
	 * @param  ?string                                                     $groupName
	 * Group name is first matched/parsed word in requested path to
	 * group routes by to try to match only routes you really need,
	 * not all of them. If `NULL` by default, routes are inserted
	 * into default group.
	 * @param  bool                                                        $prepend
	 * Optional, if `TRUE`, all given routes will be prepended from
	 * the last to the first in given list, not appended.
	 * @param  bool                                                        $throwExceptionForDuplication
	 * `TRUE` by default. Throw an exception, if route `name` or
	 * route `Controller:Action` has been defined already. If
	 * `FALSE` old route is over-written by new one.
	 * @return \MvcCore\Router
	 */
	public function AddRoutes (array $routes = [], $groupName = NULL, $prepend = FALSE, $throwExceptionForDuplication = TRUE);

	/**
	 * Append or prepend new request route.
	 * Set up route by route name into `\MvcCore\Router::$routes` array
	 * to route incoming request and also set up route by route name and
	 * by `Controller:Action` combination into `\MvcCore\Router::$urlRoutes`
	 * array to build URL addresses.
	 *
	 * Route could be defined in various forms:
	 * Example:
	 * ````
	 *   \MvcCore\Router::GetInstance()->AddRoute([
	 *       "name"    => "Products:List",
	 *       "pattern" => "/products-list/<name>/<color>",
	 *   ]);
	 * ````
	 * or:
	 * ````
	 *   \MvcCore\Router::GetInstance()->AddRoute([
	 *       "name"             => "products_list",
	 *       "pattern"          => "/products-list/<name>/<color>",
	 *       "controllerAction" => "Products:List",
	 *       "defaults"         => ["name" => "default-name", "color" => "red"],
	 *       "constraints"      => ["name" => "[^/]*",        "color" => "[a-z]*"]
	 *   ]);
	 * ````
	 * or:
	 * ````
	 *   \MvcCore\Router::GetInstance()->AddRoute(new Route(
	 *       "/products-list/<name>/<color>",
	 *       "Products:List",
	 *       ["name" => "default-name", "color" => "red"],
	 *       ["name" => "[^/]*",        "color" => "[a-z]*"]
	 *   ));
	 * ````
	 * or:
	 * ````
	 *   \MvcCore\Router::GetInstance()->AddRoute(new Route(
	 *       "name"       => "products_list",
	 *       "pattern"    => "#^/products\-list/(?<name>[^/]*)/(?<color>[a-z]*)(?=/$|$)#",
	 *       "reverse"    => "/products-list/<name>/<color>",
	 *       "controller" => "Products",
	 *       "action"     => "List",
	 *       "defaults"   => ["name" => "default-name", "color" => "red"],
	 *   ));
	 * ````
	 * @param  \MvcCore\Route|array<string,mixed> $routeCfgOrRoute
	 * Route instance or route config array.
	 * @param  ?string                            $groupName
	 * Group name is first matched/parsed word in requested path to
	 * group routes by to try to match only routes you really need,
	 * not all of them. If `NULL` by default, routes are inserted
	 * into default group.
	 * @param  bool                               $prepend
	 * Optional, if `TRUE`, given route will be prepended,
	 * not appended.
	 * @param  bool                               $throwExceptionForDuplication
	 * `TRUE` by default. Throw an exception, if route `name` or
	 * route `Controller:Action` has been defined already. If
	 * `FALSE` old route is over-written by new one.
	 * @return \MvcCore\Router
	 */
	public function AddRoute ($routeCfgOrRoute, $groupName = NULL, $prepend = FALSE, $throwExceptionForDuplication = TRUE);

	/**
	 * Get `TRUE` if router has any route by given route name or `FALSE` if not.
	 * @param  string|\MvcCore\Route $routeOrRouteName
	 * @return bool
	 */
	public function HasRoute ($routeOrRouteName);

	/**
	 * Remove route from router by given name and return removed route instance.
	 * If router has no route by given name, `NULL` is returned.
	 * @param  string $routeName
	 * @return ?\MvcCore\Route
	 */
	public function RemoveRoute ($routeName);
	
	/**
	 * Get all configured route(s) as `\MvcCore\Route` instances.
	 * Keys in returned array are route names, values are route objects.
	 * @param  ?string     $groupName
	 *                     Group name is first matched/parsed word in requested path to
	 *                     group routes by to try to match only routes you really need,
	 *                     not all of them. If `NULL` by default, there are returned
	 *                     all routes from all groups.
	 * @return \MvcCore\Route[]
	 */
	public function GetRoutes ($groupName = NULL);

	/**
	 * Get configured `\MvcCore\Route` route instances by route name, 
	 * `NULL` if no route presented.
	 * @param  string $routeName
	 * @return ?\MvcCore\Route
	 */
	public function GetRoute ($routeName);
	
	/**
	 * Set matched route instance for given request object
	 * into `\MvcCore\Route::Route();` method. Currently matched
	 * route is always assigned internally in that method.
	 * @param  \MvcCore\Route $currentRoute
	 * @return \MvcCore\Router
	 */
	public function SetCurrentRoute (\MvcCore\IRoute $currentRoute);

	/**
	 * Get matched route instance reference for given request object
	 * into `\MvcCore\Route::Route($request);` method. Currently
	 * matched route is always assigned internally in that method.
	 * @return ?\MvcCore\Route
	 */
	public function GetCurrentRoute ();

}
