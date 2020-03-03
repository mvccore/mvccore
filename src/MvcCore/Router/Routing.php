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

trait Routing
{
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
	 * @throws \LogicException Route configuration property is missing.
	 * @throws \InvalidArgumentException Wrong route pattern format.
	 * @return bool
	 */
	public function Route () {
		$this->internalRequest = $this->request->IsInternalRequest();
		if (!$this->internalRequest) 
			if (!$this->redirectToProperTrailingSlashIfNecessary()) return FALSE;
		list($requestCtrlName, $requestActionName) = $this->routeDetectStrategy();
		if ($this->routeByQueryString) {
			$this->queryStringRouting($requestCtrlName, $requestActionName);
		} else {
			$this->rewriteRouting($requestCtrlName, $requestActionName);
		}
		if (!$this->routeProcessRouteRedirectionIfAny()) return FALSE;
		return $this->routeSetUpDefaultForHomeIfNoMatch()
					->routeSetUpSelfRouteNameIfAny()
					->canonicalRedirectIfAny();
	}
	
	/**
	 * You can redefine target controller and action and it doesn't matter,
	 * what has been routed before. This method is only possible to use and it 
	 * make sense to use it only in any application post route handler, after 
	 * `Route()` method has been called and before controller is created by 
	 * application and dispatched. This method is highly advanced. There is 
	 * no missing template or controller or action checking.
	 * @param string	$controllerNamePc	Pascal case classic controller name definition.
	 * @param string	$actionNamePc		Pascal case action name without `Action` suffix.
	 * @param bool		$changeSelfRoute	`FALSE` by default to change self route to generate self URLs.
	 * @return bool
	 */
	public function RedefineRoutedTarget ($controllerNamePc = NULL, $actionNamePc = NULL, $changeSelfRoute = FALSE) {
		$toolClass = self::$toolClass;
		$ctrlNameDc = NULL;
		$actionNameDc = NULL;
		$currentRoute = $this->currentRoute;
		$currentRouteMatched = $currentRoute instanceof \MvcCore\IRoute;
		$matchedParams = $currentRouteMatched ? $currentRoute->GetMatchedParams() : [];
		$controllerNamePcNotNull = $controllerNamePc !== NULL;
		$actionNamePcNotNull = $actionNamePc !== NULL;
		if ($controllerNamePcNotNull) {
			$ctrlNameDc = str_replace(['\\', '_'], '/', $toolClass::GetDashedFromPascalCase($controllerNamePc));
			$matchedParams[static::URL_PARAM_CONTROLLER] = $ctrlNameDc;
			$this->request->SetControllerName($ctrlNameDc)->SetParam(static::URL_PARAM_CONTROLLER, $ctrlNameDc);
			if (isset($this->requestedParams[static::URL_PARAM_CONTROLLER])) $this->requestedParams[static::URL_PARAM_CONTROLLER] = $ctrlNameDc;
			$currentRoute->SetController($controllerNamePc);
		}
		if ($actionNamePcNotNull) {
			$actionNameDc = $toolClass::GetDashedFromPascalCase($actionNamePc);
			$matchedParams[static::URL_PARAM_ACTION] = $actionNameDc;
			$this->request->SetActionName($actionNameDc)->SetParam(static::URL_PARAM_ACTION, $ctrlNameDc);
			if (isset($this->requestedParams[static::URL_PARAM_ACTION])) $this->requestedParams[static::URL_PARAM_ACTION] = $actionNameDc;
			$currentRoute->SetAction($actionNamePc);
		}
		if ($currentRouteMatched) {
			$currentRoute->SetMatchedParams($matchedParams);
			$currentRouteName = $currentRoute->GetName();
			
			if (strpos($currentRouteName, ':') !== FALSE && ($controllerNamePcNotNull || $actionNamePcNotNull)) {
				list($ctrlPc, $actionPc) = explode(':', $currentRouteName);
				$currentRoute->SetName(
					 ($controllerNamePcNotNull ? $controllerNamePc : $ctrlPc)
					. ':' . ($actionNamePcNotNull ? $actionNamePc : $actionPc)
				);
			}
		}
		if ($currentRouteMatched && $changeSelfRoute) {
			$this->selfRouteName = $this->anyRoutesConfigured
				? $currentRoute->GetName()
				: $currentRoute->GetControllerAction();
			if ($controllerNamePcNotNull) 
				if (isset($this->requestedParams[static::URL_PARAM_CONTROLLER])) 
					$this->requestedParams[static::URL_PARAM_CONTROLLER] = $ctrlNameDc;
			if ($actionNamePcNotNull)
				if (isset($this->requestedParams[static::URL_PARAM_ACTION])) 
					$this->requestedParams[static::URL_PARAM_ACTION] = $actionNameDc;
		}
		return TRUE;
	}

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
	 * @param string $routeName Always as `default`, `error` or `not_found`, by constants:
	 *						 `\MvcCore\IRouter::DEFAULT_ROUTE_NAME`
	 *						 `\MvcCore\IRouter::DEFAULT_ROUTE_NAME_ERROR`
	 *						 `\MvcCore\IRouter::DEFAULT_ROUTE_NAME_NOT_FOUND`
	 * @param string $controllerPc Controller name in pascal case.
	 * @param string $actionPc Action name with pascal case without ending `Action` substring.
	 * @param bool $fallbackCall `FALSE` by default. If `TRUE`, this function is called from error rendering fallback, self route name is not changed.
	 * @return \MvcCore\Route|\MvcCore\IRoute
	 */
	public function SetOrCreateDefaultRouteAsCurrent ($routeName, $controllerPc, $actionPc, $fallbackCall = FALSE) {
		$controllerPc = strtr($controllerPc, '/', '\\');
		$ctrlActionRouteName = $controllerPc.':'. $actionPc;
		$request = $this->request;
		if (isset($this->routes[$ctrlActionRouteName])) {
			$defaultRoute = $this->routes[$ctrlActionRouteName];
		} else if (isset($this->routes[$routeName])) {
			$defaultRoute = $this->routes[$routeName];
		} else {
			$routeClass = self::$routeClass;
			$pathParamName = static::URL_PARAM_PATH;
			$defaultRoute = $routeClass::CreateInstance()
				->SetMatch("#/(?<$pathParamName>.*)#")
				->SetReverse("/<$pathParamName>")
				->SetName($routeName)
				->SetController($controllerPc)
				->SetAction($actionPc)
				->SetDefaults([
					$pathParamName					=> NULL,
					static::URL_PARAM_CONTROLLER	=> NULL,
					static::URL_PARAM_ACTION		=> NULL,
				]);
			$anyRoutesConfigured = $this->anyRoutesConfigured;
			$this->AddRoute($defaultRoute, NULL, TRUE, FALSE);
			$this->anyRoutesConfigured = $anyRoutesConfigured;
			if (!$request->IsInternalRequest()) 
				$request->SetParam(static::URL_PARAM_PATH, ($request->HasParam(static::URL_PARAM_PATH)
					? $request->GetParam(static::URL_PARAM_PATH, '.*')
					: $request->GetPath())
				);
		}
		$toolClass = self::$toolClass;
		$request
			->SetControllerName(str_replace('\\', '/', 
				$toolClass::GetDashedFromPascalCase($defaultRoute->GetController())
			))
			->SetActionName(
				$toolClass::GetDashedFromPascalCase($defaultRoute->GetAction())
			);
		$this->currentRoute = $defaultRoute;
		if (!$fallbackCall) $this->selfRouteName = $routeName;
		return $defaultRoute;
	}

