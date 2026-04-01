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
 * @mixin \MvcCore\Tool
 * @template T of object
 */
trait Reflection {

	/**
	 * Cache with keys by full interface class names and with values with
	 * only static and also only public method names by the interface name.
	 * @var array<string,array<int,string>>
	 */
	protected static $cacheIntrfsStatMthds = [];

	/**
	 * Key/value store for parsed reflection attributes constructor arguments.
	 * @var array<string,array<int|string,mixed>>
	 */
	protected static $cacheAttrsArgs = [];

	/**
	 * Serialization property metadata cache per class 
	 * and flag: TRUE if at least one dynamic (undeclared) property exists.
	 * First item under class key is properties metadata array, where 
	 * each value maps mangled key to array with three items:
	 *   [0] ReflectionProperty|null
	 *   [1] string propName
	 *   [2] bool isDynamic
	 * Second item under class key is flag: TRUE if at least one 
	 * dynamic (undeclared) property exists.
	 * @var array<string, array{"0":array<string, array{"0":?\ReflectionProperty, "1":string,"2":bool}>, "1":bool}>
	 */
	protected static $serializeProps = [];

	/**
	 * Bound Closure writers per declaring-class scope, keyed by class name.
	 * Cached once per scope; reused on every __unserialize call.
	 * @var array<string, \Closure>
	 */
	protected static $unserializeWriters = [];


