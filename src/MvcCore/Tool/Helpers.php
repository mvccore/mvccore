<?php

/**
 * MvcCore
 *
 * This source file is subject to the BSD 3 License
 * For the full copyright and license information, please view
 * the LICENSE.md file that are distributed with this source code.
 *
 * @copyright	Copyright (c) 2016 Tom Flidr (https://github.com/mvccore)
 * @license		https://mvccore.github.io/docs/mvccore/5.0.0/LICENSE.md
 */

namespace MvcCore\Tool;

trait Helpers {

	/**
	 * Platform specific temporary directory.
	 * @var string|NULL
	 */
	protected static $tmpDir = NULL;

	/**
	 * @inheritDocs
	 * @return string
	 */
	public static function GetSystemTmpDir () {
		if (self::$tmpDir === NULL) {
			$tmpDir = sys_get_temp_dir();
			if (strtolower(substr(PHP_OS, 0, 3)) == 'win') {
				// Windows:
				$sysRoot = getenv('SystemRoot');
				// do not store anything directly in C:\Windows, use C:\windows\Temp instead
				if (!$tmpDir || $tmpDir === $sysRoot) {
					$tmpDir = !empty($_SERVER['TEMP'])
						? $_SERVER['TEMP']
						: (!empty($_SERVER['TMP'])
							? $_SERVER['TMP']
							: (!empty($_SERVER['WINDIR'])
								? $_SERVER['WINDIR'] . '/Temp'
								: $sysRoot . '/Temp'
							)
						);
				}
				$tmpDir = str_replace('\\', '/', $tmpDir);
			} else if (!$tmpDir) {
				// Other systems
				$iniSysTempDir = @ini_get('sys_temp_dir');
				$tmpDir = !empty($_SERVER['TMPDIR'])
					? $_SERVER['TMPDIR']
					: (!empty($_SERVER['TMP'])
						? $_SERVER['TMP']
						: (!empty($iniSysTempDir)
							? $iniSysTempDir
							: '/tmp'
						)
					);
			}
			self::$tmpDir = $tmpDir;
		}
		return self::$tmpDir;
	}
	
	/**
	 * @inheritDocs
	 * @param string $jsonStr
	 * @return bool
	 */
	public static function IsQueryString ($queryStr) {
		$queryStr = trim($queryStr, '&=');
		$apmsCount = substr_count($queryStr, '&');
		$equalsCount = substr_count($queryStr, '=');
		$firstAndLast = mb_substr($queryStr, 0, 1) . mb_substr($queryStr, -1, 1);
		if ($firstAndLast === '{}' || $firstAndLast === '[]') return FALSE; // most likely a JSON
		if ($apmsCount === 0 && $equalsCount === 0) return FALSE; // there was `nothing`
		if ($equalsCount > 0) $equalsCount -= 1;
		if ($equalsCount === 0) return TRUE; // there was `key=value`
		return $apmsCount / $equalsCount >= 1; // there was `key=&key=value`
	}

	/**
	 * @inheritDocs
	 * @param string|callable $internalFnOrHandler
	 * @param array $args
	 * @param callable $onError
	 * @return mixed
	 */
	public static function Invoke ($internalFnOrHandler, array $args, callable $onError) {
		$prevErrorHandler = NULL;
		$prevErrorHandler = set_error_handler(
			function (
				$errLevel, $errMessage, $errFile, $errLine
			) use (
				$onError, & $prevErrorHandler, $internalFnOrHandler
			) {
				if ($errFile === '' && defined('HHVM_VERSION'))  // https://github.com/facebook/hhvm/issues/4625
					$errFile = func_get_arg(5)[1]['file'];
				if ($errFile === __FILE__) {
					$funcNameStr = is_string($internalFnOrHandler)
						? $internalFnOrHandler
						: (is_array($internalFnOrHandler) && count($internalFnOrHandler) === 2
							? $internalFnOrHandler[1]
							: strval($internalFnOrHandler)
						);
					$errMessage = preg_replace("#^$funcNameStr\(.*?\): #", '', $errMessage);
					if ($onError($errMessage, $errLevel, $errFile, $errLine) !== FALSE)
						return TRUE;
				}
				return $prevErrorHandler
					? call_user_func_array($prevErrorHandler, func_get_args())
					: FALSE;
			}
		);
		try {
			return call_user_func_array($internalFnOrHandler, $args);
		} catch (\Exception $e) { // backward compatibility
		} catch (\Throwable $e) {
		} /* finally {
			restore_error_handler();
		}*/
		restore_error_handler();
		return NULL;
	}

