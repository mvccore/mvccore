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
	 * TODO: dopsat
	 * @param callable $preRouteMatchingHandler 
	 * @return \MvcCore\Router
	 */
	public function & SetPreRouteMatchingHandler (callable $preRouteMatchingHandler) {
		$this->preRouteMatchingHandler = $preRouteMatchingHandler;
		return $this;
	}

	/**
	 * TODO: dopsat
	 * @return callable|NULL
	 */
	public function GetPreRouteMatchingHandler () {
		return $this->preRouteMatchingHandler;
	}

	/**
	 * TODO: dopsat
	 * @param callable $preRouteMatchingHandler 
	 * @return \MvcCore\Router
	 */
	public function & SetPreRouteUrlBuildingHandler (callable $preRouteUrlBuildingHandler) {
		$this->preRouteUrlBuildingHandler = $preRouteUrlBuildingHandler;
		return $this;
	}

	/**
	 * TODO: dopsat
	 * @return callable|NULL
	 */
	public function GetPreRouteUrlBuildingHandler () {
		return $this->preRouteUrlBuildingHandler;
	}
}
