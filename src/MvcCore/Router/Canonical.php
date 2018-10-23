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

trait Canonical
{
	/**
	 * TODO:
	 * Return `TRUE` if current route is route instance or `FALSE` otherwise.
	 * @return bool
	 */
	protected function canonicalRedirectIfAny () {
		if (
			$this->internalRequest || !$this->autoCanonizeRequests || 
			$this->request->GetMethod() !== \MvcCore\IRequest::METHOD_GET
		) return TRUE;
		if ($this->routeByQueryString) {
			// self url could be completed only by query string strategy
			return $this->canonicalRedirectQueryStringStrategy();
		} else if ($this->selfRouteName !== NULL) {
			// self url could be completed by rewrite routes strategy
			return $this->canonicalRedirectRewriteRoutesStrategy();
		}
		return TRUE;
	}

	protected function canonicalRedirectQueryStringStrategy () {
		/** @var $req \MvcCore\Request */
		$req = & $this->request;
		$redirectToCanonicalUrl = FALSE;
		$requestGlobalGet = & $req->GetGlobalCollection('get');
		$requestedCtrlDc = isset($requestGlobalGet['controller']) ? $requestGlobalGet['controller'] : NULL;
		$requestedActionDc = isset($requestGlobalGet['action']) ? $requestGlobalGet['action'] : NULL;
		$toolClass = self::$toolClass;
		list($dfltCtrlPc, $dftlActionPc) = $this->application->GetDefaultControllerAndActionNames();
		$dfltCtrlDc = $toolClass::GetDashedFromPascalCase($dfltCtrlPc);
		$dftlActionDc = $toolClass::GetDashedFromPascalCase($dftlActionPc);
		$requestedParamsClone = array_merge([], $this->requestedParams);
		if ($requestedCtrlDc === NULL && $requestedParamsClone['controller'] === $dfltCtrlDc) {
			unset($requestedParamsClone['controller']);
			$redirectToCanonicalUrl = TRUE;
		} else if ($requestedCtrlDc !== NULL && $requestedCtrlDc === $dfltCtrlDc) {
			unset($requestedParamsClone['controller']);
			$redirectToCanonicalUrl = TRUE;
		}
		if ($requestedActionDc === NULL && $requestedParamsClone['action'] === $dftlActionDc) {
			unset($requestedParamsClone['action']);
			$redirectToCanonicalUrl = TRUE;
		} else if ($requestedActionDc !== NULL && $requestedActionDc === $dftlActionDc) {
			unset($requestedParamsClone['action']);
			$redirectToCanonicalUrl = TRUE;
		}
		if ($redirectToCanonicalUrl) {
			$selfCanonicalUrl = $this->UrlByQueryString($this->selfRouteName, $requestedParamsClone);	
			$this->redirect($selfCanonicalUrl, \MvcCore\IResponse::MOVED_PERMANENTLY);
			return FALSE;
		}
		return TRUE;
	}
	
	protected function canonicalRedirectRewriteRoutesStrategy () {
		/** @var $req \MvcCore\Request */
		$req = & $this->request;
		$redirectToCanonicalUrl = FALSE;
		$defaultParams =  $this->GetDefaultParams() ?: [];
		list($selfUrlDomainAndBasePart, $selfUrlPathAndQueryPart) = $this->urlRoutes[$this->selfRouteName]->Url(
			$req, $this->requestedParams, $defaultParams, $this->getQueryStringParamsSepatator()
		);
		if (mb_strlen($selfUrlDomainAndBasePart) > 0 && $selfUrlDomainAndBasePart !== $req->GetBaseUrl()) 
			$redirectToCanonicalUrl = TRUE;
		if (mb_strlen($selfUrlPathAndQueryPart) > 0) {
			$path = $req->GetPath(TRUE);
			$path = $path === '' ? '/' : $path ;
			$requestedUrl = $req->GetBasePath() . $path;
			if (mb_strpos($selfUrlPathAndQueryPart, '?') !== FALSE) {
				$selfUrlPathAndQueryPart = rawurldecode($selfUrlPathAndQueryPart);
				$requestedUrl .= $req->GetQuery(TRUE, TRUE);
			}
			if ($selfUrlPathAndQueryPart !== $requestedUrl) 
				$redirectToCanonicalUrl = TRUE;
		}
		if ($redirectToCanonicalUrl) {
			$selfCanonicalUrl = $this->Url($this->selfRouteName, $this->requestedParams);
			$this->redirect($selfCanonicalUrl, \MvcCore\IResponse::MOVED_PERMANENTLY);
			return FALSE;
		}
		return TRUE;
	}
}
