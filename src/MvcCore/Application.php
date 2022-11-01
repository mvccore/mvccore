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
 * @inheritDocs
 */
class Application implements \MvcCore\IApplication {

	/**
	 * MvcCore - version:
	 * Comparison by PHP function `version_compare();`.
	 * @see http://php.net/manual/en/function.version-compare.php
	 */
	const VERSION = '5.1.45';

	/**
	 * Include traits with
	 * - Application properties.
	 * - Application getters and setters methods.
	 * - Application normal requests and error requests dispatching methods.
	 * - Application helper methods.
	 * Traits in PHP is the only option, how to get something
	 * analogous the same as partial classes C#.
	 */
	use \MvcCore\Application\Props;
	use \MvcCore\Application\GettersSetters;
	use \MvcCore\Application\Dispatching;
	use \MvcCore\Application\Helpers;

	/***********************************************************************************
	 *                      `\MvcCore\Application` - Static Calls                      *
	 ***********************************************************************************/

	/**
	 * @inheritDocs
	 * @return \MvcCore\Application
	 */
	public static function GetInstance () {
		if (self::$instance === NULL) self::$instance = new static();
		return self::$instance;
	}

	/**
	 * Its not possible to create application instance like:
	 * `$app = new Application;`. Use: `Application::GetInstance();` instead.
	 * @return void
	 */
	protected function __construct () {
	}
}