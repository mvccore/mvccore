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

interface IConstants {

	/**
	 * Default system route name, automatically created for requests:
	 * - For requests with explicitly defined ctrl and action in query string.
	 * - For requests targeting homepage with ctrl and action `Index:Index`.
	 * - For requests targeting any not matched path by other routes with
	 *   configured router as `$router->SetRouteToDefaultIfNotMatch();` which
	 *   target default route with controller and action `Index:Index`.
	 */
	const DEFAULT_ROUTE_NAME = 'default';

	/**
	 * Default system route name, automatically created for error requests,
	 * where was uncaught exception in ctrl or template, caught by application.
	 * This route is created with controller and action `Index:Error` by default.
	 */
	const DEFAULT_ROUTE_NAME_ERROR = 'error';

	/**
	 * Default system route name, automatically created for not matched requests,
	 * where was not possible to found requested ctrl or template or anything else.
	 * This route is created with controller and action `Index:NotFound` by default.
	 */
	const DEFAULT_ROUTE_NAME_NOT_FOUND = 'not_found';


	/**
	 * Always keep trailing slash in requested URL or
	 * always add trailing slash into URL and redirect to it.
	 */
	const TRAILING_SLASH_ALWAYS = 1;

	/**
	 * Be absolutely benevolent for trailing slash in requested url.
	 */
	const TRAILING_SLASH_BENEVOLENT = 0;

	/**
	 * Always remove trailing slash from requested URL if there is any and 
	 * redirect to it, except homepage.
	 */
	const TRAILING_SLASH_REMOVE = -1;


	/**
	 * URL param name to define target controller.
	 */
	const URL_PARAM_CONTROLLER = 'controller';

	/**
	 * URL param name to define target controller action.
	 */
	const URL_PARAM_ACTION = 'action';

	/**
	 * URL param name to build absolute URL address.
	 */
	const URL_PARAM_ABSOLUTE = 'absolute';
	
	/**
	 * URL param name to place custom host into route 
	 * reverse pattern placeholder `%host%`.
	 */
	const URL_PARAM_HOST = 'host';
	
	/**
	 * URL param name to place custom domain into route 
	 * reverse pattern placeholder `%domain%`.
	 */
	const URL_PARAM_DOMAIN = 'domain';
	
	/**
	 * URL param name to place custom top level domain 
	 * into route reverse pattern placeholder `%tld%`.
	 */
	const URL_PARAM_TLD = 'tld';
	
	/**
	 * URL param name to place custom second level domain 
	 * into route reverse pattern placeholder `%sld%`.
	 */
	const URL_PARAM_SLD = 'sld';
	
	/**
	 * URL param name to place custom basePath into route 
	 * reverse pattern placeholder `%basePath%`.
	 */
	const URL_PARAM_BASEPATH = 'basePath';
	
	/**
	 * URL param name to place custom basePath into route 
	 * reverse pattern placeholder `%basePath%`.
	 */
	const URL_PARAM_PATH = 'path';
}