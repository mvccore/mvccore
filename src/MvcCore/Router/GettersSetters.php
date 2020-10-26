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

trait GettersSetters
{
	/**
	 * Get `\MvcCore\Request` object as reference, used internally for:
	 * - Routing process in `\MvcCore\Router::Route();` and it's protected 
	 *   sub-methods.
	 * - URL addresses completing in `\MvcCore\Router::Url()` and it's protected 
	 *   sub-methods.
	 * @return \MvcCore\Request|\MvcCore\IRequest
	 */
	public function GetRequest () {
		/** @var $this \MvcCore\Router */
		return $this->request;
	}

	/**
	 * Sets up `\MvcCore\Request` object as reference to use it internally for:
	 * - Routing process in `\MvcCore\Router::Route();` and it's protected 
	 *   sub-methods.
	 * - URL addresses completing in `\MvcCore\Router::Url()` and it's protected 
	 *   sub-methods.
	 * This is INTERNAL, not TEMPLATE method, internally called in
	 * `\MvcCore\Application::Run();` => `\MvcCore\Application::routeRequest();`.
	 * @param \MvcCore\Request|\MvcCore\IRequest $request
	 * @return \MvcCore\Router|\MvcCore\IRouter
	 */
	public function SetRequest (\MvcCore\IRequest $request) {
		/** @var $this \MvcCore\Router */
		$this->request = $request;
		return $this;
	}

	/**
	 * Set hardly routing strategy. If this method is configures with `TRUE` 
	 * value, it disables whole routing by rewrite routes and only query string 
	 * values with controller and action are used. If this method is configures 
	 * with `FALSE` value, there are used only rewrite routes routing and no 
	 * query string data. this method is highly advanced.
	 * @param bool|NULL $routeByQueryString 
	 * @return \MvcCore\Router|\MvcCore\IRouter
	 */
	public function SetRouteByQueryString ($routeByQueryString = TRUE) {
		/** @var $this \MvcCore\Router */
		$this->routeByQueryString = $routeByQueryString;
		return $this;
	}

	/**
	 * Get routing strategy. `TRUE` means that there was automatically or 
	 * manually chosen routing by query string values and `FALSE` means that
	 * there was chosen routing by rewrite routes.
	 * @return bool|NULL
	 */
	public function GetRouteByQueryString () {
		/** @var $this \MvcCore\Router */
		return $this->routeByQueryString;
	}

	/**
	 * Get `TRUE` if request has to be automatically dispatched as default
	 * `Index:Index` route, if there was no route matching current request
	 * and if request was not `/` (homepage) but `/something-more`.
	 * Default protected property value: `FALSE`.
	 * @return bool
	 */
	public function GetRouteToDefaultIfNotMatch () {
		/** @var $this \MvcCore\Router */
		return $this->routeToDefaultIfNotMatch;
	}

	/**
	 * Set `TRUE` if request has to be automatically dispatched as default
	 * `Index:Index` route, if there was no route matching current request
	 * and if request was not `/` (homepage) but `/something-more`.
	 * Default protected property value: `FALSE`.
	 * @param bool $enable
	 * @return \MvcCore\Router|\MvcCore\IRouter
	 */
	public function SetRouteToDefaultIfNotMatch ($enable = TRUE) {
		/** @var $this \MvcCore\Router */
		$this->routeToDefaultIfNotMatch = $enable;
		return $this;
	}

	/**
	 * Get default request params - default params to build URL with possibility
	 * to define custom records for filter functions.
	 * Be careful, it could contain XSS chars. Use always `htmlspecialchars()`.
	 * @return array
	 */
	public function & GetDefaultParams () {
		/** @var $this \MvcCore\Router */
		return $this->defaultParams;
	}

	/**
	 * Get all request params - params parsed by route and query string params.
	 * Be careful, it could contain XSS chars. Use always `htmlspecialchars()`.
	 * @return array
	 */
	public function & GetRequestedParams () {
		/** @var $this \MvcCore\Router */
		return $this->requestedParams;
	}

	/**
	 * Get trailing slash behaviour - integer state about what to do with 
	 * trailing slash in all requested URL except homepage. Possible states are:
	 * - `-1` (`\MvcCore\IRouter::TRAILING_SLASH_REMOVE`)
	 *		Always remove trailing slash from requested URL if there
	 *		is any and redirect to it, except homepage.
	 * -  `0` (`\MvcCore\IRouter::TRAILING_SLASH_BENEVOLENT`)
	 *		Be absolutely benevolent for trailing slash in requested url.
	 * -  `1` (`\MvcCore\IRouter::TRAILING_SLASH_ALWAYS`)
	 *		Always keep trailing slash in requested URL or always add trailing
	 *		slash into URL and redirect to it.
	 * Default value is `-1` - `\MvcCore\IRouter::TRAILING_SLASH_REMOVE`
	 * @return int
	 */
	public function GetTrailingSlashBehaviour () {
		/** @var $this \MvcCore\Router */
		return $this->trailingSlashBehaviour;
	}

