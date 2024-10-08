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

namespace MvcCore;

/**
 * Responsibility - singleton, routes instancing, request routing and URL building.
 * - Application router singleton instance managing.
 * - Global storage for all configured routes - instancing all route(s) in 
 *   application start configuration anywhere in `Bootstrap` class.
 * - Global storage for currently matched route.
 * - Application request routing - targeting request by matched route object 
 *   (in route method `Route();` by request `path` [or more]), ) into target 
 *   route controller and route action, always called from core in:
 *   `\MvcCore\Application::Run();` => `\MvcCore\Application::routeRequest();`.
 * - Application URL addresses completing:
 *   - By `mod_rewrite` form by configured route instances.
 *   - By `index.php?` + query string form, containing `controller`, `action` 
 *     and all other params.
 */
interface IRouter extends \MvcCore\Router\IConstants {

	/**
	 * Get singleton instance of `\MvcCore\Router` stored always here.
	 * Optionally set routes as first argument.
	 * Create proper router instance type at first time by
	 * configured class name in `\MvcCore\Application` singleton.
	 *
	 * Routes could be defined in various forms:
	 * Example:
	 * ````
	 *   \MvcCore\Router::GetInstance([
	 *       "Products:List" => "/products-list/<name>/<color>",
	 *   ]);
	 * ````
	 * or:
	 * ````
	 *   \MvcCore\Router::GetInstance([
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
	 *   \MvcCore\Router::GetInstance([
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
	 *   \MvcCore\Router::GetInstance([
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
	 * @param  bool                   $autoInitialize 
	 *                                If `TRUE`, locale routes array is cleaned and then all 
	 *                                routes (or configuration arrays) are sent into method 
	 *                                `$router->AddRoutes();`, where are routes auto initialized 
	 *                                for missing route names or route controller or route action
	 *                                record, completed always from array keys. You can you 
	 *                                `FALSE` to set routes without any change or 
	 *                                auto-initialization, it could be useful to restore cached 
	 *                                routes etc.
	 * @return \MvcCore\Router
	 */
	public static function GetInstance (array $routes = [], $autoInitialize = TRUE);

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
	 * @param  string|NULL            $groupName 
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
	 * @param  string|NULL                                                 $groupName
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
	 * @param  string|NULL                        $groupName
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
	 * @return \MvcCore\Route|NULL
	 */
	public function RemoveRoute ($routeName);

	/**
	 * Get configured `\MvcCore\Route` route instances by route name, 
	 * `NULL` if no route presented.
	 * @param  string $routeName
	 * @return \MvcCore\Route|NULL
	 */
	public function GetRoute ($routeName);

	/**
	 * Get all configured route(s) as `\MvcCore\Route` instances.
	 * Keys in returned array are route names, values are route objects.
	 * @param  string|NULL $groupName
	 *                     Group name is first matched/parsed word in requested path to
	 *                     group routes by to try to match only routes you really need,
	 *                     not all of them. If `NULL` by default, there are returned
	 *                     all routes from all groups.
	 * @return \MvcCore\Route[]
	 */
	public function GetRoutes ($groupName = NULL);

	/**
	 * Get `\MvcCore\Request` object as reference, used internally for:
	 * - Routing process in `\MvcCore\Router::Route();` and it's protected 
	 *   sub-methods.
	 * - URL addresses completing in `\MvcCore\Router::Url()` and it's protected 
	 *   sub-methods.
	 * @return \MvcCore\Request
	 */
	public function GetRequest ();

	/**
	 * Sets up `\MvcCore\Request` object as reference to use it internally for:
	 * - Routing process in `\MvcCore\Router::Route();` and it's protected 
	 *   sub-methods.
	 * - URL addresses completing in `\MvcCore\Router::Url()` and it's protected 
	 *   sub-methods.
	 * This is INTERNAL, not TEMPLATE method, internally called in
	 * `\MvcCore\Application::Run();` => `\MvcCore\Application::routeRequest();`.
	 * @param  \MvcCore\Request $request
	 * @return \MvcCore\Router
	 */
	public function SetRequest (\MvcCore\IRequest $request);

