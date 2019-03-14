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
	 * @param bool $forceDevelopmentMode	If defined as `TRUE` or `FALSE`,
	 *										debugging mode will be set not 
	 *										by config but by this value.
	 * @return void
	 */
	public static function Init ($forceDevelopmentMode = NULL) {
		if (static::$debugging !== NULL) return;

		if (self::$strictExceptionsMode === NULL)
			self::SetStrictExceptionsMode(TRUE);
		
		$app = static::$app ?: (static::$app = & \MvcCore\Application::GetInstance());
		static::$requestBegin = $app->GetRequest()->GetMicrotime();
		
		if (gettype($forceDevelopmentMode) == 'boolean') {
			static::$debugging = $forceDevelopmentMode;
		} else {
			$configClass = $app->GetConfigClass();
			static::$debugging = $configClass::IsDevelopment(TRUE);
		}

		// do not initialize log directory here every time, initialize log
		// directory only if there is necessary to log something - later.

		$selfClass = version_compare(PHP_VERSION, '5.5', '>') ? self::class : __CLASS__;
		static::$originalDebugClass = ltrim($app->GetDebugClass(), '\\') == $selfClass;
		static::initHandlers();
		$initGlobalShortHandsHandler = static::$InitGlobalShortHands;
		$initGlobalShortHandsHandler(static::$debugging);
	}

	/**
	 * Configure strict exceptions mode, mode is enabled by default.
	 * If mode is configured to `FALSE` and any previous error handler exists,
	 * it's automatically assigned back, else there is only called 
	 * `restore_error_handler()` to restore system error handler.
	 * @param bool $strictExceptionsMode 
	 * @return bool
	 */
	public static function SetStrictExceptionsMode ($strictExceptionsMode) {
		if ($strictExceptionsMode && !self::$strictExceptionsMode) {
			self::$prevErrorHandler = set_error_handler(function($errLevel, $errMessage, $errFile, $errLine, $errContext) {
				if ($errFile === '' && defined('HHVM_VERSION'))  // https://github.com/facebook/hhvm/issues/4625
					$errFile = func_get_arg(5)[1]['file'];
				throw new \ErrorException($errMessage, $errLevel, $errLevel, $errFile, $errLine);
			});
		} else if (!$strictExceptionsMode && self::$strictExceptionsMode) {
			if (self::$prevErrorHandler !== NULL) {
				set_error_handler(self::$prevErrorHandler);
			} else {
				restore_error_handler();
			}
		}
		return self::$strictExceptionsMode = $strictExceptionsMode;
	}

	/**
	 * Initialize debugging and logging handlers.
	 * @return void
	 */
	protected static function initHandlers () {
		$className = version_compare(PHP_VERSION, '5.5', '>') ? static::class : get_called_class();
		foreach (static::$handlers as $key => $value) {
			static::$handlers[$key] = [$className, $value];
		}
		register_shutdown_function(static::$handlers['shutdownHandler']);
	}

	/**
	 * If log directory doesn't exist, create new directory - relative from app root.
	 * @return string
	 */
	protected static function initLogDirectory () {
		//if (static::$logDirectoryInitialized) return;
		$sysCfgDebug = static::getSystemCfgDebugSection();
		$logDirConfiguredPath = isset($sysCfgDebug['logDirectory']) 
			? $sysCfgDebug['logDirectory'] 
			: static::$LogDirectory;
		if (mb_substr($logDirConfiguredPath, 0, 1) === '~') {
			$app = static::$app ?: (static::$app = & \MvcCore\Application::GetInstance());
			$logDirAbsPath = $app->GetRequest()->GetAppRoot() . '/' . ltrim(mb_substr($logDirConfiguredPath, 1), '/');
		} else {
			$logDirAbsPath = $logDirConfiguredPath;
		}
		static::$LogDirectory = $logDirAbsPath;
		try {
			if (!is_dir($logDirAbsPath)) {
				$selfClass = version_compare(PHP_VERSION, '5.5', '>') ? self::class : __CLASS__;
				if (!mkdir($logDirAbsPath, 0777, TRUE))
					throw new \RuntimeException(
						'['.$selfClass."] It was not possible to create log directory: `".$logDirAbsPath."`."
					);
				if (!is_writable($logDirAbsPath))
					if (!chmod($logDirAbsPath, 0777))
						throw new \RuntimeException(
							'['.$selfClass."] It was not possible to setup privileges to log directory: `".$logDirAbsPath."` to writeable mode 0777."
						);
			}
		} catch (\Exception $e) {
			$selfClass = version_compare(PHP_VERSION, '5.5', '>') ? self::class : __CLASS__;
			die('['.$selfClass.'] ' . $e->getMessage());
		}
		static::$logDirectoryInitialized = TRUE;
		return $logDirAbsPath;
	}

	/**
	 * Try to load system config by configured config class and try to find and 
	 * read `debug` section as associative array (or return an empty array).
	 * @return array
	 */
	protected static function getSystemCfgDebugSection () {
		if (self::$systemConfigDebugValues !== NULL) return self::$systemConfigDebugValues;
		$result = [];
		$app = static::$app ?: (static::$app = & \MvcCore\Application::GetInstance());
		$configClass = $app->GetConfigClass();
		$cfg = $configClass::GetSystem();
		if ($cfg === FALSE) return $result;
		$cfgProps = (object) static::$systemConfigDebugProps;
		if (!isset($cfg->{$cfgProps->sectionName})) return $result;
		$cfgDebug = & $cfg->{$cfgProps->sectionName};
		if (isset($cfgDebug->{$cfgProps->emailRecepient}))
			$result['emailRecepient'] = $cfgDebug->{$cfgProps->emailRecepient};
		if (isset($cfgDebug->{$cfgProps->logDirectory}))
			$result['logDirectory'] = $cfgDebug->{$cfgProps->logDirectory};
		if (isset($cfgDebug->{$cfgProps->strictExceptions}))
			$result['exceptions'] = $cfgDebug->{$cfgProps->strictExceptions};
		self::$systemConfigDebugValues = $result;
		return $result;
	}
}
