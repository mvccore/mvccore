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

interface IUrlByRoutes {
	
	/**
	 * Complete optionally absolute, non-localized URL by route instance reverse 
	 * pattern and given `$params` array. If any param required by reverse 
	 * pattern is missing in params, there is used router default params
	 * completed in routing process.
	 * Example:
	 * ````
	 *   // Input `\MvcCore\Route::$reverse`:
	 *       "/products-list/<name>/<color>"
	 *   // Input `$params`:
	 *       [
	 *           "name"    => "cool-product-name",
	 *           "color"   => "red",
	 *           "variant" => ["L", "XL"],
	 *       ];
	 *   // Output:
	 *       "/application/base-bath/products-list/cool-product-name/blue?variant[]=L&amp;variant[]=XL"
	 * ````
	 * @param  \MvcCore\Route      $route
	 * @param  array<string,mixed> $params
	 * @param  string              $urlParamRouteName
	 * @return string
	 */
	public function UrlByRoute (\MvcCore\IRoute $route, array & $params = [], $urlParamRouteName = NULL);

}
