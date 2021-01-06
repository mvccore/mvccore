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

trait ActiveRecord {

	use \MvcCore\Model\MetaData;
	use \MvcCore\Model\Converters;

	/**
	 * Collect all model class properties values into array.
	 * Result keys could be converted by any conversion flag.
	 * @param int $propsFlags All properties flags are available except flags 
	 *						  `\MvcCore\IModel::PROPS_INITIAL_VALUES` and 
	 *						  `\MvcCore\IModel::PROPS_CONVERT_CASE_INSENSITIVE`.
	 * @param bool $getNullValues If `TRUE`, include also values with `NULL`s, default - `FALSE`.
	 * @return array
	 */
	public function GetValues ($propsFlags = 0, $getNullValues = FALSE) {
		/**
		 * @var $this \MvcCore\Model
		 * @var $prop \ReflectionProperty
		 */
		if ($propsFlags === 0) 
			$propsFlags = \MvcCore\IModel::PROPS_INHERIT | \MvcCore\IModel::PROPS_PROTECTED;
		
		$metaData = static::__getPropsMetaData($propsFlags);
		
		$phpWithTypes = PHP_VERSION_ID >= 70400;
		$keyConversionsMethods = [];
		$caseSensitiveKeysMap = '';
		if ($propsFlags > 31) {
			$keyConversionsMethods = static::getKeyConversionMethods($propsFlags);
			$toolsClass = \MvcCore\Application::GetInstance()->GetToolClass();
			if ($propsFlags > 2047)
				$caseSensitiveKeysMap = ','.implode(',', array_keys($metaData)).',';
		};

		$result = [];
		
		foreach ($metaData as $propertyName => $propData) {
			list(
				$ownedByCurrent, /*$types*/, $prop, 
				$isPrivate, /*$isProtected*/, $isPublic
			) = $propData;

			$propValue = NULL;

			if ($isPublic) {
				/**
				 * If property is public, it's ok to ask only by
				 * `isset($this->{$propertyName})`, because then it works
				 * in the same way like: `$prop->isInitialized($this)`
				 * for older PHP versions and also for PHP >= 7.4 and typed
				 * properties.
				 */
				if (isset($this->{$propertyName})) 
					$propValue = $this->{$propertyName};
			} else if ($isPrivate) {
				/**
				 * If property is private, there is only way to get
				 * it's value by reflection property object. But for
				 * PHP >= 7.4 and typed properties, there is necessary
				 * to ask `$prop->isInitialized($this)` first before
				 * calling `$prop->getValue($this);`.
				 */
				if ($phpWithTypes) {
					if ($prop->isInitialized($this))
						$propValue = $prop->getValue($this);
				} else {
					$propValue = $prop->getValue($this);
				}
			} else if (
				/**
				 * If property is not private, it's ok to ask only by
				 * `isset($this->{$propertyName})`, because then it works
				 * in the same way like: `$prop->isInitialized($this)`
				 * for older PHP versions and also for PHP >= 7.4 and typed
				 * properties.
				 */
				isset($this->{$propertyName})
			) {
				$propValue = $this->{$propertyName};
			}
			
			if (!$getNullValues && $propValue === NULL)
				continue;
			
			$resultKey = $propertyName;
			foreach ($keyConversionsMethods as $keyConversionsMethod)
				$resultKey = static::{$keyConversionsMethod}(
					$resultKey, $toolsClass, $caseSensitiveKeysMap
				);
			
			$result[$resultKey] = $currentValue;
		}

		return $result;
	}

