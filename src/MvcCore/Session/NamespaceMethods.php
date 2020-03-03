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

namespace MvcCore\Session;

trait NamespaceMethods
{
	/**
	 * Get new or existing MvcCore session namespace instance.
	 * If session is not started, start session.
	 * @param string $name Session namespace unique name.
	 * @return \MvcCore\Session|\MvcCore\ISession
	 */
	public static function GetNamespace (
		$name = \MvcCore\ISession::DEFAULT_NAMESPACE_NAME
	) {
		if (!static::GetStarted()) 
			static::Start();
		if (!isset(static::$instances[$name])) 
			static::$instances[$name] = new static($name);
		return static::$instances[$name];
	}

	/**
	 * Set MvcCore session namespace expiration by page request(s) count.
	 * @param int $hoops
	 * @return \MvcCore\Session
	 */
	public function SetExpirationHoops ($hoops) {
		/** @var $this \MvcCore\Session */
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
	public function SetExpirationSeconds ($seconds = 0) {
		/** @var $this \MvcCore\Session */
		if ($seconds > 0) 
			static::$meta->expirations[$this->__name] = static::$sessionStartTime + $seconds;
		return $this;
	}

	

	/**
	 * Destroy whole session namespace in `$_SESSION` storage
	 * and internal static storages.
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
	 * Destroy all existing session namespaces in `$_SESSION` storage
	 * and internal static storages, destroy whole PHP session.
	 * @return void
	 */
	public static function DestroyAll () {
		session_destroy();
		$_SESSION = NULL;
		static::$started = FALSE;
		$response = \MvcCore\Application::GetInstance()->GetResponse();
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
}
