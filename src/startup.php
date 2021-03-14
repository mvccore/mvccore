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
	static $includePaths = NULL;
	static $currentDir = NULL;
	if (!defined('MVCCORE_REQUIREMENTS')) {
		if (\PHP_VERSION_ID < 50400)
			die("MvcCore requires at least PHP version 5.4.0, your PHP version is: " . PHP_VERSION . ".");
		define('MVCCORE_REQUIREMENTS', TRUE);
	}
	if (!defined('MVCCORE_DOCUMENT_ROOT')) {
		if (\PHP_SAPI === 'cli') {
			$backtraceItems = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
			$scriptFilename = $backtraceItems[count($backtraceItems) - 1]['file'];
		} else {
			$scriptFilename = $_SERVER['SCRIPT_FILENAME'];
		}
		// `ucfirst()` - cause IIS has lower case drive name here - different from __DIR__ value
		$scriptFilename = ucfirst(str_replace(['\\', '//'], '/', $scriptFilename));
		define('MVCCORE_DOCUMENT_ROOT', strlen(\Phar::running()) > 0 
			? 'phar://' . $scriptFilename
			: dirname($scriptFilename)
		);
	}
	if (!defined('MVCCORE_APP_ROOT')) 
		define('MVCCORE_APP_ROOT', constant('MVCCORE_DOCUMENT_ROOT'));
	if ($includePaths === NULL) {
		if (defined('MVCCORE_INCLUDE_PATHS')) {
			$includePaths = explode(PATH_SEPARATOR, constant('MVCCORE_INCLUDE_PATHS'));
		} else {
			$includePaths = [
				MVCCORE_APP_ROOT,
				MVCCORE_APP_ROOT . '/App',
				MVCCORE_APP_ROOT . '/Libs',
			];
		}
	}
	if ($currentDir === NULL) 
		$currentDir = str_replace('\\', '/', __DIR__);
	if (!in_array($currentDir, $includePaths, TRUE)) 
		array_unshift($includePaths, $currentDir);
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
