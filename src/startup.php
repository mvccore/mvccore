<?php

/**
 * MvcCore
 *
 * This source file is subject to the BSD 3 License
 * For the full copyright and license information, please view 
 * the LICENSE.md file that are distributed with this source code.
 *
 * @copyright	Copyright (c) 2016 Tom Flídr (https://github.com/mvccore/mvccore)
 * @license		https://mvccore.github.io/docs/mvccore/2.0.0/LICENCE.md
 */

if (version_compare(PHP_VERSION, '5.3.0', "<")) {
	$m = "Startup script requires at least PHP version 5.3.0 your PHP version is: " . PHP_VERSION;
	die($m);
}

call_user_func(function(){ 
	
	error_reporting(E_ALL ^ E_NOTICE);

	$scriptFilename = str_replace('\\', '/', $_SERVER['SCRIPT_FILENAME']);
	
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
	
	set_include_path(
		get_include_path() . PATH_SEPARATOR . implode(PATH_SEPARATOR, $includePaths)
	);

	$throwExceptionIfClassIsGoingToUse = function ($className) {
		$status = 0;
		$backTraceLog = debug_backtrace();
		foreach ($backTraceLog as $backTraceInfo) {
			if ($status === 0 && $backTraceInfo['function'] == 'spl_autoload_call') {
				$status = 1;
			} else if ($status == 1 && $backTraceInfo['function'] == 'class_exists') {
				$status = 2;
				break;
			} else if ($status > 0) {
				break;
			}
		}
		if ($status < 2) throw new Exception('[startup.php] Class "' . $className . '" not found.');
	};
	
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
		/*
		echo '<pre>';
		print_r(array($fileName, $className, $includePath, $includePaths, '/'));
		echo '</pre>';
		*/
		if ($includePath) {
			include_once($includePath);
		} else {
			$throwExceptionIfClassIsGoingToUse($className);
		}
	};

	spl_autoload_register($autoload);
	
});