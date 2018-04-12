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

namespace MvcCore {

	//include_once(__DIR__ . '/Interfaces/IDebug.php');
	//include_once('Application.php');
	//include_once('Config.php');

	/**
	 * Responsibility - any devel and logging messages and exceptions printing and logging.
	 * - Printing any variable in content body.
	 * - Printing any variable in browser debug bar.
	 * - Catched exceptions printing.
	 * - Any variables and catched exceptions file logging.
	 * - Time printing.
	 */
	class Debug implements Interfaces\IDebug
	{
		/**
		 * Email recepient to send information about exceptions or errors,
		 * `"admin@localhost"` by default.
		 * @var string
		 */
		public static $EmailRecepient = 'admin@localhost';

		/**
		 * Relative path from app root to store any log information,
		 * `"/Var/Logs"` by default.
		 * @var string
		 */
		public static $LogDirectory = '/Var/Logs';

		/**
		 * Initialize global development shorthands.
		 * @var callable
		 */
		public static $InitGlobalShortHands = array();

		/**
		 * Semaphore to execute `\MvcCore\Debug::Init();` method only once.
		 * `TRUE` if development, `FALSE` if anything else.
		 * @var boolean
		 */
		protected static $development = NULL;

		/**
		 * Debuging and loging handlers, this should be customized in extended class.
		 * @var array
		 */
		protected static $handlers = array(
			'timer'				=> 'self::timerHandler',
			'dump'				=> 'self::dumpHandler',
			'barDump'			=> 'self::dumpHandler',
			'log'				=> 'self::dumpHandler',
			'fireLog'			=> 'self::dumpHandler',
			'exceptionHandler'	=> 'self::exceptionHandler',
			'shutdownHandler'	=> 'self::ShutdownHandler',
		);

		/**
		 * Store for printed dumps by output buffering to send it at response end.
		 * @var array
		 */
		protected static $dumps = array();

		/**
		 * Store timers start points.
		 * @var array
		 */
		protected static $timers = array();

		/**
		 * `TRUE` for cofigured debug class as `\MvcCore\Debug`,
		 * `FALSE` for any other configured extension.
		 * @var bool
		 */
		protected static $originalDebugClass = TRUE;

		/**
		 * Initialize debugging and logging, once only.
		 * @return void
		 */
		public static function Init () {
			if (static::$development !== NULL) return;
			$app = \MvcCore\Application::GetInstance();
			$configClass = $app->GetConfigClass();
			static::$development = $configClass::IsDevelopment();
			$cfg = $configClass::GetSystem();

			if (isset($cfg->debug)) {
				$cfgDebug = & $cfg->debug;
				if (isset($cfgDebug->emailRecepient)) {
					static::$EmailRecepient = $cfgDebug->emailRecepient;
				}
				if (isset($cfgDebug->logDirectory)) {
					static::$LogDirectory = $cfgDebug->logDirectory;
				}
			}
			$scriptFilename = $_SERVER['SCRIPT_FILENAME'];
			$scriptPath = php_sapi_name() == 'cli'
				? str_replace('\\', '/', getcwd()) . '/' . $scriptFilename
				: str_replace('\\', '/', $scriptFilename);
			$lastSlash = strrpos($scriptPath, '/');
			$appRoot = substr($scriptPath, 0, $lastSlash !== FALSE ? $lastSlash : strlen($scriptPath));
			static::$LogDirectory = $appRoot . static::$LogDirectory;

			static::$originalDebugClass = $app->GetDebugClass() == __CLASS__;
			static::initLogDirectory(static::$LogDirectory);
			static::initHandlers();
			$initGlobalShortHandsHandler = static::$InitGlobalShortHands;
			$initGlobalShortHandsHandler(static::$LogDirectory);
		}

		/**
		 * Initialize debuging and logging handlers.
		 * @return void
		 */
		protected static function initHandlers () {
			foreach (static::$handlers as $key => $value) {
				static::$handlers[$key] = array(__CLASS__, $value);
			}
			register_shutdown_function(self::$handlers['shutdownHandler']);
		}

