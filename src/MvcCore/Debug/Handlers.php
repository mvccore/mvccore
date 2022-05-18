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
trait Handlers {

	/**
	 * @inheritDocs
	 * @param  string $name Time pointer name.
	 * @return float        Elapsed seconds.
	 */
	public static function Timer ($name = NULL) {
		return static::BarDump(
			call_user_func(static::$handlers['timer'], $name),
			$name,
			['backtraceIndex' => 3]
		);
	}

	/**
	 * @inheritDocs
	 * @param  mixed  $value  Variable to dump.
	 * @param  bool   $return Let's return output instead of printing it.
	 * @param  bool   $exit   `TRUE` for last dump call by `xxx();` method
	 *                        to dump and `exit;`.
	 * @return mixed          Variable itself or dumped variable string.
	 */
	public static function Dump ($value, $return = FALSE, $exit = FALSE) {
		if (static::$originalDebugClass) {
			$options = ['bar' => FALSE, 'backtraceIndex' => 1];
			if ($exit) $options['lastDump'] = TRUE;
			$dumpedValue = static::dumpHandler($value, NULL, $options);
		} else {
			$dumpedValue = @call_user_func(static::$handlers['dump'], $value, $return);
		}
		if ($return) return $dumpedValue;
		if (static::$debugging) {
			echo $dumpedValue;
		} else {
			static::storeLogRecord($dumpedValue, \MvcCore\IDebug::DEBUG);
		}
		return $value;
	}

	/**
	 * @inheritDocs
	 * @param  mixed  $value   Variable to dump.
	 * @param  string $title   Optional title.
	 * @param  array  $options Dumper options.
	 * @return mixed           Variable itself.
	 */
	public static function BarDump ($value, $title = NULL, $options = []) {
		if (static::$originalDebugClass) {
			if (!isset($options['backtraceIndex'])) $options['backtraceIndex'] = 1;
			$options['bar'] = static::$debugging;
			$dumpedValue = static::dumpHandler($value, $title, $options);
		} else {
			$dumpedValue = @call_user_func_array(static::$handlers['barDump'], func_get_args());
		}
		if (!static::$debugging)
			static::storeLogRecord($dumpedValue, \MvcCore\IDebug::DEBUG);
		return $value;
	}

	/**
	 * @inheritDocs
	 * @param  mixed|\Exception|\Throwable $value
	 * @param  string                      $priority
	 * @return string                      Logging filename full path.
	 */
	public static function Log ($value, $priority = \MvcCore\IDebug::INFO) {
		if (static::$originalDebugClass) {
			$dumpedValue = static::dumpHandler(
				$value, NULL, ['bar' => FALSE, 'backtraceIndex' => 1]
			);
			return static::storeLogRecord($dumpedValue, $priority);
		} else {
			return @call_user_func_array(static::$handlers['log'], func_get_args());
		}
	}

	/**
	 * @inheritDocs
	 * @param  \Exception|\Error|\Throwable|array $exception
	 * @param  bool $exit
	 * @return void
	 */
	public static function Exception ($exception, $exit = TRUE) {
		if (static::$originalDebugClass) {
			$dumpedValue = static::dumpHandler(
				$exception, NULL, ['bar' => !$exit, 'backtraceIndex' => 1]
			);
			if (static::$debugging) {
				$heading = $exception instanceof \Throwable ? get_class($exception) : 'Error';
				echo '<h1>'.$heading.'</h1><pre>',$dumpedValue,'</pre>';
			} else {
				static::storeLogRecord($dumpedValue, \MvcCore\IDebug::EXCEPTION);
			}
		} else {
			@call_user_func_array(static::$handlers['exceptionHandler'], func_get_args());
		}
	}

	/**
	 * @inheritDocs
	 * @return void
	 */
	public static function ShutdownHandler () {
		$error = error_get_last();
		if (isset($error['type'])) static::Exception($error);
		if (!self::isHtmlResponse() || count(self::$dumps) === 0) return;
		list($dumps, $lastDump) = self::formatDebugDumps();
		echo str_replace(
			['%mvccoreDumps%', '"%mvccoreInitArgs%"'],
			[$dumps, ($lastDump ? '!0' : '!1') . ',' . count(self::$dumps)],
			file_get_contents(__DIR__.'/debug.html')
		);
	}

	/**
	 * Starts/stops stopwatch.
	 * @param  string|NULL $name Name.
	 * @return float             Elapsed seconds.
	 */
	protected static function timerHandler ($name = NULL) {
		$now = microtime(TRUE);
		if ($name === NULL) return $now - static::$requestBegin;
		$difference = round((isset(static::$timers[$name]) ? $now - static::$timers[$name] : 0) * 1000) / 1000;
		static::$timers[$name] = $now;
		return $difference;
	}

