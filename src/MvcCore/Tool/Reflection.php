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

trait Reflection {

	/**
	 * Cache with keys by full interface class names and with values with
	 * only static and also only public method names by the interface name.
	 * @var array
	 */
	protected static $cacheInterfStaticMths = [];

	/**
	 * Key/value store for parsed reflection attributes constructor arguments.
	 * @var array
	 */
	protected static $cacheAttrsArgs = [];


	/**
	 * @inheritDocs
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
			$errorMsg = "Class `{$testClassName}` doesn't implement interface `{$interfaceName}`.";
		}
		if ($result && $checkStaticMethods) {
			// check given test class for all implemented static methods by given interface
			$allStaticsImplemented = TRUE;
			$interfaceMethods = static::checkClassInterfaceGetPublicStaticMethods($interfaceName);
			foreach ($interfaceMethods as $methodName) {
				if (!$testClassType->hasMethod($methodName)) {
					$allStaticsImplemented = FALSE;
					$errorMsg = "Class `{$testClassName}` doesn't implement static method `{$methodName}` from interface `{$interfaceName}`.";
					break;
				}
				// arguments compatibility in presented static method are automatically checked by PHP
			}
			if (!$allStaticsImplemented)
				$result = FALSE;
		}
		// return result or thrown an exception
		if ($result) return TRUE;
		if (!$throwException) return FALSE;
		throw new \InvalidArgumentException("[".get_class()."] " . $errorMsg);
	}

	/**
	 * @inheritDocs
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
			$errorMsg = "Class `{$testClassName}` doesn't implement trait `{$traitName}`.";
		// return result or thrown an exception
		if ($result) return TRUE;
		if (!$throwException) return FALSE;
		throw new \InvalidArgumentException("[".get_class()."] " . $errorMsg);
	}


	/**
	 * @inheritDocs
	 * @param string|object $classFullNameOrInstance Class instance or full class name.
	 * @param \string[] $attrsClassesOrDocsTags Array with attribute(s) full class names 
	 *											or array with PhpDocs tag(s) name(s).
	 * @param bool|NULL $preferAttributes Prefered way to get meta data. `TRUE` means try 
	 *									  to get PHP8+ attribute(s) only, `FALSE` means 
	 *									  try to get PhpDocs tag(s) only and `NULL` (default) 
	 *									  means try to get PHP8+ attribute(s) first and if 
	 *									  there is nothing, try to get PhpDocs tag(s).
	 * @return array Keys are attributes full class names (or PhpDocs tags names) and values
	 *				 are attributes constructor arguments (or PhpDocs tags arguments).
	 */
	public static function GetClassAttrsArgs ($classFullNameOrInstance, $attrsClassesOrDocsTags, $preferAttributes = NULL) {
		$result = [];
		$attrsOnly = $preferAttributes === TRUE;
		$docsTagsOnly = $preferAttributes === FALSE || PHP_VERSION_ID < 80000;
		$reflectionObject = new \ReflectionClass($classFullNameOrInstance);
		foreach ($attrsClassesOrDocsTags as $attrClassOrDocsTag) 
			$result[$attrClassOrDocsTag] = static::getAttrArgsOrPhpDocTagArgs(
				implode('|', ['cls', $classFullNameOrInstance, $attrClassOrDocsTag]),
				$reflectionObject, $attrClassOrDocsTag, $attrsOnly, $docsTagsOnly
			);
		return $result;
	}
	
