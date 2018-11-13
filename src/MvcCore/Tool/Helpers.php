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

namespace MvcCore\Tool;

trait Helpers
{
	/**
	 * Platform specific temporary directory.
	 * @var string|NULL
	 */
	protected static $tmpDir = NULL;

	/**
	 * Safely encode json string from php value.
	 * @param mixed $data
	 * @throws \Exception JSON encoding error.
	 * @return string
	 */
	public static function EncodeJson (& $data) {
		$flags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP |
			(defined('JSON_UNESCAPED_SLASHES') ? JSON_UNESCAPED_SLASHES : 0) |
			(defined('JSON_UNESCAPED_UNICODE') ? JSON_UNESCAPED_UNICODE : 0) |
			(defined('JSON_PRESERVE_ZERO_FRACTION') ? JSON_PRESERVE_ZERO_FRACTION : 0);
		$json = json_encode($data, $flags);
		if ($errorCode = json_last_error()) {
			throw new \RuntimeException("[".__CLASS__."] ".json_last_error_msg(), $errorCode);
		}
		if (PHP_VERSION_ID < 70100) {
			$json = strtr($json, [
				"\xe2\x80\xa8" => '\u2028',
				"\xe2\x80\xa9" => '\u2029',
			]);
		}
		return $json;
	}

	/**
	 * Safely decode json string into php `stdClass/array`.
	 * Result has always keys:
	 * - `"success"`	- decoding boolean success
	 * - `"data"`		- decoded json data as stdClass/array
	 * - `"errorData"`	- array with possible decoding error message and error code
	 * @param string $jsonStr
	 * @return object
	 */
	public static function DecodeJson (& $jsonStr) {
		$result = (object) [
			'success'	=> TRUE,
			'data'		=> null,
			'errorData'	=> [],
		];
		$jsonData = @json_decode($jsonStr);
		$errorCode = json_last_error();
		if ($errorCode == JSON_ERROR_NONE) {
			$result->data = $jsonData;
		} else {
			$result->success = FALSE;
			$result->errorData = [json_last_error_msg(), $errorCode];
		}
		return $result;
	}

	/**
	 * Returns the OS-specific directory for temporary files.
	 * @return string
	 */
	public static function GetSystemTmpDir () {
		if (self::$tmpDir === NULL) {
			if (strtolower(substr(PHP_OS, 0, 3)) == 'win') {
				// Windows:
				$tmpDir = sys_get_temp_dir();
				$sysRoot = getenv('SystemRoot');
				// do not store anything directly in C:\Windows, use C\windows\Temp instead
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
			} else {
				// Other systems
				$tmpDir = !empty($_SERVER['TMPDIR']) 
					? $_SERVER['TMPDIR']
					: (!empty($_SERVER['TMP'])
						? $_SERVER['TMP']
						: '/tmp'
					);
			}
			self::$tmpDir = $tmpDir;
		}
		return self::$tmpDir;
	}

	/**
	 * Safely invoke internal PHP function with it's own error handler.
	 * Error handler accepts arguments: 
	 * - `string $errMessage`	- Error message.
	 * - `int $errLevel`		- Level of the error raised.
	 * - `string $errFile`		- Optional, full path to error file name where error was raised.
	 * - `int $errLine`			- Optional, The error file line number.
	 * - `array $errContext`	- Optional, array that points to the active symbol table at the 
	 *							  point the error occurred. In other words, errcontext will contain 
	 *							  an array of every variable that existed in the scope the error 
	 *							  was triggered in. User error handler must not modify error context.
	 *							  Warning: This parameter has been DEPRECATED as of PHP 7.2.0. 
	 *							  Relying on it is highly discouraged.
	 * If the custom error handler returns `FALSE`, normal internal error handler continues.
	 * This function is very PHP specific. It's proudly used from Nette Framework, optimized for PHP 5.4+ incl.:
	 * https://github.com/nette/utils/blob/b623b2deec8729c8285d269ad991a97504f76bd4/src/Utils/Callback.php#L63-L84
	 * @param string $internalFuncName 
	 * @param array $args 
	 * @param callable $onError 
	 * @return mixed
	 */
	public static function Invoke ($internalFuncName, array $args, callable $onError) {
		$prevErrorHandler = set_error_handler(
			function ($errLevel, $errMessage, $errFile, $errLine, $errContext) use ($onError, & $prevErrorHandler, $internalFuncName) {
				if ($errFile === '' && defined('HHVM_VERSION'))  // https://github.com/facebook/hhvm/issues/4625
					$errFile = func_get_arg(5)[1]['file'];
				if ($errFile === __FILE__) {
					$errMessage = preg_replace("#^$internalFuncName\(.*?\): #", '', $errMessage);
					if ($onError($errMessage, $errLevel, $errFile, $errLine, $errContext) !== FALSE) 
						return;
				}
				return $prevErrorHandler 
					? call_user_func_array($prevErrorHandler, func_get_args()) 
					: FALSE;
			}
		);
		try {
			return call_user_func_array($internalFuncName, $args);
		} catch (\Exception $e) {
		} /* finally {
			restore_error_handler();
		}*/
		restore_error_handler();
	}

