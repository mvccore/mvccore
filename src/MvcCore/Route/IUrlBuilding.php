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

namespace MvcCore\Route;

interface IUrlBuilding {
	
	/**
	 * Filter given `array $params` by configured `"in" | "out"` filter `callable`.
	 * This function return `array` with first item as `bool` about successful
	 * filter processing in `try/catch` and second item as filtered params `array`.
	 * @param  array<string,mixed>  $params
	 * Request matched params.
	 * @param  array<string,mixed>  $defaultParams
	 * Route default params.
	 * @param  string               $direction
	 * Strings `in` or `out`. You can use predefined constants:
	 * - `\MvcCore\IRoute::CONFIG_FILTER_IN`
	 * - `\MvcCore\IRoute::CONFIG_FILTER_OUT`
	 * @return array{0:bool,1:array<string,mixed>}
	 * Filtered params array.
	 */
	public function Filter (array $params = [], array $defaultParams = [], $direction = \MvcCore\IRoute::CONFIG_FILTER_IN);

	/**
	 * Complete route URL by given params array and route internal reverse 
	 * replacements pattern string. If there are more given params in first 
	 * argument than total count of replacement places in reverse pattern,
	 * then create URL with query string params after reverse pattern, 
	 * containing that extra record(s) value(s). Returned is an array with only
	 * one string as result URL or it could be returned for extended classes
	 * an array with two strings - result URL in two parts - first part as scheme, 
	 * domain and base path and second as path and query string.
	 * Example:
	 * ```
	 *   // Input `$params`:
	 *   [
	 *       "name"     => "cool-product-name",
	 *       "color"    => "blue",
	 *       "variants" => ["L", "XL"],
	 *   ];
	 *   // Input `\MvcCore\Route::$reverse`:
	 *   "/products-list/<name>/<color*>"
	 *   
	 *   // Output:
	 *   ["/any/app/base/path/products-list/cool-product-name/blue?variant[]=L&amp;variant[]=XL"]
	 *   // or:
	 *   [
	 *       "/any/app/base/path", 
	 *       "/products-list/cool-product-name/blue?variant[]=L&amp;variant[]=XL"
	 *   ]
	 * ````
	 * @param  \MvcCore\Request    $request 
	 * Currently requested request object.
	 * @param  array<string,mixed> $params
	 * URL params from application point 
	 * completed by developer.
	 * @param  array<string,mixed> $defaultUrlParams 
	 * Requested URL route params and query string 
	 * params without escaped HTML special chars: 
	 * `< > & " ' &`.
	 * @param  bool                $splitUrl
	 * Boolean value about to split completed result URL
	 * into two parts or not. Default is FALSE to return 
	 * a string array with only one record - the result 
	 * URL. If `TRUE`, result url is split into two 
	 * parts and function return array with two items.
	 * @param  string              $queryStringParamsSepatator 
	 * Query params separator, `&amp;` by default. Always 
	 * automatically completed by router instance.
	 * @return array<string>    Result URL address in array. If last argument is 
	 * `FALSE` by default, this function returns only 
	 * single item array with result URL. If last 
	 * argument is `TRUE`, function returns result URL 
	 * in two parts - domain part with base path and 
	 * path part with query string.
	 */
	public function Url (\MvcCore\IRequest $request, array $params = [], array $defaultUrlParams = [], $splitUrl = FALSE, $queryStringParamsSepatator = '&amp;');

}