	/**
	 * @inheritDocs
	 * @param string|object $classFullNameOrInstance Class instance or full class name.
	 * @param string $methodName Class method name.
	 * @param \string[] $attrsClassesOrDocsTags Array with attribute(s) full class names 
	 *											or array with PhpDocs tag(s) name(s).
	 * @param bool|NULL $preferAttributes Prefered way to get meta data. `TRUE` means try 
	 *									  to get PHP8+ attribute(s) only, `FALSE` means 
	 *									  try to get PhpDocs tag(s) only and `NULL` (default) 
	 *									  means try to get PHP8+ attribute(s) first and if 
	 *									  there is nothing, try to get PhpDocs tag(s).
	 * @return array Keys are attributes full class names (or PhpDocs tags names) and values
	 *				 are attributes constructor arguments (or PhpDocs tags arguments).
	 */
	public static function GetMethodAttrsArgs ($classFullNameOrInstance, $methodName, $attrsClassesOrDocsTags, $preferAttributes = NULL) {
		$result = [];
		$attrsOnly = $preferAttributes === TRUE;
		$docsTagsOnly = $preferAttributes === FALSE || PHP_VERSION_ID < 80000;
		$reflectionObject = new \ReflectionMethod($classFullNameOrInstance, $methodName);
		$classMethodFullName = $classFullNameOrInstance . '::' . $methodName;
		foreach ($attrsClassesOrDocsTags as $attrClassOrDocsTag) 
			$result[$attrClassOrDocsTag] = static::getAttrArgsOrPhpDocTagArgs(
				implode('|', ['mthd', $classMethodFullName, $attrClassOrDocsTag]),
				$reflectionObject, $attrClassOrDocsTag, $attrsOnly, $docsTagsOnly
			);
		return $result;
	}
	
	/**
	 * @inheritDocs
	 * @param string|object $classFullNameOrInstance Class instance or full class name.
	 * @param string $propertyName Class property name.
	 * @param \string[] $attrsClassesOrDocsTags Array with attribute(s) full class names 
	 *											or array with PhpDocs tag(s) name(s).
	 * @param bool|NULL $preferAttributes Prefered way to get meta data. `TRUE` means try 
	 *									  to get PHP8+ attribute(s) only, `FALSE` means 
	 *									  try to get PhpDocs tag(s) only and `NULL` (default) 
	 *									  means try to get PHP8+ attribute(s) first and if 
	 *									  there is nothing, try to get PhpDocs tag(s).
	 * @return array Keys are attributes full class names (or PhpDocs tags names) and values
	 *				 are attributes constructor arguments (or PhpDocs tags arguments).
	 */
	public static function GetPropertyAttrsArgs ($classFullNameOrInstance, $propertyName, $attrsClassesOrDocsTags, $preferAttributes = NULL) {
		$result = [];
		$attrsOnly = $preferAttributes === TRUE;
		$docsTagsOnly = $preferAttributes === FALSE || PHP_VERSION_ID < 80000;
		$reflectionObject = new \ReflectionProperty($classFullNameOrInstance, $propertyName);
		$classPropFullName = $classFullNameOrInstance . '::' . $propertyName;
		foreach ($attrsClassesOrDocsTags as $attrClassOrDocsTag) 
			$result[$attrClassOrDocsTag] = static::getAttrArgsOrPhpDocTagArgs(
				implode('|', ['prop', $classPropFullName, $attrClassOrDocsTag]),
				$reflectionObject, $attrClassOrDocsTag, $attrsOnly, $docsTagsOnly
			);
		return $result;
	}
	