	/**
	 * Set trailing slash behaviour - integer state about what to do with 
	 * trailing slash in all requested URL except homepage. Possible states are:
	 * - `-1` (`\MvcCore\IRouter::TRAILING_SLASH_REMOVE`)
	 *		Always remove trailing slash from requested URL if there
	 *		is any and redirect to it, except homepage.
	 * -  `0` (`\MvcCore\IRouter::TRAILING_SLASH_BENEVOLENT`)
	 *		Be absolutely benevolent for trailing slash in requested url.
	 * -  `1` (`\MvcCore\IRouter::TRAILING_SLASH_ALWAYS`)
	 *		Always keep trailing slash in requested URL or always add trailing
	 *		slash into URL and redirect to it.
	 * Default value is `-1` - `\MvcCore\IRouter::TRAILING_SLASH_REMOVE`
	 * @param int $trailingSlashBehaviour
	 * @return \MvcCore\Router|\MvcCore\IRouter
	 */
	public function SetTrailingSlashBehaviour ($trailingSlashBehaviour = -1) {
		/** @var $this \MvcCore\Router */
		$this->trailingSlashBehaviour = $trailingSlashBehaviour;
		return $this;
	}

	/**
	 * Get boolean info about if router does automatic check for canonical URL 
	 * request and if it process automatic redirect to canonical URL version if 
	 * detected or if it doesn't.
	 * @return bool
	 */
	public function GetAutoCanonizeRequests () {
		/** @var $this \MvcCore\Router */
		return $this->autoCanonizeRequests;
	}

	/**
	 * Set `TRUE` to process automatic check for canonical URL request to 
	 * process possible redirection if described request found after routing
	 * is processed. Default value is `TRUE` to do it. You can use `FALSE` 
	 * otherwise for example for development purposes when you develop for 
	 * example url filtering in and out.
	 * @param bool $autoCanonizeRequests 
	 * @return \MvcCore\Router|\MvcCore\IRouter
	 */
	public function SetAutoCanonizeRequests ($autoCanonizeRequests = TRUE) {
		/** @var $this \MvcCore\Router */
		$this->autoCanonizeRequests = $autoCanonizeRequests;
		return $this;
	}

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
	 *	`$router->SetPreRouteMatchingHandler(
	 *		function (\MvcCore\IRouter $router, \MvcCore\IRequest $request, $firstPathWord) {
	 *			// load any routes from database here
	 *			$routes = $db->loadRoutingRoutesGroup($firstPathWord);
	 *			// add loaded routes into router
	 *			$router->AddRoutes($routes, $firstPathWord);
	 *		}
	 *	);`
	 * @param callable $preRouteMatchingHandler 
	 * @return \MvcCore\Router|\MvcCore\IRouter
	 */
	public function SetPreRouteMatchingHandler (callable $preRouteMatchingHandler = NULL) {
		/** @var $this \MvcCore\Router */
		$this->preRouteMatchingHandler = $preRouteMatchingHandler;
		if ($preRouteMatchingHandler !== NULL) {
			$this->anyRoutesConfigured = TRUE;
		} else {
			$this->anyRoutesConfigured = count($this->routes) > 0;
		}
		return $this;
	}

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
	public function GetPreRouteMatchingHandler () {
		/** @var $this \MvcCore\Router */
		return $this->preRouteMatchingHandler;
	}

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
	 *	`$router->SetPreRouteUrlBuildingHandler(
	 *		function (\MvcCore\IRouter $router, $controllerActionOrRouteName, array $params = []) {
	 *			// load any routes from database here
	 *			$routes = $db->loadUrlRoutesGroup($controllerActionOrRouteName);
	 *			// return routes in array with keys to be route name for each route
	 *			return $routes;
	 *		}
	 *	);`
	 * @param callable $preRouteMatchingHandler 
	 * @return \MvcCore\Router|\MvcCore\IRouter
	 */
	public function SetPreRouteUrlBuildingHandler (callable $preRouteUrlBuildingHandler = NULL) {
		/** @var $this \MvcCore\Router */
		$this->preRouteUrlBuildingHandler = $preRouteUrlBuildingHandler;
		return $this;
	}

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
	public function GetPreRouteUrlBuildingHandler () {
		/** @var $this \MvcCore\Router */
		return $this->preRouteUrlBuildingHandler;
	}
}
