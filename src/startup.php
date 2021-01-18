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

call_user_func(function () {
	error_reporting(E_ALL);
	if (\PHP_VERSION_ID < 50400)
		die("MvcCore requires at least PHP version 5.4.0, your PHP version is: " . PHP_VERSION . ".");
	$iniShirtOpenTags = @ini_get("short_open_tag");
	if ($iniShirtOpenTags === FALSE || strtolower($iniShirtOpenTags) == 'off' || $iniShirtOpenTags == '0')
		die("MvcCore templates require PHP short open tags enabled. Set `short_open_tag = On` in your php.ini.");
	if (\PHP_SAPI == 'cli') {
		$backtraceItems = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
		$scriptFilename =  $backtraceItems[count($backtraceItems) - 1]['file'];
	} else {
		$scriptFilename = $_SERVER['SCRIPT_FILENAME'];
	}
	$scriptFilename = str_replace('\\', '/', $scriptFilename);
	$appRootPath = strpos(__FILE__, 'phar://') === 0
		? 'phar://' . $scriptFilename
		: dirname($scriptFilename);
	$includePaths = [
		$appRootPath,
		$appRootPath . '/App',
		$appRootPath . '/Libs',
	];
	$currentDir = str_replace('\\', '/', __DIR__);
	if (!in_array($currentDir, $includePaths, TRUE)) array_unshift($includePaths, $currentDir);
	$autoload = function ($className) use ($includePaths) {
		$classSeparator = mb_strpos($className, '\\') === FALSE ? '_' : '\\';
		$fileName = str_replace($classSeparator, '/', $className) . '.php';
		$includePath = '';
		foreach ($includePaths as $path) {
			$fullPath = $path . '/' . $fileName;
			if (file_exists($fullPath)) {
				$includePath = $fullPath;
				break;
			}
		}
		if ($includePath) include_once($includePath);
	};
	spl_autoload_register($autoload);
});
