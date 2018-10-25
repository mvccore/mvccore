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

namespace MvcCore\Debug;

trait Handlers
{
	/**
	 * Starts/stops stopwatch.
	 * @param  string $name Time pointer name.
	 * @return float		Elapsed seconds.
	 */
	public static function Timer ($name = NULL) {
		return static::BarDump(
			call_user_func(static::$handlers['timer'], $name),
			$name,
			['backtraceIndex' => 3]
		);
	}

	/**
	 * Dumps information about any variable in readable format and return it.
	 * In non-development mode - store dumped variable in `debug.log`.
	 * @param  mixed  $value		Variable to dump.
	 * @param  bool   $return		Return output instead of printing it.
	 * @param  bool   $exit			`TRUE` for last dump call by `xxx();` method to dump and `exit;`.
	 * @return mixed				Variable itself or dumped variable string.
	 */
	public static function Dump ($value, $return = FALSE, $exit = FALSE) {
		if (static::$originalDebugClass) {
			$options = ['store' => FALSE, 'backtraceIndex' => 1];
			if ($exit) $options['lastDump'] = TRUE;
			$dumpedValue = static::dumpHandler($value, NULL, $options);
		} else {
			$dumpedValue = @call_user_func(static::$handlers['dump'], $value, $return);
		}
		if ($return) return $dumpedValue;
		if (static::$development) {
			echo $dumpedValue;
		} else {
			static::storeLogRecord($dumpedValue, \MvcCore\IDebug::DEBUG);
		}
		return $value;
	}

	/**
	 * Dump any variable with output buffering in browser debug bar.
	 * In non-development mode - store dumped variable in `debug.log`.
	 * Return printed variable as string.
	 * @param  mixed	$value		Variable to dump.
	 * @param  string	$title		Optional title.
	 * @param  array	$options	Dumper options.
	 * @return mixed				Variable itself.
	 */
	public static function BarDump ($value, $title = NULL, $options = []) {
		if (static::$originalDebugClass) {
			if (!isset($options['backtraceIndex'])) $options['backtraceIndex'] = 1;
			$options['store'] = static::$development;
			$dumpedValue = static::dumpHandler($value, $title, $options);
		} else {
			$dumpedValue = @call_user_func_array(static::$handlers['barDump'], func_get_args());
		}
		if (!static::$development) {
			static::storeLogRecord($dumpedValue, \MvcCore\IDebug::DEBUG);
		}
		return $value;
	}

	/**
	 * Logs any message or exception with log datetime, in `*.log` file
	 * by given log level, in configured logging directory.
	 * @param  string|\Exception|\Throwable	$value
	 * @param  string						$priority
	 * @return string						Logging filename fullpath.
	 */
	public static function Log ($value, $priority = \MvcCore\IDebug::INFO) {
		if (static::$originalDebugClass) {
			$dumpedValue = static::dumpHandler(
				$value, NULL, ['store' => FALSE, 'backtraceIndex' => 1]
			);
			return static::storeLogRecord($dumpedValue, $priority);
		} else {
			return @call_user_func_array(static::$handlers['log'], func_get_args());
		}
	}

	/**
	 * Sends given `$value` into FireLogger console.
	 * @param	mixed	$value	Message to log.
	 * @param	string	$priority	Priority.
	 * @return	bool				Was successful?
	 */
	public static function FireLog ($value, $priority = \MvcCore\IDebug::DEBUG) {
		// TODO: implement simple firelog
		$args = func_get_args();
		if (static::$originalDebugClass) {
			$args = [$value, NULL, ['priority' => $priority]];
		}
		return call_user_func_array(static::$handlers['fireLog'], $args);
	}

	/**
	 * Print catched exception in browser.
	 * In non-development mode - store dumped exception in `exception.log`.
	 * @param \Exception|\Error|\Throwable|array $exception
	 * @param bool $exit
	 * @return void
	 */
	public static function Exception ($exception, $exit = TRUE) {
		if (static::$originalDebugClass) {
			$dumpedValue = static::dumpHandler(
				$exception, NULL, ['store' => !$exit, 'backtraceIndex' => 1]
			);
			if (static::$development) {
				echo $dumpedValue;
			} else {
				static::storeLogRecord($dumpedValue, \MvcCore\IDebug::EXCEPTION);
			}
		} else {
			@call_user_func_array(static::$handlers['exceptionHandler'], func_get_args());
		}
	}

