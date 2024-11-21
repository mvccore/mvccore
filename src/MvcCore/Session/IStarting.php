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

interface IStarting {
	
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

}