	/**
	 * @inheritDoc
	 * @param  string $testClassName      Full test class name.
	 * @param  string $interfaceName      Full interface class name.
	 * @param  bool   $checkStaticMethods Check implementation of all static methods by interface static methods.
	 * @param  bool   $throwException     If `TRUE`, throw an exception if something is not implemented or if `FALSE` return `FALSE` only.
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
		throw new \InvalidArgumentException("[".get_called_class()."] " . $errorMsg);
	}

	/**
	 * @inheritDoc
	 * @param  string $testClassName      Full test class name.
	 * @param  string $traitName          Full trait class name.
	 * @param  bool   $checkParentClasses If `TRUE`, trait implementation will be checked on all parent classes until success. Default is `FALSE`.
	 * @param  bool   $throwException     If `TRUE`, throw an exception if trait is not implemented or if `FALSE` return `FALSE` only.
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
		throw new \InvalidArgumentException("[".get_called_class()."] " . $errorMsg);
	}


	/**
	 * @inheritDoc
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
	public static function GetClassAttrsArgs ($classFullNameOrInstance, $attrsClassesOrDocsTags, $preferAttributes = NULL) {
		$result = [];
		if ($preferAttributes === NULL)
			$preferAttributes = \MvcCore\Application::GetInstance()->GetAttributesAnotations();
		$attrsOnly = $preferAttributes === TRUE;
		$docsTagsOnly = $preferAttributes === FALSE;
		$reflectionObject = new \ReflectionClass($classFullNameOrInstance);
		foreach ($attrsClassesOrDocsTags as $attrClassOrDocsTag) 
			$result[$attrClassOrDocsTag] = static::getAttrArgsOrPhpDocTagArgs(
				implode('|', ['cls', $reflectionObject->getName(), $attrClassOrDocsTag]),
				$reflectionObject, $attrClassOrDocsTag, $attrsOnly, $docsTagsOnly
			);
		return $result;
	}
	
	/**
	 * @inheritDoc
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
	public static function GetMethodAttrsArgs ($classFullNameOrInstance, $methodName, $attrsClassesOrDocsTags, $preferAttributes = NULL) {
		$result = [];
		if ($preferAttributes === NULL)
			$preferAttributes = \MvcCore\Application::GetInstance()->GetAttributesAnotations();
		$attrsOnly = $preferAttributes === TRUE;
		$docsTagsOnly = $preferAttributes === FALSE;
		$reflectionObject = new \ReflectionMethod($classFullNameOrInstance, $methodName);
		$classMethodFullName = $reflectionObject->getName() . '::' . $methodName;
		foreach ($attrsClassesOrDocsTags as $attrClassOrDocsTag) 
			$result[$attrClassOrDocsTag] = static::getAttrArgsOrPhpDocTagArgs(
				implode('|', ['mthd', $classMethodFullName, $attrClassOrDocsTag]),
				$reflectionObject, $attrClassOrDocsTag, $attrsOnly, $docsTagsOnly
			);
		return $result;
	}
	
	/**
	 * @inheritDoc
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
	public static function GetPropertyAttrsArgs ($classFullNameOrInstance, $propertyName, $attrsClassesOrDocsTags, $preferAttributes = NULL) {
		$result = [];
		if ($preferAttributes === NULL)
			$preferAttributes = \MvcCore\Application::GetInstance()->GetAttributesAnotations();
		$attrsOnly = $preferAttributes === TRUE;
		$docsTagsOnly = $preferAttributes === FALSE;
		$reflectionObject = new \ReflectionProperty($classFullNameOrInstance, $propertyName);
		$classPropFullName = $reflectionObject->getName() . '::' . $propertyName;
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
	 * @param  string                                                    $cacheKey
	 * Result cache key.
	 * @param  \ReflectionClass<T>|\ReflectionMethod|\ReflectionProperty $reflectionObject
	 * Reflection object to get attributes/tags from.
	 * @param  string                                                    $attrClassOrDocsTag
	 * Attributes class full names (or PhpDocs tags).
	 * @param  bool                                                      $attrsOnly
	 * `TRUE` to get PHP8+ attributes only, do not fall back to PhpDocs tags.
	 * @param  bool                                                      $docsTagsOnly
	 * `TRUE` to get PhpDocs tags only, do not try PHP8+ attributes.
	 * @throws \InvalidArgumentException
	 * @return array<int|string,mixed>
	 * Keys are attributes full class names (or PhpDocs tags names) and values
	 * are attributes constructor arguments (or PhpDocs tags arguments).
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
	 * @inheritDoc
	 * @param  \ReflectionClass<T>|\ReflectionMethod|\ReflectionProperty $reflectionObject 
	 * @param  string                                                    $attributeClassFullName 
	 * @param  ?bool                                                     $traversing
	 * @return ?array<int|string,mixed>
	 */
	public static function GetAttrCtorArgs ($reflectionObject, $attributeClassFullName, $traversing = NULL) {
		$result = NULL;
		$traversing = $traversing !== NULL
			? $traversing
			: $reflectionObject instanceof \ReflectionClass;
		$matchByInstanceOf = strpos($attributeClassFullName, '*') === FALSE;
		$attributeClassMatch = '#^' . str_replace(['*', '\\'], ['(.*)', '\\\\'], $attributeClassFullName) . '$#';
		while (TRUE) {
			if ($matchByInstanceOf) {
				$attrs = $reflectionObject->getAttributes($attributeClassFullName);
				if (count($attrs) > 0) {
					$result = $attrs[0]->getArguments();
					break;
				}
			} else {
				/** @var array<\ReflectionAttribute<T>> $allAttrs */
				$allAttrs = $reflectionObject->getAttributes();
				foreach ($allAttrs as $allAttr) {
					if (preg_match($attributeClassMatch, $allAttr->getName())) {
						$result = $allAttr->getArguments();
						break;
					}
				}
			}
			if ($traversing) {
				$reflectionObject = static::getParentReflectionObject($reflectionObject);
				if ($reflectionObject === FALSE) break;
			} else {
				break;
			}
		}
		return $result;
	}

	/**
	 * @inheritDoc
	 * @param  \ReflectionClass<T>|\ReflectionMethod|\ReflectionProperty $reflectionObject 
	 * @param  string                                                    $phpDocsTagName
	 * @param  ?bool                                                     $traversing
	 * @throws \InvalidArgumentException
	 * @return ?array<int|string,mixed>
	 */
	public static function GetPhpDocsTagArgs ($reflectionObject, $phpDocsTagName, $traversing = NULL) {
		$result = NULL;
		$traversing = $traversing !== NULL
			? $traversing
			: $reflectionObject instanceof \ReflectionClass;
		while (TRUE) {
			$docComment = $reflectionObject->getDocComment();
			if ($docComment !== FALSE) {
				$tagPos = mb_strpos($docComment, $phpDocsTagName);
				if ($tagPos !== FALSE) {
					$tagLines = static::getPhpDocsTagArgsParseDocComment($docComment, $phpDocsTagName);
					$result = static::getPhpDocsTagArgsEncodeTags($tagLines, $phpDocsTagName, $reflectionObject);
					break;
				}
			}
			if ($traversing) {
				$reflectionObject = static::getParentReflectionObject($reflectionObject);
				if ($reflectionObject === FALSE) break;
			} else {
				break;
			}
		}
		return $result;
	}

