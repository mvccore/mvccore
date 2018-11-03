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

if (version_compare(PHP_VERSION, '5.4.0', "<")) {
	$m = "Startup script requires at least PHP version 5.4.0 your PHP version is: " . PHP_VERSION;
	die($m);
}
call_user_func(function () {
	error_reporting(E_ALL ^ E_NOTICE);
	if (php_sapi_name() == 'cli') {
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
		$fileName = str_replace(['_', '\\'], '/', $className) . '.php';
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