	/**
	 * Print all stored dumps at the end of sended response body as browser debug bar.
	 * This function is called from registered shutdown handler by
	 * `register_shutdown_function()` from `\MvcCore\Debug::initHandlers();`.
	 * @return void
	 */
	public static function ShutdownHandler () {
		$error = error_get_last();
		if (isset($error['type'])) static::Exception($error);
		$dumpsCount = count(self::$dumps);
		if (!$dumpsCount) return;
		$app = \MvcCore\Application::GetInstance();
		$appRoot = $app->GetRequest()->GetAppRoot();
		$response = $app->GetResponse();
		if ($response->HasHeader('Content-Type') && !$response->IsHtmlOutput()) return;
		$dumps = '';
		$lastDump = FALSE;
		foreach (self::$dumps as $values) {
			$options = $values[2];
			$dumps .= '<div class="item">';
			if ($values[1] !== NULL) {
				$dumps .= '<pre class="title">'.$values[1].'</pre>';
			}
			$file = $options['file'];
			$line = $options['line'];
			$displayedFile = str_replace('\\', '/', $file);
			if (strpos($displayedFile, $appRoot) === 0) {
				$displayedFile = substr($displayedFile, strlen($appRoot));
			}
			$link = '<a class="editor" href="editor://open/?file='
				.rawurlencode($file).'&amp;line='.$line.'">'
					.$displayedFile.':'.$line
				.'</a>';
			$dumps .= '<div class="value">'
				.preg_replace("#\[([^\]]*)\]=>([^\n]*)\n(\s*)#", "[$1] => ",
					str_replace("<required>","&lt;required&gt;",$link.$values[0])
				)
				.'</div></div>';
			if (isset($values[2]['lastDump']) && $values[2]['lastDump']) $lastDump = TRUE;
		}
		$template = file_get_contents(__DIR__.'/debug.html');
		echo str_replace(
			['%mvccoreDumps%', '%mvccoreDumpsCount%', '%mvccoreDumpsClose%'],
			[$dumps, count(self::$dumps), $lastDump ? 'q(!0);' : 'q();'],
			$template
		);
	}

	/**
	 * Starts/stops stopwatch.
	 * @param  string  Name.
	 * @return float   Elapsed seconds.
	 */
	protected static function timerHandler ($name = NULL) {
		$now = microtime(TRUE);
		if ($name === NULL) return $now - static::$requestBegin;
		$difference = round((isset(static::$timers[$name]) ? $now - static::$timers[$name] : 0) * 1000) / 1000;
		static::$timers[$name] = $now;
		return $difference;
	}

	/**
	 * Dump any variable as string with output buffering,
	 * store result for printing later. Return printed variable string.
	 * @param  mixed	$value		Variable to dump.
	 * @param  string	$title		Optional title.
	 * @param  array	$options	Dumper options.
	 * @return string
	 */
	protected static function dumpHandler ($value, $title = NULL, $options = []) {
		ob_start();
		var_dump($value);
		// format xdebug first small element with file:
		$content = preg_replace("#\</small\>\n#", '</small>', ob_get_clean(), 1);
		$content = preg_replace("#\<small\>([^\>]*)\>#", '', $content, 1);
		$backtraceIndex = isset($options['backtraceIndex']) ? $options['backtraceIndex'] : 2 ;
		$backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $backtraceIndex + 1);
		$originalPlace = (object) $backtrace[$backtraceIndex];
		$options['file'] = $originalPlace->file;
		$options['line'] = $originalPlace->line;
		if ($options['store']) self::$dumps[] = [$content, $title, $options];
		return $content;
	}

	/**
	 * Store given log record in text file.
	 * Return full path where the message has been written.
	 * @param mixed $value
	 * @param string $priority
	 * @return string
	 */
	protected static function storeLogRecord ($value, $priority) {
		$content = date('[Y-m-d H-i-s]') . "\n" . $value;
		$content = preg_replace("#\n(\s)#", "\n\t$1", $content) . "\n";
		if (!static::$logDirectoryInitialized) static::initLogDirectory();
		$fullPath = static::$LogDirectory . '/' . $priority . '.log';
		file_put_contents($fullPath, $content, FILE_APPEND);
		return $fullPath;
	}
}
