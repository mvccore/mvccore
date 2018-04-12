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

namespace MvcCore\Interfaces;

/**
 * Responsibility - any devel and logging messages and exceptions printing and logging.
 * - Printing any variable in content body.
 * - Printing any variable in browser debug bar.
 * - Catched exceptions printing.
 * - Any variables and catched exceptions file logging.
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
	 * @return void
	 */
	public static function Init ();

	/**
	 * Starts/stops stopwatch.
	 * @param  string $name Time pointer name.
	 * @return float        Elapsed seconds.
	 */
	public static function Timer ($name = NULL);

	/**
	 * Dumps information about any variable in readable format and return it.
	 * @param  mixed  $value	Variable to dump.
	 * @param  bool   $return	Return output instead of printing it.
	 * @return mixed			Variable itself or dump or null.
	 */
	public static function Dump ($value, $return = FALSE);

	/**
	 * Dump any variable with output buffering in browser debug bar,
	 * store result for printing later. Return printed variable as string.
	 * @param  mixed	$value		Variable to dump.
	 * @param  string	$title		Optional title.
	 * @param  array	$options	Dumper options.
	 * @return mixed				Variable itself.
	 */
	public static function BarDump ($value, $title = NULL, $options = array());

	/**
	 * Logs any message or exception with log datetime, in `*.log` file
	 * by given log level, in configured logging directory.
	 * @param  string|\Exception|\Throwable	$value
	 * @param  string						$priority
	 * @return string						Logged error filename.
	 */
	public static function Log ($value, $priority = self::INFO);

	/**
	 * Sends given `$value` into FireLogger console.
	 * @param	mixed	$value	Message to log.
	 * @param	string	$priority	Priority.
	 * @return	bool				Was successful?
	 */
	public static function FireLog ($value, $priority = self::DEBUG);

	/**
	 * Handler to print catched exception in browser, no file logging.
	 * If you want to log exception to file, use `\MvcCore\Debug::Log($e);` instead.
	 * @param  \Exception|\Throwable
	 * @return void
	 */
	public static function Exception ($exception, $exit = TRUE);

	/**
	 * Print all catched dumps at the end of sended response body as browser debug bar.
	 * This function is called from registered shutdown handler by
	 * `register_shutdown_function()` from `\MvcCore\Debug::initHandlers();`.
	 * @return void
	 */
	public static function ShutdownHandler ();
}