	/**
	 * Set hardly routing strategy. If this method is configures with `TRUE` 
	 * value, it disables whole routing by rewrite routes and only query string 
	 * values with controller and action are used. If this method is configures 
	 * with `FALSE` value, there are used only rewrite routes routing and no 
	 * query string data. this method is highly advanced.
	 * @param  bool|NULL $routeByQueryString 
	 * @return \MvcCore\Router
	 */
	public function SetRouteByQueryString ($routeByQueryString = TRUE);

	/**
	 * Get routing strategy. `TRUE` means that there was automatically or 
	 * manually chosen routing by query string values and `FALSE` means that
	 * there was chosen routing by rewrite routes.
	 * @return bool|NULL
	 */
	public function GetRouteByQueryString ();

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
	 * @return \MvcCore\Route|NULL
	 */
	public function GetCurrentRoute ();

	/**
	 * Get `TRUE` if request has to be automatically dispatched as default
	 * `Index:Index` route, if there was no route matching current request
	 * and if request was not `/` (homepage) but `/something-more`.
	 * Default protected property value: `FALSE`.
	 * @return bool
	 */
	public function GetRouteToDefaultIfNotMatch ();

	/**
	 * Set `TRUE` if request has to be automatically dispatched as default
	 * `Index:Index` route, if there was no route matching current request
	 * and if request was not `/` (homepage) but `/something-more`.
	 * Default protected property value: `FALSE`.
	 * @param  bool $enable
	 * @return \MvcCore\Router
	 */
	public function SetRouteToDefaultIfNotMatch ($enable = TRUE);

	/**
	 * Get default request params - default params to build URL with possibility
	 * to define custom records for filter functions.
	 * Be careful, it could contain XSS chars. Use always `htmlspecialchars()`.
	 * @return array<string,mixed>
	 */
	public function & GetDefaultParams ();

	/**
	 * Get all request params - params parsed by route and query string params.
	 * Be careful, it could contain XSS chars. Use always `htmlspecialchars()`.
	 * @return array<string,mixed>
	 */
	public function & GetRequestedParams ();

	/**
	 * Get trailing slash behaviour - integer state about what to do with 
	 * trailing slash in all requested URL except homepage. Possible states are:
	 * - `-1` - `\MvcCore\IRouter::TRAILING_SLASH_REMOVE`
	 *          Always remove trailing slash from requested URL if there
	 *          is any and redirect to it, except homepage.
	 * -  `0` - `\MvcCore\IRouter::TRAILING_SLASH_BENEVOLENT`
	 *          Be absolutely benevolent for trailing slash in requested url.
	 * -  `1` - `\MvcCore\IRouter::TRAILING_SLASH_ALWAYS`
	 *          Always keep trailing slash in requested URL or always add trailing
	 *          slash into URL and redirect to it.
	 * Default value is `-1` - `\MvcCore\IRouter::TRAILING_SLASH_REMOVE`
	 * @return int
	 */
	public function GetTrailingSlashBehaviour ();

	/**
	 * Set trailing slash behaviour - integer state about what to do with 
	 * trailing slash in all requested URL except homepage. Possible states are:
	 * - `-1` - `\MvcCore\IRouter::TRAILING_SLASH_REMOVE`
	 *          Always remove trailing slash from requested URL if there
	 *          is any and redirect to it, except homepage.
	 * -  `0` - `\MvcCore\IRouter::TRAILING_SLASH_BENEVOLENT`
	 *          Be absolutely benevolent for trailing slash in requested url.
	 * -  `1` - `\MvcCore\IRouter::TRAILING_SLASH_ALWAYS`
	 *          Always keep trailing slash in requested URL or always add trailing
	 *          slash into URL and redirect to it.
	 * Default value is `-1` - `\MvcCore\IRouter::TRAILING_SLASH_REMOVE`
	 * @param  int $trailingSlashBehaviour
	 * @return \MvcCore\Router
	 */
	public function SetTrailingSlashBehaviour ($trailingSlashBehaviour = -1);

	/**
	 * Get boolean info about if router does automatic check for canonical URL 
	 * request and if it process automatic redirect to canonical URL version if 
	 * detected or if it doesn't.
	 * @return bool
	 */
	public function GetAutoCanonizeRequests ();

	/**
	 * Set `TRUE` to process automatic check for canonical URL request to 
	 * process possible redirection if described request found after routing
	 * is processed. Default value is `TRUE` to do it. You can use `FALSE` 
	 * otherwise for example for development purposes when you develop for 
	 * example url filtering in and out.
	 * @param  bool $autoCanonizeRequests 
	 * @return \MvcCore\Router
	 */
	public function SetAutoCanonizeRequests ($autoCanonizeRequests = TRUE);

