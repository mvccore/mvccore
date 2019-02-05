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

trait Starting
{
	/**
	 * Session safe start only once.
	 * - called by `\MvcCore\Application::GetInstance()->SessionStart();`
	 *   - called by `\MvcCore\Controller::Init();`
	 * It's free to call this function anywhere sooner for custom purposes,
	 * for example in `Bootstrap.php` by: `\MvcCore\Application::GetInstance()->SessionStart();`
	 * @return void
	 */
	public static function Start (& $session = []) {
		$started = static::GetStarted();
		if ($started) {
			$req = self::$req ?: self::$req = & \MvcCore\Application::GetInstance()->GetRequest();
			if ($req->IsInternalRequest() === TRUE)
				return;
		}
		static::$started = session_start();
		static::$sessionStartTime = time();
		static::$sessionMaxTime = static::$sessionStartTime;
		static::setUpMeta();
		static::setUpData();
	}

	/**
	 * Get Unix epoch for current request session start moment.
	 * This method is used for debugging purposes.
	 * @return int
	 */
	public static function GetSessionStartTime () {
		return static::$sessionStartTime;
	}

	/**
	 * Get static boolean about if session has been already started or not.
	 * @return bool
	 */
	public static function GetStarted () {
		if (static::$started === NULL) {
			$req = self::$req ?: self::$req = & \MvcCore\Application::GetInstance()->GetRequest();
			if (!$req->IsCli()) {
				$alreadyStarted = session_status() === PHP_SESSION_ACTIVE && session_id() !== '';
				if ($alreadyStarted) {
					// if already started but `static::$started` property is `NULL`:
					static::$sessionStartTime = time();
					static::$sessionMaxTime = static::$sessionStartTime;
					static::setUpMeta();
					static::setUpData();
				}
				static::$started = $alreadyStarted;
			}
		}
		return static::$started;
	}
}
