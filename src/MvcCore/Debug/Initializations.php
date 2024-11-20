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
trait Initializations {

	/**
	 * @inheritDoc
	 * @param  bool $forceDebugging If defined as `TRUE` or `FALSE`,
	 *                              debugging mode will be set not
	 *                              by config but by this value.
	 * @return void
	 */
	public static function Init ($forceDebugging = NULL) {
		if (static::$debugging !== NULL) return;

		if (static::$strictExceptionsMode === NULL)
			self::initStrictExceptionsMode(static::$strictExceptionsMode);

		$app = static::$app ?: (static::$app = \MvcCore\Application::GetInstance());
		static::$requestBegin = $app->GetRequest()->GetStartTime();
		
		if (is_bool($forceDebugging)) {
			static::$debugging = $forceDebugging;
		} else {
			$sysCfgDebug = static::GetSystemCfgDebugSection();
			if (isset($sysCfgDebug->enabled)) {
				static::$debugging = !!$sysCfgDebug->enabled;
			} else {
				$environment = $app->GetEnvironment();
				static::$debugging = !$environment->IsProduction();	
			}
		}
		
		// do not initialize log directory here every time, initialize log
		// directory only if there is necessary to log something - later.

		static::$originalDebugClass = ltrim($app->GetDebugClass(), '\\') === __CLASS__;
		static::initHandlers();
		if (static::$InitGlobalShortHands !== NULL)
			call_user_func(static::$InitGlobalShortHands, [static::$debugging]);
	}

	/**
	 * @inheritDoc
	 * @param  bool       $strictExceptionsMode
	 * @param  array<int> $errorLevelsToExceptions E_ERROR, E_RECOVERABLE_ERROR, E_CORE_ERROR, E_USER_ERROR, E_WARNING, E_CORE_WARNING, E_USER_WARNING
	 * @return bool|NULL
	 */
	public static function SetStrictExceptionsMode ($strictExceptionsMode, array $errorLevelsToExceptions = []) {
		if ($strictExceptionsMode && !static::$strictExceptionsMode) {
			$errorLevels = array_fill_keys($errorLevelsToExceptions, TRUE);
			$allLevelsToExceptions = isset($errorLevels[E_ALL]);
			$prevErrorHandler = NULL;
			$newErrorHandler = function(
				$errLevel, $errMessage, $errFile, $errLine
			) use (
				& $prevErrorHandler, $errorLevels, $allLevelsToExceptions
			) {
				if ($errFile === '' && defined('HHVM_VERSION'))  // https://github.com/facebook/hhvm/issues/4625
					$errFile = func_get_arg(5)[1]['file'];
				if ($allLevelsToExceptions || isset($errorLevels[$errLevel]))
					throw new \ErrorException($errMessage, $errLevel, $errLevel, $errFile, $errLine);
				return $prevErrorHandler
					? call_user_func_array($prevErrorHandler, func_get_args())
					: FALSE;
			};
			$prevErrorHandler = set_error_handler($newErrorHandler);
			$error = error_get_last();
			if ($error !== NULL) // some error before this initialization
				$newErrorHandler($error['type'], $error['message'], $error['file'], $error['line']);
			self::$prevErrorHandler = & $prevErrorHandler;
		} else if (!$strictExceptionsMode && static::$strictExceptionsMode) {
			if (self::$prevErrorHandler !== NULL) {
				set_error_handler(self::$prevErrorHandler);
			} else {
				restore_error_handler();
			}
		}
		return static::$strictExceptionsMode = $strictExceptionsMode;
	}

	/**
	 * @inheritDoc
	 * @return bool
	 */
	public static function GetDebugging () {
		return static::$debugging;
	}

	/**
	 * @inheritDoc
	 * @param  bool $debugging
	 * @return bool
	 */
	public static function SetDebugging ($debugging) {
		return static::$debugging = $debugging;
	}