	/**
	 * 
	 * @param  \ReflectionClass<T>|\ReflectionMethod|\ReflectionProperty $reflectionObject 
	 * @return \ReflectionClass<T>|\ReflectionMethod|\ReflectionProperty|FALSE
	 */
	protected static function getParentReflectionObject ($reflectionObject) {
		if ($reflectionObject instanceof \ReflectionClass) {
			return $reflectionObject->getParentClass();
		} else if ($reflectionObject instanceof \ReflectionProperty) {
			$declaredClass = $reflectionObject->getDeclaringClass();
			$parentClass = $declaredClass->getParentClass();
			if ($parentClass === FALSE)
				return FALSE;
			if (!$parentClass->hasProperty($reflectionObject->name))
				return FALSE;
			return $parentClass->getProperty($reflectionObject->name);
		} else if ($reflectionObject instanceof \ReflectionMethod) {
			$declaredClass = $reflectionObject->getDeclaringClass();
			$parentClass = $declaredClass->getParentClass();
			if ($parentClass === FALSE)
				return FALSE;
			if (!$parentClass->hasMethod($reflectionObject->name))
				return FALSE;
			return $parentClass->getMethod($reflectionObject->name);
		}
	}

	/**
	 * Parse PHP Doc comment into tag(s) array.
	 * @param  string $docComment 
	 * @param  string $phpDocsTagName 
	 * @return string[]
	 */
	protected static function getPhpDocsTagArgsParseDocComment ($docComment, $phpDocsTagName) {
		$docComment = str_replace(["\r\n", "\r"], "\n", trim(mb_substr($docComment, 3, mb_strlen($docComment) - 5)));
		$tagLines = [];
		$docCommentLines = explode("\n", $docComment);
		$tagMatched = FALSE;
		$index = 0;
		$length = count($docCommentLines);
		$docCommentLine = $docCommentLines[$index];
		$pattern = "#({$phpDocsTagName})\s*([^@]*)(@[a-zA-Z0-9]+)?#";
		while ($index < $length) {
			$docCommentLine = ltrim($docCommentLine, "*\t \v\0");
			if ($tagMatched) {
				if (mb_strpos($docCommentLine, '@') === 0) {
					$tagMatched = FALSE;
					continue;
				}
				$tagLines[] = $docCommentLine;
				$index++;
				if ($index === $length) break;
				$docCommentLine = $docCommentLines[$index];
			} else {
				$tagPos = mb_strpos($docCommentLine, $phpDocsTagName);
				if ($tagPos !== FALSE) {
					$tagMatched = TRUE;
					if ($tagPos > 0) $docCommentLine = mb_substr($docCommentLine, $tagPos);
					preg_match($pattern, $docCommentLine, $matches, PREG_OFFSET_CAPTURE);
					if ($matches && count($matches) > 3) {
						$nextTagPos = $matches[3][1];
						$tagLines[] = mb_substr($docCommentLine, 0, $nextTagPos);
						$docCommentLine = mb_substr($docCommentLine, $nextTagPos);
					} else {
						$tagLines[] = $docCommentLine;
						$index++;
						if ($index === $length) break;
						$docCommentLine = $docCommentLines[$index];
					}
				} else {
					$index++;
					if ($index === $length) break;
					$docCommentLine = $docCommentLines[$index];
				}
			}
		}
		return $tagLines;
	}

