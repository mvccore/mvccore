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

trait Initializations
{
	/**
	 * Initialize debugging and logging, once only.
	 * @param bool $forceDevelopmentMode If defined as `TRUE` or `FALSE`,
	 *								   debug mode will be set not by config but by this value.
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

		static::$originalDebugClass = ltrim(static::$app->GetDebugClass(), '\\') == __CLASS__;
		static::initHandlers();
		$initGlobalShortHandsHandler = static::$InitGlobalShortHands;
		$initGlobalShortHandsHandler(static::$development);
	}

	/**
	 * Initialize debugging and logging handlers.
	 * @return void
	 */
	protected static function initHandlers () {
		$className = get_called_class();
		foreach (static::$handlers as $key => $value) {
			static::$handlers[$key] = [$className, $value];
		}
		register_shutdown_function(static::$handlers['shutdownHandler']);
	}

	/**
	 * If log directory doesn't exist, create new directory - relative from app root.
	 * @param string $logDirAbsPath Absolute directory path.
	 * @return void
	 */
	protected static function initLogDirectory () {
		if (static::$logDirectoryInitialized) return;
		$app = static::$app ?: (static::$app = & \MvcCore\Application::GetInstance());
		$configClass = $app->GetConfigClass();
		$cfg = $configClass::GetSystem();
		$logDirRelPath = static::$LogDirectory;
		if ($cfg !== FALSE && isset($cfg->debug)) {
			$cfgDebug = & $cfg->debug;
			if (isset($cfgDebug->emailRecepient))
				static::$EmailRecepient = $cfgDebug->emailRecepient;
			if (isset($cfgDebug->logDirectory))
				$logDirRelPath = $cfgDebug->logDirectory; // relative path from app root
		}
		if (php_sapi_name() == 'cli') {
			$backtraceItems = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
			$scriptFilename =  $backtraceItems[count($backtraceItems) - 1]['file'];
		} else {
			$scriptFilename = $_SERVER['SCRIPT_FILENAME'];
		}
		$scriptFilename = str_replace('\\', '/', $scriptFilename);
		$appRoot = dirname($scriptFilename);
		$logDirAbsPath = $appRoot . $logDirRelPath;
		static::$LogDirectory = $logDirAbsPath;
		try {
			if (!is_dir($logDirAbsPath)) {
				if (!mkdir($logDirAbsPath, 0777, TRUE))
					throw new \RuntimeException(
						'['.__CLASS__."] It was not possible to create log directory: `".$logDirAbsPath."`."
					);
				if (!is_writable($logDirAbsPath))
					if (!chmod($logDirAbsPath, 0777))
						throw new \RuntimeException(
							'['.__CLASS__."] It was not possible to setup privileges to log directory: `".$logDirAbsPath."` to writeable mode 0777."
						);
			}
		} catch (\Exception $e) {
			die('['.__CLASS__.'] ' . $e->getMessage());
		}
		static::$logDirectoryInitialized = TRUE;
	}
}
