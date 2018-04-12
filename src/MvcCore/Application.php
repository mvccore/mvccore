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

require_once(__DIR__.'/Interfaces/IApplication.php');
require_once(__DIR__.'/Application/GettersSetters.php');
require_once(__DIR__.'/Application/Dispatching.php');
require_once(__DIR__.'/Application/Helpers.php');
require_once('Debug.php');
require_once('Request.php');
require_once('Response.php');
require_once('Router.php');
require_once('Tool.php');

namespace MvcCore;

/**
 * Responsibility - singleton, instancing all core classes and handling request.
 * - Global store and managing singleton application instance.
 * - Main application objects container (request, response, controller, etc.).
 * - MvcCore compile mode managing (single file mode, php, phar, or no package).
 * - Global store for all main core class names, to use them as modules,
 *   to be changed any time (request class, response class, debug class, etc.).
 * - Processing application run (`\MvcCore\Application::Run();`):
 *   - Completing request and response.
 *   - Calling pre/post handlers.
 *   - Controller/action dispatching.
 *   - Error handling and error responses.
 */
class Application implements \MvcCore\Interfaces\IApplication
{
	/**
	 * Include traits with
	 * - Application properties, getters and setters methods.
	 * - Application normal requests and error requests dispatching methods.
	 * - Application helper methods.
	 * Traits in PHP is the only option, how to get something
	 * analogicly the same as partial classes C#.
	 */
	use \MvcCore\Application\GettersSetters;
	use \MvcCore\Application\Dispatching;
	use \MvcCore\Application\Helpers;

	/***********************************************************************************
	 *                      `\MvcCore\Application` - Static Calls                      *
	 ***********************************************************************************/

	/**
	 * Static constructor (called INTERNALY - do not call this in application).
	 * It initializes application compilation mode before:
	 * `\MvcCore\Application::GetInstance()->Run();`.
	 * @return void
	 */
	public static function StaticInit () {
		$instance = static::GetInstance();
		$instance->microtime = microtime(TRUE);
		if (is_null($instance->compiled)) {
			$compiled = static::NOT_COMPILED;
			if (strpos(__FILE__, 'phar://') === 0) {
				$compiled = static::COMPILED_PHAR;
			} else if (class_exists('\Packager_Php_Wrapper')) {
				$compiled = constant('\Packager_Php_Wrapper::FS_MODE');
			}
			$instance->compiled = $compiled;
		}
	}

	/**
	 * Returns singleton `\MvcCore\Application` instance as reference.
	 * @return \MvcCore\Application
	 */
	public static function & GetInstance () {
		if (!static::$instance) static::$instance = new static();
		return static::$instance;
	}
}
Application::StaticInit();
