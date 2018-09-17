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

//include_once(__DIR__ . '/Interfaces/ISession.php');
//include_once('Application.php');
//include_once('Request.php');

/**
 * Responsibility - session data management - starting, writing and expirations.
 * - Safe start (only once)
 *   - By `\MvcCore\Interfaces\ISession::Start()`
 *	 - Called by `\MvcCore\Application::GetInstance()->SessionStart();`
 *		 - Called by `\MvcCore\Controller::Init();`.
 * - Session writing and closing at request end:
 *   - In `\MvcCore\Interfaces\ISession::Close()`
 *	 - Called over `register_shutdown_function()`
 *	   from `\MvcCore::Terminate();`
 * - Session namespaces management:
 *   - Variables expiration by seconds.
 *   - Variables expiration by request hoops.
 */
class Session extends \ArrayObject implements Interfaces\ISession
{
	/**
	 * Default session namespace name.
	 * @var string
	 */
	protected $__name = \MvcCore\Interfaces\ISession::DEFAULT_NAMESPACE_NAME;

	/**
	 * Static boolean about if session has been allready started or not.
	 * @var bool
	 */
	protected static $started = FALSE;

	/**
	 * Metadata array or stdClass with all MvcCore namespaces metadata information:
	 * - `"names"`			=> Array with all namespace records names.
	 * - `"hoops"`			=> Array with all namespace records page requests count to expire.
	 * - `"expirations"`	=> Array with all records expiration times.
	 * This metadata arrays are decoded from `$_SESSION` storrage only once at in session start.
	 * @var array|\stdClass
	 */
	protected static $meta = [
		/** @var \string[] Array with all namespace records names. */
		'names'			=> [],
		/** @var \int[] Array with all namespace records page requests count to expire. Keyed by namespace names. */
		'hoops'			=> [],
		/** @var \int[] Array with all records expiration times. Keyed by namespace names. */
		'expirations'	=> [],
	];

	/**
	 * Array of created `\MvcCore\Interfaces\ISession` instances,
	 * keys in this array storrage are session namespaces names.
	 * @var \MvcCore\Interfaces\ISession[]
	 */
	protected static $instances = [];

	/**
	 * Unix epoch for current request session start moment.
	 * @var int
	 */
	protected static $sessionStartTime = 0;

	/**
	 * The highest expiration in seconds for namespace with
	 * the highest expiration to set expiration for `PHPSESSID` cookie.
	 * @var int
	 */
	protected static $sessionMaxTime = 0;

	/**
	 * Session safe start only once.
	 * - called by `\MvcCore\Application::GetInstance()->SessionStart();`
	 *   - called by `\MvcCore\Controller::Init();`
	 * It's free to call this function anywhere sooner for custom purposes,
	 * for example in `Bootstrap.php` by: `\MvcCore\Application::GetInstance()->SessionStart();`
	 * @return void
	 */
	public static function Start (& $session = []) {
		if (static::$started) return;
		if (\MvcCore\Application::GetInstance()->GetRequest()->IsInternalRequest() === TRUE) return;
		$sessionNotStarted = function_exists('session_status')
			? session_status() == PHP_SESSION_NONE
			: session_id() == '' ;
		if ($sessionNotStarted) {
			session_start();
			static::$sessionStartTime = time();
			static::$sessionMaxTime = static::$sessionStartTime;
			static::setUpMeta();
			static::setUpData();
		}
		static::$started = TRUE;
	}

	/**
	 * Get unix epoch for current request session start moment.
	 * This method is used for debuging purposses.
	 * @return int
	 */
	public static function GetSessionStartTime () {
		return static::$sessionStartTime;
	}

	/**
	 * Get the highest expiration in seconds for namespace with
	 * the highest expiration to set expiration for `PHPSESSID` cookie.
	 * @return int
	 */
	public static function GetSessionMaxTime () {
		static::$sessionMaxTime = static::$sessionStartTime;
		foreach (static::$meta->expirations as $sessionNamespaceName => $expiration) {
			if ($expiration > static::$sessionMaxTime)
				static::$sessionMaxTime = $expiration;
		}
		return static::$sessionMaxTime;
	}

	/**
	 * Get session metadata about session namespaces.
	 * This method is used for debuging purposses.
	 * @return \stdClass
	 */
	public static function GetSessionMetadata () {
		return static::$meta;
	}