	/**
	 * @inheritDocs
	 * @see http://php.net/manual/en/function.flock.php
	 * @see http://php.net/manual/en/function.set-error-handler.php
	 * @see http://php.net/manual/en/function.clearstatcache.php
	 * @param string $fullPath File full path.
	 * @param string $content String content to write.
	 * @param string $writeMode PHP `fopen()` second argument flag, could be `w`, `w+`, `a`, `a+` etc...
	 * @param int $lockWaitMilliseconds Milliseconds to wait before next lock file existence is checked in `while()` cycle.
	 * @param int $maxLockWaitMilliseconds Maximum milliseconds time to wait before thrown an exception about not possible write.
	 * @param int $oldLockMillisecondsTolerance Maximum milliseconds time to consider lock file as operative or as old after some died process.
	 * @throws \Exception
	 * @return bool
	 */
	public static function AtomicWrite (
		$fullPath,
		$content,
		$writeMode = 'w',
		$lockWaitMilliseconds = 100,
		$maxLockWaitMilliseconds = 5000,
		$oldLockMillisecondsTolerance = 30000
	) {
		$waitUTime = $lockWaitMilliseconds * 1000;
		$lockHandle = NULL;

		$tmpDir = self::GetSystemTmpDir();
		$lockFullPath = $tmpDir . '/mvccore_lock_' . sha1($fullPath) . '.tmp';

		// capture E_WARNINGs for `fopen()` and `filemtime()` and do not log them:
		set_error_handler(function ($level, $msg, $file, $line, $args) use (& $fullPath, & $lockFullPath, & $lockHandle) {
			if ($level == E_WARNING) {
				if (
					mb_strpos($msg, 'fopen(' . $fullPath) === 0 ||
					mb_strpos($msg, 'filemtime(' . $fullPath) === 0 ||
					mb_strpos($msg, 'fopen(' . $lockFullPath) === 0 ||
					mb_strpos($msg, 'filemtime(' . $lockFullPath) === 0
				) {
					if ($lockHandle !== NULL) {
						// unlock before exception
						@flock($lockHandle, LOCK_UN);
						fclose($lockHandle);
						unlink($lockFullPath);
					}
					throw new \Exception ($msg);
				}
			}
			return FALSE;
		}, E_WARNING);

		// get last modification time for lock file
		// if exists to prevent old locks in cache
		clearstatcache(TRUE, $lockFullPath);
		if (file_exists($lockFullPath)) {
			$fileModTime = @filemtime($lockFullPath);
			if ($fileModTime !== FALSE) {
				if (time() > $fileModTime + $oldLockMillisecondsTolerance)
					unlink($lockFullPath);
			}
		}

		// try to create lock file handle
		$waitingTime = 0;
		while (TRUE) {
			clearstatcache(TRUE, $lockFullPath);
			$lockHandle = @fopen($lockFullPath, 'x');
			if ($lockHandle !== FALSE) break;
			$waitingTime += $lockWaitMilliseconds;
			if ($waitingTime > $maxLockWaitMilliseconds) {
				throw new \Exception(
					'Unable to create lock handle: `' . $lockFullPath
					. '` for file: `' . $fullPath
					. '`. Lock creation timeout. Try to clear cache: `'
					. $tmpDir . '`'
				);
			}
			usleep($waitUTime);
		}
		if (!flock($lockHandle, LOCK_EX)) {
			// unlock before exception
			fclose($lockHandle);
			unlink($lockFullPath);
			throw new \Exception(
				'Unable to create lock handle: `' . $lockFullPath
				. '` for file: `' . $fullPath
				. '`. Lock creation timeout. Try to clear cache: `'
				. $tmpDir . '`'
			);
		}
		fwrite($lockHandle, $fullPath);
		fflush($lockHandle);

		// write or append the file
		clearstatcache(TRUE, $fullPath);
		$handle = @fopen($fullPath, $writeMode);
		if ($handle && !flock($handle, LOCK_EX))
			$handle = FALSE;
		if (!$handle) {
			// unlock before exception
			flock($lockHandle, LOCK_UN);
			fclose($lockHandle);
			unlink($lockFullPath);
			throw new \Exception(
				'Unable to create locked handle for file: `' . $fullPath . '`.'
			);
		}
		fwrite($handle, $content);
		fflush($handle);
		flock($handle, LOCK_UN);

		// unlock
		flock($lockHandle, LOCK_UN);
		fclose($lockHandle);
		$success = unlink($lockFullPath);

		restore_error_handler();

		return $success;
	}

	/**
	 * @inheritDocs
	 * @see https://www.php.net/manual/en/function.realpath.php
	 * @param string $path
	 * @return string
	 */
	public static function RealPathVirtual ($path) {
		$path = str_replace('\\', '/', $path);
		$rawParts = explode('/', $path);
		$parts = array_filter($rawParts, 'strlen');
		if ($rawParts[0] == '' && mb_substr($path, 0, 1) == '/')
			array_unshift($parts, '');
		$items = [];
		foreach ($parts as $part) {
			if ('.' == $part) continue;
			if ('..' == $part) {
				array_pop($items);
			} else {
				$items[] = $part;
			}
		}
		return implode('/', $items);
	}
}