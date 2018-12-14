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
 * Responsibility - singleton, routes instancing, request routing and URL building.
 * - Application router singleton instance managing.
 * - Global storage for all configured routes - instancing all route(s) in 
 *   application start configuration anywhere in `Bootstrap` class.
 * - Global storage for currently matched route.
 * - Application request routing - targeting request by matched route object 
 *   (in route method `Route();` by request `path` [or more]), ) into target 
 *   route controller and route action, always called from core in:
 *   `\MvcCore\Application::Run();` => `\MvcCore\Application::routeRequest();`.
 * - Application URL addresses completing:
 *   - By `mod_rewrite` form by configured route instances.
 *   - By `index.php?` + query string form, containing `controller`, `action` 
 *     and all other params.
 */
class Router implements IRouter
{
	use \MvcCore\Router\Props;
	use \MvcCore\Router\GettersSetters;
	use \MvcCore\Router\Instancing;
	use \MvcCore\Router\RouteMethods;
	use \MvcCore\Router\Routing;
	use \MvcCore\Router\RewriteRouting;
	use \MvcCore\Router\Canonical;
	use \MvcCore\Router\Redirecting;
	use \MvcCore\Router\UrlBuilding;
	use \MvcCore\Router\UrlByQuery;
	use \MvcCore\Router\UrlByRoutes;
}
