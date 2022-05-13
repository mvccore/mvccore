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

namespace MvcCore;

/**
 * Responsibility - static helpers for core classes.
 * - Static functions for string case conversions.
 * - Static functions to safely encode/decode JSON.
 * - Static functions to get client/server IPs.
 * - Static functions to get system temp directory.
 * - Static functions to safely invoke dangerous calls.
 * - Static functions to write into file by one process only.
 * - Static functions to check core classes inheritance.
 * - Static functions to cache and read attributes (or PhpDocs tags).
 */
interface ITool {

	/**
	 * Convert all strings `"from" => "to"`:
	 * - `"MyCustomValue"               => "my-custom-value"`
	 * - `"MyWTFValue"                  => "my-w-t-f-value"`
	 * - `"MyWtfValue"                  => "my-wtf-value"`
	 * - `"MyCustom/Value/InsideFolder" => "my-custom/value/inside-folder"`
	 * @param  string $pascalCase
	 * @return string
	 */
	public static function GetDashedFromPascalCase ($pascalCase);

	/**
	 * Convert all string `"from" => "to"`:
	 * - `"my-custom-value"               => "MyCustomValue"`
	 * - `"my-wtf-value"                  => "MyWtfValue"`
	 * - `"my-w-t-f-value"                => "MyWTFValue"`
	 * - `"my-custom/value/inside-folder" => "MyCustom/Value/InsideFolder"`
	 * @param  string $dashed
	 * @return string
	 */
	public static function GetPascalCaseFromDashed ($dashed);

	/**
	 * Convert all string `"from" => "to"`:
	 * - `"MyCutomValue"               => "my_custom_value"`
	 * - `"MyWTFValue"                 => "my_w_t_f_value"`
	 * - `"MyWtfValue"                 => "my_wtf_value"`
	 * - `"MyCutom/Value/InsideFolder" => "my_custom/value/inside_folder"`
	 * @param  string $pascalCase
	 * @return string
	 */
	public static function GetUnderscoredFromPascalCase ($pascalCase);

	/**
	 * Convert all string `"from" => "to"`:
	 * - `"my_custom_value"               => "MyCutomValue"`
	 * - `"my_wtf_value"                  => "MyWtfValue"`
	 * - `"my_w_t_f_value"                => "MyWTFValue"`
	 * - `"my_custom/value/inside_folder" => "MyCutom/Value/InsideFolder"`
	 * @param  string $underscored
	 * @return string
	 */
	public static function GetPascalCaseFromUnderscored ($underscored);

	/**
	 * Safely encode json string from php value.
	 * JSON encoding flags used by default:
	 *  - `JSON_HEX_TAG`:
	 *     All < and > are converted to \u003C and \u003E. Available as of PHP 5.3.0.
	 *  - `JSON_HEX_AMP`:
	 *    All & are converted to \u0026. Available as of PHP 5.3.0.
	 *  - `JSON_HEX_APOS`:
	 *    All ' are converted to \u0027. Available as of PHP 5.3.0.
	 *  - `JSON_HEX_QUOT`:
	 *    All " are converted to \u0022. Available as of PHP 5.3.0.
	 *  - `JSON_UNESCAPED_SLASHES`:
	 *    Don't escape /. Available as of PHP 5.4.0.
	 *  - `JSON_PRESERVE_ZERO_FRACTION`:
	 *    Ensures that float values are always encoded as a float value. Available as of PHP 5.6.6.
	 * Possible JSON encoding flags to add:
	 *  - `JSON_PRETTY_PRINT`:
	 *    Encode JSON into pretty print syntax, Available as of PHP 5.4.0.
	 *  - `JSON_NUMERIC_CHECK`:
	 *    Encodes numeric strings as numbers (be carefull for phone numbers). Available as of PHP 5.3.3.
	 *  - `JSON_UNESCAPED_UNICODE`:
	 *    Encode multibyte Unicode characters literally (default is to escape as \uXXXX). Available as of PHP 5.4.0.
	 *  - `JSON_UNESCAPED_LINE_TERMINATORS`:
	 *    The line terminators are kept unescaped when JSON_UNESCAPED_UNICODE
	 *    is supplied. It uses the same behaviour as it was before PHP 7.1
	 *    without this constant. Available as of PHP 7.1.0.	The following
	 *    constants can be combined to form options for json_decode()
	 *    and json_encode().
	 *  - `JSON_INVALID_UTF8_IGNORE`:
	 *    Ignore invalid UTF-8 characters. Available as of PHP 7.2.0.
	 *  - `JSON_INVALID_UTF8_SUBSTITUTE`:
	 *    Convert invalid UTF-8 characters to \0xfffd (Unicode Character
	 *    'REPLACEMENT CHARACTER') Available as of PHP 7.2.0.
	 *  - `JSON_THROW_ON_ERROR`:
	 *    Throws JsonException if an error occurs instead of setting the global
	 *    error state that is retrieved with json_last_error() and
	 *    json_last_error_msg(). JSON_PARTIAL_OUTPUT_ON_ERROR takes precedence
	 *    over JSON_THROW_ON_ERROR. Available as of PHP 7.3.0.
	 * @param  mixed $data
	 * @param  int   $flags
	 * @param  int   $depth Set the maximum depth. Must be greater than zero, default: 512.
	 * @throws \RuntimeException|\JsonException JSON encoding error.
	 * @return string
	 */
	public static function JsonEncode ($data, $flags = 0, $depth = 512);

