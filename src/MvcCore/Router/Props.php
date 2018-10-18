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

namespace MvcCore\Router;

trait Props
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
	 * Keys by `Url()` method first argument, when 
	 * it was not possible to found any rewrite route
	 * to build url.
	 * @var array
	 */
	protected $noUrlRoutes = [];

	/**
	 * TODO: dopsat
	 * @var bool
	 */
	protected $internalRequest = FALSE;
}
