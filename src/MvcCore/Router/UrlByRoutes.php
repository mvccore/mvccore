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
	 * @inheritDoc
	 * @param  \MvcCore\Route      $route
	 * @param  array<string,mixed> $params
	 * @param  string              $urlParamRouteName
	 * @return string
	 */
	public function UrlByRoute (\MvcCore\IRoute $route, array & $params = [], $urlParamRouteName = NULL) {
		if ($urlParamRouteName == 'self')
			$params = array_merge($this->requestedParams ?: [], $params);
		$defaultParams = $this->GetDefaultParams() ?: [];
		/** @var \MvcCore\Route $route */
		list ($resultUrl) = $route->Url(
			$this->request, $params, $defaultParams, FALSE
		);
		return $resultUrl;
	}

}
