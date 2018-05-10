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

//include_once(__DIR__.'/Interfaces/IApplication.php');
//include_once(__DIR__.'/Application/GettersSetters.php');
//include_once(__DIR__.'/Application/Dispatching.php');
//include_once(__DIR__.'/Application/Helpers.php');

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
	 * Returns singleton `\MvcCore\Application` instance as reference.
	 * @return \MvcCore\Application
	 */
	public static function & GetInstance () {
		if (!static::$instance) static::$instance = new static();
		return static::$instance;
	}
}
