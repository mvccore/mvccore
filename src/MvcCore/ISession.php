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
 * Responsibility - session data management - starting, writing and expirations.
 * - Safe start (only once)
 *   - By `\MvcCore\Session::Start()`
 *	 - Called by `\MvcCore\Application::GetInstance()->SessionStart();`
 *		 - Called by `\MvcCore\Controller::Init();`.
 * - Session writing and closing at request end:
 *   - In `\MvcCore\Session::Close()`
 *	 - Called over `register_shutdown_function()`
 *	   from `\MvcCore::Terminate();`
 * - Session namespaces management:
 *   - Variables expiration by seconds.
 *   - Variables expiration by request hoops.
 */
interface ISession extends \MvcCore\Session\IConstants {

	/**
	 * Session safe start only once.
	 * - called by `\MvcCore\Application::GetInstance()->SessionStart();`
	 *   - called by `\MvcCore\Controller::Init();`
	 * It's free to call this function anywhere sooner for custom purposes,
	 * for example in `Bootstrap.php` by: `\MvcCore\Application::GetInstance()->SessionStart();`
	 * @return void
	 */
	public static function Start ();

	/**
	 * Get Unix epoch for current request session start moment.
	 * @return int
	 */
	public static function GetSessionStartTime ();

	/**
	 * Get static boolean about if session has been already started or not.
	 * @return bool
	 */
	public static function GetStarted ();

	/**
	 * Get session metadata about session namespaces.
	 * This method is used for debugging purposes.
	 * @return \stdClass
	 */
	public static function GetSessionMetadata ();

	/**
	 * Write and close session in `\MvcCore::Terminate();`.
	 * Serialize all metadata and call php function to write session into php session storage.
	 * (HDD, Redis, database, etc., depends on php configuration).
	 * @return void
	 */
	public static function Close ();

	/**
	 * Get new or existing MvcCore session namespace instance.
	 * If session is not started, start session.
	 * @param string $name Session namespace unique name.
	 * @return \MvcCore\Session
	 */
	public static function GetNamespace ($name = \MvcCore\ISession::DEFAULT_NAMESPACE_NAME);

	/**
	 * Set MvcCore session namespace expiration by page request(s) count.
	 * @param int $hoops
	 * @return \MvcCore\Session
	 */
	public function SetExpirationHoops ($hoops);

	/**
	 * Set MvcCore session namespace expiration by expiration seconds.
	 * Zero (`0`) means "until the browser is closed" if there is no more
	 * higher namespace expirations in whole session.
	 * @param int $seconds
	 * @return \MvcCore\Session
	 */
	public function SetExpirationSeconds ($seconds);

	/**
	 * Send `PHPSESSID` http cookie with session id hash before response body is sent.
	 * This function is always called by `\MvcCore\Application::Terminate();` at the request end.
	 * @return void
	 */
	public static function SendCookie ();

	/**
	 * Get the highest expiration in seconds for namespace with
	 * the highest expiration to set expiration for `PHPSESSID` cookie.
	 * @return int
	 */
	public static function GetSessionMaxTime ();

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
	 * Magic function triggered by: `$value = \MvcCore\Session->key;`.
	 * @param string $key
	 * @return mixed
	 */
	public function __get ($key);

	/**
	 * Magic function triggered by: `\MvcCore\Session->key = "value";`.
	 * @param string $key
	 * @param mixed $value
	 * @return void
	 */
	public function __set ($key, $value);

	/**
	 * Magic function triggered by: `isset(\MvcCore\Session->key);`.
	 * @param string $key
	 * @return bool
	 */
	public function __isset ($key);

	/**
	 * Magic function triggered by: `unset(\MvcCore\Session->key);`.
	 * @param string $key
	 * @return void
	 */
	public function __unset ($key);
}
