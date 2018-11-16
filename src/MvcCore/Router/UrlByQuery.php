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

trait UrlByQuery
{
	/**
	 * Complete optionally absolute, non-localized url with all params in query string.
	 * Example: `"/application/base-bath/index.php?controller=ctrlName&amp;action=actionName&amp;name=cool-product-name&amp;color=blue"`
	 * @param string $controllerActionOrRouteName
	 * @param array  $params
	 * @param string $givenRouteName
	 * @return string
	 */
	public function UrlByQueryString ($controllerActionOrRouteName = 'Index:Index', array & $params = [], $givenRouteName = NULL) {
		if ($givenRouteName == 'self') {
			$params = array_merge($this->requestedParams ?: [], $params);
			if ($controllerActionOrRouteName === static::DEFAULT_ROUTE_NAME && isset($params[static::URL_PARAM_PATH]))
				unset($params[static::URL_PARAM_PATH]);
		}
		list($ctrlPc, $actionPc) = $this->urlByQueryStringCompleteCtrlAction(
			$controllerActionOrRouteName, $params
		);
		$absolute = $this->urlGetAbsoluteParam($params);
		$result = $this->urlByQueryStringCompleteResult(
			$ctrlPc, $actionPc, $params
		);
		$result = $this->request->GetBasePath() . $result;
		if ($absolute) 
			$result = $this->request->GetDomainUrl() . $result;
		return $result;
	}
	
	protected function urlByQueryStringCompleteCtrlAction ($controllerActionOrRouteName, array & $params) {
		list($ctrlPc, $actionPc) = strpos($controllerActionOrRouteName, ':') !== FALSE
			? explode(':', $controllerActionOrRouteName)
			: [NULL, NULL];
		if (isset($params['controller'])) {
			$ctrlPc = $params['controller'];
			unset($params['controller']);
		}
		if (isset($params['action'])) {
			$actionPc = $params['action'];
			unset($params['action']);
		}
		$ctrlPc = str_replace('\\', '/', $ctrlPc);
		return [$ctrlPc, $actionPc];
	}

	protected function urlByQueryStringCompleteResult ($ctrlPc, $actionPc, array & $params) {
		$result = '';
		$toolClass = self::$toolClass;
		$amp = $this->getQueryStringParamsSepatator();
		list($dfltCtrlPc, $dftlActionPc) = $this->application->GetDefaultControllerAndActionNames();
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
			// `http_build_query()` automatically converts all XSS chars to entities (`< > & " ' &`):
			$result .= $sep . str_replace('%2F', '/', http_build_query($params, '', $amp, PHP_QUERY_RFC3986));
		}
		if ($result == '') $result = '/';
		return $result;
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
}
