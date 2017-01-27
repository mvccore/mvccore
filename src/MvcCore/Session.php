<?php

/**
 * MvcCore
 *
 * This source file is subject to the BSD 3 License
 * For the full copyright and license information, please view 
 * the LICENSE.md file that are distributed with this source code.
 *
 * @copyright	Copyright (c) 2016 Tom Flídr (https://github.com/mvccore/mvccore)
 * @license		https://mvccore.github.io/docs/mvccore/4.0.0/LICENCE.md
 */

namespace MvcCore;

/**
 * Core session:
 * - session safe starting
 * - session writing and closing
 *   - by registered shutdown function
 *   - \MvcCore\Session::Close() - how to register the handler
 *   - close handler called registered by \MvcCore::Terminate();
 * - session namespaces management
 *   - variables expiration by seconds
 *   - variables expiration by request hoops
 */
class Session extends \ArrayObject {
	/**
	 * Metadata key in $_SESSION
	 * @var string
	 */
	const SESSION_METADATA_KEY = '__MC';

	/**
	 * Default namespace name
	 * @var string
	 */
	protected $__name = 'default';

	/**
	 * Boolean telling about if session is started
	 * @var bool
	 */
	protected static $started = FALSE;

	/**
	 * Metadata array or stdClass with elements: names, hoops and expirations - all items are arrays
	 * @var array|\stdClass
	 */
	protected static $meta = array();

	/**
	 * Array of created \MvcCore\Session instances, keys in array are session namespaces names
	 * @var array
	 */
	protected static $instances = array();

	/**
	 * Start session, called in Controller::Init();
	 * It's also possible to call this anywhere sooner, for example in bootstrap by: \MvcCore::SessionStart();
	 * @return void
	 */
	public static function Start () {
		if (static::$started) return;
		if (!\MvcCore::GetInstance()->GetRequest()->IsAppRequest()) return;
		$sessionNotStarted = function_exists('session_status') ? session_status() == PHP_SESSION_NONE : session_id() == '' ;
		if ($sessionNotStarted) {
			session_start();
			static::setUpMeta();
			static::setUpData();
		}
		static::$started = TRUE;
	}

	/**
	 * Set up session metadata about namespaces names, hoops and expirations
	 * @return void
	 */
	protected static function setUpMeta () {
		$metaKey = static::SESSION_METADATA_KEY;
		$meta = array();
		if (isset($_SESSION[$metaKey])) {
			$meta = @unserialize($_SESSION[$metaKey]);
		}
		if (!$meta) {
			$meta = array(
				'names'			=> array(),
				'hoops'			=> array(),
				'expirations'	=> array(),
			);
		}
		static::$meta = (object) $meta;
	}

	/**
	 * Set up namespaces data - only if they has not expired
	 * @return void
	 */
	protected static function setUpData () {
		$hoops = & static::$meta->hoops;
		$names = & static::$meta->names;
		$expirations = & static::$meta->expirations;
		foreach ($hoops as $name => $hoop) {
			$hoops[$name] -= 1;
		}
		$now = time();
		foreach ($names as $name => $one) {
			$unset = array();
			if (isset($hoops[$name])) {
				if ($hoops[$name] < 0) $unset[] = 'hoops';
			}
			if (isset($expirations[$name])) {
				if ($expirations[$name] < $now) $unset[] = 'expirations';
			}
			if ($unset) {
				$currentErrRepLevels = error_reporting();
				error_reporting(0);
				foreach ($unset as $unsetKey) {
					if (isset(static::$meta->$unsetKey) && isset(static::$meta->$unsetKey[$name])) 
						unset(static::$meta->$unsetKey[$name]);
				}
				error_reporting($currentErrRepLevels);
				unset($names[$name]);
				unset($_SESSION[$name]);
			}
		}
	}

	/**
	 * Write and close session in \MvcCore::Terminate();
	 * Serialize all metadata and call php function to write session.
	 * @return void
	 */
	public static function Close () {
		register_shutdown_function(function () {
			foreach (static::$instances as & $instance) {
				if (count($instance) === 0) $instance->Destroy();
			}
			$metaKey = static::SESSION_METADATA_KEY;
			$_SESSION[$metaKey] = serialize(static::$meta);
			@session_write_close();
		});
	}

	/**
	 * Get new or existed session namespace
	 * @param string $name 
	 * @return \MvcCore\Session
	 */
	public static function & GetNamespace ($name = 'default') {
		if (!isset(static::$instances[$name])) {
			static::$instances[$name] = new static($name);
		}
		return static::$instances[$name];
	}

	/**
	 * Get new or existed session namespace
	 * @param string $name 
	 * @return \MvcCore\Session
	 */
	public function __construct ($name = 'default') {
		if (!static::$started) static::Start();
		$this->__name = $name;
		static::$meta->names[$name] = 1;
		if (!isset($_SESSION[$name])) $_SESSION[$name] = array();
		static::$instances[$name] = $this;
	}

	/**
	 * Set expiration page requests count
	 * @param int $hoops
	 * @return \MvcCore\Session
	 */
	public function SetExpirationHoops ($hoops) {
		static::$meta->hoops[$this->__name] = $hoops;
		return $this;
	}

	/**
	 * Set expiration seconds
	 * @param int $hoops
	 * @return \MvcCore\Session
	 */
	public function SetExpirationSeconds ($seconds) {
		static::$meta->expirations[$this->__name] = time() + $seconds;
		return $this;
	}

	/**
	 * Destroy whole session namespace
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
	 * Magic function triggered by: isset($session->key);
	 * @param string $key 
	 * @return bool
	 */
	public function __isset ($key) {
		return isset($_SESSION[$this->__name][$key]);
	}

	/**
	 * Magic function triggered by: unset($session->key);
	 * @param string $key 
	 * @return void
	 */
	public function __unset ($key) {
		$name = $this->__name;
		if (isset($_SESSION[$name][$key])) unset($_SESSION[$name][$key]);
	}

	/**
	 * Magic function triggered by: $value = $session->key;
	 * @param string $key 
	 * @return mixed
	 */
	public function __get ($key) {
		$name = $this->__name;
		if (isset($_SESSION[$name][$key])) return $_SESSION[$name][$key];
		return NULL;
	}

	/**
	 * Magic function triggered by: $session->key = "value";
	 * @param string $key 
	 * @param mixed $value
	 * @return void
	 */
	public function __set ($key, $value) {
		$_SESSION[$this->__name][$key] = $value;
	}

	/**
	 * ArrayObject function triggered by: count($session;
	 * @return int
	 */
	public function count () {
		return count($_SESSION[$this->__name]);
	}
}