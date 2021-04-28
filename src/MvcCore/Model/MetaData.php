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

namespace MvcCore\Model;

/**
 * @mixin \MvcCore\Model
 */
trait MetaData {
	
	/**
	 * @inheritDocs
	 * @param  int $propsFlags
	 * @return array
	 */
	public static function GetMetaData ($propsFlags = 0) {
		/** @var \MvcCore\Model $this */

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
	 * @param  string $classFullName 
	 * @param  int    $accessModFlags 
	 * @param  bool   $inclInherit 
	 * @throws \InvalidArgumentException 
	 * @return array
	 */
	protected static function parseMetaData ($classFullName, $accessModFlags, $inclInherit) {
		$metaDataItem = [];
		$phpWithTypes = PHP_VERSION_ID >= 70400;
		$phpWithUnionTypes = PHP_VERSION_ID >= 80000;
		$props = (new \ReflectionClass($classFullName))
			->getProperties($accessModFlags);
		/** @var \ReflectionProperty $prop */
		foreach ($props as $prop) {
			if (
				$prop->isStatic() ||
				(!$inclInherit && $prop->class !== $classFullName) ||
				isset(static::$protectedProperties[$prop->name])
			) continue;
			$metaDataItem[$prop->name] = static::parseMetaDataProperty(
				$prop, [$phpWithTypes, $phpWithUnionTypes]
			);
		}
		return $metaDataItem;
	}

	/**
	 * Return `array` with metadata:
	 * - `0`	`boolean`	`TRUE` for private property.
	 * - `1'	`boolean`	`TRUE` to allow `NULL` values.
	 * - `2`	`string[]`	Property types from code or from doc comments or empty array.
	 * @param  \ReflectionProperty $prop 
	 * @param  array               $params [bool $phpWithTypes, bool $phpWithUnionTypes]
	 * @return array
	 */
	protected static function parseMetaDataProperty (\ReflectionProperty $prop, $params) {
		list ($phpWithTypes, $phpWithUnionTypes) = $params;
		$types = [];
		$allowNull = FALSE;
		if ($phpWithTypes && $prop->hasType()) {
			/** @var $reflType \ReflectionUnionType|\ReflectionNamedType */
			$refType = $prop->getType();
			if ($refType !== NULL) {
				if ($phpWithUnionTypes && $refType instanceof \ReflectionUnionType) {
					$refTypes = $refType->getTypes();
					/** @var \ReflectionNamedType $refTypesItem */
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
				$nullPos = mb_stripos($rawTypes,'|null|');
				$qmPos = mb_strpos($rawTypes, '?');
				$qmMatched = $qmPos !== FALSE;
				$nullMatched = $nullPos !== FALSE;
				$allowNull = $qmMatched || $nullMatched;
				if ($qmMatched) 
					$rawTypes = str_replace('?', '', $rawTypes);
				if ($nullMatched)
					$rawTypes = (
						mb_substr($rawTypes, 0, $nullPos) . 
						mb_substr($rawTypes, $nullPos + 5)
					);
				$rawTypes = mb_substr($rawTypes, 1, mb_strlen($rawTypes) - 2);
				$types = explode('|', $rawTypes);
			}
		}
		
		return [
			$prop->isPrivate(),	// boolean
			$allowNull,			// boolean
			$types,				// \string[]
		];
	}
	
	/**
	 * Complete meta data cache key flag, reflection properties getter flags
	 * and boolean about to include inherit properties or not.
	 * @param  int   $propsFlags 
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