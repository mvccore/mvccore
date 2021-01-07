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

trait DataMethods {

	/**
	 * @inheritDocs
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
			list(/*$types*/, $prop, $isPrivate, $isPublic) = $propData;

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
			
			$result[$resultKey] = $propValue;
		}

		return $result;
	}

	/**
	 * @inheritDocs
	 * @param array $data Raw row data from database.
	 * @param int $propsFlags All properties flags are available.
	 * @return \MvcCore\Model Current `$this` context.
	 */
	public function SetUp ($data = [], $propsFlags = 0) {
		/**
		 * @var $this \MvcCore\Model
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
				list ($typeStrings, $prop, $isPrivate) = $metaData[$propertyName];
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
	 * @inheritDocs
	 * @param int $propsFlags All properties flags are available except flags 
	 *						  `\MvcCore\IModel::PROPS_INITIAL_VALUES` and 
	 *						  `\MvcCore\IModel::PROPS_CONVERT_CASE_INSENSITIVE`.
	 * @return array 
	 */
	public function GetTouched ($propsFlags = 0) {
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
			list (/*$types*/, $prop, $isPrivate, $isPublic) = $propData;

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

			if (static::compareValues($initialValue, $currentValue)) continue;
			
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