	/**
	 * Write or append file content by only one single PHP process.
	 * @see http://php.net/manual/en/function.flock.php
	 * @see http://php.net/manual/en/function.set-error-handler.php
	 * @see http://php.net/manual/en/function.clearstatcache.php
	 * @param string $fullPath File full path.
	 * @param string $content String content ot write.
	 * @param string $writeMode PHP `fopen()` second argument flag, could be `w`, `w+`, `a`, `a+` etc...
	 * @param int $lockWaitMiliSeconds Miliseconds to wait before next lock file existence is checked in `while()` cycle.
	 * @param int $maxLockWaitMiliSeconds Maximum miliseconds time to wait before thrown an exception about not possible write.
	 * @param int $oldLockMilisecondsTolerance Maximum miliseconds time to consider lock file as operative or as old after some died process.
	 * @throws \Exception About 
	 * @return bool
	 */
	public function SingleProcessWrite (
		$fullPath, 
		& $content, 
		$writeMode = 'w', 
		$lockWaitMiliSeconds = 10, 
		$maxLockWaitMiliSeconds = 5000, 
		$oldLockMilisecondsTolerance = 30000
	) {
		$waitUTime = $lockWaitMiliSeconds * 1000;

		$tmpDir = self::GetSystemTmpDir();
		$lockFullPath = $tmpDir . '/mvccore_lock_' . sha1($fullPath) . '.tmp';

		// capture E_WARNINGs for fopen() and filemtime() and do not log them:
		set_error_handler(function ($level, $msg, $file, $line, $args) use ($lockFullPath) {
			if ($level == E_WARNING) {
				// do not log any fopen() E_WARNINGs
				if (mb_strpos($msg, 'fopen(' . $lockFullPath) === 0) return TRUE;
				// do not log any fopen() E_WARNINGs
				if (mb_strpos($msg, 'filemtime(' . $lockFullPath) === 0) return TRUE;
			}
			return FALSE;
		}, E_WARNING);

		// get last modification time for lock file 
		// if exists to prevent old locks in cache
		clearstatcache(TRUE, $lockFullPath);
		if (file_exists($lockFullPath)) {
			$fileModTime = @filemtime($lockFullPath);
			if ($fileModTime !== FALSE) {
				if (time() > $fileModTime + $oldLockMilisecondsTolerance) 
					unlink($lockFullPath);
			}
		}

		// try to create lock file handle
		$waitingTime = 0;
		$lockHandle = NULL;
		while (TRUE) {
			clearstatcache(TRUE, $lockFullPath);
			$lockHandle = @fopen($lockFullPath, 'x');
			if ($lockHandle !== FALSE) break;
			$waitingTime += $lockWaitMiliSeconds;
			if ($waitingTime > $maxLockWaitMiliSeconds) {
				throw new \Exception(
					'Unable to create lock handle for file: `' . $fullPath 
					. '`. Lock creation timeout. Try to clear cache: `' 
					. $tmpDir . '`'
				);
			}
			usleep($waitUTime);
		}
		if (!flock($lockHandle, LOCK_EX)) throw new \Exception(
			'Unable to create write lock for file: `'.$lockFullPath.'`.'
		);
		fwrite($lockHandle, $fullPath);
		fflush($lockHandle);
			
		// write or append the file
		$handle = @fopen($fullPath, $writeMode);
		if ($handle && !flock($handle, LOCK_EX)) 
			$handle = FALSE;
		if (!$handle) throw new \Exception(
			'Unable to create locked handle for file: `' . $fullPath . '`.'
		);
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
}
