<?php

/**
 * MvcCore
 *
 * This source file is subject to the BSD 3 License
 * For the full copyright and license information, please view
 * the LICENSE.md file that are distributed with this source code.
 *
 * @copyright	Copyright (c) 2016 Tom Flidr (https://github.com/mvccore)
 * @license  https://mvccore.github.io/docs/mvccore/5.0.0/LICENCE.md
 */

namespace MvcCore\Model;

trait MetaData {
	
	/**
	 * Return cached array about properties in current class to not create
	 * and parse reflection objects every time. 
	 * 
	 * Every key in array is property name, every value is array with metadata:
	 * - `0`	`boolean`	`TRUE` for private property.
	 * - `1'	`boolean`	`TRUE` to allow `NULL` values.
	 * - `2`	`string[]`	Property types from code or from doc comments or empty array.
	 * 
	 * Possible reading flags:
	 *  - `\MvcCore\IModel::PROPS_INHERIT`
	 *  - `\MvcCore\IModel::PROPS_PRIVATE`
	 *  - `\MvcCore\IModel::PROPS_PROTECTED`
	 *  - `\MvcCore\IModel::PROPS_PUBLIC`
	 * @param int $propsFlags
	 * @return array
	 */
	protected static function getMetaData ($propsFlags = 0) {
		/** @var $this \MvcCore\Model */

		/**
		 * This is static hidden property, so it has different values 
		 * for each static call. Keys in this array are integer flags, 
		 * values are arrays with metadata. Metadata array has key 
		 * by properties names.
		 * @var array
		 */
		static $__metaData = [];

		if ($propsFlags === 0) 
			$propsFlags = \MvcCore\IModel::PROPS_INHERIT | \MvcCore\IModel::PROPS_PROTECTED;

		list (
			$cacheFlags, $accessModFlags, $inclInherit
		) = static::getMetaDataFlags($propsFlags);

		if (isset($__metaData[$cacheFlags])) 
			return $__metaData[$cacheFlags];
		
		$classFullName = get_called_class();

		$metaDataItem = static::parseMetaData(
			$classFullName, $accessModFlags, $inclInherit
		);
		
		$__metaData[$cacheFlags] = $metaDataItem;
		
		return $metaDataItem;
	}

	/**
	 * Parse called class metadata with reflection.
	 * @param string $classFullName 
	 * @param int $accessModFlags 
	 * @param bool $inclInherit 
	 * @throws \InvalidArgumentException 
	 * @return array
	 */
	protected static function parseMetaData ($classFullName, $accessModFlags, $inclInherit) {
		$metaDataItem = [];
		$phpWithTypes = PHP_VERSION_ID >= 70400;
		$props = (new \ReflectionClass($classFullName))
			->getProperties($accessModFlags);
		/** @var $prop \ReflectionProperty */
		foreach ($props as $prop) {
			if (
				$prop->isStatic() ||
				(!$inclInherit && $prop->class !== $classFullName) ||
				isset(static::$protectedProperties[$prop->name])
			) continue;
			$metaDataItem[$prop->name] = static::parseMetaDataProperty($prop, $phpWithTypes);
		}
		return $metaDataItem;
	}

	/**
	 * Return `array` with metadata:
	 * - `0`	`boolean`	`TRUE` for private property.
	 * - `1'	`boolean`	`TRUE` to allow `NULL` values.
	 * - `2`	`string[]`	Property types from code or from doc comments or empty array.
	 * @param \ReflectionProperty $prop 
	 * @param bool $phpWithTypes 
	 * @return array
	 */
	protected static function parseMetaDataProperty (\ReflectionProperty $prop, $phpWithTypes) {
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
			$prop->isPrivate(),	// boolean
			$allowNull,			// boolean
			$types,				// string[]
		];
	}
	
	/**
	 * Complete meta data cache key flag, reflection properties getter flags
	 * and boolean about to include inherit properties or not.
	 * @param int $propsFlags 
	 * @return array [int, int, bool]
	 */
	protected static function getMetaDataFlags ($propsFlags) {
		$cacheFlags = 0;
		$accessModFlags = 0;
		$inclInherit = FALSE;
		if (($propsFlags & \MvcCore\IModel::PROPS_INHERIT) != 0) {
			$cacheFlags |= \MvcCore\IModel::PROPS_INHERIT;
			$inclInherit = TRUE;
		}
		if (($propsFlags & \MvcCore\IModel::PROPS_PRIVATE) != 0) {
			$cacheFlags |= \MvcCore\IModel::PROPS_PRIVATE;
			$accessModFlags |= \ReflectionProperty::IS_PRIVATE;
		}
		if (($propsFlags & \MvcCore\IModel::PROPS_PROTECTED) != 0) {
			$cacheFlags |= \MvcCore\IModel::PROPS_PROTECTED;
			$accessModFlags |= \ReflectionProperty::IS_PROTECTED;
		}
		if (($propsFlags & \MvcCore\IModel::PROPS_PUBLIC) != 0) {
			$cacheFlags |= \MvcCore\IModel::PROPS_PUBLIC;
			$accessModFlags |= \ReflectionProperty::IS_PUBLIC;
		}
		return [$cacheFlags, $accessModFlags, $inclInherit];
	}
}