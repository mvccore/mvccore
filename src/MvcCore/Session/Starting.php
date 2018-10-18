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
}
