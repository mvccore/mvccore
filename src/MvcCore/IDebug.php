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

/**
 * Responsibility - any development and logging messages and exceptions 
 * printing and logging.
 * - Printing any variable in content body.
 * - Printing any variable in browser debug bar.
 * - Caught exceptions printing.
 * - Any variables and caught exceptions file logging.
 * - Time printing.
 */
interface IDebug
{
	/**
	 * Logging levels and file names.
	 */
	const
		DEBUG = 'debug',
		INFO = 'info',
		WARNING = 'warning',
		ERROR = 'error',
		EXCEPTION = 'exception',
		CRITICAL = 'critical',
		JAVASCRIPT = 'javascript';

	/**
	 * Initialize debugging and logging, once only.
	 * @param bool $forceDevelopmentMode	If defined as `TRUE` or `FALSE`,
	 *										debugging mode will be set not 
	 *										by config but by this value.
	 * @return void
	 */
	public static function Init ($forceDevelopmentMode = NULL);

	/**
	 * Starts/stops stopwatch.
	 * @param  string $name Time pointer name.
	 * @return float		Elapsed seconds.
	 */
	public static function Timer ($name = NULL);

	/**
	 * Dumps information about any variable in readable format and return it.
	 * In non-development mode - store dumped variable in `debug.log`.
	 * @param  mixed  $value	Variable to dump.
	 * @param  bool   $return	Return output instead of printing it.
	 * @param  bool   $exit		`TRUE` for last dump call by `xxx();` method to 
	 *							dump and `exit;`.
	 * @return mixed			Variable itself or dumped variable string.
	 */
	public static function Dump ($value, $return = FALSE, $exit = FALSE);

	/**
	 * Dump any variable with output buffering in browser debug bar.
	 * In non-development mode - store dumped variable in `debug.log`.
	 * Return printed variable as string.
	 * @param  mixed	$value		Variable to dump.
	 * @param  string	$title		Optional title.
	 * @param  array	$options	Dumper options.
	 * @return mixed				Variable itself.
	 */
	public static function BarDump ($value, $title = NULL, $options = []);

	/**
	 * Logs any message or exception with log date time, in `*.log` file
	 * by given log level, in configured logging directory.
	 * @param  mixed|\Exception|\Throwable	$value
	 * @param  string						$priority
	 * @return string						Logging filename full path.
	 */
	public static function Log ($value, $priority = \MvcCore\IDebug::INFO);

	/**
	 * Print caught exception in browser.
	 * In non-development mode - store dumped exception in `exception.log`.
	 * @param \Exception|\Error|\Throwable|array $exception
	 * @param bool $exit
	 * @return void
	 */
	public static function Exception ($exception, $exit = TRUE);

	/**
	 * Print all stored dumps at the end of sent response body as browser debug 
	 * bar. This function is called from registered shutdown handler by
	 * `register_shutdown_function()` from `\MvcCore\Debug::initHandlers();`.
	 * @return void
	 */
	public static function ShutdownHandler ();
}
