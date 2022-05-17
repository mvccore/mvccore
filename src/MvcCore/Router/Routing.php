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

/**
 * @mixin \MvcCore\Router
 */
trait Routing {

	/**
	 * @inheritDocs
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
	 * @inheritDocs
	 * @param  string $controllerNamePc Pascal case classic controller name definition.
	 * @param  string $actionNamePc     Pascal case action name without `Action` suffix.
	 * @param  bool   $changeSelfRoute  `FALSE` by default to change self route to generate self URLs.
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
		$ctrlActionParamType = $this->routeByQueryString
			? \MvcCore\IRequest::PARAM_TYPE_QUERY_STRING
			: \MvcCore\IRequest::PARAM_TYPE_URL_REWRITE;
		if ($controllerNamePcNotNull) {
			$ctrlNameDc = str_replace(['\\', '_'], '/', $toolClass::GetDashedFromPascalCase($controllerNamePc));
			$matchedParams[static::URL_PARAM_CONTROLLER] = $ctrlNameDc;
			$this->request->SetControllerName($ctrlNameDc)->SetParamSourceType(
				static::URL_PARAM_CONTROLLER, $ctrlActionParamType
			);
			if (isset($this->requestedParams[static::URL_PARAM_CONTROLLER])) $this->requestedParams[static::URL_PARAM_CONTROLLER] = $ctrlNameDc;
			$currentRoute->SetController($controllerNamePc);
		}
		if ($actionNamePcNotNull) {
			$actionNameDc = $toolClass::GetDashedFromPascalCase($actionNamePc);
			$matchedParams[static::URL_PARAM_ACTION] = $actionNameDc;
			$this->request->SetActionName($actionNameDc)->SetParamSourceType(
				static::URL_PARAM_ACTION, $ctrlActionParamType
			);
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
	 * @inheritDocs
	 * @param  string $routeName    Always as `default`, `error` or `not_found`, by constants:
	 *                              - `\MvcCore\IRouter::DEFAULT_ROUTE_NAME`
	 *                              - `\MvcCore\IRouter::DEFAULT_ROUTE_NAME_ERROR`
	 *                              - `\MvcCore\IRouter::DEFAULT_ROUTE_NAME_NOT_FOUND`
	 * @param  string $controllerPc Controller name in pascal case.
	 * @param  string $actionPc     Action name with pascal case without ending `Action` substring.
	 * @param  bool   $fallbackCall `FALSE` by default. If `TRUE`, this function is called from error rendering fallback, self route name is not changed.
	 * @return \MvcCore\Route
	 */
	public function SetOrCreateDefaultRouteAsCurrent ($routeName, $controllerPc, $actionPc, $fallbackCall = FALSE) {
		$ctrlAbsPath = mb_strpos($controllerPc, '//') === 0;
		if ($ctrlAbsPath) $controllerPc = mb_substr($controllerPc, 2);
		$controllerPc = strtr($controllerPc, '/', '\\');
		if ($ctrlAbsPath) $controllerPc = '//' . $controllerPc;
		$ctrlActionRouteName = $controllerPc.':'. $actionPc;
		$request = $this->request;
		$ctrlActionParamType = $this->routeByQueryString
			? \MvcCore\IRequest::PARAM_TYPE_QUERY_STRING
			: \MvcCore\IRequest::PARAM_TYPE_URL_REWRITE;
		if (isset($this->routes[$ctrlActionRouteName])) {
			$defaultRoute = $this->routes[$ctrlActionRouteName];
		} else if (isset($this->routes[$routeName])) {
			$defaultRoute = $this->routes[$routeName];
		} else {
			$routeClass = self::$routeClass;
			$pathParamName = static::URL_PARAM_PATH;
			$defaultRoute = $routeClass::CreateInstance()
				->SetMatch("#/(?<{$pathParamName}>.*)#")
				->SetReverse("/<{$pathParamName}>")
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
				$request->SetParam(
					static::URL_PARAM_PATH, 
					$request->HasParam(static::URL_PARAM_PATH)
						? $request->GetParam(static::URL_PARAM_PATH, '.*')
						: $request->GetPath(),
					$ctrlActionParamType
				);
		}
		$toolClass = self::$toolClass;
		$request
			->SetControllerName(str_replace('\\', '/', 
				$toolClass::GetDashedFromPascalCase($defaultRoute->GetController())
			))
			->SetActionName($toolClass::GetDashedFromPascalCase($defaultRoute->GetAction()))
			->SetParamSourceType(static::URL_PARAM_CONTROLLER, $ctrlActionParamType)
			->SetParamSourceType(static::URL_PARAM_ACTION, $ctrlActionParamType);
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
	 * @return \string[]
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
				$this->anyRoutesConfigured !== TRUE ||
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
		$this->defaultParams = array_merge(
			[], 
			$this->defaultParams, 
			$this->request->GetParams(
				FALSE, [], \MvcCore\IRequest::PARAM_TYPE_QUERY_STRING
			)
		);
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
					$requestParams = array_merge(
						[], $this->request->GetParams(
							FALSE, [], \MvcCore\IRequest::PARAM_TYPE_QUERY_STRING | \MvcCore\IRequest::PARAM_TYPE_URL_REWRITE
						)
					);
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
		if ($this->currentRoute instanceof \MvcCore\IRoute) 
			$this->selfRouteName = $this->anyRoutesConfigured
				? $this->currentRoute->GetName()
				: $this->currentRoute->GetControllerAction();
		return $this;
	}
}
