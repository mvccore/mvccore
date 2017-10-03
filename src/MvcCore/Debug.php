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
	
	require_once('Config.php');

	/**
	 * Core debug tools
	 * - printing any value by var_dump(); in fixed 
	 *   bar at browser window right bottom border
	 * - timing printing
	 * - debuging shortcut functions initialization
	 * - exceptions hdd logging or printing in development mode
	 */
	class Debug
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
		 * Email recepient to send information about exceptions or errors.
		 * 'admin@localhost' by default.
		 * @var string
		 */
		public static $EmailRecepient = 'admin@localhost';

		/**
		 * Relative path from app root to store any log information.
		 * '/Var/Logs' by default.
		 * @var mixed
		 */
		public static $LogDirectory = '/Var/Logs';

		/**
		 * Semaphore to execute Init(); method only one time.
		 * TRUE if development, FALSE if anything else
		 * @var boolean
		 */
		protected static $development = NULL;
	
		/**
		 * Debuging and loging handlers, this shoud be customized in extended class.
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
		 * True for cofigured debug class as \MvcCore\Debug, FALSE for any other configured extension
		 * @var bool
		 */
		protected static $originalDebugClass = TRUE;

		/**
		 * Initialize global development shorthands.
		 * @param string $logDirectory relative path from app root
		 * @var callable
		 */
		public static $InitGlobalShortHands = array();

		/**
		 * Initialize debuging and loging.
		 * @return void
		 */
		public static function Init () {
			if (!is_null(static::$development)) return;
			$app = \MvcCore::GetInstance();
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
			
			$scriptPath = php_sapi_name() == 'cli'
				? str_replace('\\', '/', getcwd()) . '/' . $_SERVER['SCRIPT_FILENAME']
				: str_replace('\\', '/', $_SERVER['SCRIPT_FILENAME']);
			$lastSlas = strrpos($scriptPath, '/');
			$appRoot = substr($scriptPath, 0, $lastSlas !== FALSE ? $lastSlas : strlen($scriptPath));
			static::$LogDirectory = $appRoot . static::$LogDirectory;

			static::$originalDebugClass = $app->GetDebugClass() == __CLASS__;
			static::initLogDirectory(static::$LogDirectory);
			static::initHandlers();
			$initGlobalShortHandsHandler = static::$InitGlobalShortHands;
			$initGlobalShortHandsHandler(static::$LogDirectory);
		}

		/**
		 * Initialize debuging and loging handlers.
		 * @return void
		 */
		protected static function initHandlers () {
			foreach (static::$handlers as $key => $value) {
				static::$handlers[$key] = array(__CLASS__, $value);
			}
			static::$handlers = (object) static::$handlers;
			register_shutdown_function(self::$handlers->shutdownHandler);
		}

		/**
		 * If log directory doesn't exist, create new directory - relative from app root.
		 * @param string $logDirectory relative path from app root
		 * @return void
		 */
		protected static function initLogDirectory ($logDirectory) {
			if (!is_dir($logDirectory)) mkdir($logDirectory, 0777, TRUE);
			if (!is_writable($logDirectory)) {
				try {
					chmod($logDirectory, 0777);
				} catch (\Exception $e) {
					die('['.static::class.'] ' . $e->getMessage());
				}
			}
		}

		/**
		 * Starts/stops stopwatch.
		 * @param  string  $name time pointer name
		 * @return float		 elapsed seconds
		 */
		public static function Timer ($name = NULL) {
			return static::BarDump(
				call_user_func(static::$handlers->timer, $name),
				$name
			);
		}

		/**
		 * Dumps information about a variable in readable format.
		 * @tracySkipLocation
		 * @param  mixed  $value	variable to dump
		 * @param  bool   $return	return output instead of printing it? (bypasses $productionMode)
		 * @return mixed			variable itself or dump
		 */
		public static function Dump ($value, $return = FALSE) {
			if (static::$originalDebugClass) {
				$args = func_get_args();
				$options = isset($args[2]) ? array('dieDumpCall' => TRUE) : array() ;
				if ($return) $options['doNotStore'] = TRUE;
				$options['backtraceIndex'] = 1;
				$result = static::dumpHandler($value, NULL, $options);
			} else {
				$result = call_user_func(static::$handlers->dump, $value, $return);
			}
			if ($return) return $result;
		}

		/**
		 * Dumps information about a variable in Tracy Debug Bar.
		 * @tracySkipLocation
		 * @param  mixed	$value		variable to dump
		 * @param  string	$title		optional title
		 * @param  array	$options	dumper options
		 * @return mixed				variable itself
		 */
		public static function BarDump ($value, $title = NULL, $options = array()) {
			return call_user_func_array(static::$handlers->barDump, func_get_args());
		}

		/**
		 * Logs message or exception.
		 * @param  string|\Exception	$value
		 * @param  string				$priority
		 * @return string				logged error filename
		 */
		public static function Log ($value, $priority = self::INFO) {
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
				return @call_user_func_array(static::$handlers->log, $args);
			}
		}

		/**
		 * Sends message to FireLogger console.
		 * @param	mixed	$message	message to log
		 * @param	string	$priority	priority
		 * @return	bool				was successful?
		 */
		public static function FireLog ($message, $priority = self::DEBUG) {
			// TODO: implement simple firelog
			$args = func_get_args();
			if (static::$originalDebugClass) {
				$args = array($message, NULL, array('priority' => $priority));
			}
			return call_user_func_array(static::$handlers->fireLog, $args);
		}

		/**
		 * Handler to catch uncaught exception.
		 * @param  \Exception|\Throwable
		 * @return void
		 */
		public static function Exception ($exception, $exit = TRUE) {
			return call_user_func_array(static::$handlers->exceptionHandler, func_get_args());
		}

		/**
		 * Print all catched dumps at the end of sended response body.
		 * @return void
		 */
		public static function ShutdownHandler () {
			if (!count(static::$dumps)) return;
			$dumps = '';
			$dieDump = FALSE;
			foreach (static::$dumps as $values) {
				$dumps .= '<div class="item">';
				if (!is_null($values[1])) {
					$dumps .= '<pre class="title">'.$values[1].'</pre>';
				}
				$dumps .= '<div class="value">'
					.preg_replace("#\[([^\]]*)\]=>([^\n]*)\n(\s*)#", "[$1] => ", 
						str_replace("<required>","&lt;required&gt;", $values[0])
					)
					.'</div></div>';
				if (isset($values[2]['dieDumpCall']) && $values[2]['dieDumpCall']) $dieDump = TRUE;
			}
			$template = file_get_contents(dirname(__FILE__).'/debug.html');
			echo str_replace(
				array('%mvccoreDumps%', '%mvccoreDumpsCount%', '%mvccoreDumpsClose%'),
				array($dumps, count(static::$dumps), $dieDump ? ';' : 'q();'),
				$template
			);
		}

		/**
		 * Starts/stops stopwatch.
		 * @param  string  name
		 * @return float   elapsed seconds
		 */
		protected static function timerHandler ($name = NULL) {
			$now = microtime(TRUE);
			if (is_null($name)) return $now - \MvcCore::GetInstance()->GetMicrotime();
			$difference = isset(static::$timers[$name]) ? $now - static::$timers[$name] : 0;
			static::$timers[$name] = $now;
			return $difference;
		}

		/**
		 * Dump any variable into string throw output buffering, 
		 * store result for printing later. Return printed variable string.
		 * @param mixed $var
		 * @param string $title
		 * @param array $options
		 * @return string
		 */
		protected static function dumpHandler ($var, $title = NULL, $options = array()) {
			ob_start();
			var_dump($var);
			// format xdebug first small element with file:
			$content = preg_replace("#\</small\>\n#", '</small>', ob_get_clean(), 1);
			$content = preg_replace("#\<small\>([^\>]*)\>#", '', $content, 1);
			$backtraceIndex = isset($options['backtraceIndex']) ? $options['backtraceIndex'] : 2 ;
			$backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $backtraceIndex + 1);
			$originalPlace = (object) $backtrace[$backtraceIndex];
			$content = '<small class="file">' . $originalPlace->file . ':' . $originalPlace->line . '</small>' . $content;
			if (!isset($options['doNotStore'])) static::$dumps[] = array($content, $title, $options);
			return $content;
		}

		/**
		 * Exception printing for development, logging for production.
		 * @param \Exception $e
		 * @return void
		 */
		protected static function exceptionHandler (\Exception $e, $exit = TRUE) {
			throw $e;
			//if ($exit) exit;
		}
	}

}

namespace {
	\MvcCore\Debug::$InitGlobalShortHands = function () {
		/**
			* Dump a variable.
			* @param  mixed  $value	variable to dump
			* @param  string $title	optional title
			* @param  array  $options	dumper options
			* @return mixed  variable itself
			*/
		function x ($value, $title = NULL, $options = array()) {
			return \MvcCore\Debug::BarDump($value, $title, $options);
		}
		/**
			* Dumps variables about a variable.
			* @param  ...mixed  variables to dump
			*/
		function xx () {
			$args = func_get_args();
			foreach ($args as $arg) \MvcCore\Debug::BarDump($arg);
		}
		/**
			* Dump a variable and die. If no variable, throw stop exception.
			* @param  mixed  $var		variable to dump
			* @param  string $title	optional title
			* @param  array  $options	dumper options
			* @throws \Exception
			* @return void
			*/
		function xxx ($var = NULL, $title = NULL, $options = array()) {
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