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

namespace MvcCore\Interfaces;

/**
 * Responsibility - session data management - starting, writing and expirations.
 * - Safe start (only once)
 *   - By `\MvcCore\Interfaces\ISession::Start()`
 *     - Called by `\MvcCore\Application::GetInstance()->SessionStart();`
 *	     - Called by `\MvcCore\Controller::Init();`.
 * - Session writing and closing at request end:
 *   - In `\MvcCore\Interfaces\ISession::Close()`
 *     - Called over `register_shutdown_function()`
 *       from `\MvcCore::Terminate();`
 * - Session namespaces management:
 *   - Variables expiration by seconds.
 *   - Variables expiration by request hoops.
 */
interface ISession
{
	/**
	 * Metadata key in `$_SESSION` storrage.
	 * @var string
	 */
	const SESSION_METADATA_KEY = '__MC';

	/**
	 * Default session namespace name.
	 * @var string
	 */
	const DEFAULT_NAMESPACE_NAME = 'default';

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
	 * Write and close session in `\MvcCore::Terminate();`.
	 * Serialize all metadata and call php function to write session into php session storrage.
	 * (HDD, Redis, database, etc., depends on php configuration).
	 * @return void
	 */
	public static function Close ();

	/**
	 * Get new or existing MvcCore session namespace instance.
	 * @param string $name
	 * @return \MvcCore\Interfaces\ISession
	 */
	public static function & GetNamespace ($name = \MvcCore\Interfaces\ISession::DEFAULT_NAMESPACE_NAME);

	/**
	 * Set MvcCore session namespace expiration by page request(s) count.
	 * @param int $hoops
	 * @return \MvcCore\Interfaces\ISession
	 */
	public function & SetExpirationHoops ($hoops);

	/**
	 * Set MvcCore session namespace expiration by expiration seconds.
	 * @param int $seconds
	 * @return \MvcCore\Interfaces\ISession
	 */
	public function & SetExpirationSeconds ($seconds);

	/**
	 * Destroy whole session namespace in `$_SESSION` storrage
	 * and internal static storrages.
	 * @return void
	 */
	public function Destroy ();

	/**
	 * Destroy all existing session namespaces in `$_SESSION` storrage
	 * and internal static storrages, destroy whole PHP session.
	 * @return void
	 */
	public static function DestroyAll ();

	/**
	 * Magic function triggered by: `isset(\MvcCore\Interfaces\ISession->key);`.
	 * @param string $key
	 * @return bool
	 */
	public function __isset ($key);

	/**
	 * Magic function triggered by: `unset(\MvcCore\Interfaces\ISession->key);`.
	 * @param string $key
	 * @return void
	 */
	public function __unset ($key);

	/**
	 * Magic function triggered by: `$value = \MvcCore\Interfaces\ISession->key;`.
	 * @param string $key
	 * @return mixed
	 */
	public function __get ($key);

	/**
	 * Magic function triggered by: `\MvcCore\Interfaces\ISession->key = "value";`.
	 * @param string $key
	 * @param mixed $value
	 * @return void
	 */
	public function __set ($key, $value);

	/**
	 * Magic `\ArrayObject` function triggered by: `count(\MvcCore\Interfaces\ISession);`.
	 * @return int
	 */
	public function count ();
}
