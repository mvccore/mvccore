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

namespace MvcCore\Response;

interface IConstants {

	/**
	 * HTTP response code 200 for OK response;
	 */
	const OK = 200;

	/**
	 * HTTP response code 301 for moved permanently redirection;
	 */
	const MOVED_PERMANENTLY = 301;

	/**
	 * HTTP response code 303 for see other redirection;
	 */
	const SEE_OTHER = 303;

	/**
	 * HTTP response code 404 for not found error;
	 */
	const NOT_FOUND = 404;

	/**
	 * HTTP response code 500 for internal server error;
	 */
	const INTERNAL_SERVER_ERROR = 500;

	/**
	 * MvcCore internal header always sent in every response.
	 */
	const HEADER_X_MVCCORE_CPU_RAM = 'X-MvcCore-Cpu-Ram';

	
	/**
	 * Default CSRF cookie name: `__MCP`.
	 * @var string
	 */
	const COOKIE_CSRF_DEFAULT_NAME = '__MCP';


	/**
	 * Cookie `SameSite` mode `None`.
	 * @var string
	 */
	const COOKIE_SAMESITE_NONE = 'None';
	
	/**
	 * Cookie `SameSite` mode `Lax`.
	 * @var string
	 */
	const COOKIE_SAMESITE_LAX = 'Lax';
	
	/**
	 * Cookie `SameSite` mode `Strict`.
	 * @var string
	 */
	const COOKIE_SAMESITE_STRICT = 'Strict';
}