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

trait Props {

	/**
	 * Current `\MvcCore\Router` singleton instance storage.
	 * @var \MvcCore\Router
	 */
	protected static $instance = NULL;

	/**
	 * Value from `\MvcCore\Application::GetInstance()->GetRouterClass();`.
	 * @var string|NULL
	 */
	protected static $routerClass = NULL;

	/**
	 * Value from `\MvcCore\Application::GetInstance()->GetRouteClass();`.
	 * @var string|NULL
	 */
	protected static $routeClass = NULL;

	/**
	 * Value from `\MvcCore\Application::GetInstance()->GetToolClass();`.
	 * @var string|NULL
	 */
	protected static $toolClass = NULL;

	/**
	 * Reference to `\MvcCore\Application::GetInstance();` 
	 * to not call this very time we need app instance.
	 * @var \MvcCore\Application|NULL
	 */
	protected $application = NULL;

	/**
	 * Internally used `\MvcCore\Request` request object reference for:
	 * - Routing process in `\MvcCore\Router::Route();` and it's protected sub-methods.
	 * - URL addresses completing in `\MvcCore\Router::Url()` and it's protected sub-methods.
	 * @var \MvcCore\Request|NULL
	 */
	protected $request = NULL;

	/**
	 * Global application route instances store to match request.
	 * Keys are route(s) names, values are `\MvcCore\Route` instances.
	 * @var \MvcCore\Route[]
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
	 * Global application route instances store to complete URL addresses.
	 * Keys are route(s) names and `Controller:Action` combinations,
	 * values are `\MvcCore\Route` instances.
	 * @var \MvcCore\Route[]
	 */
	protected $urlRoutes = [];

	/**
	 * Main router strategy boolean. This property is automatically set to 
	 * `TRUE`/`FALSE` in method `\MvcCore\Router::Route();`. By this property,
	 * there is chosen routing strategy, how to complete requested controller 
	 * and action. If value is set to `TRUE`, there is processed only routing by
	 * query string variables controller and action. If value is `FALSE`, there 
	 * is processed routing by rewrite routes. This property is possible to set 
	 * manually into any boolean value you want and it will not be automatically 
	 * detected anymore.
	 * @var bool|NULL
	 */
	protected $routeByQueryString = NULL;

	/**
	 * Matched route by `\MvcCore\Router::Match();` processing or NULL if no match.
	 * By this route, there is created and dispatched controller lifecycle by core.
	 * @var \MvcCore\Route|NULL
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
	 * `TRUE` if request has to be automatically dispatched as default
	 * `Index:Index` route, if there was no route matching current request
	 * and if request was not `/` (homepage) but `/something-more`.
	 * Default value: `FALSE`.
	 * @var bool
	 */
	protected $routeToDefaultIfNotMatch = FALSE;

	/**
	 * All request params - params parsed by route and query string params.
	 * Be careful, it could contain XSS chars. Use always `htmlspecialchars()`.
	 * Those params could contain additional user params from filter function.
	 * @var array|NULL
	 */
	protected $defaultParams = [];

	/**
	 * All request params - params parsed by route and query string params.
	 * Be careful, it could contain XSS chars. Use always `htmlspecialchars()`.
	 * @var array|NULL
	 */
	protected $requestedParams = [];

	/**
	 * Trailing slash behaviour - integer state about what to do with trailing
	 * slash in all requested URL except homepage. Possible states are:
	 * - `-1` - `\MvcCore\IRouter::TRAILING_SLASH_REMOVE`)
	 *          Always remove trailing slash from requested URL if there
	 *          is any and redirect to it, except homepage.
	 * -  `0` - `\MvcCore\IRouter::TRAILING_SLASH_BENEVOLENT`)
	 *          Be absolutely benevolent for trailing slash in requested url.
	 * -  `1` - `\MvcCore\IRouter::TRAILING_SLASH_ALWAYS`)
	 *          Always keep trailing slash in requested URL or always add trailing
	 *          slash into URL and redirect to it.
	 * Default value is `-1` - `\MvcCore\IRouter::TRAILING_SLASH_REMOVE`
	 * @var int
	 */
	protected $trailingSlashBehaviour = 0;

	/**
	 * This property allows (by `TRUE` value by default) an auto canonical URL 
	 * redirection. It allows to redirect to canonical URL, if request is not 
	 * an internal and also if request is not realized by GET method. Then router 
	 * try to complete canonical (shorter) URL by detected strategy and if 
	 * canonical URL is different, router redirects to it.
	 * @var bool
	 */
	protected $autoCanonizeRequests = TRUE;

	/**
	 * Query string params separator, always initialized by configured response type.
	 * If response has no `Content-Type` header yet, query string separator is automatically
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
	 * Custom pre-route matching handler. This handler will be executed every 
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
	 * $router->preRouteMatchingHandler = 
	 *     function (\MvcCore\IRouter $router, \MvcCore\IRequest $request, $firstPathWord) {
	 *         // load any routes from database here
	 *         $routes = $db->loadRoutingRoutesGroup($firstPathWord);
	 *         // add loaded routes into router
	 *         $router->AddRoutes($routes, $firstPathWord);
	 *     };
	 * ````
	 * @var callable|NULL
	 */
	protected $preRouteMatchingHandler = NULL;
	
	/**
	 * Custom handler executed before building URL by rewrite routes. This 
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
	 * $router->preRouteUrlBuildingHandler =
	 *     function (\MvcCore\IRouter $router, $controllerActionOrRouteName, array $params = []) {
	 *         // load any routes from database here
	 *         $routes = $db->loadUrlRoutesGroup($controllerActionOrRouteName);
	 *         // return routes in array with keys to be route name for each route
	 *         return $routes;
	 *     };
	 * ````
	 * @var callable|NULL
	 */
	protected $preRouteUrlBuildingHandler = NULL;

	/**
	 * Keys by `Url()` method first argument, when 
	 * it was not possible to found any rewrite route
	 * to build url.
	 * @var array
	 */
	protected $noUrlRoutes = [];

	/**
	 * This boolean property is only cached result from request object method
	 * `\MvcCore\Request::IsInternalRequest();`, completed always in the 
	 * beginning in router method `\MvcCore\Router::Route();`. It tells router if
	 * request targets any internal controller action or not, for example packed
	 * JS/CSS or image in single file mode.
	 * @var bool
	 */
	protected $internalRequest = FALSE;
}