	/**
	 * Dump any variable as string with output buffering and return dumped string.
	 * If given `$options` array contains record about `bar` boolean - to render
	 * dumped string in debug bar - store the dump record for HTML response later
	 * rendering in shutdown handler or render dumped string directly in HTTP
	 * header for AJAX response, before any output body.
	 * @param  mixed  $value   Variable to dump.
	 * @param  string $title   Optional title.
	 * @param  array  $options Dumper options.
	 * @return string
	 */
	protected static function dumpHandler ($value, $title = NULL, $options = []) {
		ob_start();
		var_dump($value);
		// format xdebug first small element with file:
		$content = preg_replace("#\</small\>\n#", '</small>', ob_get_clean(), 1);
		$content = preg_replace("#\<small\>([^\>]*)\>#", '', $content, 1);
		$content = preg_replace("#\[([^\]]*)\]=>([^\n]*)\n(\s*)#", "[$1] => ", $content);
		$backtraceIndex = isset($options['backtraceIndex']) ? $options['backtraceIndex'] : 2 ;
		$backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $backtraceIndex + 1);
		$originalPlace = (object) $backtrace[$backtraceIndex];
		$options['file'] = $originalPlace->file;
		$options['line'] = $originalPlace->line;
		if ($options['bar']) {
			if (self::isHtmlResponse()) {
				self::$dumps[] = [$content, $title, $options];
			} else {
				self::sendDumpInAjaxHeader($content, $title, $options);
			}
		}
		return $content;
	}

	/**
	 * Store given log record in text file.
	 * Return full path where the message has been written.
	 * @param  mixed  $value
	 * @param  string $priority
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

	/**
	 * Format all dump records into single string with source PHP script file
	 * link element and remove all useless new lines in PHP dumps.
	 * @return array An array with formatted dumps string and boolean about last dump before script exit.
	 */
	protected static function formatDebugDumps () {
		$dumps = '';
		$lastDump = FALSE;
		$app = static::$app ?: (static::$app = \MvcCore\Application::GetInstance());
		$appRoot = $app->GetRequest()->GetAppRoot();
		foreach (self::$dumps as $values) {
			list($dumpResult, $lastDumpLocal) = self::formatDebugDump($values, $appRoot);
			$dumps .= $dumpResult;
			if ($lastDumpLocal) $lastDump = $lastDumpLocal;
		}
		return [$dumps, $lastDump];
	}

	/**
	 * Format one dump record into single string with source PHP script file
	 * link element and remove all useless new lines in PHP dumps.
	 * @param  array       $dumpRecord Dump record from `self::$dumps` with items under indexes: `0` => dump string, `1` => title, `2` => options.
	 * @param  string|NULL $appRoot
	 * @return array       An array with formatted dump string and boolean about last dump before script exit.
	 */
	protected static function formatDebugDump ($dumpRecord, $appRoot = NULL) {
		$result = '';
		$lastDump = FALSE;
		if ($appRoot === NULL) {
			$app = static::$app ?: (static::$app = \MvcCore\Application::GetInstance());
			$appRoot = $app->GetRequest()->GetAppRoot();
		}
		$options = $dumpRecord[2];
		$result .= '<div class="item">';
		if ($dumpRecord[1] !== NULL)
			$result .= '<pre class="title">'.$dumpRecord[1].'</pre>';
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
		// make array dumps shorter
		$dump = & $dumpRecord[0];
		$dump = preg_replace("#\n(\s+)\<b\>array\</b\>\s([^\n]+)\n(\s+)(\<i\>\<font )([^\>]+)(\>empty)#m", "<b>array</b> $2 $4$5$6", $dump);
		$dump = preg_replace("#\n(\s+)\<b\>array\</b\>\s([^\n]+)\n(\s+)\.\.\.#m", "<b>array</b> $2 ...", $dump);
		$dump = preg_replace("#\n(\s+)\<b\>array\</b\>\s([^\n]+)\n#m", "<b>array</b> $2\n", $dump);
		// make object dumps shorter
		$dump = preg_replace("#(\<font color='\#)([^']+)'\>\=&gt;\</font\>\s\n\s+([^\n]+)\n\s+\.\.\.#m", "<font color='#$2'>=&gt;</font> $3 ...", $dump);
		$dump = preg_replace("#\n\s+(.*)\<b\>object\</b\>([^\n]+)\n#m", "$1<b>object</b> $2\n", $dump);
		$result .= '<div class="value">'
			.preg_replace("#\[([^\]]*)\]=>([^\n]*)\n(\s*)#", "[$1] => ",
				str_replace("<required>","&lt;required&gt;",$link.$dump)
			)
			.'</div></div>';
		if (isset($dumpRecord[2]['lastDump']) && $dumpRecord[2]['lastDump'])
			$lastDump = TRUE;
		return [$result, $lastDump];
	}

	/**
	 * Sent given dump record into client in specific header for ajax response.
	 * @param  mixed  $value   Variable to dump.
	 * @param  string $title   Optional title.
	 * @param  array  $options Dumper options.
	 * @return void
	 */
	protected static function sendDumpInAjaxHeader ($value, $title, $options) {
		static $ajaxHeadersIndex = 0;
		$app = static::$app ?: (static::$app = \MvcCore\Application::GetInstance());
		$response = $app->GetResponse();
		list ($dumpStr,) = self::formatDebugDump(
			[$value, $title, $options],
			$app->GetRequest()->GetAppRoot()
		);
		$dumpStr64Arr = str_split(base64_encode($dumpStr), 5000);
		foreach ($dumpStr64Arr as $key => $base64Item)
			$response->SetHeader(
				'X-MvcCore-Debug-' . $ajaxHeadersIndex . '-' . $key,
				$base64Item
			);
		$ajaxHeadersIndex += 1;
		$response->SetHeader('X-MvcCore-Debug', (string) $ajaxHeadersIndex);
	}

	/**
	 * Get `TRUE` if response is considered as HTML type.
	 * @return bool
	 */
	protected static function isHtmlResponse () {
		$app = static::$app ?: (static::$app = \MvcCore\Application::GetInstance());
		$request = $app->GetRequest();
		if ($request->IsInternalRequest()) return FALSE;
		$response = $app->GetResponse();
		$hasContentType = $response->HasHeader('Content-Type');
		return !$hasContentType || ($hasContentType && $response->IsHtmlOutput());
	}
}