		/**
		 * If log directory doesn't exist, create new directory - relative from app root.
		 * @param string $logDirabsPath Absolute directory path.
		 * @return void
		 */
		protected static function initLogDirectory ($logDirabsPath) {
			if (!is_dir($logDirabsPath)) mkdir($logDirabsPath, 0777, TRUE);
			if (!is_writable($logDirabsPath)) {
				try {
					chmod($logDirabsPath, 0777);
				} catch (\Exception $e) {
					die('['.static::class.'] ' . $e->getMessage());
				}
			}
		}

		/**
		 * Starts/stops stopwatch.
		 * @param  string $name Time pointer name.
		 * @return float        Elapsed seconds.
		 */
		public static function Timer ($name = NULL) {
			return static::BarDump(
				call_user_func(static::$handlers['timer'], $name),
				$name,
				array('backtraceIndex' => 3)
			);
		}

		/**
		 * Dumps information about any variable in readable format and return it.
		 * @param  mixed  $value	Variable to dump.
		 * @param  bool   $return	Return output instead of printing it.
		 * @return mixed			Variable itself or dump or null.
		 */
		public static function Dump ($value, $return = FALSE) {
			if (static::$originalDebugClass) {
				$args = func_get_args();
				$options = isset($args[2]) ? array('dieDumpCall' => TRUE) : array() ;
				if ($return) $options['doNotStore'] = TRUE;
				$options['backtraceIndex'] = 1;
				$result = static::dumpHandler($value, NULL, $options);
			} else {
				$result = call_user_func(static::$handlers['dump'], $value, $return);
			}
			if ($return) return $result;
			return NULL;
		}

		/**
		 * Dump any variable with output buffering in browser debug bar,
		 * store result for printing later. Return printed variable as string.
		 * @param  mixed	$value		Variable to dump.
		 * @param  string	$title		Optional title.
		 * @param  array	$options	Dumper options.
		 * @return mixed				Variable itself.
		 */
		public static function BarDump ($value, $title = NULL, $options = array()) {
			return call_user_func_array(static::$handlers['barDump'], func_get_args());
		}

		/**
		 * Logs any message or exception with log datetime, in `*.log` file
		 * by given log level, in configured logging directory.
		 * @param  string|\Exception|\Throwable	$value
		 * @param  string						$priority
		 * @return string						Logged error filename.
		 */
		public static function Log ($value, $priority = \MvcCore\Interfaces\IDebug::INFO) {
			$args = func_get_args();
			if (static::$originalDebugClass) {
				$content = date('[Y-m-d H-i-s]') . "\n" . static::dumpHandler(
					$value, NULL, array('doNotStore' => TRUE, 'backtraceIndex' => 1)
				);
				$content = str_replace("\n", "\n\t", $content) . "\n";
				$fullPath = static::$LogDirectory . DIRECTORY_SEPARATOR . $priority . '.log';
				file_put_contents($fullPath, $content, FILE_APPEND);
				return $fullPath;
			} else {
				return @call_user_func_array(static::$handlers['log'], $args);
			}
		}

		/**
		 * Sends given `$value` into FireLogger console.
		 * @param	mixed	$value	Message to log.
		 * @param	string	$priority	Priority.
		 * @return	bool				Was successful?
		 */
		public static function FireLog ($value, $priority = \MvcCore\Interfaces\IDebug::DEBUG) {
			// TODO: implement simple firelog
			$args = func_get_args();
			if (static::$originalDebugClass) {
				$args = array($value, NULL, array('priority' => $priority));
			}
			return call_user_func_array(static::$handlers['fireLog'], $args);
		}

		/**
		 * Handler to print catched exception in browser, no file logging.
		 * If you want to log exception to file, use `\MvcCore\Debug::Log($e);` instead.
		 * @param  \Exception|\Throwable
		 * @return void
		 */
		public static function Exception ($exception, $exit = TRUE) {
			return call_user_func_array(static::$handlers['exceptionHandler'], func_get_args());
		}

