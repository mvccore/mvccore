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
trait Canonical {

	/**
	 * Redirect to canonical URL if request is not an internal and also if request 
	 * is not realized by GET method and also if canonical redirect is not permitted.
	 * Then try to complete canonical (shorter) URL by detected strategy and if
	 * canonical URL is different, redirect to it.
	 * Return `TRUE` if there was not necessary to redirect, `FALSE` otherwise.
	 * @return bool
	 */
	protected function canonicalRedirectIfAny () {
		if (
			$this->request->IsCli() ||
			$this->internalRequest || 
			!$this->autoCanonizeRequests || 
			$this->request->GetMethod() !== \MvcCore\IRequest::METHOD_GET
		) return TRUE;
		if ($this->routeByQueryString) {
			// self URL could be completed only by query string strategy
			return $this->canonicalRedirectQueryStringStrategy();
		} else if ($this->selfRouteName !== NULL) {
			// self URL could be completed by rewrite routes strategy
			return $this->canonicalRedirectRewriteRoutesStrategy();
		}
		return TRUE;
	}

	/**
	 * If request is routed by query string strategy, check if request controller
	 * or request action is the same as default values. Then redirect to shorter
	 * canonical URL.
	 * @return bool
	 */
	protected function canonicalRedirectQueryStringStrategy () {
		$request = $this->request;
		$redirectToCanonicalUrl = 0;
		$requestGlobalGet = & $request->GetGlobalCollection('get');
		$requestedCtrlDc = isset($requestGlobalGet[static::URL_PARAM_CONTROLLER]) ? $requestGlobalGet[static::URL_PARAM_CONTROLLER] : NULL;
		$requestedActionDc = isset($requestGlobalGet[static::URL_PARAM_ACTION]) ? $requestGlobalGet[static::URL_PARAM_ACTION] : NULL;
		$toolClass = self::$toolClass;
		list($dfltCtrlPc, $dftlActionPc) = $this->application->GetDefaultControllerAndActionNames();
		$dfltCtrlDc = $toolClass::GetDashedFromPascalCase($dfltCtrlPc);
		$dftlActionDc = $toolClass::GetDashedFromPascalCase($dftlActionPc);
		$requestedParamsClone = array_merge([], $this->requestedParams);
		if ($requestedCtrlDc === NULL) {
			unset($requestedParamsClone[static::URL_PARAM_CONTROLLER]);
		} else if ($requestedCtrlDc === $dfltCtrlDc) {
			unset($requestedParamsClone[static::URL_PARAM_CONTROLLER]);
			$redirectToCanonicalUrl = 1;
		}
		if ($requestedActionDc === NULL) {
			unset($requestedParamsClone[static::URL_PARAM_ACTION]);
		} else if ($requestedActionDc === $dftlActionDc) {
			unset($requestedParamsClone[static::URL_PARAM_ACTION]);
			$redirectToCanonicalUrl = 2;
		}
		if (isset($requestedParamsClone[static::URL_PARAM_PATH])) {
			$pathParam = $requestedParamsClone[static::URL_PARAM_PATH];
			if ($pathParam === '/')
				unset($requestedParamsClone[static::URL_PARAM_PATH]);
		}
		if ($redirectToCanonicalUrl) {
			if (isset($requestedParamsClone[static::URL_PARAM_PATH])) {
				$pathParam = $requestedParamsClone[static::URL_PARAM_PATH];
				if ($pathParam === $request->GetScriptName())
					unset($requestedParamsClone[static::URL_PARAM_PATH]);
			}
			$selfCanonicalUrl = $this->UrlByQueryString($this->selfRouteName, $requestedParamsClone);
			$this->redirect($selfCanonicalUrl, \MvcCore\IResponse::MOVED_PERMANENTLY, "Canonical URL (state: {$redirectToCanonicalUrl})");
			return FALSE;
		}
		return TRUE;
	}
	
	/**
	 * If request is routed by rewrite routes strategy, try to complete canonical
	 * URL by current route. Then compare completed base URL part with requested 
	 * base URL part or completed path and query part with requested path and query
	 * part. If first or second part is different, redirect to canonical shorter URL.
	 * @return bool
	 */
	protected function canonicalRedirectRewriteRoutesStrategy () {
		/** @var \MvcCore\Request $request */
		$request = $this->request;
		$redirectToCanonicalUrl = 0;
		$defaultParams =  $this->GetDefaultParams() ?: [];
		list(
			$selfUrlDomainAndBasePart, $selfUrlPathAndQueryPart
		) = $this->currentRoute->Url(
			$request, $this->requestedParams, 
			$defaultParams, 
			TRUE,
			'&' // do not use '&amp;' here, because compared request url contains not encoded `&` char
		);
		if (mb_strpos($selfUrlDomainAndBasePart, '//') === FALSE)
			$selfUrlDomainAndBasePart = $request->GetDomainUrl() . $selfUrlDomainAndBasePart;
		if (
			mb_strlen($selfUrlDomainAndBasePart) > 0 && 
			$selfUrlDomainAndBasePart !== $request->GetBaseUrl()
		) {
			$redirectToCanonicalUrl = 3;
		} else if (mb_strlen($selfUrlPathAndQueryPart) > 0) {
			$path = $request->GetPath(TRUE);
			$requestedUrl = $path === '' ? '/' : $path ;
			$selfUrlPathAndQueryPart = rawurldecode($selfUrlPathAndQueryPart);
			if (mb_strpos($selfUrlPathAndQueryPart, '?') !== FALSE) 
				$requestedUrl .= $request->GetQuery(TRUE, TRUE);
			if ($selfUrlPathAndQueryPart !== $requestedUrl) 
				$redirectToCanonicalUrl = 4;
		}
		if ($redirectToCanonicalUrl > 0) {
			$selfCanonicalUrl = $this->Url($this->selfRouteName, $this->requestedParams);
			$this->redirect($selfCanonicalUrl, \MvcCore\IResponse::MOVED_PERMANENTLY, "Canonical URL (state: {$redirectToCanonicalUrl})");
			return FALSE;
		}
		return TRUE;
	}
}
