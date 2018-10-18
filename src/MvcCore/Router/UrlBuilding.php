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

trait UrlBuilding
{
	/**
	 * Generates url:
	 * - By `"Controller:Action"` name and params array
	 *   (for routes configuration when routes array has keys with `"Controller:Action"` strings
	 *   and routes has not controller name and action name defined inside).
	 * - By route name and params array
	 *	 (route name is key in routes configuration array, should be any string
	 *	 but routes must have information about controller name and action name inside).
	 * Result address (url string) should have two forms:
	 * - Nice rewrited url by routes configuration
	 *   (for apps with URL rewrite support (Apache `.htaccess` or IIS URL rewrite module)
	 *   and when first param is key in routes configuration array).
	 * - For all other cases is url form like: `"index.php?controller=ctrlName&amp;action=actionName"`
	 *	 (when first param is not founded in routes configuration array).
	 * @param string $controllerActionOrRouteName	Should be `"Controller:Action"` combination or just any route name as custom specific string.
	 * @param array  $params						Optional, array with params, key is param name, value is param value.
	 * @throws \InvalidArgumentException
	 * @return string
	 */
	public function Url ($controllerActionOrRouteName = 'Index:Index', array $params = []) {
		$result = '';
		$ctrlActionOrRouteNameKey = $this->urlGetCompletedCtrlActionKey(
			$controllerActionOrRouteName
		);
		if ($this->anyRoutesConfigured) {
			// try to found url route in global `$this->urlRoutes` store
			if (isset($this->urlRoutes[$ctrlActionOrRouteNameKey])) {
				// if there was a route under `$ctrlActionOrRouteNameKey` key already, 
				// we can complete url by this route
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
					if ($this->preRouteUrlBuildingHandler !== NULL) 
						call_user_func($this->preRouteUrlBuildingHandler, $this, $ctrlActionOrRouteNameKey, $params);
					// try to found url route again
					if (isset($this->urlRoutes[$ctrlActionOrRouteNameKey])) {
						$urlRouteFound = FALSE;
					} else {
						$this->noUrlRoutes[$ctrlActionOrRouteNameKey] = TRUE;
					}
				}
				if ($urlRouteFound) {
					// if route under key `$ctrlActionOrRouteNameKey` has been loaded by calling
					// configured handler `$this->preRouteUrlBuildingHandler`, complete url by this route
					$result = $this->UrlByRoute(
						$this->urlRoutes[$ctrlActionOrRouteNameKey], 
						$params, $controllerActionOrRouteName
					);
				} else {
					// there is probably no route for given key `$ctrlActionOrRouteNameKey`,
					// so complete result url with query string logic
					$result = $this->UrlByQueryString(
						$ctrlActionOrRouteNameKey, 
						$params, $controllerActionOrRouteName
					);
				}
			}
		} else {
			// if there are no url routes configured - complete url with query string logic
			$result = $this->UrlByQueryString(
				$ctrlActionOrRouteNameKey, 
				$params, $controllerActionOrRouteName
			);
		}
		return $result;
	}

	protected function urlGetCompletedCtrlActionKey ($controllerAction) {
		$result = $controllerAction;
		if (strpos($controllerAction, ':') !== FALSE) {
			list($ctrlPc, $actionPc) = explode(':', $controllerAction);
			if (!$ctrlPc) {
				$toolClass = self::$toolClass;
				$ctrlPc = $toolClass::GetPascalCaseFromDashed($this->request->GetControllerName());
			}
			if (!$actionPc) {
				$toolClass = self::$toolClass;
				$actionPc = $toolClass::GetPascalCaseFromDashed($this->request->GetActionName());
			}
			$result = "$ctrlPc:$actionPc";
		} else if ($controllerAction == 'self') {
			$result = $this->selfRouteName;
		}
		return $result;
	}

	/**
	 * Complete optionally absolute, non-localized url with all params in query string.
	 * Example: `"/application/base-bath/index.php?controller=ctrlName&amp;action=actionName&amp;name=cool-product-name&amp;color=blue"`
	 * @param string $controllerActionOrRouteName
	 * @param array  $params
	 * @param string $givenRouteName
	 * @return string
	 */
	public function UrlByQueryString ($controllerActionOrRouteName = 'Index:Index', array & $params = [], $givenRouteName = NULL) {
		if ($givenRouteName == 'self') 
			$params = array_merge($this->requestedParams ?: [], $params);
		$toolClass = self::$toolClass;
		list($ctrlPc, $actionPc) = explode(':', $controllerActionOrRouteName);
		if (isset($params['controller'])) {
			$ctrlPc = $params['controller'];
			unset($params['controller']);
		}
		if (isset($params['action'])) {
			$actionPc = $params['action'];
			unset($params['action']);
		}
		$amp = $this->getQueryStringParamsSepatator();
		list($dfltCtrlPc, $dftlActionPc) = $this->application->GetDefaultControllerAndActionNames();
		$absolute = $this->urlGetAbsoluteParam($params);
		$result = '';
		$ctrlIsNotDefault = $ctrlPc !== $dfltCtrlPc;
		$actionIsNotDefault = $actionPc !== $dftlActionPc;
		$sep = '?';
		if ($params || $ctrlIsNotDefault || $actionIsNotDefault) {
			$result .= $this->request->GetScriptName();
		}
		if ($ctrlIsNotDefault) {
			$result .= $sep . 'controller=' . $toolClass::GetDashedFromPascalCase($ctrlPc);
			$sep = $amp;
		}
		if ($actionIsNotDefault) {
			$result .= $sep . 'action=' . $toolClass::GetDashedFromPascalCase($actionPc);
			$sep = $amp;
		}
		if ($params) {
			// `http_build_query()` automaticly converts all XSS chars to entities (`< > & " ' &`):
			$result .= $sep . str_replace('%2F', '/', http_build_query($params, '', $amp, PHP_QUERY_RFC3986));
		}
		if ($result == '') $result = '/';
		$result = $this->request->GetBasePath() . $result;
		if ($absolute) 
			$result = $this->request->GetDomainUrl() . $result;
		return $result;
	}

	/**
	 * Complete optionally absolute, non-localized url by route instance reverse info.
	 * Example:
	 *	Input (`\MvcCore\Route::$reverse`):
	 *		`"/products-list/<name>/<color>"`
	 *	Input ($params):
	 *		`array(
	 *			"name"		=> "cool-product-name",
	 *			"color"		=> "red",
	 *			"variant"	=> array("L", "XL"),
	 *		);`
	 *	Output:
	 *		`/application/base-bath/products-list/cool-product-name/blue?variant[]=L&amp;variant[]=XL"`
	 * @param \MvcCore\Route $route
	 * @param array $params
	 * @param string $urlParamRouteName
	 * @return string
	 */
	public function UrlByRoute (\MvcCore\IRoute & $route, array & $params = [], $urlParamRouteName = NULL) {
		if ($urlParamRouteName == 'self') 
			$params = array_merge($this->requestedParams ?: [], $params);
		$defaultParams = $this->GetDefaultParams() ?: [];
		return implode('', $route->Url(
			$this->request, $params, $defaultParams, $this->getQueryStringParamsSepatator()
		));
	}

	/**
	 * Get `TRUE` if given `array $params` contains `boolean` record under 
	 * `"absolute"` array key and if the record is `TRUE`. Unset the absolute 
	 * flag from `$params` in any case.
	 * @param array $params 
	 * @return boolean
	 */
	protected function urlGetAbsoluteParam (array & $params = []) {
		$absolute = FALSE;
		$absoluteParamName = static::URL_PARAM_ABSOLUTE;
		if ($params && isset($params[$absoluteParamName])) {
			$absolute = (bool) $params[$absoluteParamName];
			unset($params[$absoluteParamName]);
		}
		return $absolute;
	}

	/**
	 * Return XML query string separator `&amp;`, if response has any `Content-Type` header with `xml` substring inside
	 * or return XML query string separator `&amp;` if `\MvcCore\View::GetDoctype()` is has any `XML` or any `XHTML` substring inside.
	 * Otherwise return HTML query string separator `&`.
	 * @return string
	 */
	protected function getQueryStringParamsSepatator () {
		if ($this->queryParamsSepatator === NULL) {
			$response = \MvcCore\Application::GetInstance()->GetResponse();
			if ($response->HasHeader('Content-Type')) {
				$this->queryParamsSepatator = $response->IsXmlOutput() ? '&amp;' : '&';
			} else {
				$viewDocType = \MvcCore\View::GetDoctype();
				$this->queryParamsSepatator = (
					strpos($viewDocType, \MvcCore\View::DOCTYPE_XML) !== FALSE ||
					strpos($viewDocType, \MvcCore\View::DOCTYPE_XHTML) !== FALSE
				) ? '&amp;' : '&';
			}
		}
		return $this->queryParamsSepatator;
	}
}
