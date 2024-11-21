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

interface IRouting {
	
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
