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

/**
 * @phpstan-type OnErrorHandler callable(int,string,string,int): bool
 * @phpstan-type ParsedUrl array{"scheme":?string,"user":?string,"pass":?string,"host":?string,"port":?string,"path":?string,"query":?string,"fragment":?string}
 */
interface IHelpers {
	
	/**
	 * Returns the OS-specific directory for temporary files.
	 * @return string
	 */
	public static function GetSystemTmpDir ();

	/**
	 * Recognize if given string is query string without parsing.
	 * It recognizes query strings like:
	 * - `key1=value1`
	 * - `key1=value1&`
	 * - `key1=value1&key2=value2`
	 * - `key1=value1&key2=value2&`
	 * - `key1=&key2=value2`
	 * - `key1=value&key2=`
	 * - `key1=value&key2=&key3=`
	 * ...
	 * @param  string $queryStr
	 * @return bool
	 */
	public static function IsQueryString ($queryStr);

	/**
	 * Generates cryptographically secure pseudo-random bytes for PHP >= 7
	 * and non-secure random bytes for older PHP versions.
	 * Result is generated by available functions:
	 * - `random_bytes()`
	 * - `mcrypt_create_iv()`
	 * - `openssl_random_pseudo_bytes()`
	 * - `mt_rand()`
	 * @param  int $charsLen 
	 * @return string
	 */
	public static function GetRandomHash ($charsLen = 64);

	/**
	 * Safely invoke internal PHP function with it's own error handler.
	 * Error handler accepts arguments:
	 * - `string $errMessage` - Error message.
	 * - `int $errLevel`      - Level of the error raised.
	 * - `string $errFile`    - Optional, full path to error file name where error was raised.
	 * - `int $errLine`       - Optional, The error file line number.
	 * If the custom error handler returns `FALSE`, normal internal error handler continues.
	 * This function is very PHP specific. It's proudly used from Nette Framework, optimized for PHP 5.4+ incl.:
	 * https://github.com/nette/utils/blob/b623b2deec8729c8285d269ad991a97504f76bd4/src/Utils/Callback.php#L63-L84
	 * @param  string|callable         $internalFnOrHandler
	 * @param  array<int|string,mixed> $args
	 * @param  OnErrorHandler          $onError
	 * @return mixed
	 */
	public static function Invoke ($internalFnOrHandler, array $args, callable $onError);

	/**
	 * Write or append file content by only one single PHP process.
	 * @see http://php.net/manual/en/function.flock.php
	 * @see http://php.net/manual/en/function.set-error-handler.php
	 * @see http://php.net/manual/en/function.clearstatcache.php
	 * @param  string $fullPath                     File full path.
	 * @param  string $content                      String content to write.
	 * @param  string $writeMode                    PHP `fopen()` second argument flag, could be `w`, `w+`, `a`, `a+` etc...
	 * @param  int    $lockWaitMilliseconds         Milliseconds to wait before next lock file existence is checked in `while()` cycle.
	 * @param  int    $maxLockWaitMilliseconds      Maximum milliseconds time to wait before thrown an exception about not possible write.
	 * @param  int    $oldLockMillisecondsTolerance Maximum milliseconds time to consider lock file as operative or as old after some died process.
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
	);

	/**
	 * PHP `realpath()` function without checking file/directory existence.
	 * @see https://www.php.net/manual/en/function.realpath.php
	 * @param  string $path
	 * @return string
	 */
	public static function RealPathVirtual ($path);

	/**
	 * Parse a URL and return it's components.
	 * @see https://www.php.net/manual/en/function.parse-url.php
	 * @see https://bugs.php.net/bug.php?id=73192
	 * @see https://en.wikipedia.org/wiki/Uniform_Resource_Identifier
	 * @param  string|NULL $uri 
	 * @param  int         $component 
	 * @return ParsedUrl|string|int|null|false
	 */
	public static function ParseUrl ($uri, $component = -1);

	/**
	 * Return `TRUE` if `$a` and `$b` are equal, `FALSE` otherwise.
	 * `$a` and `$b` are queal if both are `NULL` or if both are
	 * floats and absolute difference is lower than `PHP_FLOAT_EPSILON`.
	 * Third param `$fractionLength` could be used optionally to 
	 * compare both numbers only by limited number of fraction digits.
	 * @param  float|int|string|null $n1 
	 * @param  float|int|string|null $n2 
	 * @param  int|NULL              $fractionLength
	 * @return bool
	 */
	public static function CompareFloats ($n1, $n2, $fractionLength = NULL) ;

}