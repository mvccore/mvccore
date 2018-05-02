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
			'timer'				=> 'timerHandler',
			'dump'				=> 'dumpHandler',
			'barDump'			=> 'dumpHandler',
			'log'				=> 'dumpHandler',
			'fireLog'			=> 'dumpHandler',
			'exceptionHandler'	=> 'exceptionHandler',
			'shutdownHandler'	=> 'ShutdownHandler',
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
		 * `TRUE` if debug class is MvcCore original debug class and
		 * if logs directory has been already initialized.
		 * @var bool
		 */
		protected static $logDirectoryInitialized = FALSE;

		/**
		 * Reference to singleton instance in `\MvcCore\Application::GetInstance();`.
		 * @var \MvcCore\Application
		 */
		protected static $app;

		/**
		 * Reference to `\MvcCore\Application::GetInstance()->GetRequest()->GetMicrotime();`.
		 * @var float
		 */
		protected static $requestBegin;

		/**
		 * Initialize debugging and logging, once only.
		 * @param bool $forceDevelopmentMode If defined as `TRUE` or `FALSE`,
		 *                                   debug mode will be set not by config but by this value.
		 * @return void
		 */
		public static function Init ($forceDevelopmentMode = NULL) {
			if (static::$development !== NULL) return;

			static::$app = & \MvcCore\Application::GetInstance();
			static::$requestBegin = static::$app->GetRequest()->GetMicrotime();

			if (gettype($forceDevelopmentMode) == 'boolean') {
				static::$development = $forceDevelopmentMode;
			} else {
				$configClass = static::$app->GetConfigClass();
				static::$development = $configClass::IsDevelopment(TRUE);
			}

			// do not initialize log directory here every time, initialize log
			//directory only if there is necessary to log something - later.

			static::$originalDebugClass = ltrim(static::$app->GetDebugClass(), '\\') == get_called_class();
			static::initHandlers();
			$initGlobalShortHandsHandler = static::$InitGlobalShortHands;
			$initGlobalShortHandsHandler(static::$development);
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
		 * In non-development mode - store dumped variable in `debug.log`.
		 * @param  mixed  $value	Variable to dump.
		 * @param  bool   $return	Return output instead of printing it.
		 * @param  bool   $exit		`TRUE` for last dump call by `xxx();` method to dump and `exit;`.
		 * @return mixed			Variable itself or dumped variable string.
		 */
		public static function Dump ($value, $return = FALSE, $exit = FALSE) {
			if (static::$originalDebugClass) {
				$options = array('store' => FALSE, 'backtraceIndex' => 1);
				if ($exit) $options['lastDump'] = TRUE;
				$dumpedValue = static::dumpHandler($value, NULL, $options);
			} else {
				$dumpedValue = @call_user_func(static::$handlers['dump'], $value, $return);
			}
			if ($return) return $dumpedValue;
			if (static::$development) {
				echo $dumpedValue;
			} else {
				static::storeLogRecord($dumpedValue, \MvcCore\Interfaces\IDebug::DEBUG);
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
		public static function BarDump ($value, $title = NULL, $options = array()) {
			if (static::$originalDebugClass) {
				if (!isset($options['backtraceIndex'])) $options['backtraceIndex'] = 1;
				$options['store'] = static::$development;
				$dumpedValue = static::dumpHandler($value, $title, $options);
			} else {
				$dumpedValue = @call_user_func_array(static::$handlers['barDump'], func_get_args());
			}
			if (!static::$development) {
				static::storeLogRecord($dumpedValue, \MvcCore\Interfaces\IDebug::DEBUG);
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
		public static function Log ($value, $priority = \MvcCore\Interfaces\IDebug::INFO) {
			if (static::$originalDebugClass) {
				$dumpedValue = static::dumpHandler(
					$value, NULL, array('store' => FALSE, 'backtraceIndex' => 1)
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
		public static function FireLog ($value, $priority = \MvcCore\Interfaces\IDebug::DEBUG) {
			// TODO: implement simple firelog
			$args = func_get_args();
			if (static::$originalDebugClass) {
				$args = array($value, NULL, array('priority' => $priority));
			}
			return call_user_func_array(static::$handlers['fireLog'], $args);
		}

		/**
		 * Print catched exception in browser.
		 * In non-development mode - store dumped exception in `exception.log`.
		 * @param \Exception|\Throwable $exception
		 * @param bool $exit
		 * @return void
		 */
		public static function Exception ($exception, $exit = TRUE) {
			if (static::$originalDebugClass) {
				$dumpedValue = static::dumpHandler(
					$exception, NULL, array('store' => !$exit, 'backtraceIndex' => 1)
				);
				if (static::$development) {
					echo $dumpedValue;
				} else {
					static::storeLogRecord($dumpedValue, \MvcCore\Interfaces\IDebug::EXCEPTION);
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
				array('%mvccoreDumps%', '%mvccoreDumpsCount%', '%mvccoreDumpsClose%'),
				array($dumps, count(self::$dumps), $lastDump ? 'q(!0);' : 'q();'),
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
		protected static function dumpHandler ($value, $title = NULL, $options = array()) {
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
			if ($options['store']) self::$dumps[] = array($content, $title, $options);
			return $content;
		}

		/**
		 * Store given log record in text file.
		 * Return full path where the message has been writen.
		 * @param mixed $value
		 * @param string $priority
		 * @return string
		 */
		protected static function storeLogRecord ($value, $priority) {
			$content = date('[Y-m-d H-i-s]') . "\n" . $value;
			$content = preg_replace("#\n(\s)#", "\n\t$1", $content) . "\n";
			if (!static::$logDirectoryInitialized) static::initLogDirectory();
			$fullPath = static::$LogDirectory . '/' . $priority . '.log';
			if (!is_dir(static::$LogDirectory)) {
				mkdir(static::$LogDirectory);
				if (!is_writable(static::$LogDirectory)) {
					try {
						chmod(static::$LogDirectory, 0777);
					} catch (\Exception $e) {
						die('['.__CLASS__.'] ' . $e->getMessage());
					}
				}
			}
			file_put_contents($fullPath, $content, FILE_APPEND);
			return $fullPath;
		}

		/**
		 * Initialize debuging and logging handlers.
		 * @return void
		 */
		protected static function initHandlers () {
			$className = get_called_class();
			foreach (static::$handlers as $key => $value) {
				static::$handlers[$key] = array($className, $value);
			}
			register_shutdown_function(self::$handlers['shutdownHandler']);
		}

		/**
		 * If log directory doesn't exist, create new directory - relative from app root.
		 * @param string $logDirAbsPath Absolute directory path.
		 * @return void
		 */
		protected static function initLogDirectory () {
			if (static::$logDirectoryInitialized) return;
			$configClass = static::$app->GetConfigClass();
			$cfg = $configClass::GetSystem();
			$logDirRelPath = static::$LogDirectory;
			if ($cfg !== FALSE && isset($cfg->debug)) {
				$cfgDebug = & $cfg->debug;
				if (isset($cfgDebug->emailRecepient))
					static::$EmailRecepient = $cfgDebug->emailRecepient;
				if (isset($cfgDebug->logDirectory))
					$logDirRelPath = $cfgDebug->logDirectory; // relative path from app root
			}

			$scriptFilename = $_SERVER['SCRIPT_FILENAME'];
			$scriptPath = php_sapi_name() == 'cli'
				? str_replace('\\', '/', getcwd()) . '/' . $scriptFilename
				: str_replace('\\', '/', $scriptFilename);
			$lastSlashPos = strrpos($scriptPath, '/');
			$appRoot = substr($scriptPath, 0, $lastSlashPos !== FALSE ? $lastSlashPos : strlen($scriptPath));
			$logDirAbsPath = $appRoot . $logDirRelPath;
			static::$LogDirectory = $logDirAbsPath;

			if (!is_dir($logDirAbsPath)) mkdir($logDirAbsPath, 0777, TRUE);
			if (!is_writable($logDirAbsPath)) {
				try {
					chmod($logDirAbsPath, 0777);
				} catch (\Exception $e) {
					die('['.__CLASS__.'] ' . $e->getMessage());
				}
			}

			static::$logDirectoryInitialized = TRUE;
		}
	}
}

namespace {
	\MvcCore\Debug::$InitGlobalShortHands = function ($development) {
		/**
		 * Dump any variable with output buffering in browser debug bar,
		 * store result for printing later. Return printed variable as string.
		 * @param  mixed	$value		Variable to dump.
		 * @param  string	$title		Optional title.
		 * @param  array	$options	Dumper options.
		 * @return mixed				Variable itself.
		 */
		function x ($value, $title = NULL, $options = array()) {
			$options['backtraceIndex'] = 2;
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
			foreach ($args as $arg) \MvcCore\Debug::BarDump($arg, NULL, array('backtraceIndex' => 2));
		}

		if ($development) {
			/**
			 * Dump variables and die. If no variable, throw stop exception.
			 * @param  ...mixed  $args	Variables to dump.
			 * @throws \Exception
			 * @return void
			 */
			function xxx (/*...$args*/) {
				$args = func_get_args();
				if (count($args) === 0) {
					throw new \Exception("Stopped.");
				} else {
					ob_start();
					\MvcCore\Application::GetInstance()->GetResponse()->SetHeader('Content-Type', 'text/html');
					@header('Content-Type: text/html');
					echo '<pre><code>';
					foreach ($args as $arg) {
						$dumpedArg = \MvcCore\Debug::Dump($arg, TRUE, TRUE);
						echo preg_replace("#\[([^\]]*)\]=>([^\n]*)\n(\s*)#", "[$1] => ", $dumpedArg);
						echo '</code></pre>';
					}
				}
				die();
			}
		} else {
			/**
			 * Log variables and die. If no variable, throw stop exception.
			 * @param  ...mixed  $args	Variables to dump.
			 * @throws \Exception
			 * @return void
			 */
			function xxx (/*...$args*/) {
				$args = func_get_args();
				if (count($args) === 0)
					throw new \Exception("Stopped.");
				else
					foreach ($args as $arg) \MvcCore\Debug::Log($arg, \MvcCore\Interfaces\IDebug::DEBUG);
				die();
			}
		}
	};
}
