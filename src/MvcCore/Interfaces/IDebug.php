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
 * - Printing any variable in content body.
 * - Printing any variable in browser debug bar.
 * - Time printing.
 * - Catched exceptions logging or printing in dev mode.
 */
interface IDebug
{
	const
		DEBUG = 'debug',
		INFO = 'info',
		WARNING = 'warning',
		ERROR = 'error',
		EXCEPTION = 'exception',
		CRITICAL = 'critical',
		JAVASCRIPT = 'javascript';

	/**
	 * Initialize debugging and logging (only once).
	 * @return void
	 */
	public static function Init ();

	/**
	 * Starts/stops stopwatch.
	 * @param  string $name time pointer name
	 * @return float        elapsed seconds
	 */
	public static function Timer ($name = NULL);

	/**
	 * Dumps information about any variable in readable format.
	 * @tracySkipLocation
	 * @param  mixed  $value	variable to dump
	 * @param  bool   $return	return output instead of printing it? (bypasses $productionMode)
	 * @return mixed			variable itself or dump or null
	 */
	public static function Dump ($value, $return = FALSE);

	/**
	 * Dumps information about any variable in browser debug bar.
	 * @tracySkipLocation
	 * @param  mixed	$value		variable to dump
	 * @param  string	$title		optional title
	 * @param  array	$options	dumper options
	 * @return mixed				variable itself
	 */
	public static function BarDump ($value, $title = NULL, $options = array());

	/**
	 * Logs message or exception.
	 * @param  string|\Exception	$value
	 * @param  string				$priority
	 * @return string				logged error filename
	 */
	public static function Log ($value, $priority = self::INFO);

	/**
	 * Sends message to FireLogger console.
	 * @param	mixed	$message	message to log
	 * @param	string	$priority	priority
	 * @return	bool				was successful?
	 */
	public static function FireLog ($message, $priority = self::DEBUG);

	/**
	 * Handler to render catched exception.
	 * @param  \Exception|\Throwable
	 * @return void
	 */
	public static function Exception ($exception, $exit = TRUE);

	/**
	 * Print all catched dumps at the end of sended response body.
	 * @return void
	 */
	public static function ShutdownHandler ();
}