	/**
	 * @inheritDoc
	 * @return \stdClass
	 */
	public static function GetSystemCfgDebugSection () {
		if (self::$systemConfigDebugValues !== NULL) 
			return self::$systemConfigDebugValues;
		$sysConfigProps = array_merge([], static::$systemConfigDebugProps);
		$sectionName = $sysConfigProps['sectionName'];
		unset($sysConfigProps['sectionName']);
		$app = static::$app ?: (static::$app = \MvcCore\Application::GetInstance());
		$configClass = $app->GetConfigClass();
		$cfg = $configClass::GetConfigSystem();
		if ($cfg === NULL || ($cfg !== NULL && !isset($cfg->{$sectionName}))) {
			$result = (object) array_fill_keys($sysConfigProps, NULL);
			return self::$systemConfigDebugValues = $result;
		}
		$result = $cfg->{$sectionName};
		if (is_array($result)) {
			foreach ($sysConfigProps as $prop)
				if (!isset($result[$prop]))
					$result[$prop] = NULL;
			$result = (object) $result;
		} else {
			foreach ($sysConfigProps as $prop)
				if (!isset($result->{$prop}))
					$result->{$prop} = NULL;
		}
		return self::$systemConfigDebugValues = $result;
	}

	/**
	 * Initialize strict exceptions mode in default levels or in customized
	 * levels from system config.
	 * @param  bool|NULL $strictExceptionsMode
	 * @return bool|NULL
	 */
	protected static function initStrictExceptionsMode ($strictExceptionsMode) {
		$errorLevelsToExceptions = [];
		if ($strictExceptionsMode !== FALSE) {
			$sysCfgDebug = static::GetSystemCfgDebugSection();
			if ($sysCfgDebug->strictExceptions !== NULL) {
				$rawStrictExceptions = $sysCfgDebug->strictExceptions;
				if (
					$rawStrictExceptions === 0 ||
					$rawStrictExceptions === FALSE
				) {
					$strictExceptionsMode = FALSE;
				} else {
					$strictExceptionsMode = TRUE;
					$rawStrictExceptions = is_array($rawStrictExceptions)
						? $rawStrictExceptions
						: explode(',', trim($rawStrictExceptions, '[]'));
					$errorLevelsToExceptions = array_map(
						function ($rawErrorLevel) {
							$rawErrorLevel = trim($rawErrorLevel);
							if (is_numeric($rawErrorLevel)) return intval($rawErrorLevel);
							return constant($rawErrorLevel);
						}, $rawStrictExceptions
					);
				}
			} else {
				$strictExceptionsMode = TRUE;
				$errorLevelsToExceptions = static::$strictExceptionsModeDefaultLevels;
			}
		}
		return static::SetStrictExceptionsMode($strictExceptionsMode, $errorLevelsToExceptions);
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
	 * @return string
	 */
	protected static function initLogDirectory () {
		if (static::$logDirectoryInitialized) return;
		$sysCfgDebug = static::GetSystemCfgDebugSection();
		$app = static::$app ?: (static::$app = \MvcCore\Application::GetInstance());
		if (isset($sysCfgDebug->logDirectory))
			$app->SetPathLogs($sysCfgDebug->logDirectory);
		$logDirAbsPath = $app->GetPathLogs(TRUE);
		try {
			if (!is_dir($logDirAbsPath)) {
				if (!mkdir($logDirAbsPath, 0777, TRUE))
					throw new \RuntimeException(
						'['.get_called_class()."] It was not possible to create log directory: `".$logDirAbsPath."`."
					);
				if (!is_writable($logDirAbsPath))
					if (!chmod($logDirAbsPath, 0777))
						throw new \RuntimeException(
							'['.get_called_class()."] It was not possible to setup privileges to log directory: `".$logDirAbsPath."` to writeable mode 0777."
						);
			}
		} catch (\Exception $e) {
			die('['.get_called_class().'] ' . $e->getMessage());
		}
		static::$logDirectoryInitialized = TRUE;
		return $logDirAbsPath;
	}

}
