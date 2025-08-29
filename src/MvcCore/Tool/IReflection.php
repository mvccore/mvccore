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

namespace MvcCore\Tool;

/**
 * @template T of object
 */
interface IReflection {
	
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
	 * @param string|object $classFullNameOrInstance
	 * Class instance or full class name.
	 * @param array<string> $attrsClassesOrDocsTags
	 * Array with attribute(s) full class names 
	 * or array with PhpDocs tag(s) name(s).
	 * @param ?bool         $preferAttributes
	 * Prefered way to get meta data. `TRUE` means try 
	 * to get PHP8+ attribute(s) only, `FALSE` means 
	 * try to get PhpDocs tag(s) only and `NULL` (default) 
	 * means try to get PHP8+ attribute(s) first and if 
	 * there is nothing, try to get PhpDocs tag(s).
	 * @throws \InvalidArgumentException
	 * @return array<string,array<int|string,mixed>>
	 * Keys are attributes full class names (or PhpDocs tags names) and values
	 * are attributes constructor arguments (or PhpDocs tags arguments).
	 */
	public static function GetClassAttrsArgs ($classFullNameOrInstance, $attrsClassesOrDocsTags, $preferAttributes = NULL);

	/**
	 * Get (cached) class method attribute(s) constructor arguments or 
	 * get class method PhpDocs tags and it's arguments for older PHP versions.
	 * You can optionally set prefered way to get desired meta data.
	 * @param  string|object $classFullNameOrInstance
	 * Class instance or full class name.
	 * @param  string        $methodName
	 * Class method name.
	 * @param  array<string> $attrsClassesOrDocsTags
	 * Array with attribute(s) full class names 
	 * or array with PhpDocs tag(s) name(s).
	 * @param  ?bool         $preferAttributes
	 * Prefered way to get meta data. `TRUE` means try 
	 * to get PHP8+ attribute(s) only, `FALSE` means 
	 * try to get PhpDocs tag(s) only and `NULL` (default) 
	 * means try to get PHP8+ attribute(s) first and if 
	 * there is nothing, try to get PhpDocs tag(s).
	 * @throws \InvalidArgumentException
	 * @return array<string,array<int|string,mixed>>
	 * Keys are attributes full class names (or PhpDocs tags names) and values
	 * are attributes constructor arguments (or PhpDocs tags arguments).
	 */
	public static function GetMethodAttrsArgs ($classFullNameOrInstance, $methodName, $attrsClassesOrDocsTags, $preferAttributes = NULL);

	/**
	 * Get (cached) class property attribute(s) constructor arguments or 
	 * get class property PhpDocs tags and it's arguments for older PHP versions.
	 * You can optionally set prefered way to get desired meta data.
	 * @param  string|object $classFullNameOrInstance
	 * Class instance or full class name.
	 * @param  string        $propertyName
	 * Class property name.
	 * @param  array<string> $attrsClassesOrDocsTags
	 * Array with attribute(s) full class names 
	 * or array with PhpDocs tag(s) name(s).
	 * @param  ?bool         $preferAttributes
	 * Prefered way to get meta data. `TRUE` means try 
	 * to get PHP8+ attribute(s) only, `FALSE` means 
	 * try to get PhpDocs tag(s) only and `NULL` (default) 
	 * means try to get PHP8+ attribute(s) first and if 
	 * there is nothing, try to get PhpDocs tag(s).
	 * @throws \InvalidArgumentException
	 * @return array<string,array<int|string,mixed>>
	 * Keys are attributes full class names (or PhpDocs tags names) and values
	 * are attributes constructor arguments (or PhpDocs tags arguments).
	 */
	public static function GetPropertyAttrsArgs ($classFullNameOrInstance, $propertyName, $attrsClassesOrDocsTags, $preferAttributes = NULL);

	/**
	 * Return reflection object attribute constructor arguments.
	 * @param  \ReflectionClass<T>|\ReflectionMethod|\ReflectionProperty $reflectionObject 
	 * @param  string                                                    $attributeClassFullName 
	 * @param  ?bool                                                     $traversing
	 * @return ?array<int|string,mixed>
	 */
	public static function GetAttrCtorArgs ($reflectionObject, $attributeClassFullName, $traversing);

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
	 * @param  \ReflectionClass<T>|\ReflectionMethod|\ReflectionProperty $reflectionObject 
	 * @param  string                                                    $phpDocsTagName
	 * @param  ?bool                                                     $traversing
	 * @throws \InvalidArgumentException
	 * @return ?array<int|string,mixed>
	 */
	public static function GetPhpDocsTagArgs ($reflectionObject, $phpDocsTagName, $traversing = NULL);

	/**
	 * Return serializable properties names for `__sleep()` method result.
	 * First argument is instance, where is called magic method `__sleep()`,
	 * second argument is optional and it's array with keys as properties
	 * names and values as booleans about not to serialize. If boolean value
	 * is `FALSE`, property will not be used for serialization.
	 * @param  mixed              $instance 
	 * @param  array<string,bool> $propNamesNotToSerialize 
	 * @return array<string>
	 */
	public static function GetSleepPropNames ($instance, $propNamesNotToSerialize = []);

}
