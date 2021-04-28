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
trait UrlByRoutes {

	/**
	 * @inheritDocs
	 * @param  \MvcCore\Route $route
	 * @param  array          $params
	 * @param  string         $urlParamRouteName
	 * @return string
	 */
	public function UrlByRoute (\MvcCore\IRoute $route, array & $params = [], $urlParamRouteName = NULL) {
		if ($urlParamRouteName == 'self')
			$params = array_merge($this->requestedParams ?: [], $params);
		$defaultParams = $this->GetDefaultParams() ?: [];
		/** @var \MvcCore\Route $route */
		list ($resultUrl) = $route->Url(
			$this->request, $params, $defaultParams, $this->getQueryStringParamsSepatator(), FALSE
		);
		return $resultUrl;
	}

	/**
	 * Return XML query string separator `&amp;`, if response has any
	 * `Content-Type` header with `xml` substring inside or return XML query
	 * string separator `&amp;` if `\MvcCore\View::GetDoctype()` is has any
	 * `XML` or any `XHTML` substring inside. Otherwise return HTML query
	 * string separator `&`.
	 * @return string
	 */
	protected function getQueryStringParamsSepatator () {
		if ($this->queryParamsSepatator === NULL) {
			$response = \MvcCore\Application::GetInstance()->GetResponse();
			if ($response->HasHeader('Content-Type')) {
				$this->queryParamsSepatator = $response->IsXmlOutput() ? '&amp;' : '&';
			} else {
				$viewClass = $this->application->GetViewClass();
				$viewDocType = $viewClass::GetDoctype();
				$this->queryParamsSepatator = (
					strpos($viewDocType, \MvcCore\IView::DOCTYPE_XML) !== FALSE ||
					strpos($viewDocType, \MvcCore\IView::DOCTYPE_XHTML) !== FALSE
				) ? '&amp;' : '&';
			}
		}
		return $this->queryParamsSepatator;
	}
}
