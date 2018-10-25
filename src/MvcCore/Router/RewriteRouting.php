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

trait RewriteRouting
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
	protected function rewriteRouting ($requestCtrlName, $requestActionName) {
		$request = & $this->request;
		$requestedPathFirstWord = $this->rewriteRoutingGetReqPathFirstWord();
		$this->rewriteRoutingProcessPreHandler($requestedPathFirstWord);
		$routes = & $this->rewriteRoutingGetRoutesToMatch($requestedPathFirstWord);
		$requestMethod = $request->GetMethod();
		foreach ($routes as & $route) {
			/** @var $route \MvcCore\Route */
			if ($this->rewriteRoutingCheckRoute($route, [$requestMethod])) continue;
			$allMatchedParams = $route->Matches($request);
			if ($allMatchedParams !== NULL) {
				$this->currentRoute = clone $route;
				$this->currentRoute->SetMatchedParams($allMatchedParams);

				$this->rewriteRoutingSetRequestedAndDefaultParams(
					$allMatchedParams, $requestCtrlName, $requestActionName
				);
				if ($this->rewriteRoutingSetRequestParams($allMatchedParams)) continue;
				
				$this->rewriteRoutingSetUpCurrentRouteByRequest();
				break;
			}
		}
	}

	protected function rewriteRoutingCheckRoute (\MvcCore\IRoute & $route, array $additionalInfo) {
		list ($requestMethod,) = $additionalInfo;
		$routeMethod = $route->GetMethod();
		if ($routeMethod !== NULL && $routeMethod !== $requestMethod) return TRUE;
		return FALSE;
	}

	protected function rewriteRoutingGetReqPathFirstWord () {
		$requestedPath = ltrim($this->request->GetPath(), '/');
		$nextSlashPos = mb_strpos($requestedPath, '/');
		if ($nextSlashPos === FALSE) $nextSlashPos = mb_strlen($requestedPath);
		return mb_substr($requestedPath, 0, $nextSlashPos);
	}

	protected function rewriteRoutingProcessPreHandler ($firstPathWord) {
		if ($this->preRouteMatchingHandler === NULL) return;
		call_user_func($this->preRouteMatchingHandler, $this, $this->request, $firstPathWord);
	}

	protected function & rewriteRoutingGetRoutesToMatch ($firstPathWord) {
		if (isset($this->routesGroups[$firstPathWord])) {
			$routes = & $this->routesGroups[$firstPathWord];
		} else if (isset($this->routesGroups[''])) {
			$routes = & $this->routesGroups[''];
		} else {
			$routes = [];
		}
		reset($routes);
		return $routes;
	}

	protected function rewriteRoutingSetRequestedAndDefaultParams (array & $allMatchedParams, $requestCtrlName = NULL, $requestActionName = NULL) {
		// in array `$allMatchedParams` - there could be sometimes presented matched 
		// or route speficied values from configuration already, under keys `controller` and 
		// `action`, always with a value, never with `NULL`
		/** @var $request \MvcCore\Request */
		$request = & $this->request;
		$rawQueryParams = array_merge([], $request->GetParams(FALSE));
		// complete controller and action from any possible source
		list($ctrlDfltNamePc, $actionDfltNamePc) = $this->application->GetDefaultControllerAndActionNames();
		$toolClass = self::$toolClass;
		if ($requestCtrlName !== NULL) {
			$request->SetControllerName($requestCtrlName);
			$allMatchedParams['controller'] = $requestCtrlName;
			$rawQueryParams['controller'] = $requestCtrlName;
		} else if (isset($allMatchedParams['controller'])) {
			$request->SetControllerName($allMatchedParams['controller']);
		} else {
			$defaultCtrlNameDashed = $toolClass::GetDashedFromPascalCase($ctrlDfltNamePc);
			$request->SetControllerName($defaultCtrlNameDashed);
			$allMatchedParams['controller'] = $defaultCtrlNameDashed;
		}
		if ($requestActionName !== NULL) {
			$request->SetActionName($requestActionName);
			$allMatchedParams['action'] = $requestActionName;
			$rawQueryParams['action'] = $requestActionName;
		} else if (isset($allMatchedParams['action'])) {
			$request->SetActionName($allMatchedParams['action']);
		} else {
			$defaultActionNameDashed = $toolClass::GetDashedFromPascalCase($actionDfltNamePc);
			$request->SetActionName($defaultActionNameDashed);
			$allMatchedParams['action'] = $defaultActionNameDashed;
		}
		// complete params for request object - there have to be everytring including ctrl and action
		$this->defaultParams = array_merge(
			// default params are merged with previous default params to have 
			// possiblity to add domain params by extended module router
			$this->currentRoute->GetDefaults(), $this->defaultParams, 
			$allMatchedParams, $rawQueryParams
		);
		// redirect route with strictly defined match regexp and not defined reverse could have `NULL` method result:
		$routeReverseParams = $this->currentRoute->GetReverseParams() ?: [];
		// complete realy matched params from path - unset ctrl and action if ctrl and even action are not in pattern
		$pathOnlyMatchedParams = array_merge([], $allMatchedParams);
		$controllerInReverse	= in_array('controller', $routeReverseParams, TRUE);
		$actionInReverse		= in_array('action', $routeReverseParams, TRUE);
		if (!$controllerInReverse)	unset($pathOnlyMatchedParams['controller']);
		if (!$actionInReverse)		unset($pathOnlyMatchedParams['action']);
		// requested params - all realy requested params for self URL addresses
		// building base params array, parsed from path, merged with all query params 
		// and merged later with given params array into method `Url()`.
		// There cannot be `ctonroller` and `action` keys from route configuration,
		// only if ctrl and action is defined by query string, that's different
		$this->requestedParams = array_merge([], $pathOnlyMatchedParams, $rawQueryParams);
	}

	protected function rewriteRoutingSetRequestParams (array & $allMatchedParams) {
		$request = & $this->request;
		$defaultParamsBefore = array_merge([], $this->defaultParams);
		$requestParams = array_merge([], $this->defaultParams);
		// filter request params
		list($success, $requestParamsFiltered) = $this->currentRoute->Filter(
			$requestParams, $this->defaultParams, \MvcCore\IRoute::CONFIG_FILTER_IN
		);
		if ($success === FALSE) {
			$this->defaultParams = $defaultParamsBefore;
			$this->requestedParams = [];
			$allMatchedParams = NULL;
			$this->currentRoute = NULL;
			return TRUE;
		}
		$requestParamsFiltered = $requestParamsFiltered ?: $requestParams;
		$request->SetParams($requestParamsFiltered);
		if (isset($requestParamsFiltered['controller']))
			$request->SetControllerName($requestParamsFiltered['controller']);
		if (isset($requestParamsFiltered['action']))
			$request->SetActionName($requestParamsFiltered['action']);
		return FALSE;
	}

	/**
	 * TODO: neaktualni
	 * @return void
	 */
	protected function rewriteRoutingSetUpCurrentRouteByRequest () {
		$request = & $this->request;
		$toolClass = self::$toolClass;
		$this->currentRoute
			->SetController(str_replace(['/', '\\\\'], ['\\', '//'],
				$toolClass::GetPascalCaseFromDashed($request->GetControllerName())
			))
			->SetAction(
				$toolClass::GetPascalCaseFromDashed($request->GetActionName())
			);
	}
}