	/**
	 * Set up MvcCore session namespaces metadata
	 * about namespaces names, hoops and expirations.
	 * Called only once at session start by `\MvcCore\Interfaces\ISession::Start();`.
	 * @return void
	 */
	protected static function setUpMeta () {
		$metaKey = static::SESSION_METADATA_KEY;
		$meta = [];
		if (isset($_SESSION[$metaKey]))
			$meta = @unserialize($_SESSION[$metaKey]);
		if (!$meta)
			$meta = [
				'names'			=> [],
				'hoops'			=> [],
				'expirations'	=> [],
			];
		$meta = (object) $meta;
		static::$meta = & $meta;
	}

	/**
	 * Set up namespaces data - only if data has not been expired yet,
	 * if data has been expired, unset data from
	 * `\MvcCore\Interfaces\ISession::$meta` and `$_SESSION` storrage.
	 * Called only once at session start by `\MvcCore\Interfaces\ISession::Start();`.
	 * @return void
	 */
	protected static function setUpData () {
		$hoops = & static::$meta->hoops;
		$names = & static::$meta->names;
		$expirations = & static::$meta->expirations;
		foreach ($hoops as $name => $hoop) {
			$hoops[$name] -= 1;
		}
		foreach ($names as $name => $one) {
			$unset = [];
			if (isset($hoops[$name])) {
				if ($hoops[$name] < 0) $unset[] = 'hoops';
			}
			if (isset($expirations[$name])) {
				$expiration = $expirations[$name];
				if ($expiration < static::$sessionStartTime) {
					$unset[] = 'expirations';
				} else if ($expiration > static::$sessionMaxTime) {
					static::$sessionMaxTime = $expiration;
				}
			}
			if ($unset) {
				$currentErrRepLevels = error_reporting();
				error_reporting(0);
				foreach ($unset as $unsetKey) {
					if (isset(static::$meta->{$unsetKey}) && isset(static::$meta->{$unsetKey}[$name])) {
						unset(static::$meta->{$unsetKey}[$name]);
					}
				}
				error_reporting($currentErrRepLevels);
				unset($names[$name]);
				unset($_SESSION[$name]);
			}
		}
	}

	/**
	 * Write and close session in `\MvcCore::Terminate();`.
	 * Serialize all metadata and call php function to write session into php session storrage.
	 * (HDD, Redis, database, etc., depends on php configuration).
	 * @return void
	 */
	public static function Close () {
		register_shutdown_function(function () {
			foreach (static::$instances as & $instance)
				if (count((array) $_SESSION[$instance->__name]) === 0)
					// if there is nothing in namespace - destroy it. It's useless.
					$instance->Destroy();
			$metaKey = static::SESSION_METADATA_KEY;
			$_SESSION[$metaKey] = serialize(static::$meta);
			@session_write_close();
		});
	}

	/**
	 * Get new or existing MvcCore session namespace instance.
	 * If session is not started, start session.
	 * @param string $name Session namespace unique name.
	 * @return \MvcCore\Session
	 */
	public static function & GetNamespace (
		$name = \MvcCore\Interfaces\ISession::DEFAULT_NAMESPACE_NAME
	) {
		if (!static::$started) static::Start();
		if (!isset(static::$instances[$name])) {
			static::$instances[$name] = new static($name);
		}
		return static::$instances[$name];
	}

	/**
	 * Get new or existing MvcCore session namespace instance.
	 * @param string $name
	 * @return \MvcCore\Session
	 */
	public function __construct ($name = \MvcCore\Interfaces\ISession::DEFAULT_NAMESPACE_NAME) {
		if (!static::$started) static::Start();
		$this->__name = $name;
		static::$meta->names[$name] = 1;
		if (!isset($_SESSION[$name])) $_SESSION[$name] = [];
		static::$instances[$name] = $this;
	}

	/**
	 * Set MvcCore session namespace expiration by page request(s) count.
	 * @param int $hoops
	 * @return \MvcCore\Session
	 */
	public function & SetExpirationHoops ($hoops) {
		static::$meta->hoops[$this->__name] = $hoops;
		return $this;
	}