	/**
	 * Parse tag line(s) array into array(s) of parsed arguments.
	 * Input is always array of one or more tags with the same name:
	 * ````
	 *   ['@validator SafeString', '@validator MyClass', '@validator AnotherClass({"key":"value",...})']
	 * ````
	 * Output could be like:
	 * ````
	 *   [
	 *       'SafeString',
	 *       'MyClass',
	 *       'AnotherClass',
	 *       (object) ["key", "value", ...]
	 *   ]
	 * ````
	 * @param  array<string>                                             $tagLines 
	 * @param  string                                                    $phpDocsTagName
	 * @param  \ReflectionClass<T>|\ReflectionMethod|\ReflectionProperty $reflectionObject 
	 * @throws \InvalidArgumentException
	 * @return array<int|string,mixed>
	 */
	protected static function getPhpDocsTagArgsEncodeTags ($tagLines, $phpDocsTagName, $reflectionObject) {
		$result = [];
		if (count($tagLines) > 0) {
			$tagContent = mb_substr(implode("\n", $tagLines), mb_strlen($phpDocsTagName));
			if (mb_strpos($tagContent, $phpDocsTagName) !== FALSE) {
				$tagsContents = explode($phpDocsTagName, $tagContent);
			} else {
				$tagsContents = [$tagContent];
			}
			foreach ($tagsContents as $tagContent) {
				$classCtorParsed = FALSE;
				$localResult = [];
				if (preg_match_all("#^(\s*)([\\\\_A-Za-z0-9]*)?\((.*)\)(\s*);?(\s*)$#s", $tagContent, $matches)) {
					$className = $matches[2][0];
					if ($className !== '') 
						$localResult[] = $className;
					$jsonStr = NULL;
					try {
						$jsonStr = str_replace(["\n", "\r", "\t"], "", $matches[3][0]);
						$parsedData = static::JsonDecode($jsonStr, JSON_OBJECT_AS_ARRAY);
						if (count($localResult) > 0) {
							// if there is special class defined - there is possible to recognize param names
							$localResult[] = $parsedData;
						} else {
							// if there is only PHP Docs tag with JSON object - return directly this as array
							$localResult = $parsedData;
						}
						$classCtorParsed = TRUE;
					} catch (\Exception $e) {
						if ($reflectionObject instanceof \ReflectionClass) {
							$declaredClassFullName = $reflectionObject->getName();
							$declaredMember = NULL;
						} else {
							$declaredClassFullName = $reflectionObject->getDeclaringClass()->getName();
							$declaredMember = $reflectionObject->getName();
						}
						$errorMessage = "Syntax error in PHP Docs tag `{$phpDocsTagName}` in class `{$declaredClassFullName}`";
						if ($declaredMember !== NULL)
							$errorMessage .= " at member `{$declaredMember}`";
						$errorMessage .= " (json: `{$jsonStr}`).";
						throw new \InvalidArgumentException($errorMessage, 500, $e);
					}
				}
				if (!$classCtorParsed) {
					$rawResult = explode(',', $tagContent);
					foreach ($rawResult as $rawItem) {
						$rawItem = trim($rawItem);
						if ($rawItem !== '')
							$localResult[] = $rawItem;
					}
				}
				foreach ($localResult as $localItem)
					$result[] = $localItem;
			}
		}
		return $result;
	}

	/**
	 * Complete array with only static and also only public method names by given interface name.
	 * Return completed array and cache it in static local array.
	 * @param  string $interfaceName
	 * @return array<int,string>
	 */
	protected static function & checkClassInterfaceGetPublicStaticMethods ($interfaceName) {
		if (!isset(static::$cacheIntrfsStatMthds[$interfaceName]))
			static::$cacheIntrfsStatMthds[$interfaceName] = array_map(
				function (\ReflectionMethod $method) {
					return $method->name;
				},
				(new \ReflectionClass($interfaceName))->getMethods(
					\ReflectionMethod::IS_STATIC
				)
			);
		return static::$cacheIntrfsStatMthds[$interfaceName];
	}

	
	/**
	 * @inheritDoc
	 * @param  object              $instance
	 * @param  array<string, bool> $propNamesNotToSerialize
	 * @return array<string, mixed>
	 */
	public static function SerializeGetData ($instance, $propNamesNotToSerialize = []) {
		$class = get_class($instance);
		if (!isset(self::$serializeProps[$class]))
			static::serializeInitPropsCache($instance, $class, $propNamesNotToSerialize);
		return static::serializeCollectValues($instance, $class);
	}

	/**
	 * @inheritDoc
	 * @param  object               $instance
	 * @param  array<string, mixed> $data
	 * @return void
	 */
	public static function UnserializeSetData ($instance, $data) {
		foreach ($data as $mangledKey => $value) {
			$pos = strrpos($mangledKey, "\0");
			if ($pos === FALSE) {
				$instance->{$mangledKey} = $value;
				continue;
			}
			$scope = strpos($mangledKey, "\0*\0") === 0
				? get_class($instance)
				: substr($mangledKey, 1, $pos - 1);
			$propName = substr($mangledKey, $pos + 1);
			if (!isset(self::$unserializeWriters[$scope]))
				self::$unserializeWriters[$scope] = \Closure::bind(
					static function ($obj, $prop, $val) {
						$obj->{$prop} = $val;
					},
					NULL,
					$scope
				);
			$writer = self::$unserializeWriters[$scope];
			$writer($instance, $propName, $value);
		}
	}

