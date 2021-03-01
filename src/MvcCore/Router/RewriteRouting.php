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

trait RewriteRouting {

	/**
	 * Try to parse first word from request path to get proper routes group.
	 * If there is no first word in request path, get default routes group. 
	 * 
	 * If there is any configured pre-routing handler, execute the handler to
	 * for example load only specific routes from database or anything else.
	 * 
	 * Go through all chosen routes and check if route is possible to use for 
	 * current request. Then try to match route by given request. If route doesn't 
	 * match the request, continue to another route and try to complete current
	 * route object. If route matches the request, set up default and request 
	 * params and try to process route filtering in. If it is successful, set 
	 * up current route object and end route matching process.
	 * @param  string|NULL $requestCtrlName
	 *                                   Possible controller name value or `NULL` assigned directly 
	 *                                   from request object in `\MvcCore\router::routeDetectStrategy();`
	 * @param  string|NULL $requestActionName
	 *                                   Possible action name value or `NULL` assigned directly 
	 *                                   from request object in `\MvcCore\router::routeDetectStrategy();`
	 * @throws \LogicException           Route configuration property is missing.
	 * @throws \InvalidArgumentException Wrong route pattern format.
	 * @return void
	 */
	protected function rewriteRouting ($requestCtrlName, $requestActionName) {
		/** @var $this \MvcCore\Router */
		$request = $this->request;
		$requestedPathFirstWord = $this->rewriteRoutingGetReqPathFirstWord();
		$this->rewriteRoutingProcessPreHandler($requestedPathFirstWord);
		$routes = $this->rewriteRoutingGetRoutesToMatch($requestedPathFirstWord);
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
	
	/**
	 * Parse first word from request path - first element between first two slashes.
	 * Return for example from `/eshop/detail/name` first word `eshop`.
	 * If there is no first word in request path, return an empty string.
	 * @return string
	 */
	protected function rewriteRoutingGetReqPathFirstWord () {
		/** @var $this \MvcCore\Router */
		$requestedPath = ltrim($this->request->GetPath(), '/');
		$nextSlashPos = mb_strpos($requestedPath, '/');
		if ($nextSlashPos === FALSE) $nextSlashPos = mb_strlen($requestedPath);
		return mb_substr($requestedPath, 0, $nextSlashPos);
	}
	
	/**
	 * Call any configured pre-route matching handler with first parsed word from
	 * requested path and with request object to load for example from database
	 * only routes you need to use for routing, not all of them.
	 * @param  string $firstPathWord 
	 * @return void
	 */
	protected function rewriteRoutingProcessPreHandler ($firstPathWord) {
		/** @var $this \MvcCore\Router */
		if ($this->preRouteMatchingHandler === NULL) return;
		call_user_func($this->preRouteMatchingHandler, $this, $this->request, $firstPathWord);
	}
	
	/**
	 * Get specific routes group by first parsed word from request path if any.
	 * If first path word is an empty string, there is returned routes with no group
	 * word defined. If still there are no such routes in default group, returned 
	 * is an empty array.
	 * @param  string $firstPathWord 
	 * @return array|\MvcCore\Route[]
	 */
	protected function rewriteRoutingGetRoutesToMatch ($firstPathWord) {
		/** @var $this \MvcCore\Router */
		if (isset($this->routesGroups[$firstPathWord])) {
			$routes = $this->routesGroups[$firstPathWord];
		} else if (isset($this->routesGroups[''])) {
			$routes = $this->routesGroups[''];
		} else {
			$routes = [];
		}
		reset($routes);
		return $routes;
	}

	/**
	 * Return `TRUE` if there is possible by additional info array records 
	 * to route request by given route as first argument. For example if route
	 * object has defined http method and request has the same method or not 
	 * or much more by additional info array records in extended classes.
	 * @param  \MvcCore\IRoute $route 
	 * @param  array           $additionalInfo 
	 * @return bool
	 */
	protected function rewriteRoutingCheckRoute (\MvcCore\IRoute $route, array $additionalInfo) {
		/** @var $this \MvcCore\Router */
		list ($requestMethod,) = $additionalInfo;
		$routeMethod = $route->GetMethod();
		if ($routeMethod !== NULL && $routeMethod !== $requestMethod) return TRUE;
		return FALSE;
	}

	/**
	 * When route is matched, set up request and default params. 
	 * 
	 * Request params are necessary to complete any `self` URL, to route request
	 * properly, to complete canonical URL and to process possible route redirection.
	 * 
	 * Default params are necessary to handle route filtering in and out and to
	 * complete URL by any other route name for case, when some required param 
	 * is not presented in second `$params` argument in Url() method (then the
	 * param is assigned from default params).
	 * 
	 * This method also completes any missing `controller` or `action` param
	 * values with default values. Request params can not contain those 
	 * automatically completed values, only values really requested.
	 * @param  array       $allMatchedParams  All matched params completed `\MvcCore\Route::Matches();`, 
	 *                                        where could be controller and action if it is defined in 
	 *                                        route object, default param values from route and all 
	 *                                        rewrite params parsed by route.
	 * @param  string|NULL $requestCtrlName   Possible controller name value or `NULL` assigned directly 
	 *                                        from request object in `\MvcCore\router::routeDetectStrategy();`
	 * @param  string|NULL $requestActionName Possible action name value or `NULL` assigned directly from 
	 *                                        request object in `\MvcCore\router::routeDetectStrategy();`
	 * @return void
	 */
	protected function rewriteRoutingSetRequestedAndDefaultParams (array & $allMatchedParams, $requestCtrlName = NULL, $requestActionName = NULL) {
		/** @var $this \MvcCore\Router */
		// in array `$allMatchedParams` - there could be sometimes presented matched 
		// or route specified values from configuration already, under keys `controller` and 
		// `action`, always with a value, never with `NULL`
		/** @var $request \MvcCore\Request */
		$request = $this->request;
		// get only rewrited params from url and query string params:
		$rawQueryParams = array_merge(
			[], $request->GetParams(
				FALSE, [], \MvcCore\IRequest::PARAM_TYPE_QUERY_STRING | \MvcCore\IRequest::PARAM_TYPE_URL_REWRITE
			)
		);
		// complete controller and action from any possible source
		list($ctrlDfltNamePc, $actionDfltNamePc) = $this->application->GetDefaultControllerAndActionNames();
		$toolClass = self::$toolClass;
		if ($requestCtrlName !== NULL) {
			$request->SetControllerName($requestCtrlName);
			$allMatchedParams[static::URL_PARAM_CONTROLLER] = $requestCtrlName;
			$rawQueryParams[static::URL_PARAM_CONTROLLER] = $requestCtrlName;
		} else if (isset($allMatchedParams[static::URL_PARAM_CONTROLLER])) {
			$request->SetControllerName($allMatchedParams[static::URL_PARAM_CONTROLLER]);
		} else {
			$defaultCtrlNameDashed = $toolClass::GetDashedFromPascalCase($ctrlDfltNamePc);
			$request->SetControllerName($defaultCtrlNameDashed);
			$allMatchedParams[static::URL_PARAM_CONTROLLER] = $defaultCtrlNameDashed;
		}
		if ($requestActionName !== NULL) {
			$request->SetActionName($requestActionName);
			$allMatchedParams[static::URL_PARAM_ACTION] = $requestActionName;
			$rawQueryParams[static::URL_PARAM_ACTION] = $requestActionName;
		} else if (isset($allMatchedParams[static::URL_PARAM_ACTION])) {
			$request->SetActionName($allMatchedParams[static::URL_PARAM_ACTION]);
		} else {
			$defaultActionNameDashed = $toolClass::GetDashedFromPascalCase($actionDfltNamePc);
			$request->SetActionName($defaultActionNameDashed);
			$allMatchedParams[static::URL_PARAM_ACTION] = $defaultActionNameDashed;
		}
		$request->SetParamSourceType(static::URL_PARAM_CONTROLLER, \MvcCore\IRequest::PARAM_TYPE_URL_REWRITE);
		$request->SetParamSourceType(static::URL_PARAM_ACTION, \MvcCore\IRequest::PARAM_TYPE_URL_REWRITE);
		// complete params for request object - there have to be everything including ctrl and action
		$this->defaultParams = array_merge(
			// default params are merged with previous default params to have 
			// possibility to add domain params by extended module router
			$this->currentRoute->GetDefaults(), $this->defaultParams, 
			$allMatchedParams, $rawQueryParams
		);
		// redirect route with strictly defined match regular expression and not defined reverse could have `NULL` method result:
		$routeReverseParams = $this->currentRoute->GetReverseParams() ?: [];
		// complete really matched params from path - unset ctrl and action if ctrl and even action are not in pattern
		$pathOnlyMatchedParams = array_merge([], $allMatchedParams);
		$controllerInReverse	= in_array(static::URL_PARAM_CONTROLLER, $routeReverseParams, TRUE);
		$actionInReverse		= in_array(static::URL_PARAM_ACTION, $routeReverseParams, TRUE);
		if (!$controllerInReverse)	unset($pathOnlyMatchedParams[static::URL_PARAM_CONTROLLER]);
		if (!$actionInReverse)		unset($pathOnlyMatchedParams[static::URL_PARAM_ACTION]);
		// requested params - all really requested params for self URL addresses
		// building base params array, parsed from path, merged with all query params 
		// and merged later with given params array into method `Url()`.
		// There cannot be `controller` and `action` keys from route configuration,
		// only if ctrl and action is defined by query string, that's different
		$this->requestedParams = array_merge([], $pathOnlyMatchedParams, $rawQueryParams);
	}

	/**
	 * Filter route in and if filtering is not successful, return `TRUE` about 
	 * continuing another route matching. If filtering is successful, set matched
	 * controller and action into request object and return `TRUE` to finish routes
	 * matching process.
	 * @param  array $allMatchedParams All matched params completed `\MvcCore\Route::Matches();`, 
	 *                                 where could be controller and action if it is defined in 
	 *                                 route object, default param values from route and all 
	 *                                 rewrite params parsed by route.
	 * @return bool
	 */
	protected function rewriteRoutingSetRequestParams (array & $allMatchedParams) {
		/** @var $this \MvcCore\Router */
		$request = $this->request;
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
		foreach ($requestParamsFiltered as $requestParamName => $requestParamValue) {
			$sourceType = $request->GetParamSourceType($requestParamName);
			if (!$sourceType) $sourceType = \MvcCore\IRequest::PARAM_TYPE_URL_REWRITE;
			$request->SetParam($requestParamName, $requestParamValue, $sourceType);
		}
		if (isset($requestParamsFiltered[static::URL_PARAM_CONTROLLER])) 
			$request
				->SetControllerName($requestParamsFiltered[static::URL_PARAM_CONTROLLER])
				->SetParamSourceType(static::URL_PARAM_CONTROLLER, \MvcCore\IRequest::PARAM_TYPE_URL_REWRITE);
		if (isset($requestParamsFiltered[static::URL_PARAM_ACTION])) 
			$request
				->SetActionName($requestParamsFiltered[static::URL_PARAM_ACTION])
				->SetParamSourceType(static::URL_PARAM_ACTION, \MvcCore\IRequest::PARAM_TYPE_URL_REWRITE);
		return FALSE;
	}

	/**
	 * Set up into current route controller and action 
	 * in pascal case from request object.
	 * @return void
	 */
	protected function rewriteRoutingSetUpCurrentRouteByRequest () {
		/** @var $this \MvcCore\Router */
		$request = $this->request;
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