	/**
	 * Set up pre-route matching handler. This handler will be executed every 
	 * time after rewrite routes strategy is chosen, after first word from 
	 * requested path is parsed and before rewrite routes will be processed.
	 * The handler could be used to fill in routes you need by the first parsed 
	 * word from request path and by completed request object. Given handler 
	 * callable has to accept first argument to be router instance, second
	 * argument to be request object instance and third argument to be a string 
	 * with possibly parsed first word from requested path or an empty string.
	 * Handler could return value to be void or anything else, doesn't matter.
	 * Example:
	 * ````
	 *   $router->SetPreRouteMatchingHandler(
	 *       function (\MvcCore\Router $router, \MvcCore\Request $request, $firstPathWord) {
	 *           // load any routes from database here
	 *           $routes = $db->loadRoutingRoutesGroup($firstPathWord);
	 *           // add loaded routes into router
	 *           $router->AddRoutes($routes, $firstPathWord);
	 *       }
	 *   );
	 * ````
	 * @param  callable|NULL $preRouteMatchingHandler 
	 * @throws \Exception Pre-route matching handler is not callable.
	 * @return \MvcCore\Router
	 */
	public function SetPreRouteMatchingHandler ($preRouteMatchingHandler = NULL);

	/**
	 * Get pre-route matching handler. This handler is always executed every 
	 * time after rewrite routes strategy is chosen, after first word from 
	 * requested path is parsed and before rewrite routes will be processed.
	 * The handler is always used to fill in routes you need by the first parsed 
	 * word from request path and by completed request object. The handler 
	 * callable accepts first argument to be router instance, second
	 * argument to be request object instance and third argument to be a string 
	 * with possibly parsed first word from requested path or an empty string.
	 * Handler returns value to be void or anything else, doesn't matter.
	 * @return callable|NULL
	 */
	public function GetPreRouteMatchingHandler ();

	/**
	 * Set up handler executed before building URL by rewrite routes. This 
	 * handler will be executed every time there is necessary to build an URL
	 * when there are configured any rewrite routes and when there is no route 
	 * found to do it. Then the handler is executed to load any group of routes 
	 * from database into router instance if desired route is not already there. 
	 * If there is no route found in database, route name to build url is 
	 * marked to not request the database again automatically. Given handler 
	 * callable has to accept first argument to be router instance, second
	 * argument to be a string with first `Url()` method argument - it could be 
	 * controller and action combination or route name and third argument to be
	 * and array with params - the second argument from `Url()` method with
	 * arguments for final URL address. Handler has to return an array, empty
	 * or array with keys to be route names for each route to merge those new
	 * routes with already defined routes in router instance in protected 
	 * property `$router->urlRoutes`.
	 * Example:
	 * ````
	 *   $router->SetPreRouteUrlBuildingHandler(
	 *       function (\MvcCore\Router $router, $controllerActionOrRouteName, array $params = []) {
	 *           // load any routes from database here
	 *           $routes = $db->loadUrlRoutesGroup($controllerActionOrRouteName);
	 *           // return routes in array with keys to be route name for each route
	 *           return $routes;
	 *       }
	 *   );
	 * ````
	 * @param  callable|NULL $preRouteUrlBuildingHandler 
	 * @throws \Exception Pre-url building handler is not callable.
	 * @return \MvcCore\Router
	 */
	public function SetPreRouteUrlBuildingHandler ($preRouteUrlBuildingHandler = NULL);

	/**
	 * Get handler executed before building URL by rewrite routes. This 
	 * handler will be executed every time there is necessary to build an URL
	 * when there are configured any rewrite routes and when there is no route 
	 * found to do it. Then the handler is executed to load any group of routes 
	 * from database into router instance if desired route is not already there.
	 * If there is no route found in database, route name to build url is 
	 * marked to not request the database again automatically. Given handler 
	 * callable has to accept first argument to be router instance, second
	 * argument to be a string with first `Url()` method argument - it could be 
	 * controller and action combination or route name and third argument to be
	 * and array with params - the second argument from `Url()` method with
	 * arguments for final URL address. Handler has to return an array, empty
	 * or array with keys to be route names for each route to merge those new
	 * routes with already defined routes in router instance in protected 
	 * property `$router->urlRoutes`.
	 * @return callable|NULL
	 */
	public function GetPreRouteUrlBuildingHandler ();