	/**
	 * Set MvcCore session namespace expiration by expiration seconds.
	 * Zero (`0`) means "until the browser is closed" if there is no more
	 * higher namespace expirations in whole session.
	 * @param int $seconds
	 * @return \MvcCore\Session
	 */
	public function & SetExpirationSeconds ($seconds = 0) {
		if ($seconds > 0) 
			static::$meta->expirations[$this->__name] = static::$sessionStartTime + $seconds;
		return $this;
	}

	/**
	 * Send `PHPSESSID` http cookie with session id hash before response body is sent.
	 * This function is always called by `\MvcCore\Application::Terminate();` at the request end.
	 * @return void
	 */
	public static function SendCookie () {
		$maxExpiration = static::GetSessionMaxTime();
		$response = & \MvcCore\Application::GetInstance()->GetResponse();
		if (!$response->IsSent()) {
			$params = (object) session_get_cookie_params();
			$response->SetCookie(
				session_name(),
				session_id(),
				($maxExpiration > static::$sessionStartTime
					? (static::$sessionMaxTime - static::$sessionStartTime)
					: (isset($params->lifetime) ? $params->lifetime : 0)),
				$params->path,
				$params->domain,
				$params->secure,
				TRUE
			);
		}
	}

	/**
	 * Destroy whole session namespace in `$_SESSION` storrage
	 * and internal static storrages.
	 * @return void
	 */
	public function Destroy () {
		$name = $this->__name;
		$names = & static::$meta->names;
		$hoops = & static::$meta->hoops;
		$expirations = & static::$meta->expirations;
		$instances = & static::$instances;
		if (isset($names[$name])) unset($names[$name]);
		if (isset($hoops[$name])) unset($hoops[$name]);
		if (isset($expirations[$name])) unset($expirations[$name]);
		if (isset($_SESSION[$name])) unset($_SESSION[$name]);
		if (isset($instances[$name])) unset($instances[$name]);
	}

	/**
	 * Destroy all existing session namespaces in `$_SESSION` storrage
	 * and internal static storrages, destroy whole PHP session.
	 * @return void
	 */
	public static function DestroyAll () {
		session_destroy();
		$_SESSION = NULL;
		static::$started = false;
		$response = & \MvcCore\Application::GetInstance()->GetResponse();
		if (!$response->IsSent()) {
			$params = (object) session_get_cookie_params();
			$response->DeleteCookie(
				session_name(),
				$params->path,
				$params->domain,
				$params->secure
			);
		}
	}

	/**
	 * Magic function triggered by: `isset(\MvcCore\Interfaces\ISession->key);`.
	 * @param string $key
	 * @return bool
	 */
	public function __isset ($key) {
		return isset($_SESSION[$this->__name][$key]);
	}

	/**
	 * Magic function triggered by: `unset(\MvcCore\Interfaces\ISession->key);`.
	 * @param string $key
	 * @return void
	 */
	public function __unset ($key) {
		$name = $this->__name;
		if (isset($_SESSION[$name][$key])) unset($_SESSION[$name][$key]);
	}

	/**
	 * Magic function triggered by: `$value = \MvcCore\Interfaces\ISession->key;`.
	 * @param string $key
	 * @return mixed
	 */
	public function __get ($key) {
		$name = $this->__name;
		if (isset($_SESSION[$name][$key])) return $_SESSION[$name][$key];
		return NULL;
	}

	/**
	 * Magic function triggered by: `\MvcCore\Interfaces\ISession->key = "value";`.
	 * @param string $key
	 * @param mixed $value
	 * @return void
	 */
	public function __set ($key, $value) {
		$_SESSION[$this->__name][$key] = $value;
	}

	/**
	 * Print all about current session namespace instance for debug purposses.
	 * @return array
	 */
	public function __debugInfo () {
		$hoops = isset(static::$meta->hoops[$this->__name])
			? static::$meta->hoops[$this->__name]
			: NULL;
		$expiration = isset(static::$meta->expirations[$this->__name])
			? static::$meta->expirations[$this->__name]
			: NULL;
		return [
			'name'					=> $this->__name,
			'expirationSeconds'		=> date('D, d M Y H:i:s', $expiration),
			'expirationHoops'		=> $hoops,
			'values'				=> $_SESSION[$this->__name],
		];
	}

	/**
	 * Magic `\ArrayObject` function triggered by: `count(\MvcCore\Interfaces\ISession);`.
	 * @return int
	 */
	public function count () {
		return count((array) $_SESSION[$this->__name]);
	}
}
