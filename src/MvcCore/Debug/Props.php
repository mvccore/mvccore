<?php

/**
* MvcCore
*
* This source file is subject to the BSD 3 License
* For the full copyright and license information, please view
* the LICENSE.md file that are distributed with this source code.
*
* @copyright	Copyright (c) 2016 Tom Flídr (https://github.com/mvccore/mvccore)
* @license		https://mvccore.github.io/docs/mvccore/5.0.0/LICENSE.md
*/

namespace MvcCore\Debug;

/**
 * @mixin \MvcCore\Debug
 */
trait Props {

	/**
	 * Email recipient to send information about exceptions or errors,
	 * `"admin@localhost"` by default. This property is not used in core debug
	 * class, you need to instal extension `mvccore/ext-debug-tracy`.
	 * @var string
	 */
	public static $EmailRecepient = 'admin@localhost';

	/**
	 * Relative path from app root to store any log information,
	 * `"~/Var/Logs"` by default.
	 * @var string
	 */
	public static $LogDirectory = '~/Var/Logs';

	/**
	 * Initialize global development shorthands.
	 * @var callable
	 */
	public static $InitGlobalShortHands = [];

	/**
	 * Semaphore to execute `\MvcCore\Debug::Init();` method only once.
	 * `TRUE` on `dev` environment, `FALSE` if any other environment detected.
	 * @var bool
	 */
	protected static $debugging = NULL;

	/**
	 * All PHP and user notices, warnings and errors are automatically turned
	 * and thrown as `\ErrorException`, initialized into `TRUE` in `Init()`
	 * function by default.
	 * @var bool|NULL
	 */
	protected static $strictExceptionsMode = NULL;

	/**
	 * Error levels to turn into exceptions by default.
	 * @var \int[]
	 */
	protected static $strictExceptionsModeDefaultLevels = [
		E_ERROR, E_RECOVERABLE_ERROR,
		E_CORE_ERROR, E_USER_ERROR,
		E_WARNING, E_CORE_WARNING, E_USER_WARNING
	];

	/**
	 * Previous error handler before strict exceptions mode is defined.
	 * @var callable|NULL
	 */
	protected static $prevErrorHandler = NULL;

	/**
	 * System config debug configuration root node name (`debug` by default)
	 * and all it's properties names.
	 * @var string
	 */
	protected static $systemConfigDebugProps = [
		'sectionName'		=> 'debug',				// debug section root node
		'emailRecepient'	=> 'emailRecepient',	// debug email, `admin@localhost` by default
		'logDirectory'		=> 'logDirectory',		// log directory, `/Var/Logs` by default
		'strictExceptions'	=> 'strictExceptions',	// strict exceptions mode, `TRUE` by default
	];

	/**
	 * Loaded system config debug section values.
	 * @var array|NULL
	 */
	protected static $systemConfigDebugValues = NULL;

	/**
	 * Debugging and logging handlers, this should be customized in extended class.
	 * @var array
	 */
	protected static $handlers = [
		'timer'				=> 'timerHandler',
		'dump'				=> 'dumpHandler',
		'barDump'			=> 'dumpHandler',
		'log'				=> 'dumpHandler',
		'exceptionHandler'	=> 'exceptionHandler',
		'shutdownHandler'	=> 'ShutdownHandler',
	];

	/**
	 * Store for printed dumps by output buffering to send it at response end.
	 * @var array
	 */
	protected static $dumps = [];

	/**
	 * Store timers start points.
	 * @var array
	 */
	protected static $timers = [];

	/**
	 * `TRUE` for configured debug class as `\MvcCore\Debug`,
	 * `FALSE` for any other configured extension.
	 * @var bool
	 */
	protected static $originalDebugClass = TRUE;

	/**
	 * `TRUE` if debug class is MvcCore original debug class and
	 * if logs directory has been already initialized.
	 * @var bool
	 */
	protected static $logDirectoryInitialized = FALSE;

	/**
	 * Reference to singleton instance in `\MvcCore\Application::GetInstance();`.
	 * @var \MvcCore\Application
	 */
	protected static $app = NULL;

	/**
	 * Reference to `\MvcCore\Application::GetInstance()->GetRequest()->GetStartTime();`.
	 * @var float
	 */
	protected static $requestBegin = 0.0;
}
