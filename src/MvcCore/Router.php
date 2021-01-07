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
 * @inheritDocs
 */
class Router implements IRouter {
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