		/**
		 * Print all catched dumps at the end of sended response body as browser debug bar.
		 * This function is called from registered shutdown handler by
		 * `register_shutdown_function()` from `\MvcCore\Debug::initHandlers();`.
		 * @return void
		 */
		public static function ShutdownHandler () {
			if (!count(self::$dumps)) return;
			$app = \MvcCore\Application::GetInstance();
			$appRoot = $app->GetRequest()->GetAppRoot();
			$response = $app->GetResponse();
			if (!$response->IsHtmlOutput()) return;
			$dumps = '';
			$dieDump = FALSE;
			foreach (self::$dumps as $values) {
				$dumps .= '<div class="item">';
				if ($values[1] !== NULL) {
					$dumps .= '<pre class="title">'.$values[1].'</pre>';
				}
				$dumps .= '<div class="value">'
					.preg_replace("#\[([^\]]*)\]=>([^\n]*)\n(\s*)#", "[$1] => ",
						str_replace("<required>","&lt;required&gt;", 
							preg_replace_callback (
								"#\<small class\=\"file\"\>([^\<]*)\</small\>#", 
								function ($m) use ($appRoot) {
									$str = $m[1];
									$pos = strrpos($str, ':');
									if ($pos !== FALSE) {
										$file = substr($str, 0, $pos);
										$line = substr($str, $pos + 1);
									} else {
										$file = $str;
										$line = 0;
									}
									$displayedFile = str_replace('\\', '/', $file);
									if (strpos($displayedFile, $appRoot) === 0) {
										$displayedFile = substr($displayedFile, strlen($appRoot));
									}
									return '<a class="editor" href="editor://open/?file='.rawurlencode($file).'&amp;line='.$line.'">'.$displayedFile.':'.$line.'</a>';
								},
								$values[0]
							)
						)
					)
					.'</div></div>';
				if (isset($values[2]['dieDumpCall']) && $values[2]['dieDumpCall']) $dieDump = TRUE;
			}
			$template = file_get_contents(__DIR__.'/debug.html');
			echo str_replace(
				array('%mvccoreDumps%', '%mvccoreDumpsCount%', '%mvccoreDumpsClose%'),
				array($dumps, count(self::$dumps), $dieDump ? 'q(!0);' : 'q();'),
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
			if ($name === NULL) return $now - \MvcCore\Application::GetInstance()->GetMicrotime();
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
		protected static function dumpHandler ($value, $title = NULL, $options = array()) {
			ob_start();
			var_dump($value);
			// format xdebug first small element with file:
			$content = preg_replace("#\</small\>\n#", '</small>', ob_get_clean(), 1);
			$content = preg_replace("#\<small\>([^\>]*)\>#", '', $content, 1);
			$backtraceIndex = isset($options['backtraceIndex']) ? $options['backtraceIndex'] : 2 ;
			$backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $backtraceIndex + 1);
			$originalPlace = (object) $backtrace[$backtraceIndex];
			$content = '<small class="file">' . $originalPlace->file . ':' . $originalPlace->line . '</small>' . $content;
			if (!isset($options['doNotStore'])) self::$dumps[] = array($content, $title, $options);
			return $content;
		}

		/**
		 * Print all catched dumps at the end of sended response body.
		 * @return void
		 */
		protected static function exceptionHandler (\Exception $e, $exit = TRUE) {
			echo '<pre>';
			throw $e;
			//if ($exit) exit;
		}
	}
}

namespace {
	\MvcCore\Debug::$InitGlobalShortHands = function () {
		/**
		 * Dump any variable with output buffering in browser debug bar,
		 * store result for printing later. Return printed variable as string.
		 * @param  mixed	$value		Variable to dump.
		 * @param  string	$title		Optional title.
		 * @param  array	$options	Dumper options.
		 * @return mixed				Variable itself.
		 */
		function x ($value, $title = NULL, $options = array()) {
			return \MvcCore\Debug::BarDump($value, $title, $options);
		}
		/**
		 * Dumps multiple variables with output buffering in browser debug bar.
		 * store result for printing later.
		 * @param  ...mixed  Variables to dump.
		 * @return void
		 */
		function xx () {
			$args = func_get_args();
			foreach ($args as $arg) \MvcCore\Debug::BarDump($arg);
		}
		/**
		 * Dump a variable and die. If no variable, throw stop exception.
		 * @param  mixed  $value	Variable to dump.
		 * @param  string $title	Optional title.
		 * @param  array  $options	Dumper options.
		 * @throws \Exception
		 * @return void
		 */
		function xxx ($value = NULL, $title = NULL, $options = array()) {
			$args = func_get_args();
			if (count($args) === 0) {
				throw new \Exception("Stopped.");
			} else {
				@header("Content-Type: text/html; charset=utf-8");
				foreach ($args as $arg) \MvcCore\Debug::Dump($arg, FALSE, TRUE);
			}
			echo ob_get_clean();
			die();
		}
	};
}
