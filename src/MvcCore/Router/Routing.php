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
	 * - Return `TRUE` if routing has no redirection or `FALSE` for redirection.
	 *
	 * This method is always called from core routing by:
	 * - `\MvcCore\Application::Run();` => `\MvcCore\Application::routeRequest();`.
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
	 * Here you can redefine target controller and action and it doesn't matter,
	 * what has been routed before. This method is only possible to use and it 
	 * make sence to use it only in any application post route handler, after 
	 * `Route()` method has been called and before controller is created by 
	 * application and dispatched. This method is very advanced. you have to 
	 * know what you are doing. There is no missing template or controller or 
	 * action checking!
	 * @param string $controllerNamePc Pascal case clasic controller name definition.
	 * @param string $actionNamePc Pascal case action name without `Action` suffix.
	 * @param bool $changeSelfRoute `FALSE` by default to change self route to generate self urls.
	 * @return bool
	 */
	public function RedefineRoutedTarget ($controllerNamePc = NULL, $actionNamePc = NULL, $changeSelfRoute = FALSE) {
		$toolClass = self::$toolClass;
		$ctrlNameDc = NULL;
		$actionNameDc = NULL;
		$currentRoute = & $this->currentRoute;
		$currentRouteMatched = $currentRoute instanceof \MvcCore\IRoute;
		$matchedParams = $currentRouteMatched ? $currentRoute->GetMatchedParams() : [];
		$controllerNamePcNotNull = $controllerNamePc !== NULL;
		$actionNamePcNotNull = $actionNamePc !== NULL;
		if ($controllerNamePcNotNull) {
			$ctrlNameDc = str_replace(['\\', '_'], '/', $toolClass::GetDashedFromPascalCase($controllerNamePc));
			$matchedParams['controller'] = $ctrlNameDc;
			$this->request->SetControllerName($ctrlNameDc)->SetParam('controller', $ctrlNameDc);
			if (isset($this->requestedParams['controller'])) $this->requestedParams['controller'] = $ctrlNameDc;
			$currentRoute->SetController($controllerNamePc);
		}
		if ($actionNamePcNotNull) {
			$actionNameDc = $toolClass::GetDashedFromPascalCase($actionNamePc);
			$matchedParams['action'] = $actionNameDc;
			$this->request->SetActionName($actionNameDc)->SetParam('action', $ctrlNameDc);
			if (isset($this->requestedParams['action'])) $this->requestedParams['action'] = $actionNameDc;
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
				if (isset($this->requestedParams['controller'])) 
					$this->requestedParams['controller'] = $ctrlNameDc;
			if ($actionNamePcNotNull)
				if (isset($this->requestedParams['action'])) 
					$this->requestedParams['action'] = $actionNameDc;
		}
		return TRUE;
	}

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
	 *						 `\MvcCore\IRouter::DEFAULT_ROUTE_NAME`
	 *						 `\MvcCore\IRouter::DEFAULT_ROUTE_NAME_ERROR`
	 *						 `\MvcCore\IRouter::DEFAULT_ROUTE_NAME_NOT_FOUND`
	 * @param string $controllerPc Controller name in pascal case.
	 * @param string $actionPc Action name with pascal case without ending `Action` substring.
	 * @param bool $fallbackCall `FALSE` by default. If `TRUE`, this function is called from error rendering fallback, self route name is not changed.
	 * @return \MvcCore\Route|\MvcCore\IRoute
	 */
	public function & SetOrCreateDefaultRouteAsCurrent ($routeName, $controllerPc, $actionPc, $fallbackCall = FALSE) {
		$controllerPc = strtr($controllerPc, '/', '\\');
		$ctrlActionRouteName = $controllerPc.':'. $actionPc;
		$request = & $this->request;
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
			->SetControllerName($toolClass::GetDashedFromPascalCase($defaultRoute->GetController()))
			->SetActionName($toolClass::GetDashedFromPascalCase($defaultRoute->GetAction()));
		$this->currentRoute = $defaultRoute;
		if (!$fallbackCall) $this->selfRouteName = $routeName;
		return $defaultRoute;
	}

	/**
	 * TODO: neaktualni
	 * @return array
	 */
	protected function routeDetectStrategy () {
		$request = & $this->request;
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
	 * Complete current route in `\MvcCore\Router::$currentRoute`
	 * and it's params by query string data. If missing `controller`
	 * or if missing `action` param, use configured default controller and default action name.
	 * @param string $controllerName
	 * @param string $actionName
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
		// possiblity to add domain params by extended module router
		$this->defaultParams = array_merge([], $this->defaultParams, $this->request->GetParams(FALSE));
		$this->requestedParams = array_merge([], $this->defaultParams);
	}

	/**
	 * TODO: dopsat
	 * @return bool
	 */
	protected function routeProcessRouteRedirectionIfAny () {
		if ($this->currentRoute instanceof \MvcCore\IRoute) {
			$redirectRouteName = $this->currentRoute->GetRedirect();
			if ($redirectRouteName !== NULL) {
				$redirectUrl = $this->Url($redirectRouteName, $this->requestedParams);
				$this->redirect($redirectUrl, \MvcCore\IResponse::MOVED_PERMANENTLY);
				return FALSE;
			}
		}
		return TRUE;
	}

	/**
	 * After routing is done, check if there is any current route and if not,
	 * check if request is homepage or if router is configured to route
	 * request to default controller and action if no match and set up new 
	 * route as current route for default controller and action if necessary.
	 * @return \MvcCore\Router
	 */
	protected function routeSetUpDefaultForHomeIfNoMatch () {
		if ($this->currentRoute === NULL) {
			$request = & $this->request;
			$requestIsHome = (
				trim($request->GetPath(), '/') == '' || 
				$request->GetPath() == $request->GetScriptName()
			);
			if ($requestIsHome || $this->routeToDefaultIfNotMatch) {
				list($dfltCtrl, $dftlAction) = $this->application->GetDefaultControllerAndActionNames();
				$this->SetOrCreateDefaultRouteAsCurrent(
					static::DEFAULT_ROUTE_NAME, $dfltCtrl, $dftlAction
				);
				// set up requested params from query string if there are any 
				// (and path if there is path from previous fn)
				$requestParams = array_merge([], $this->request->GetParams(FALSE));
				unset($requestParams['controller'], $requestParams['action']);
				$this->requestedParams = & $requestParams;
			}
		}
		return $this;
	}

	/**
	 * After routing is done, check if there is any current route and set up
	 * property `$this->selfRouteName` with currently matched route name.
	 * @return \MvcCore\Router
	 */
	protected function routeSetUpSelfRouteNameIfAny () {
		if ($this->currentRoute instanceof \MvcCore\IRoute) 
			$this->selfRouteName = $this->anyRoutesConfigured
				? $this->currentRoute->GetName()
				: $this->currentRoute->GetControllerAction();
		return $this;
	}
}