	/**
	 * Detect and set up route strategy to complete requested controller and action.
	 * Return strategy to complete those values by query string if:
	 * - If there is controller and also action, both defined in query string.
	 * - Or if requested path is `/` (or `/index.php`) and there is defined 
	 *   controller or action in query string.
	 * Else then choose strategy to complete controller and action by rewrite routes.
	 * Return array with possible query string controller name and action.
	 * @return array
	 */
	protected function routeDetectStrategy () {
		$request = $this->request;
		$requestCtrlName = $request->GetControllerName();
		$requestActionName = $request->GetActionName();
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

	/**
	 * If there is chosen query string routing strategy, create new route by 
	 * controller and action directly assigned from request and if controller or 
	 * action values are missing, assign there default controller and action values.
	 * Set up also default and request params by request object.
	 * 
	 * Request params are necessary to complete any `self` URL, to route request
	 * properly, to complete canonical URL and to process possible route redirection.
	 * 
	 * Default params are necessary to handle route filtering in and out and to
	 * complete URL by any other route name for case, when some required param 
	 * is not presented in second `$params` argument in Url() method (then the
	 * param is assigned from default params).
	 * @param string|NULL $requestCtrlName		Possible controller name value or `NULL` assigned directly 
	 *											from request object in `\MvcCore\router::routeDetectStrategy();`
	 * @param string|NULL $requestActionName	Possible action name value or `NULL` assigned directly 
	 *											from request object in `\MvcCore\router::routeDetectStrategy();`
	 * @return void
	 */
	protected function queryStringRouting ($requestCtrlName, $requestActionName) {
		$toolClass = self::$toolClass;
		list($ctrlDfltName, $actionDfltName) = $this->application->GetDefaultControllerAndActionNames();
		$this->SetOrCreateDefaultRouteAsCurrent(
			\MvcCore\IRouter::DEFAULT_ROUTE_NAME,
			$toolClass::GetPascalCaseFromDashed($requestCtrlName ?: $ctrlDfltName),
			$toolClass::GetPascalCaseFromDashed($requestActionName ?: $actionDfltName)
		);
		// default params are merged with previous default params to have 
		// possibility to add domain params by extended module router
		$this->defaultParams = array_merge([], $this->defaultParams, $this->request->GetParams(FALSE));
		$this->requestedParams = array_merge([], $this->defaultParams);
	}

	/**
	 * After route matching process is done and there is completed current route 
	 * object, check if current route object contains redirect configuration.
	 * If route contains redirection to another route, redirect request to target 
	 * URL, which is completed by standard `Url()` router method by target route
	 * name and by given request params. Redirect with code 301 - Moved permanently.
	 * If there was necessary to redirect, return `FALSE`, else return `TRUE`.
	 * @return bool
	 */
	protected function routeProcessRouteRedirectionIfAny () {
		if ($this->currentRoute instanceof \MvcCore\IRoute) {
			$redirectRouteName = $this->currentRoute->GetRedirect();
			if ($redirectRouteName !== NULL) {
				$redirectUrl = $this->Url($redirectRouteName, $this->requestedParams);
				$this->redirect(
					$redirectUrl, 
					\MvcCore\IResponse::MOVED_PERMANENTLY,
					'Redirection route '
				);
				return FALSE;
			}
		}
		return TRUE;
	}

	/**
	 * After routing is done, check if there is any current route.
	 * If there is no current route found by any strategy, there is possible to
	 * create default route object into current object automatically newly created
	 * by default configured values. It is necessary to check if request is 
	 * targeting homepage or if router is configured to route request to default 
	 * controller and action. If those two conditions are OK, create new route 
	 * with default controller and action and set this new route as current route 
	 * to process default controller and action even if there is no route for it.
	 * @return \MvcCore\Router
	 */
	protected function routeSetUpDefaultForHomeIfNoMatch () {
		/** @var $this \MvcCore\Router */
		if ($this->currentRoute === NULL) {
			$request = $this->request;
			if ($this->routeToDefaultIfNotMatch) {
				$requestIsHome = (
					trim($request->GetPath(), '/') == '' || 
					$request->GetPath() == $request->GetScriptName()
				);
				if ($requestIsHome) {
					list($dfltCtrl, $dftlAction) = $this->application->GetDefaultControllerAndActionNames();
					$this->SetOrCreateDefaultRouteAsCurrent(
						static::DEFAULT_ROUTE_NAME, $dfltCtrl, $dftlAction
					);
					// set up requested params from query string if there are any 
					// (and path if there is path from previous function)
					$requestParams = array_merge([], $this->request->GetParams(FALSE));
					unset($requestParams[static::URL_PARAM_CONTROLLER], $requestParams[static::URL_PARAM_ACTION]);
					$this->requestedParams = & $requestParams;
				}
			}
		}
		return $this;
	}

	/**
	 * After routing is done, check if there is any current route and set up
	 * property `$this->selfRouteName` about currently matched route name
	 * to complete any time later `self` url.
	 * @return \MvcCore\Router
	 */
	protected function routeSetUpSelfRouteNameIfAny () {
		/** @var $this \MvcCore\Router */
		if ($this->currentRoute instanceof \MvcCore\IRoute) 
			$this->selfRouteName = $this->anyRoutesConfigured
				? $this->currentRoute->GetName()
				: $this->currentRoute->GetControllerAction();
		return $this;
	}
}
