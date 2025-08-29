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

namespace MvcCore\Session;

interface IClosing {
	
	/**
	 * Write and close session in `\MvcCore::Terminate();`.
	 * Serialize all metadata and call php function to write session into php session storage.
	 * (HDD, Redis, database, etc., depends on php configuration).
	 * @return void
	 */
	public static function Close ();

	/**
	 * Send `PHPSESSID` http(s) cookie with session id hash before response body is sent.
	 * This function is always called by `\MvcCore\Application::Terminate();` at the request end.
	 * @return void
	 */
	public static function SendSessionIdCookie ();

	/**
	 * Get the highest expiration in seconds for namespace with
	 * the highest expiration to set expiration for `PHPSESSID` cookie.
	 * @return int
	 */
	public static function GetSessionMaxTime ();

	/**
	 * Send `__MCS` http(s) cookie as security protection againts session fixation
	 * and CSRF atacks before response body is sent. This function is always 
	 * called by `\MvcCore\Application::Terminate();` at the request end.
	 * @return void
	 */
	public static function SendSecurityCookie ();

	/**
	 * Get security protection cookie expiration in seconds, `0` by default,
	 * means "until the browser is closed". If there is found any Authentication 
	 * class installed, result is returned by authorization expiration.
	 * @return int
	 */
	public static function GetSessionSecurityMaxTime ();

}
