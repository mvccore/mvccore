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

trait RoutingByRoutes
{
	/**
	 * Complete `\MvcCore\Router::$currentRoute` and request params by defined routes.
	 * Go throught all configured routes and try to find matching route.
	 * If there is catched any matching route - reset `\MvcCore\Request::$params`
	 * with default route params, with params itself and with params parsed from matching process.
	 * @param string $controllerName
	 * @param string $actionName
	 * @return void
	 */
	protected function routeByRewriteRoutes ($requestCtrlName, $requestActionName) {
		$request = & $this->request;
		$requestedPathFirstWord = $this->routeByRRGetRequestedPathFirstWord();
		$this->routeByRRProcessPrehandlerIfAny($requestedPathFirstWord);
		$routes = & $this->routeByRRGetRoutesToMatch($requestedPathFirstWord);
		$requestMethod = $request->GetMethod();
		$allMatchedParams = [];
		foreach ($routes as & $route) {
			/** @var $route \MvcCore\Route */
			$routeMethod = $route->GetMethod();
			if ($routeMethod !== NULL && $routeMethod !== $requestMethod) continue;
			if ($allMatchedParams = $route->Matches($request)) {
				$this->currentRoute = clone $route;
				$this->currentRoute->SetMatchedParams($allMatchedParams);
				$requestParams = $this->routeByRRSetRequestedAndDefaultParams(
					$allMatchedParams
				);
				$break = $this->routeByRRSetRequestParams($allMatchedParams, $requestParams);
				if ($break) break;
			}
		}
		if ($this->currentRoute !== NULL) 
			$this->routeByRRSetUpRequestByCurrentRoute(
				$allMatchedParams['controller'], $allMatchedParams['action']
			);
	}

	protected function routeByRRGetRequestedPathFirstWord () {
		$requestedPath = ltrim($this->request->GetPath(), '/');
		$nextSlashPos = mb_strpos($requestedPath, '/');
		if ($nextSlashPos === FALSE) $nextSlashPos = mb_strlen($requestedPath);
		return mb_substr($requestedPath, 0, $nextSlashPos);
	}

	protected function routeByRRProcessPrehandlerIfAny ($firstPathWord) {
		if ($this->preRouteMatchingHandler === NULL) return;
		call_user_func($this->preRouteMatchingHandler, $this, $this->request, $firstPathWord);
	}

	protected function & routeByRRGetRoutesToMatch ($firstPathWord) {
		if (array_key_exists($firstPathWord, $this->routesGroups)) {
			$routes = & $this->routesGroups[$firstPathWord];
		} else {
			$routes = & $this->routesGroups[''];
		}
		reset($routes);
		return $routes;
	}

	protected function & routeByRRSetRequestedAndDefaultParams (& $allMatchedParams) {
		$request = & $this->request;
		$routeDefaults = & $this->currentRoute->GetDefaults();
		$rawQueryParams = $request->GetParams(FALSE);
		// redirect route with strictly defined match regexp and not defined reverse could have `NULL` method result:
		$routeReverseParams = $this->currentRoute->GetReverseParams() ?: [];
		// complete realy matched params from path
		$pathMatchedParams = array_merge([], $allMatchedParams);
		$controllerInReverse	= in_array('controller', $routeReverseParams, TRUE);
		$actionInReverse		= in_array('action', $routeReverseParams, TRUE);
		if (!$controllerInReverse)	unset($pathMatchedParams['controller']);
		if (!$actionInReverse)		unset($pathMatchedParams['action']);
		// complete params for request object
		$requestParams = array_merge(
			$routeDefaults, $pathMatchedParams, $rawQueryParams
		);
		// complete default params - default params to build url with user 
		// defined records possibility from filtering functions
		$this->defaultParams = array_merge(
			$routeDefaults, $allMatchedParams, $rawQueryParams
		);
		// requested params - all realy requested params for self URL addresses
		// parsed from path and merged with query params
		$this->requestedParams = array_merge([], $allMatchedParams);
		if (!$controllerInReverse)	unset($this->requestedParams['controller']);
		if (!$actionInReverse)		unset($this->requestedParams['action']);
		$this->requestedParams = array_merge($this->requestedParams, $rawQueryParams);
		return $requestParams;
	}

	protected function routeByRRSetRequestParams (& $allMatchedParams, & $requestParams) {
		$request = & $this->request;
		// filter request params
		list($success, $requestParamsFiltered) = $this->currentRoute->Filter(
			$requestParams, $this->defaultParams, \MvcCore\IRoute::CONFIG_FILTER_IN
		);
		if ($success === FALSE) {
			$this->currentRoute = NULL;
			$allMatchedParams = [];
			return FALSE;
		}
		$requestParamsFiltered['controller'] = $allMatchedParams['controller'];
		$requestParamsFiltered['action'] = $allMatchedParams['action'];
		$request->SetParams($requestParamsFiltered);
		return TRUE;
	}

	/**
	 * Set up request object controller and action by current route (routing 
	 * result) routed by method `$this->routeByRewriteRoutes();`. If there is no
	 * controller name and only action name or if there is no action name and only 
	 * controller name, complete those missing record by default core values for 
	 * controller name and action name.
	 * @param string $controllerName
	 * @param string $actionName
	 * @return void
	 */
	protected function routeByRRSetUpRequestByCurrentRoute ($requestCtrlName, $requestActionName) {
		$route = $this->currentRoute;
		$request = & $this->request;
		$toolClass = self::$toolClass;
		$routeCtrl = $route->GetController();
		$routeAction = $route->GetAction();
		if (!$routeCtrl || !$routeAction) {
			list($ctrlDfltName, $actionDfltName) = $this->application->GetDefaultControllerAndActionNames();
			if (!$routeCtrl)
				$route->SetController(
					$requestCtrlName
						? $toolClass::GetPascalCaseFromDashed($requestCtrlName)
						: $ctrlDfltName
				);
			if (!$routeAction)
				$route->SetAction(
					$requestActionName
						? $toolClass::GetPascalCaseFromDashed($requestActionName)
						: $actionDfltName
					);
		}
		$request
			->SetControllerName($toolClass::GetDashedFromPascalCase($route->GetController()))
			->SetActionName($toolClass::GetDashedFromPascalCase($route->GetAction()));
	}
}
