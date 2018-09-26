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

namespace MvcCore;

//include_once(__DIR__ . '/Interfaces/ITool.php');

/**
 * Responsibility - static helpers for core classes inheritance, string conversions and JSON.
 * - Static translation functions (supports containing folder or file path):
 *   - `"dashed-case"		=> "PascalCase"`
 *   - `"PascalCase"		=> "dashed-case"`
 *   - `"unserscore_case"	=> "PascalCase"`
 *   - `"PascalCase"		=> "unserscore_case"`
 * - Static functions to safely encode/decode JSON.
 * - Static functions to get client/server IPs.
 * - Static function to check core classes inheritance.
 */
class Tool implements Interfaces\ITool
{
	/**
	 * Cache with keys by full interface class names and with values with
	 * only static and also only public method names by the interface name.
	 * @var array
	 */
	protected static $interfacesStaticMethodsCache = [];

	/**
	 * Convert all strings `"from" => "to"`:
	 * - `"MyCustomValue"				=> "my-custom-value"`
	 * - `"MyCustom/Value/InsideFolder"	=> "my-custom/value/inside-folder"`
	 * @param string $pascalCase
	 * @return string
	 */
	public static function GetDashedFromPascalCase ($pascalCase = '') {
		return strtolower(preg_replace("#([a-z])([A-Z])#", "$1-$2", lcfirst($pascalCase)));
	}

	/**
	 * Convert all string `"from" => "to"`:
	 * - `"my-custom-value"					=> "MyCustomValue"`
	 * - `"my-custom/value/inside-folder"	=> "MyCustom/Value/InsideFolder"`
	 * @param string $dashed
	 * @return string
	 */
	public static function GetPascalCaseFromDashed ($dashed = '') {
		$a = explode('/', $dashed);
		foreach ($a as & $b) $b = ucfirst(str_replace('-', '', ucwords($b, '-')));
		return ucfirst(implode('/', $a));
	}

	/**
	 * Convert all string `"from" => "to"`:
	 * - `"MyCutomValue"				=> "my_custom_value"`
	 * - `"MyCutom/Value/InsideFolder"	=> "my_custom/value/inside_folder"`
	 * @param string $pascalCase
	 * @return string
	 */
	public static function GetUnderscoredFromPascalCase ($pascalCase = '') {
		return strtolower(preg_replace("#([a-z])([A-Z])#", "$1_$2", lcfirst($pascalCase)));
	}

	/**
	 * Convert all string `"from" => "to"`:
	 * - `"my_custom_value"					=> "MyCutomValue"`
	 * - `"my_custom/value/inside_folder"	=> "MyCutom/Value/InsideFolder"`
	 * @param string $underscored
	 * @return string
	 */
	public static function GetPascalCaseFromUnderscored ($underscored = '') {
		$a = explode('/', $underscored);
		foreach ($a as & $b) $b = ucfirst(str_replace('_', '', ucwords($b, '_')));
		return ucfirst(implode('/', $a));
	}

	/**
	 * Safely encode json string from php value.
	 * @param mixed $data
	 * @throws \Exception
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
	 * This function is very PHP specific. It's proudly used from Nette Framework, optimized for PHP 5.4+:
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
	 * @return boolean
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
