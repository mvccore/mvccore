<?php

/**
 * MvcCore
 *
 * This source file is subject to the BSD 3 License
 * For the full copyright and license information, please view 
 * the LICENSE.md file that are distributed with this source code.
 *
 * @copyright	Copyright (c) 2016 Tom Flídr (https://github.com/mvccore/mvccore)
 * @license		https://mvccore.github.io/docs/mvccore/1.0.0/LICENCE.md
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
	
	$autoload = function ($className) use ($includePaths) {
		
		$fileName = str_replace('_', '/', $className) . '.php';
		
		$includePath = '';
		foreach ($includePaths as $includePath) {
			$fullPath = $includePath . '/' . $fileName;
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
			@include_once($includePath);
		} else {
			throw new Exception('[startup.php] Class "' . $className . '" not found.');
		}
	};

	spl_autoload_register($autoload);
	
});