	/**
	 * Build serialization metadata cache for given class.
	 * Deduplicates ReflectionClass instances per owner class within one scan.
	 * Sets $classHasDynamic flag when at least one dynamic property is found.
	 * @param  object              $instance
	 * @param  string              $class
	 * @param  array<string, bool> $propNamesNotToSerialize
	 * @return void
	 */
	protected static function serializeInitPropsCache ($instance, $class, $propNamesNotToSerialize) {
		$mangledKeys = array_keys(
			$instance instanceof \ArrayObject
				? $instance
				: (array) $instance
		);
		/** @var array<string, array{"0":?\ReflectionProperty, "1":string,"2":bool}> $classPropsMeta */
		$classPropsMeta = [];
		$classHasDynamic = FALSE;
		/** @var array<string, string> $deferDynamic */
		$deferDynamic = [];
		/** @var array<string, \ReflectionClass<object>> $reflClasses */
		$reflClasses = [];
		foreach ($mangledKeys as $mangledKey) {
			$ownerClass = $class;
			$propName   = $mangledKey;
			$pos        = strrpos($mangledKey, "\0");
			if ($pos !== FALSE) {
				if (strpos($mangledKey, "\0*") === FALSE)
					$ownerClass = substr($mangledKey, 1, $pos - 1);
				$propName = substr($mangledKey, $pos + 1);
			}
			if (
				isset($propNamesNotToSerialize[$propName]) &&
				!$propNamesNotToSerialize[$propName]
			) continue;
			if (!isset($reflClasses[$ownerClass]))
				$reflClasses[$ownerClass] = new \ReflectionClass($ownerClass);
			/** @var \ReflectionClass<object> $reflClass */
			$reflClass = $reflClasses[$ownerClass];
			if (!$reflClass->hasProperty($propName)) {
				$deferDynamic[$propName] = $mangledKey;
				continue;
			}
			$reflProp = $reflClass->getProperty($propName);
			if ($reflProp->isStatic()) continue;
			if (!$reflProp->isPublic() && PHP_VERSION_ID < 80500)
				$reflProp->setAccessible(TRUE);
			$val = $reflProp->getValue($instance);
			if (is_resource($val) || $val instanceof \Closure) continue;
			$classPropsMeta[$mangledKey] = [$reflProp, $propName, FALSE];
		}
		if ($deferDynamic) {
			$classHasDynamic = TRUE;
			$objectVars = get_object_vars($instance);
			foreach ($deferDynamic as $propName => $mangledKey) {
				$val = isset($objectVars[$propName])
					? $objectVars[$propName]
					: NULL;
				if (is_resource($val) || $val instanceof \Closure) continue;
				$classPropsMeta[$mangledKey] = [NULL, $propName, TRUE];
			}
		}
		self::$serializeProps[$class] = [$classPropsMeta, $classHasDynamic];
	}

	/**
	 * Collect current property values from $instance using cached metadata.
	 * Calls get_object_vars() at most once, only when dynamic props are present.
	 * @param  object $instance
	 * @param  string $class
	 * @return array<string, mixed>
	 */
	protected static function serializeCollectValues ($instance, $class) {
		$data       = [];
		list($classPropsMeta, $classHasDynamic) = self::$serializeProps[$class];
		$objectVars = $classHasDynamic
			? get_object_vars($instance)
			: [];
		foreach ($classPropsMeta as $mangledKey => $meta) {
			list(
				/** @var \ReflectionProperty|null $reflProp */
				$reflProp, 
				/** @var string $propName */
				$propName, 
				/** @var bool $isDynamic */
				$isDynamic
			) = $meta;
			$data[$mangledKey] = $isDynamic
				? (isset($objectVars[$propName]) ? $objectVars[$propName] : NULL)
				: $reflProp->getValue($instance);
		}
		return $data;
	}

}
