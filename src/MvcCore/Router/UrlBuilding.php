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
trait UrlBuilding {

	/**
	 * @inheritDocs
	 * @param  string $controllerActionOrRouteName Should be `"Controller:Action"` combination 
	 *                                             or just any route name as custom specific string.
	 * @param  array  $params                      Optional, array with params, key is 
	 *                                             param name, value is param value.
	 * @throws \InvalidArgumentException
	 * @return string
	 */
	public function Url ($controllerActionOrRouteName = 'Index:Index', array $params = []) {
		$result = '';
		$ctrlActionOrRouteNameKey = $this->urlGetCompletedCtrlActionKey(
			$controllerActionOrRouteName
		);
		if ($this->anyRoutesConfigured && !(
			$this->routeByQueryString && 
			$ctrlActionOrRouteNameKey === static::DEFAULT_ROUTE_NAME
		)) {
			// try to found URL route in global `$this->urlRoutes` store
			if (
				isset($this->urlRoutes[$controllerActionOrRouteName]) && 
				$this->urlRoutes[$controllerActionOrRouteName]->GetName() !== static::DEFAULT_ROUTE_NAME
			) {
				// if there was a route under `$controllerActionOrRouteName` key already, 
				// we can complete URL by this route
				$result = $this->UrlByRoute(
					$this->urlRoutes[$controllerActionOrRouteName], 
					$params, $controllerActionOrRouteName
				);
			} else if (
				isset($this->urlRoutes[$ctrlActionOrRouteNameKey]) && 
				$this->urlRoutes[$ctrlActionOrRouteNameKey]->GetName() !== static::DEFAULT_ROUTE_NAME
			) {
				// if there was a route under `$ctrlActionOrRouteNameKey` key already, 
				// we can complete URL by this route
				$result = $this->UrlByRoute(
					$this->urlRoutes[$ctrlActionOrRouteNameKey], 
					$params, $controllerActionOrRouteName
				);
			} else {
				// if there is no route under key `$ctrlActionOrRouteNameKey` yet, 
				// try to call configured `$this->preRouteUrlBuildingHandler` if any
				// to load more routes for example from database and than, try to
				// find route under key `$ctrlActionOrRouteNameKey` again
				$urlRouteFound = FALSE;
				if (!isset($this->noUrlRoutes)) {
					if ($this->preRouteUrlBuildingHandler !== NULL) {
						$newUrlRoutes = call_user_func(
							$this->preRouteUrlBuildingHandler, 
							$this, $ctrlActionOrRouteNameKey, $params
						);
						if (is_array($newUrlRoutes) && $newUrlRoutes) 
							$this->urlRoutes = array_merge($newUrlRoutes, $this->urlRoutes);
					}
					// try to found URL route again
					if (isset($this->urlRoutes[$ctrlActionOrRouteNameKey]) && $this->urlRoutes[$ctrlActionOrRouteNameKey]->GetName() !== static::DEFAULT_ROUTE_NAME) {
						$urlRouteFound = TRUE;
					} else {
						$this->noUrlRoutes[$ctrlActionOrRouteNameKey] = TRUE;
					}
				}
				if ($urlRouteFound) {
					// if route under key `$ctrlActionOrRouteNameKey` has been loaded by calling
					// configured handler `$this->preRouteUrlBuildingHandler`, complete URL by this route
					$result = $this->UrlByRoute(
						$this->urlRoutes[$ctrlActionOrRouteNameKey], 
						$params, $controllerActionOrRouteName
					);
				} else {
					// there is probably no route for given key `$ctrlActionOrRouteNameKey`,
					// so complete result URL with query string logic
					$result = $this->UrlByQueryString(
						$ctrlActionOrRouteNameKey, 
						$params, $controllerActionOrRouteName
					);
				}
			}
		} else {
			// if there are no URL routes configured - complete URL with query string logic
			$result = $this->UrlByQueryString(
				$ctrlActionOrRouteNameKey, 
				$params, $controllerActionOrRouteName
			);
		}
		return $this->EncodeUrl($result);
	}
	
	/**
	 * @inheritDocs
	 * @param  string $url 
	 * @return string
	 */
	public function EncodeUrl ($url) {
		return preg_replace_callback(
			'/[^\x21\x23\x25\x26\x2D-\x3A\x3D\x3F-\x5B\x5D\x5F\x7E]+/',
			[get_class($this), 'encodeUrlCallback'], 
			$url
		);
	}

	/**
	 * Returm first array item encoded by `rawurlencode()`.
	 * @param  array $match
	 * @return string
	 */
	protected static function encodeUrlCallback ($match) {
		return rawurlencode($match[0]);
	}

	/**
	 * Correct `Url()` method first argument. Given controller and/or action
	 * combination could have missing controller or missing actions. If there is
	 * colon character in first Url() method argument, complete missing value 
	 * with default controller name or default action. 
	 * If first `Url()` method argument is `self` keyword, return self route name
	 * value from routing process.
	 * @param  string $controllerActionOrRouteName Should be `"Controller:Action"` combination or just any route name as custom specific string.
	 * @return mixed
	 */
	protected function urlGetCompletedCtrlActionKey ($controllerActionOrRouteName) {
		$result = $controllerActionOrRouteName;
		if (strpos($controllerActionOrRouteName, ':') !== FALSE) {
			list($ctrlPc, $actionPc) = explode(':', $controllerActionOrRouteName);
			if (!$ctrlPc) {
				$toolClass = self::$toolClass;
				$reqCtrlName = $this->request->GetControllerName();
				$ctrlAbsPath = mb_strpos($reqCtrlName, '//') === 0;
				if ($ctrlAbsPath) $reqCtrlName = mb_substr($reqCtrlName, 2);
				$ctrlPc = str_replace('/', '\\', 
					$toolClass::GetPascalCaseFromDashed($reqCtrlName)
				);
				if ($ctrlAbsPath) $ctrlPc = '//' . $ctrlPc;
			}
			if (!$actionPc) {
				$toolClass = self::$toolClass;
				$actionPc = $toolClass::GetPascalCaseFromDashed($this->request->GetActionName());
			}
			$result = "{$ctrlPc}:{$actionPc}";
		} else if ($controllerActionOrRouteName == 'self') {
			if ($this->selfRouteName !== NULL) {
				$result = $this->selfRouteName;	
			} else {
				$defaultCtrlName = $this->application->GetDefaultControllerName();
				$notFoundActionName = $this->application->GetDefaultControllerNotFoundActionName();
				$result = "{$defaultCtrlName}:{$notFoundActionName}";
			}
		}
		return $result;
	}
}
