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
trait Instancing {

	/**
	 * @inheritDocs
	 * @param  \MvcCore\Route[]|array $routes 
	 *                                Keyed array with routes, keys are route names or route
	 *                                `Controller::Action` definitions.
	 * @param  bool                   $autoInitialize 
	 *                                If `TRUE`, locale routes array is cleaned and then all 
	 *                                routes (or configuration arrays) are sent into method 
	 *                                `$router->AddRoutes();`, where are routes auto initialized 
	 *                                for missing route names or route controller or route action
	 *                                record, completed always from array keys. You can you 
	 *                                `FALSE` to set routes without any change or 
	 *                                auto-initialization, it could be useful to restore cached 
	 *                                routes etc.
	 * @return \MvcCore\Router
	 */
	public static function GetInstance (array $routes = [], $autoInitialize = TRUE) {
		if (!self::$instance) {
			/** @var \MvcCore\Application $app */
			$app = \MvcCore\Application::GetInstance();
			self::$routeClass = $app->GetRouteClass();
			self::$routerClass = $app->GetRouterClass();
			self::$toolClass = $app->GetToolClass();
			$routerClass = $app->GetRouterClass();
			$instance = new $routerClass($routes, $autoInitialize);
			$instance->application = $app;
			self::$instance = $instance;
		} else if ($routes) {
			self::$instance->SetRoutes($routes, NULL, $autoInitialize);
		}
		return self::$instance;
	}

	/**
	 * Create router as every time new instance,
	 * no singleton instance management here.
	 * optionally set routes as first argument.
	 * If there is no name configured in route array configuration,
	 * set route name by given `$routes` array key, if key is not numeric.
	 *
	 * Routes could be defined in various forms:
	 * Example:
	 * ````
	 *    new \MvcCore\Router([
	 *        "Products:List"    => "/products-list/<name>/<color>",
	 *    ]);
	 * ````
	 * or:
	 * ````
	 *   new \MvcCore\Router([
	 *       'products_list'        => [
	 *           "pattern"          => "/products-list/<name>/<color>",
	 *           "controllerAction" => "Products:List",
	 *           "defaults"         => ["name" => "default-name", "color" => "red"],
	 *           "constraints"      => ["name" => "[^/]*",        "color" => "[a-z]*"]
	 *       ]
	 *   ]);
	 * ````
	 * or:
	 * ````
	 *   new \MvcCore\Router([
	 *       new Route(
	 *           "/products-list/<name>/<color>",
	 *           "Products:List",
	 *           ["name" => "default-name", "color" => "red"],
	 *           ["name" => "[^/]*",        "color" => "[a-z]*"]
	 *       )
	 *   ]);
	 * ````
	 * or:
	 * ````
	 *   new \MvcCore\Router([
	 *       new Route(
	 *           "name"       => "products_list",
	 *           "pattern"    => "#^/products\-list/(?<name>[^/]*)/(?<color>[a-z]*)(?=/$|$)#",
	 *           "reverse"    => "/products-list/<name>/<color>",
	 *           "controller" => "Products",
	 *           "action"     => "List",
	 *           "defaults"   => ["name" => "default-name", "color" => "red"],
	 *       )
	 *   ]);
	 * ````
	 * @param \MvcCore\Route[]|array $routes
	 *                               Keyed array with routes,
	 *                                keys are route names or route
	 *                               `Controller::Action` definitions.
	 * @param bool                   $autoInitialize
	 *                               If `TRUE`, locale routes array is cleaned and 
	 *                               then all routes (or configuration arrays) are 
	 *                               sent into method `$router->AddRoutes();`, 
	 *                               where are routes auto initialized for missing 
	 *                               route names or route controller or route action
	 *                               record, completed always from array keys.
	 *                               You can you `FALSE` to set routes without any 
	 *                               change or auto-initialization, it could be useful 
	 *                               to restore cached routes etc.
	 * @return void
	 */
	public function __construct (array $routes = [], $autoInitialize = TRUE) {
		if ($routes) $this->SetRoutes($routes, NULL, $autoInitialize);
	}
}