	/**
	 * Get (cached) reflection object attribute(s) constructor arguments or 
	 * get reflection object PhpDocs tags and it's arguments for older PHP versions.
	 * Set result into local memory cache. You can optionally set prefered way 
	 * to get desired meta data by last two arguments.
	 * @param string $cacheKey Result cache key.
	 * @param \ReflectionClass|\ReflectionMethod|\ReflectionProperty $reflectionObject Reflection object to get attributes/tags from.
	 * @param string $attrClassOrDocsTag Attributes class full names (or PhpDocs tags).
	 * @param bool $attrsOnly `TRUE` to get PHP8+ attributes only, do not fall back to PhpDocs tags.
	 * @param bool $docsTagsOnly `TRUE` to get PhpDocs tags only, do not try PHP8+ attributes.
	 * @return array Keys are attributes full class names (or PhpDocs tags names) and values
	 *				 are attributes constructor arguments (or PhpDocs tags arguments).
	 */
	protected static function getAttrArgsOrPhpDocTagArgs ($cacheKey, $reflectionObject, $attrClassOrDocsTag, $attrsOnly, $docsTagsOnly) {
		if (array_key_exists($cacheKey, self::$cacheAttrsArgs)) {
			$result = self::$cacheAttrsArgs[$cacheKey];
		} else {
			if ($attrsOnly) {
				$result = static::GetAttrCtorArgs(
					$reflectionObject, $attrClassOrDocsTag
				);
			} else if ($docsTagsOnly) {
				$result = static::GetPhpDocsTagArgs(
					$reflectionObject, $attrClassOrDocsTag
				);
			} else {
				$result = static::GetAttrCtorArgs(
					$reflectionObject, $attrClassOrDocsTag
				);
				if ($result === NULL) 
					$result = static::GetPhpDocsTagArgs(
						$reflectionObject, $attrClassOrDocsTag
					);
			}
			self::$cacheAttrsArgs[$cacheKey] = $result;
		}
		return $result;
	}

	/**
	 * @inheritDocs
	 * @param \ReflectionClass|\ReflectionMethod|\ReflectionProperty $reflectionObject 
	 * @param string $attributeClassFullName 
	 * @return array|NULL
	 */
	public static function GetAttrCtorArgs ($reflectionObject, $attributeClassFullName) {
		$result = NULL;
		$traversing = $reflectionObject instanceof \ReflectionClass;
		while (TRUE) {
			$attrs = $reflectionObject->getAttributes($attributeClassFullName);
			if (count($attrs) > 0) {
				$result = $attrs[0]->getArguments();
				break;
			}
			if ($traversing) {
				$reflectionObject = $reflectionObject->getParentClass();
				if ($reflectionObject === FALSE) break;
			} else {
				break;
			}
		}
		return $result;
	}

	/**
	 * @inheritDocs
	 * @param \ReflectionClass|\ReflectionMethod|\ReflectionProperty $reflectionObject 
	 * @param string $phpDocsTagName
	 * @return array|NULL
	 */
	public static function GetPhpDocsTagArgs ($reflectionObject, $phpDocsTagName) {
		$result = NULL;
		$traversing = $reflectionObject instanceof \ReflectionClass;
		while (TRUE) {
			$docComment = $reflectionObject->getDocComment();
			$tagPos = mb_strpos($docComment, $phpDocsTagName);
			if ($tagPos !== FALSE) {
				$result = [];
				preg_match("#{$phpDocsTagName}\s+([^\r\n\*@]+)#", $docComment, $matches, 0, $tagPos);
				if ($matches && count($matches) > 1) {
					$rawResult = explode(',', $matches[1]);
					foreach ($rawResult as $rawItem) {
						$rawItem = trim($rawItem);
						if ($rawItem !== '')
							$result[] = $rawItem;
					}
				}
				break;
			}
			if ($traversing) {
				$reflectionObject = $reflectionObject->getParentClass();
				if ($reflectionObject === FALSE) break;
			} else {
				break;
			}
		}
		return $result;
	}

	/**
	 * Complete array with only static and also only public method names by given interface name.
	 * Return completed array and cache it in static local array.
	 * @param string $interfaceName
	 * @return array
	 */
	protected static function & checkClassInterfaceGetPublicStaticMethods ($interfaceName) {
		if (!isset(static::$cacheInterfStaticMths[$interfaceName]))
			static::$cacheInterfStaticMths[$interfaceName] = array_map(
				function (\ReflectionMethod $method) {
					return $method->name;
				},
				(new \ReflectionClass($interfaceName))->getMethods(
					\ReflectionMethod::IS_STATIC
				)
			);
		return static::$cacheInterfStaticMths[$interfaceName];
	}
}