	/**
	 * Safely decode json string into php `stdClass/array`.
	 * - `JSON_BIGINT_AS_STRING`:
	 *    Decodes large integers as their original string value. Available as of PHP 5.4.0.
	 * - `JSON_OBJECT_AS_ARRAY`:
	 *   Decodes JSON objects as PHP array. This option can be added automatically by calling json_decode() with
	 *   the second parameter equal to TRUE. Available as of PHP 5.4.0.
	 * - `JSON_INVALID_UTF8_IGNORE`:
	 *   Ignore invalid UTF-8 characters. Available as of PHP 7.2.0.
	 *  - `JSON_INVALID_UTF8_SUBSTITUTE`:
	 *    Convert invalid UTF-8 characters to \0xfffd (Unicode Character
	 *    'REPLACEMENT CHARACTER') Available as of PHP 7.2.0.
	 *  - `JSON_THROW_ON_ERROR`:
	 *    Throws JsonException if an error occurs instead of setting the global
	 *    error state that is retrieved with json_last_error() and
	 *    json_last_error_msg(). JSON_PARTIAL_OUTPUT_ON_ERROR takes precedence
	 *    over JSON_THROW_ON_ERROR. Available as of PHP 7.3.0.
	 * @param  string $jsonStr
	 * @param  int    $flags
	 * @param  int    $depth   User specified recursion depth, default: 512.
	 * @throws \RuntimeException|\JsonException JSON decoding error.
	 * @return object
	 */
	public static function JsonDecode ($jsonStr, $flags = 0, $depth = 512);

	/**
	 * Recognize if given string is JSON or not without JSON parsing.
	 * @see https://www.ietf.org/rfc/rfc4627.txt
	 * @param  string $jsonStr
	 * @return bool
	 */
	public static function IsJsonString ($jsonStr);

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
	 * Safely invoke internal PHP function with it's own error handler.
	 * Error handler accepts arguments:
	 * - `string $errMessage` - Error message.
	 * - `int $errLevel`      - Level of the error raised.
	 * - `string $errFile`    - Optional, full path to error file name where error was raised.
	 * - `int $errLine`       - Optional, The error file line number.
	 * If the custom error handler returns `FALSE`, normal internal error handler continues.
	 * This function is very PHP specific. It's proudly used from Nette Framework, optimized for PHP 5.4+ incl.:
	 * https://github.com/nette/utils/blob/b623b2deec8729c8285d269ad991a97504f76bd4/src/Utils/Callback.php#L63-L84
	 * @param  string|callable $internalFnOrHandler
	 * @param  array           $args
	 * @param  callable        $onError
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
	 * @param  string $uri 
	 * @param  int    $component 
	 * @return array|string|int|null|false
	 */
	public static function ParseUrl ($uri, $component = -1);

	
	/**
	 * Set prefered PHP classes and properties anontation preference.
	 * PHP8+ attributes anotation is default. Set value to `FALSE`
	 * to prefer PhpDocs tags anotation instead.
	 * @param  bool $attributesAnotation 
	 * @return bool
	 */
	public static function SetAttributesAnotations ($attributesAnotation = TRUE);
	
	/**
	 * Get prefered PHP classes and properties anontation preference.
	 * PHP8+ attributes anotation is default. `FALSE` value means
	 * to prefer PhpDocs tags anotation instead.
	 * @return bool
	 */
	public static function GetAttributesAnotations ();

	/**
	 * Check if given class implements given interface, else throw an exception.
	 * @param  string $testClassName      Full test class name.
	 * @param  string $interfaceName      Full interface class name.
	 * @param  bool   $checkStaticMethods Check implementation of all static methods by interface static methods.
	 * @param  bool   $throwException     If `TRUE`, throw an exception if something is not implemented or if `FALSE` return `FALSE` only.
	 * @throws \InvalidArgumentException
	 * @return boolean
	 */
	public static function CheckClassInterface ($testClassName, $interfaceName, $checkStaticMethods = FALSE, $throwException = TRUE);

	/**
	 * Check if given class implements given trait, else throw an exception.
	 * @param  string $testClassName  Full test class name.
	 * @param  string $traitName      Full trait class name.
	 * @param  bool   $throwException If `TRUE`, throw an exception if trait is not implemented or if `FALSE` return `FALSE` only.
	 * @throws \InvalidArgumentException
	 * @return boolean
	 */
	public static function CheckClassTrait ($testClassName, $traitName, $throwException = TRUE);

