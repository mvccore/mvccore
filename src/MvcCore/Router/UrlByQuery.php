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

trait UrlByQuery {

	/**
	 * @inheritDocs
	 * @param  string $controllerActionOrRouteName
	 * @param  array  $params
	 * @param  string $givenRouteName
	 * @return string
	 */
	public function UrlByQueryString ($controllerActionOrRouteName = 'Index:Index', array & $params = [], $givenRouteName = NULL) {
		/** @var $this \MvcCore\Router */
		if ($givenRouteName == 'self') {
			$params = array_merge($this->requestedParams ?: [], $params);
			if (isset($params[static::URL_PARAM_PATH])) {
				$defaultRouteName = static::DEFAULT_ROUTE_NAME;
				if (
					$controllerActionOrRouteName === $defaultRouteName || (
						$this->currentRoute != NULL && 
						$this->currentRoute->GetName() === $defaultRouteName
					)
				) unset($params[static::URL_PARAM_PATH]);
			}
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
	
	/**
	 * Complete controller or action possible passed through `$params` array.
	 * It there is controller or action founded, unset it from `$params` and 
	 * returns it as result Array - first index is controller, second is action. 
	 * @param  mixed $controllerActionOrRouteName 
	 * @param  array $params 
	 * @return array
	 */
	protected function urlByQueryStringCompleteCtrlAction ($controllerActionOrRouteName, array & $params) {
		/** @var $this \MvcCore\Router */
		list($ctrlPc, $actionPc) = strpos($controllerActionOrRouteName, ':') !== FALSE
			? explode(':', $controllerActionOrRouteName)
			: [NULL, NULL];
		if (isset($params[static::URL_PARAM_CONTROLLER])) {
			$ctrlPc = $params[static::URL_PARAM_CONTROLLER];
			unset($params[static::URL_PARAM_CONTROLLER]);
		}
		if (isset($params[static::URL_PARAM_ACTION])) {
			$actionPc = $params[static::URL_PARAM_ACTION];
			unset($params[static::URL_PARAM_ACTION]);
		}
		$ctrlPc = str_replace('\\', '/', $ctrlPc);
		return [$ctrlPc, $actionPc];
	}

	/**
	 * Complete query string URL address - the query part from `?` by fully completed 
	 * arguments - controller and action. If controller or action has default 
	 * values, do not render them in result URL address. If there are also any
	 * `$params` in third argument, add those params as query string after.
	 * If controller and also action has default values and there are no params,
	 * return `/` slash (to target homepage).
	 * @param  string $ctrlPc 
	 * @param  string $actionPc 
	 * @param  array  $params 
	 * @return string
	 */
	protected function urlByQueryStringCompleteResult ($ctrlPc, $actionPc, array & $params) {
		/** @var $this \MvcCore\Router */
		$result = '';
		$toolClass = self::$toolClass;
		$amp = $this->getQueryStringParamsSepatator();
		list($dfltCtrlPc, $dftlActionPc) = $this->application->GetDefaultControllerAndActionNames();
		$ctrlIsNotDefault = $ctrlPc !== $dfltCtrlPc;
		$actionIsNotDefault = $actionPc !== $dftlActionPc;
		$sep = '?';
		if ($params || $ctrlIsNotDefault || $actionIsNotDefault) 
			$result .= $this->request->GetScriptName();
		if ($ctrlIsNotDefault) {
			$result .= $sep . 'controller=' . $toolClass::GetDashedFromPascalCase($ctrlPc);
			$sep = $amp;
		}
		if ($actionIsNotDefault) {
			$result .= $sep . 'action=' . $toolClass::GetDashedFromPascalCase($actionPc);
			$sep = $amp;
		}
		if ($params) 
			// `http_build_query()` automatically converts all XSS chars to entities (`< > & " ' &`):
			$result .= $sep . str_replace('%2F', '/', http_build_query($params, '', $amp, PHP_QUERY_RFC3986));
		if ($result == '') 
			$result = '/';
		return $result;
	}

	/**
	 * Return boolean about to generate absolute URL address or not. Get `TRUE` 
	 * if given `array $params` contains `boolean` record under `"absolute"`
	 * array key and if the record is `TRUE`. Unset the absolute flag from 
	 * `$params` in any case and return the boolean value (`FALSE` by default).
	 * @param  array $params Params array, the second argument from router `Url()` method.
	 * @return boolean
	 */
	protected function urlGetAbsoluteParam (array & $params = []) {
		/** @var $this \MvcCore\Router */
		$absolute = FALSE;
		$absoluteParamName = static::URL_PARAM_ABSOLUTE;
		if ($params && isset($params[$absoluteParamName])) {
			$absolute = (bool) $params[$absoluteParamName];
			unset($params[$absoluteParamName]);
		}
		return $absolute;
	}
}
