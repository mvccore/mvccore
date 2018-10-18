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

trait OopChecking
{
	/**
	 * Cache with keys by full interface class names and with values with
	 * only static and also only public method names by the interface name.
	 * @var array
	 */
	protected static $interfacesStaticMethodsCache = [];


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
	 * Check if given class implements given interface, else throw an exception.
	 * @param string $testClassName Full test class name.
	 * @param string $interfaceName Full interface class name.
	 * @param bool $checkStaticMethods Check implementation of all static methods by interface static methods.
	 * @param bool $throwException If `TRUE`, throw an exception if something is not implemented or if `FALSE` return `FALSE` only.
	 * @throws \InvalidArgumentException
	 * @return bool
	 */
	public static function CheckClassInterface ($testClassName, $interfaceName, $checkStaticMethods = FALSE, $throwException = TRUE) {
		$result = FALSE;
		$errorMsg = '';
		// check given test class for all implemented instance methods by given interface
		$interfaceName = trim($interfaceName, '\\');
		$testClassType = new \ReflectionClass($testClassName);
		if (in_array($interfaceName, $testClassType->getInterfaceNames(), TRUE)) {
			$result = TRUE;
		} else {
			$errorMsg = "Class `$testClassName` doesn't implement interface `$interfaceName`.";
		}
		if ($result && $checkStaticMethods) {
			// check given test class for all implemented static methods by given interface
			$allStaticsImplemented = TRUE;
			$interfaceMethods = static::checkClassInterfaceGetPublicStaticMethods($interfaceName);
			foreach ($interfaceMethods as $methodName) {
				if (!$testClassType->hasMethod($methodName)) {
					$allStaticsImplemented = FALSE;
					$errorMsg = "Class `$testClassName` doesn't implement static method `$methodName` from interface `$interfaceName`.";
					break;
				}
				$testClassStaticMethod = $testClassType->getMethod($methodName);
				if (!$testClassStaticMethod->isStatic()) {
					$allStaticsImplemented = FALSE;
					$errorMsg = "Class `$testClassName` doesn't implement static method `$methodName` from interface `$interfaceName`, method is not static.";
					break;
				}
				// arguments compatibility in presented static method are automatically checked by PHP
			}
			if (!$allStaticsImplemented) $result = FALSE;
		}
		// return result or thrown an exception
		if ($result) return TRUE;
		if (!$throwException) return FALSE;
		throw new \InvalidArgumentException("[".__CLASS__."] " . $errorMsg);
	}

	/**
	 * Check if given class implements given trait, else throw an exception.
	 * @param string $testClassName Full test class name.
	 * @param string $traitName Full trait class name.
	 * @param bool $checkParentClasses If `TRUE`, trait implementation will be checked on all parent classes until success. Default is `FALSE`.
	 * @param bool $throwException If `TRUE`, throw an exception if trait is not implemented or if `FALSE` return `FALSE` only.
	 * @throws \InvalidArgumentException
	 * @return boolean
	 */
	public static function CheckClassTrait ($testClassName, $traitName, $checkParentClasses = FALSE, $throwException = TRUE) {
		$result = FALSE;
		$errorMsg = '';
		// check given test class for all implemented instance methods by given interface
		$testClassType = new \ReflectionClass($testClassName);
		if (in_array($traitName, $testClassType->getTraitNames(), TRUE)) {
			$result = TRUE;
		} else if ($checkParentClasses) {
			$currentClassType = $testClassType;
			while (TRUE) {
				$parentClass = $currentClassType->getParentClass();
				if ($parentClass === FALSE) break;
				$parentClassType = new \ReflectionClass($parentClass->getName());
				if (in_array($traitName, $parentClassType->getTraitNames(), TRUE)) {
					$result = TRUE;
					break;
				} else {
					$currentClassType = $parentClassType;
				}
			}
		}
		if (!$result) 
			$errorMsg = "Class `$testClassName` doesn't implement trait `$traitName`.";
		// return result or thrown an exception
		if ($result) return TRUE;
		if (!$throwException) return FALSE;
		throw new \InvalidArgumentException("[".__CLASS__."] " . $errorMsg);
	}

	/**
	 * Complete array with only static and also only public method names by given interface name.
	 * Return completed array and cache it in static local array.
	 * @param string $interfaceName
	 * @return array
	 */
	protected static function & checkClassInterfaceGetPublicStaticMethods ($interfaceName) {
		if (!isset(static::$interfacesStaticMethodsCache[$interfaceName])) {
			$methods = [];
			$interfaceType = new \ReflectionClass($interfaceName);
			$publicOrStaticMethods = $interfaceType->getMethods(\ReflectionMethod::IS_PUBLIC | \ReflectionMethod::IS_STATIC);
			/** @var $publicOrStaticMethod \ReflectionMethod */
			foreach ($publicOrStaticMethods as $publicOrStaticMethod) {
				// filter methods for public and also static method only
				if ($publicOrStaticMethod->isStatic() && $publicOrStaticMethod->isPublic()) {
					$methods[] = $publicOrStaticMethod->getName();
				}
			}
			static::$interfacesStaticMethodsCache[$interfaceName] = $methods;
		}
		return static::$interfacesStaticMethodsCache[$interfaceName];
	}
}
