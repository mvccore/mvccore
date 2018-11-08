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
 * Responsibility - session data management - starting, writing and expirations.
 * - Safe start (only once)
 *   - By `\MvcCore\ISession::Start()`
 *	 - Called by `\MvcCore\Application::GetInstance()->SessionStart();`
 *		 - Called by `\MvcCore\Controller::Init();`.
 * - Session writing and closing at request end:
 *   - In `\MvcCore\ISession::Close()`
 *	 - Called over `register_shutdown_function()`
 *	   from `\MvcCore::Terminate();`
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
	 * Number of seconds for 1 minute (60).
	 */
	const EXPIRATION_SECONDS_MINUTE	= 60;

	/**
	 * Number of seconds for 1 hour (60 * 60 = 3600).
	 */
	const EXPIRATION_SECONDS_HOUR	= 3600;

	/**
	 * Number of seconds for 1 day (60 * 60 * 24 = 86400).
	 */
	const EXPIRATION_SECONDS_DAY	= 86400;

	/**
	 * Number of seconds for 1 week (60 * 60 * 24 * 7 = 3600).
	 */
	const EXPIRATION_SECONDS_WEEK	= 604800;

	/**
	 * Number of seconds for 1 month, 30 days (60 * 60 * 24 * 30 = 3600).
	 */
	const EXPIRATION_SECONDS_MONTH	= 2592000;

	/**
	 * Number of seconds for 1 year, 365 days (60 * 60 * 24 * 365 = 3600).
	 */
	const EXPIRATION_SECONDS_YEAR	= 31536000;


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
	 * Get unix epoch for current request session start moment.
	 * @return int
	 */
	public static function GetSessionStartTime ();

	/**
	 * Get session metadata about session namespaces.
	 * This method is used for debuging purposses.
	 * @return \stdClass
	 */
	public static function GetSessionMetadata ();

	/**
	 * Write and close session in `\MvcCore::Terminate();`.
	 * Serialize all metadata and call php function to write session into php session storrage.
	 * (HDD, Redis, database, etc., depends on php configuration).
	 * @return void
	 */
	public static function Close ();

	/**
	 * Get new or existing MvcCore session namespace instance.
	 * If session is not started, start session.
	 * @param string $name Session namespace unique name.
	 * @return \MvcCore\ISession
	 */
	public static function & GetNamespace ($name = \MvcCore\ISession::DEFAULT_NAMESPACE_NAME);

	/**
	 * Set MvcCore session namespace expiration by page request(s) count.
	 * @param int $hoops
	 * @return \MvcCore\ISession
	 */
	public function & SetExpirationHoops ($hoops);

	/**
	 * Set MvcCore session namespace expiration by expiration seconds.
	 * Zero (`0`) means "until the browser is closed" if there is no more
	 * higher namespace expirations in whole session.
	 * @param int $seconds
	 * @return \MvcCore\ISession
	 */
	public function & SetExpirationSeconds ($seconds);

	/**
	 * Send `PHPSESSID` http cookie with session id hash before response body is sent.
	 * This function is always called by `\MvcCore\Application::Terminate();` at the request end.
	 * @return void
	 */
	public static function SendCookie ();

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
	 * Magic function triggered by: `isset(\MvcCore\ISession->key);`.
	 * @param string $key
	 * @return bool
	 */
	public function __isset ($key);

	/**
	 * Magic function triggered by: `unset(\MvcCore\ISession->key);`.
	 * @param string $key
	 * @return void
	 */
	public function __unset ($key);

	/**
	 * Magic function triggered by: `$value = \MvcCore\ISession->key;`.
	 * @param string $key
	 * @return mixed
	 */
	public function __get ($key);

	/**
	 * Magic function triggered by: `\MvcCore\ISession->key = "value";`.
	 * @param string $key
	 * @param mixed $value
	 * @return void
	 */
	public function __set ($key, $value);

	/**
	 * Magic `\ArrayObject` function triggered by: `count(\MvcCore\ISession);`.
	 * @return int
	 */
	public function count ();

	/**
	 * Return new iterator from the internal data store 
	 * to use session namespace instance in for each cycle.
	 * Example: `foreach ($sessionNamespace as $key => $value) { var_dump([$key, $value]); }`
	 * @return \ArrayIterator|\Traversable
	 */
	public function getIterator ();

	/**
	 * Set the value at the specified index.
	 * Example: `$sessionNamespace['any'] = 'thing';`
	 * @param mixed $offset 
	 * @param mixed $value 
	 */
	public function offsetSet ($offset, $value);

	/**
	 * Get the value at the specified index.
	 * Example: `$thing = $sessionNamespace['any'];`
	 * @param mixed $offset 
	 * @param mixed $value 
	 */
    public function offsetGet ($offset);

    /**
     * Return whether the requested index exists.
	 * Example: `isset($sessionNamespace['any']);`
     * @param mixed $offset 
     * @return bool
     */
    public function offsetExists ($offset);

    /**
     * Unset the value at the specified index.
	 * Example: `unset($sessionNamespace['any']);`
     * @param mixed $offset 
     */
    public function offsetUnset ($offset);
}
