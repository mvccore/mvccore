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

namespace MvcCore;

/**
 * Responsibility - singleton, routes instancing, request routing and url building.
 * - Application router singleton instance managing.
 * - Global storage for all configured routes.
 *	 - Instancing all route(s) from application start
 *	   configuration somewhere in `Bootstrap` class.
 * - Global storage for currently matched route.
 * - Matching proper route object in `\MvcCore\Router::Route();`
 *   by `\MvcCore\Request::$Path`, always called from core in
 *   `\MvcCore\Application::Run();` => `\MvcCore\Application::routeRequest();`.
 * - Application url addresses completing:
 *   - Into `mod_rewrite` form by configured route instances.
 *   - Into `index.php?` + query string form, containing
 *	 `controller`, `action` and all other params.
 */
class Router implements IRouter
{
	use \MvcCore\Router\Props;
	use \MvcCore\Router\GettersSetters;
	use \MvcCore\Router\Instancing;
	use \MvcCore\Router\RouteMethods;
	use \MvcCore\Router\Routing;
	use \MvcCore\Router\RoutingByRoutes;
	use \MvcCore\Router\Canonical;
	use \MvcCore\Router\Redirecting;
	use \MvcCore\Router\UrlBuilding;
	
}