	/**
	 * Route current app request by configured routes lists or by query string.
	 * 1. Check if request is targeting any internal action in internal ctrl.
	 * 2. If request is not internal, redirect to possible better URL form by
	 *    configured trailing slash strategy and return `FALSE` for redirection.
	 * 3. Choose route strategy by request path and existing query string 
	 *    controller and/or action values - strategy by query string or by 
	 *    rewrite routes.
	 * 4. Try to complete current route object by chosen strategy.
	 * 5. If any current route found and if route contains redirection, do it.
	 * 6. If there is no current route and request is targeting homepage, create
	 *    new empty route by default values if ctrl configuration allows it.
	 * 7. If there is any current route completed, complete self route name by 
	 *    it to generate `self` routes and canonical URL later.
	 * 8. If there is necessary, try to complete canonical URL and if canonical 
	 *    URL is shorter than requested URL, redirect user to shorter version.
	 * If there was necessary to redirect user in routing process, return 
	 * immediately `FALSE` and return from this method. Else continue to next 
	 * step and return `TRUE`. This method is always called from core routing by:
	 * `\MvcCore\Application::Run();` => `\MvcCore\Application::routeRequest();`.
	 * @throws \LogicException           Route configuration property is missing.
	 * @throws \InvalidArgumentException Wrong route pattern format.
	 * @return bool
	 */
	public function Route ();

	/**
	 * You can redefine target controller and action and it doesn't matter,
	 * what has been routed before. This method is only possible to use and it 
	 * make sense to use it only in any application post route handler, after 
	 * `Route()` method has been called and before controller is created by 
	 * application and dispatched. This method is highly advanced. There is 
	 * no missing template or controller or action checking.
	 * @param  string $controllerNamePc Pascal case classic controller name definition.
	 * @param  string $actionNamePc     Pascal case action name without `Action` suffix.
	 * @param  bool   $changeSelfRoute  `FALSE` by default to change self route to generate self URLs.
	 * @return bool
	 */
	public function RedefineRoutedTarget ($controllerNamePc = NULL, $actionNamePc = NULL, $changeSelfRoute = FALSE);

	/**
	 * Generates url:
	 * - By `"Controller:Action"` name and params array
	 *   (for routes configuration when routes array has keys with 
	 *   `"Controller:Action"` strings and routes has not controller name and 
	 *   action name defined inside).
	 * - By route name and params array
	 *   (route name is key in routes configuration array, should be any string,
	 *   routes must have information about controller name and action name 
	 *   inside).
	 * Result address (url string) should have two forms:
	 * - Nice rewritten URL by routes configuration (for apps with URL rewrite 
	 *   support [Apache `.htaccess` or IIS URL rewrite module] and when first 
	 *   param is key in routes configuration array).
	 * - For all other cases is URL form like: 
	 *   `"index.php?controller=ctrlName&amp;action=actionName"`
	 *   (when first param is not founded in routes configuration array).
	 * Method tries to find any route between routes by first argument and if
	 * there is no route but if there is any pre route URL building handler 
	 * defined, the handler is called to assign desired routes from database 
	 * or any other place and then there is processed route search between 
	 * routes again. If there is still no routes, result url is completed 
	 * in query string form.
	 * @param  string              $controllerActionOrRouteName
	 * Should be `"Controller:Action"` combination 
	 * or just any route name as custom specific string.
	 * @param  array<string,mixed> $params
	 * Optional, array with params, key is 
	 * param name, value is param value.
	 * @throws \InvalidArgumentException
	 * @return string
	 */
	public function Url ($controllerActionOrRouteName = 'Index:Index', array $params = []);
	
