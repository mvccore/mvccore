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
	// check PHP requirements
	if (!defined('MVCCORE_REQUIREMENTS')) {
		if (\PHP_VERSION_ID < 50400)
			die("MvcCore requires at least PHP version 5.4.0, your PHP version is: " . PHP_VERSION . ".");
		define('MVCCORE_REQUIREMENTS', TRUE);
	}
	// Initialize document root if not defined
	if (!defined('MVCCORE_DOC_ROOT')) {
		if (mb_strpos(\PHP_SAPI, 'cli') === 0) {
			$backtraceItems = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
			$scriptFilename = $backtraceItems[count($backtraceItems) - 1]['file'];
			// If php is running by direct input like `php -r "/* php code */":
			if (
				mb_strpos($scriptFilename, DIRECTORY_SEPARATOR) === FALSE &&
				empty($_SERVER['SCRIPT_FILENAME'])
			) {
				// Try to define app root and document root 
				// by possible Composer class location:
				$composerFullClassName = 'Composer\\Autoload\\ClassLoader';
				if (class_exists($composerFullClassName, TRUE)) {
					$ccType = new \ReflectionClass($composerFullClassName);
					$scriptFilename = dirname($ccType->getFileName(), 2);
				} else {
					// If there is no composer class, define 
					// document root by called current working directory:
					$scriptFilename = getcwd();
				}
			}
		} else {
			$scriptFilename = $_SERVER['SCRIPT_FILENAME'];
		}
		$insidePhar = strlen(\Phar::running()) > 0;
		$docRoot = $insidePhar
			? $scriptFilename
			: dirname($scriptFilename);
		// `ucfirst()` - cause IIS has lower case drive name here - different from __DIR__ value
		$docRoot = str_replace(['\\', '//'], '/', ucfirst($docRoot));
		define('MVCCORE_DOC_ROOT', $insidePhar ? 'phar://' . $docRoot : $docRoot);
	}
	// Initialize app root if not defined
	if (!defined('MVCCORE_DOC_ROOT_DIRNAME')) 
		define('MVCCORE_DOC_ROOT_DIRNAME', 'www');
	if (!defined('MVCCORE_APP_ROOT')) {
		$docRoot = constant('MVCCORE_DOC_ROOT');
		$docRootDirName = constant('MVCCORE_DOC_ROOT_DIRNAME');
		$docRootDirNamePos = mb_strrpos($docRoot, '/' . $docRootDirName);
		$estimatedPos = mb_strlen($docRoot) - mb_strlen($docRootDirName) - 1;
		$appRoot = $docRootDirNamePos !== FALSE && $docRootDirNamePos === $estimatedPos
			? mb_substr($docRoot, 0, $estimatedPos)
			: $docRoot;
		define('MVCCORE_APP_ROOT', $appRoot);
	}
	// Initialize autoloading include paths
	if ($includePaths === NULL) {
		if (defined('MVCCORE_INCLUDE_PATHS')) {
			$includePaths = explode(PATH_SEPARATOR, constant('MVCCORE_INCLUDE_PATHS'));
		} else {
			if (!defined('MVCCORE_APP_ROOT_DIRNAME')) 
				define('MVCCORE_APP_ROOT_DIRNAME', 'App');
			if (!defined('MVCCORE_LIBS_DIRNAME')) 
				define('MVCCORE_LIBS_DIRNAME', 'Libs');
			$includePaths = [
				MVCCORE_APP_ROOT,
				MVCCORE_APP_ROOT . '/' . MVCCORE_APP_ROOT_DIRNAME,
				MVCCORE_APP_ROOT . '/' . MVCCORE_LIBS_DIRNAME,
			];
		}
	}
	if ($currentDir === NULL) 
		$currentDir = str_replace('\\', '/', __DIR__);
	if (!in_array($currentDir, $includePaths, TRUE)) 
		array_unshift($includePaths, $currentDir);
	// Initialize autoloading
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
