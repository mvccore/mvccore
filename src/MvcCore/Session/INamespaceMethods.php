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

interface INamespaceMethods {
	
	/**
	 * Get new or existing MvcCore session namespace instance.
	 * If session is not started, start session.
	 * @param  string $name Session namespace unique name.
	 * @return \MvcCore\Session
	 */
	public static function GetNamespace ($name = \MvcCore\ISession::DEFAULT_NAMESPACE_NAME);
	
	/**
	 * Set MvcCore session namespace expiration by page request(s) count.
	 * @param  int $hoopsCount           Requests count.
	 * @param  int $ignoredRequestsFlags Ignored requests flags, 1022 by default.
	 * @return \MvcCore\Session
	 */
	public function SetExpirationHoops ($hoopsCount, $ignoredRequestsFlags = \MvcCore\ISession::EXPIRATION_HOOPS_IGNORE_DEFAULT);

	/**
	 * Set MvcCore session namespace expiration by expiration seconds.
	 * Zero (`0`) means "until the browser is closed" if there is no more
	 * higher namespace expirations in whole session.
	 * @param  int $seconds
	 * @return \MvcCore\Session
	 */
	public function SetExpirationSeconds ($seconds);
	
	/**
	 * Destroy whole session namespace in `$_SESSION` storage
	 * and internal static storages.
	 * @return void
	 */
	public function Destroy ();

	/**
	 * Destroy all existing session namespaces in `$_SESSION` storage
	 * and internal static storages, destroy whole PHP session.
	 * @return void
	 */
	public static function DestroyAll ();

	/**
	 * Return CSRF protection session namespace with secret hash.
	 * @return \MvcCore\Session
	 */
	public static function GetSecurityNamespace ();

}