	/**
	 * Set up given `$data` items into `$this` instance context
	 * as typed properties by PHP types (or by PhpDocs comments in PHP < 7.4) 
	 * as properties with the same names as `$data` array keys or converted
	 * by properties flags. Case sensitivelly by default.
	 * Do not set any `$data` items, which are not declared in `$this` context.
	 * @param array $data Raw row data from database.
	 * @param int $propsFlags All properties flags are available.
	 * @return \MvcCore\Model|\MvcCore\Model\ActiveRecord Current `$this` context.
	 */
	public function SetUp ($data = [], $propsFlags = 0) {
		/**
		 * @var $this \MvcCore\Model|\MvcCore\Model\ActiveRecord
		 * @var $typeStrings \string[]
		 * @var $prop \ReflectionProperty
		 */
		if ($propsFlags === 0) 
			$propsFlags = \MvcCore\IModel::PROPS_INHERIT | \MvcCore\IModel::PROPS_PROTECTED;
		
		$completeInitialValues = ($propsFlags & \MvcCore\IModel::PROPS_INITIAL_VALUES) != 0;
		if ($completeInitialValues) 
			$propsFlags ^= \MvcCore\IModel::PROPS_INITIAL_VALUES;

		$metaData = static::__getPropsMetaData($propsFlags);

		$keyConversionsMethods = [];
		$caseSensitiveKeysMap = '';
		if ($propsFlags > 31) {
			$keyConversionsMethods = static::getKeyConversionMethods($propsFlags);
			$toolsClass = \MvcCore\Application::GetInstance()->GetToolClass();
			if ($propsFlags > 2047)
				$caseSensitiveKeysMap = ','.implode(',', array_keys($metaData)).',';
		};

		foreach ($data as $dbKey => $dbValue) {
			$propertyName = $dbKey;
			foreach ($keyConversionsMethods as $keyConversionsMethod)
				$propertyName = static::{$keyConversionsMethod}(
					$propertyName, $toolsClass, $caseSensitiveKeysMap
				);
			$isNotNull = $dbValue !== NULL;
			$isPrivate = NULL;
			if ($isNotNull && isset($metaData[$propertyName])) {
				list(
					/*$ownedByCurrent*/, $typeStrings, 
					$prop, $isPrivate
				) = $metaData[$propertyName];
				$targetTypeValue = NULL;
				foreach ($typeStrings as $typeString) {
					if (substr($typeString, -2, 2) === '[]') {
						if (!is_array($value)) {
							$value = trim(strval($dbValue));
							$value = $value === '' ? [] : explode(',', $value);
						}
						$arrayItemTypeString = substr($typeString, 0, strlen($typeString) - 2);
						$targetTypeValue = [];
						$conversionResult = TRUE;
						foreach ($value as $key => $item) {
							list(
								$conversionResultLocal, $targetTypeValueLocal
							) = static::convertToType($item, $arrayItemTypeString);
							if ($conversionResultLocal) {
								$targetTypeValue[$key] = $targetTypeValueLocal;
							} else {
								$conversionResult = FALSE;
								break;
							}
						}
					} else {
						list(
							$conversionResult, $targetTypeValue
						) = static::convertToType($dbValue, $typeString);
					}
					if ($conversionResult) {
						$value = $targetTypeValue;
						break;
					} else {
						$value = $dbValue;
					}
				}
			} else {
				$value = $dbValue;
			}

			if ($isPrivate) {
				$prop->setValue($this, $value);
			} else {
				$this->{$propertyName} = $value;
			}

			if ($completeInitialValues)
				$this->initialValues[$propertyName] = $value;
		}
		return $this;
	}

	/**
	 * Get touched properties from `$this` context.
	 * Touched properties are properties with different value than key 
	 * in `$this->initialValues` (initial array completed in `SetUp()` method).
	 * Result keys could be converted by any conversion flag.
	 * @param int $propsFlags All properties flags are available except flags 
	 *						  `\MvcCore\IModel::PROPS_INITIAL_VALUES` and 
	 *						  `\MvcCore\IModel::PROPS_CONVERT_CASE_INSENSITIVE`.
	 * @return array 
	 */
	public function GetTouched ($propsFlags = 0) {
		/**
		 * @var $this \MvcCore\Model|\MvcCore\Model\ActiveRecord
		 * @var $prop \ReflectionProperty
		 */
		if ($propsFlags === 0) 
			$propsFlags = \MvcCore\IModel::PROPS_INHERIT | \MvcCore\IModel::PROPS_PROTECTED;
		
		$metaData = static::__getPropsMetaData($propsFlags);
		
		$phpWithTypes = PHP_VERSION_ID >= 70400;
		$keyConversionsMethods = [];
		$caseSensitiveKeysMap = '';
		if ($propsFlags > 31) {
			$keyConversionsMethods = static::getKeyConversionMethods($propsFlags);
			$toolsClass = \MvcCore\Application::GetInstance()->GetToolClass();
			if ($propsFlags > 2047)
				$caseSensitiveKeysMap = ','.implode(',', array_keys($metaData)).',';
		};

		$result = [];

		foreach ($metaData as $propertyName => $propData) {
			list(
				$ownedByCurrent, /*$types*/, $prop, 
				$isPrivate, /*$isProtected*/, $isPublic
			) = $propData;

			$initialValue = NULL;
			$currentValue = NULL;
			if (array_key_exists($propertyName, $this->initialValues))
				$initialValue = $this->initialValues[$propertyName];

			if ($isPublic) {
				/**
				 * If property is public, it's ok to ask only by
				 * `isset($this->{$propertyName})`, because then it works
				 * in the same way like: `$prop->isInitialized($this)`
				 * for older PHP versions and also for PHP >= 7.4 and typed
				 * properties.
				 */
				if (isset($this->{$propertyName})) 
					$currentValue = $this->{$propertyName};
			} else if ($isPrivate) {
				/**
				 * If property is private, there is only way to get
				 * it's value by reflection property object. But for
				 * PHP >= 7.4 and typed properties, there is necessary
				 * to ask `$prop->isInitialized($this)` first before
				 * calling `$prop->getValue($this);`.
				 */
				if ($phpWithTypes) {
					if ($prop->isInitialized($this))
						$currentValue = $prop->getValue($this);
				} else {
					$currentValue = $prop->getValue($this);
				}
			} else if (
				/**
				 * If property is not public or private, it's ok to ask only by
				 * `isset($this->{$propertyName})`, because then it works
				 * in the same way like: `$prop->isInitialized($this)`
				 * for older PHP versions and also for PHP >= 7.4 and typed
				 * properties.
				 */
				isset($this->{$propertyName})
			) {
				$currentValue = $this->{$propertyName};
			}

			if ($initialValue === $currentValue) continue;
			
			$resultKey = $propertyName;
			foreach ($keyConversionsMethods as $keyConversionsMethod)
				$resultKey = static::{$keyConversionsMethod}(
					$resultKey, $toolsClass, $caseSensitiveKeysMap
				);
			
			$result[$resultKey] = $currentValue;
		}

		return $result;
	}
}