	/**
	 * Encode URL special chars to percent (%) sign followed by two hex digits:
	 * - encode ASCII chars with lower index than 33, including space (\x00-\x20)
	 * - encode ASCII special chars for HTML `" ' < > \` (\x22\x27\x3C\x3E\x5C)
	 * - encode ASCII chars not used in HTML and URL `$ ( ) * + ´ ; ^ ` { }` (\x24\x28\x29\x2A\x2B\x2C\x3B\x5E\x60\x7B\x7D)
	 * - keep ASCII special chars for URL `! # % & - . / : = ? @ [ ] _ ~` (\x21\x23\x25\x26\x2D\x2E\x2F\x3A\x3D\x3F\x40\x5B\x5D\x5F\x7E)
	 * - keep ASCII alphanumeric chars (\x30-\x39,\x41-\x5A)
	 * @param  string $url 
	 * @return string
	 */
	public function EncodeUrl ($url);

	/**
	 * Complete optionally absolute, non-localized URL with all params in query string.
	 * Example: `"/application/base-bath/index.php?controller=ctrlName&amp;action=actionName&amp;name=cool-product-name&amp;color=blue"`
	 * @param  string              $controllerActionOrRouteName
	 * @param  array<string,mixed> $params
	 * @param  string              $givenRouteName
	 * @return string
	 */
	public function UrlByQueryString ($controllerActionOrRouteName = 'Index:Index', array & $params = [], $givenRouteName = NULL);

	/**
	 * Complete optionally absolute, non-localized URL by route instance reverse 
	 * pattern and given `$params` array. If any param required by reverse 
	 * pattern is missing in params, there is used router default params
	 * completed in routing process.
	 * Example:
	 * ````
	 *   // Input `\MvcCore\Route::$reverse`:
	 *       "/products-list/<name>/<color>"
	 *   // Input `$params`:
	 *       [
	 *           "name"    => "cool-product-name",
	 *           "color"   => "red",
	 *           "variant" => ["L", "XL"],
	 *       ];
	 *   // Output:
	 *       "/application/base-bath/products-list/cool-product-name/blue?variant[]=L&amp;variant[]=XL"
	 * ````
	 * @param  \MvcCore\Route      $route
	 * @param  array<string,mixed> $params
	 * @param  string              $urlParamRouteName
	 * @return string
	 */
	public function UrlByRoute (\MvcCore\IRoute $route, array & $params = [], $urlParamRouteName = NULL);

	/**
	 * THIS METHOD IS MOSTLY USED INTERNALLY.
	 * 
	 * Try to find any existing route by `$routeName` argument
	 * or try to find any existing route by `$controllerPc:$actionPc` arguments
	 * combination and set this founded route instance as current route object.
	 *
	 * Also re-target, re-set request object controller and action values 
	 * (or also path) to this newly configured current route object.
	 *
	 * If there is no route by name or controller and action combination found,
	 * create new empty route by configured route class from application core
	 * and set up this new route by given `$routeName`, `$controllerPc`, `$actionPc`
	 * with route match pattern to match any request `#/(?<path>.*)#` and with 
	 * reverse pattern `/<path>` to create URL by single `path` param only. And 
	 * add this newly created route into routes (into default routes group) and 
	 * set this new route as current route object.
	 *
	 * This method is always called internally for following cases:
	 * - When router has no routes configured and request is necessary
	 *   to route by query string arguments only (controller and action).
	 * - When no route matched and when is necessary to create
	 *   default route object for homepage, handled by `Index:Index` by default.
	 * - When no route matched and when router is configured to route
	 *   requests to default route if no route matched by
	 *   `$router->SetRouteToDefaultIfNotMatch();`.
	 * - When is necessary to create not found route or error route
	 *   when there was not possible to route the request or when
	 *   there was any uncaught exception in controller or template
	 *   caught later by application.
	 * 
	 * @internal
	 * @param  string $routeName    Always as `default`, `error` or `not_found`, by constants:
	 *                              `\MvcCore\IRouter::DEFAULT_ROUTE_NAME`
	 *                              `\MvcCore\IRouter::DEFAULT_ROUTE_NAME_ERROR`
	 *                              `\MvcCore\IRouter::DEFAULT_ROUTE_NAME_NOT_FOUND`
	 * @param  string $controllerPc Controller name in pascal case.
	 * @param  string $actionPc     Action name with pascal case without ending `Action` substring.
	 * @param  bool $fallbackCall   `FALSE` by default. If `TRUE`, this function is called from error rendering fallback, self route name is not changed.
	 * @return \MvcCore\Route
	 */
	public function SetOrCreateDefaultRouteAsCurrent ($routeName, $controllerPc, $actionPc, $fallbackCall = FALSE);
}
