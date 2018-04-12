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

if (version_compare(PHP_VERSION, '5.4.0', "<")) {
	$m = "Startup script requires at least PHP version 5.4.0 your PHP version is: " . PHP_VERSION;
	die($m);
}
call_user_func(function () {
	error_reporting(E_ALL ^ E_NOTICE);
	$scriptFilename = $_SERVER['SCRIPT_FILENAME'];
	$scriptFilename = php_sapi_name() == 'cli'
		? str_replace('\\', '/', getcwd()) . '/' . $scriptFilename
		: str_replace('\\', '/', $scriptFilename);
	if (strpos(__FILE__, 'phar://') === 0) {
		$appRootPath = 'phar://' . $scriptFilename;
	} else {
		$appRootPath = substr($scriptFilename, 0, strrpos($scriptFilename, '/'));
	}
	$includePaths = array(
		$appRootPath,
		$appRootPath . '/App',
		$appRootPath . '/Libs',
	);
	$currentDir = str_replace('\\', '/', __DIR__);
	if (!in_array($currentDir, $includePaths)) array_unshift($includePaths, $currentDir);
	$autoload = function ($className) use ($includePaths, $throwExceptionIfClassIsGoingToUse) {
		$fileName = str_replace(array('_', '\\'), '/', $className) . '.php';
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
