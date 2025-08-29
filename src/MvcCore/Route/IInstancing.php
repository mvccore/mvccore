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

interface IInstancing {
	
	/**
	 * Create every time new route instance, no singleton managing.
	 * Called usually from core methods:
	 * - `\MvcCore\Router::AddRoutes();`
	 * - `\MvcCore\Router::AddRoute();`
	 * - `\MvcCore\Router::SetOrCreateDefaultRouteAsCurrent();`
	 * This method is the best place where to implement custom route init for 
	 * configured core class. First argument could be configuration array with 
	 * all necessary constructor values or all separated arguments - first is 
	 * route pattern value to parse into match and reverse values, then 
	 * controller with action, params default values and constraints.
	 * Example:
	 * ````
	 * new Route([
	 *     "pattern"          => "/products-list/<name>/<color>",
	 *     "controllerAction" => "Products:List",
	 *     "defaults"         => ["name" => "default-name", "color" => "red"],
	 *     "constraints"      => ["name" => "[^/]*",        "color" => "[a-z]*"]
	 * ]);
	 * ````
	 * or:
	 * ````
	 * new Route(
	 *     "/products-list/<name>/<color>",
	 *     "Products:List",
	 *     ["name" => "default-name", "color" => "red"],
	 *     ["name" => "[^/]*",        "color" => "[a-z]*"]
	 * );
	 * ````
	 * or:
	 * ````
	 * new Route([
	 *     "name"       => "products_list",
	 *     "match"      => "#^/products\-list/(?<name>[^/]*)/(?<color>[a-z]*)(?=/$|$)#",
	 *     "reverse"    => "/products-list/<name>/<color>",
	 *     "controller" => "Products",
	 *     "action"     => "List",
	 *     "defaults"   => ["name" => "default-name", "color" => "red"],
	 * ]);
	 * ````
	 * @param string|array<string,mixed> $pattern
	 * Required, configuration array or route pattern value
	 * to parse into match and reverse patterns.
	 * @param ?string                    $controllerAction
	 * Optional, controller and action name in pascal case
	 * like: `"Products:List"`.
	 * @param ?array<string,mixed>       $defaults
	 * Optional, default param values like:
	 * `["name" => "default-name", "page" => 1]`.
	 * @param ?array<string,string>      $constraints
	 * Optional, params regular expression constraints for
	 * regular expression match function if no `"match"`
	 * property in config array as first argument defined.
	 * @param array<string,mixed>        $config
	 * Optional, array with adwanced configuration.
	 * There could be defined:
	 * - string   `method`   HTTP method name. If `NULL` (by default), 
	 *                       request with any http method could be matched 
	 *                       by this route. Given value is automatically 
	 *                       converted to upper case.
	 * - string   `redirect` Redirect route name.
	 * - bool     `absolute` Absolutize URL.
	 * - callable `in`       URL filter in, callable accepting arguments:
	 *                       `array $params, array $defaultParams, \MvcCore\Request $request`.
	 * - callable `out`      URL filter out, callable accepting arguments:
	 *                       `array $params, array $defaultParams, \MvcCore\Request $request`.
	 * @return \MvcCore\Route
	 */
	public static function CreateInstance (
		$pattern = NULL,
		$controllerAction = NULL,
		$defaults = NULL,
		$constraints = NULL,
		$config = []
	);

}
