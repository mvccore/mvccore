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

trait UrlByRoutes
{
	/**
	 * Complete optionally absolute, non-localized URL by route instance reverse
	 * pattern and given `$params` array. If any param required by reverse
	 * pattern is missing in params, there is used router default params
	 * completed in routing process.
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
	 * @param \MvcCore\Route|\MvcCore\IRoute $route
	 * @param array $params
	 * @param string $urlParamRouteName
	 * @return string
	 */
	public function UrlByRoute (\MvcCore\IRoute $route, array & $params = [], $urlParamRouteName = NULL) {
		/** @var $this \MvcCore\Router */
		if ($urlParamRouteName == 'self')
			$params = array_merge($this->requestedParams ?: [], $params);
		$defaultParams = $this->GetDefaultParams() ?: [];
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
		/** @var $this \MvcCore\Router */
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
