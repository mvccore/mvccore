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

namespace MvcCore\Model;

trait MetaData {
	
	/**
	 * Return cached array about properties in current class to not create
	 * and parse reflection objects every time. 
	 * 
	 * Every key in array is property name, every value is array with metadata:
	 * - `0`	`string[]`	Property types from code or from doc comments or empty array.
	 * - `1`	`boolean`	`TRUE` for public property.
	 * - `2`	`boolean`	`TRUE` for private property.
	 * - `3'	`boolean`	`TRUE` to allow `NULL` values.
	 * 
	 * Possible reading flags:
	 *  - `\MvcCore\IModel::PROPS_INHERIT`
	 *  - `\MvcCore\IModel::PROPS_PRIVATE`
	 *  - `\MvcCore\IModel::PROPS_PROTECTED`
	 *  - `\MvcCore\IModel::PROPS_PUBLIC`
	 * @param int $readingFlags
	 * @return array
	 */
	private static function _getMetaData ($readingFlags = 0) {
		/** @var $this \MvcCore\Model */

		/**
		 * This is private static hidden property (private as it's method), 
		 * so it has different values for each class. Keys in this array
		 * are integer flags, values are arrays with metadata. Metadata
		 * array has key by properties names.
		 * @var array
		 */
		static $__propsMetaData = [];

		list (
			$cacheFlags, $accessModFlags, $inclInherit
		) = static::_getMetaDataFlagsAndInheriting($readingFlags);

		if (isset($__propsMetaData[$cacheFlags])) 
			return $__propsMetaData[$cacheFlags];
		
		$calledClassFullName = get_called_class();

		$metaDataItem = static::_parseMetaData(
			$calledClassFullName, $accessModFlags, $inclInherit
		);
		
		$__propsMetaData[$cacheFlags] = $metaDataItem;
		
		return $metaDataItem;
	}

	/**
	 * Parse called class metadata with reflection.
	 * @param string $calledClassFullName 
	 * @param int $accessModFlags 
	 * @param bool $inclInherit 
	 * @throws \InvalidArgumentException 
	 * @return array
	 */
	private static function _parseMetaData ($calledClassFullName, $accessModFlags, $inclInherit) {
		$metaDataItem = [];
		$phpWithTypes = PHP_VERSION_ID >= 70400;
		$calledClassFullName = get_called_class();
		$props = (new \ReflectionClass($calledClassFullName))
			->getProperties($accessModFlags);
		/** @var $prop \ReflectionProperty */
		foreach ($props as $prop) {
			if (
				$prop->isStatic() ||
				(!$inclInherit && $prop->class !== $calledClassFullName) ||
				isset(static::$protectedProperties[$prop->name])
			) continue;
			$metaDataItem[$prop->name] = static::_getMetaDataProp($prop, $phpWithTypes);
		}
		return $metaDataItem;
	}

	/**
	 * Return `array` with metadata:
	 * - `0`	`string[]`	Property types from code or from doc comments or empty array.
	 * - `1`	`boolean`	`TRUE` for public property.
	 * - `2`	`boolean`	`TRUE` for private property.
	 * - `3'	`boolean`	`TRUE` to allow `NULL` values.
	 * @param \ReflectionProperty $prop 
	 * @param bool $phpWithTypes 
	 * @return array
	 */
	private static function _getMetaDataProp (\ReflectionProperty $prop, $phpWithTypes) {
		$types = [];
		$allowNull = FALSE;
		if ($phpWithTypes && $prop->hasType()) {
			/** @var $reflType \ReflectionUnionType|\ReflectionNamedType */
			$refType = $prop->getType();
			if ($refType !== NULL) {
				if ($refType instanceof \ReflectionUnionType) {
					$refTypes = $refType->getTypes();
					/** @var $refTypesItem \ReflectionNamedType */
					$strIndex = NULL;
					foreach ($refTypes as $index => $refTypesItem) {
						$typeName = $refTypesItem->getName();
						if ($strIndex === NULL && $typeName === 'string')
							$strIndex = $index;
						if ($typeName !== 'null')
							$types[] = $typeName;
					}
					if ($strIndex !== NULL) {
						unset($types[$strIndex]);
						$types = array_values($types);
						$types[] = 'string';
					}
				} else {
					$types = [$refType->getName()];
				}
				$allowNull = $refType->allowsNull();
			}
		} else {
			preg_match('/@var\s+([^\s]+)/', $prop->getDocComment(), $matches);
			if ($matches) {
				$rawTypes = '|'.$matches[1].'|';
				$nullPos = stripos($rawTypes,'|null|');
				$qmPos = strpos($rawTypes, '?');
				$qmMatched = $qmPos !== FALSE;
				$nullMatched = $nullPos !== FALSE;
				$allowNull = $qmMatched || $nullMatched;
				if ($qmMatched) 
					$rawTypes = str_replace('?', '', $rawTypes);
				if ($nullMatched)
					$rawTypes = (
						substr($rawTypes, 0, $nullPos) . 
						substr($rawTypes, $nullPos + 5)
					);
				$rawTypes = substr($rawTypes, 1, strlen($rawTypes) - 2);
				$types = explode('|', $matches[1]);
			}
		}
		
		return [
			$types,				// string[]
			$prop->isPublic(),	// boolean
			$prop->isPrivate(),	// boolean
			$allowNull,			// boolean
		];
	}
	
	/**
	 * Complete meta data cache key flag, reflection properties getter flags
	 * and boolean about to include inherit properties or not.
	 * @param int $readingFlags 
	 * @return array [int, int, bool]
	 */
	private static function _getMetaDataFlagsAndInheriting ($readingFlags) {
		$cacheFlags = 0;
		$accessModFlags = 0;
		$inclInherit = FALSE;
		if (($readingFlags & \MvcCore\IModel::PROPS_INHERIT) != 0) {
			$cacheFlags |= \MvcCore\IModel::PROPS_INHERIT;
			$inclInherit = TRUE;
		}
		if (($readingFlags & \MvcCore\IModel::PROPS_PRIVATE) != 0) {
			$cacheFlags |= \MvcCore\IModel::PROPS_PRIVATE;
			$accessModFlags |= \ReflectionProperty::IS_PRIVATE;
		}
		if (($readingFlags & \MvcCore\IModel::PROPS_PROTECTED) != 0) {
			$cacheFlags |= \MvcCore\IModel::PROPS_PROTECTED;
			$accessModFlags |= \ReflectionProperty::IS_PROTECTED;
		}
		if (($readingFlags & \MvcCore\IModel::PROPS_PUBLIC) != 0) {
			$cacheFlags |= \MvcCore\IModel::PROPS_PUBLIC;
			$accessModFlags |= \ReflectionProperty::IS_PUBLIC;
		}
		return [$cacheFlags, $accessModFlags, $inclInherit];
	}
}