	/**
	 * Get (cached) class attribute(s) constructor arguments or 
	 * get class PhpDocs tags and it's arguments for older PHP versions.
	 * You can optionally set prefered way to get desired meta data.
	 * @param  string|object $classFullNameOrInstance
	 *                       Class instance or full class name.
	 * @param  \string[]     $attrsClassesOrDocsTags
	 *                       Array with attribute(s) full class names 
	 *                       or array with PhpDocs tag(s) name(s).
	 * @param  bool|NULL     $preferAttributes
	 *                       Prefered way to get meta data. `TRUE` means try 
	 *                       to get PHP8+ attribute(s) only, `FALSE` means 
	 *                       try to get PhpDocs tag(s) only and `NULL` (default) 
	 *                       means try to get PHP8+ attribute(s) first and if 
	 *                       there is nothing, try to get PhpDocs tag(s).
	 * @return array         Keys are attributes full class names (or PhpDocs tags names) and values
	 *                       are attributes constructor arguments (or PhpDocs tags arguments).
	 */
	public static function GetClassAttrsArgs ($classFullNameOrInstance, $attrsClassesOrDocsTags, $preferAttributes = NULL);

	/**
	 * Get (cached) class method attribute(s) constructor arguments or 
	 * get class method PhpDocs tags and it's arguments for older PHP versions.
	 * You can optionally set prefered way to get desired meta data.
	 * @param  string|object $classFullNameOrInstance
	 *                       Class instance or full class name.
	 * @param  string        $methodName
	 *                       Class method name.
	 * @param  \string[]     $attrsClassesOrDocsTags
	 *                       Array with attribute(s) full class names 
	 *                       or array with PhpDocs tag(s) name(s).
	 * @param  bool|NULL     $preferAttributes
	 *                       Prefered way to get meta data. `TRUE` means try 
	 *                       to get PHP8+ attribute(s) only, `FALSE` means 
	 *                       try to get PhpDocs tag(s) only and `NULL` (default) 
	 *                       means try to get PHP8+ attribute(s) first and if 
	 *                       there is nothing, try to get PhpDocs tag(s).
	 * @return array         Keys are attributes full class names (or PhpDocs tags names) and values
	 *                       are attributes constructor arguments (or PhpDocs tags arguments).
	 */
	public static function GetMethodAttrsArgs ($classFullNameOrInstance, $methodName, $attrsClassesOrDocsTags, $preferAttributes = NULL);

	/**
	 * Get (cached) class property attribute(s) constructor arguments or 
	 * get class property PhpDocs tags and it's arguments for older PHP versions.
	 * You can optionally set prefered way to get desired meta data.
	 * @param  string|object $classFullNameOrInstance
	 *                       Class instance or full class name.
	 * @param  string        $propertyName
	 *                       Class property name.
	 * @param  \string[]     $attrsClassesOrDocsTags
	 *                       Array with attribute(s) full class names 
	 *                       or array with PhpDocs tag(s) name(s).
	 * @param  bool|NULL     $preferAttributes
	 *                       Prefered way to get meta data. `TRUE` means try 
	 *                       to get PHP8+ attribute(s) only, `FALSE` means 
	 *                       try to get PhpDocs tag(s) only and `NULL` (default) 
	 *                       means try to get PHP8+ attribute(s) first and if 
	 *                       there is nothing, try to get PhpDocs tag(s).
	 * @return array         Keys are attributes full class names (or PhpDocs tags names) and values
	 *                       are attributes constructor arguments (or PhpDocs tags arguments).
	 */
	public static function GetPropertyAttrsArgs ($classFullNameOrInstance, $propertyName, $attrsClassesOrDocsTags, $preferAttributes = NULL);

	/**
	 * Return reflection object attribute constructor arguments.
	 * @param  \ReflectionClass|\ReflectionMethod|\ReflectionProperty $reflectionObject 
	 * @param  string                                                 $attributeClassFullName 
	 * @return array|NULL
	 */
	public static function GetAttrCtorArgs ($reflectionObject, $attributeClassFullName);

	/**
	 * Return PhpDocs tag arguments, arguments could be in three different formats:
	 * 1. Comma separated strings
	 * ````
	 *   @tagName val1, val2, val3...
	 * ````
	 * 2. Tag name with constructor data in brackets:
	 * ````
	 *   @tagName({
	 *       "option1": "val1",
	 *       "option2": "val2",
	 *       ...
	 *   })
	 * ````
	 * 3. Tag name with Class name and JSON constructor data in brackets:
	 * ````
	 *   @tagName Full\ClassName({
	 *       "option1": "val1",
	 *       "option2": "val2",
	 *       ...
	 *   })
	 * ````
	 * @param  \ReflectionClass|\ReflectionMethod|\ReflectionProperty $reflectionObject 
	 * @param  string                                                 $phpDocsTagName
	 * @return array|NULL
	 */
	public static function GetPhpDocsTagArgs ($reflectionObject, $phpDocsTagName);

	/**
	 * Return serializable properties names for `__sleep()` method result.
	 * First argument is instance, where is called magic method `__sleep()`,
	 * second argument is optional and it's array with keys as properties
	 * names and values as booleans about not to serialize. If boolean value
	 * is `FALSE`, property will not be used for serialization.
	 * @param  mixed $instance 
	 * @param  array $propNamesNotToSerialize 
	 * @return \string[]
	 */
	public static function GetSleepPropNames ($instance, $propNamesNotToSerialize = []);
}
