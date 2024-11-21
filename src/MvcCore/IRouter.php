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
interface	IRouter
extends		\MvcCore\Router\IConstants,
			\MvcCore\Router\IInstancing,
			\MvcCore\Router\IGettersSetters,
			\MvcCore\Router\IRouteMethods,
			\MvcCore\Router\IRouting,
			\MvcCore\Router\IUrlBuilding,
			\MvcCore\Router\IUrlByQuery,
			\MvcCore\Router\IUrlByRoutes {
}
