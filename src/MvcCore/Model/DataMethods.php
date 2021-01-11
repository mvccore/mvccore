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
	 * @param int $propsFlags		All properties flags are available except flags: 
	 *								- `\MvcCore\IModel::PROPS_INITIAL_VALUES`,
	 *								- `\MvcCore\IModel::PROPS_CONVERT_CASE_INSENSITIVE`,
	 *								- `\MvcCore\IModel::PROPS_NAMES_BY_*`.
	 * @param bool $getNullValues	If `TRUE`, include also values with `NULL`s, 
	 *								`FALSE` by default.
	 * @throws \InvalidArgumentException
	 * @return array
	 */
	public function GetValues ($propsFlags = 0, $getNullValues = FALSE) {
		/** @var $this \MvcCore\Model */
		if ($propsFlags === 0) 
			$propsFlags = \MvcCore\IModel::PROPS_INHERIT | \MvcCore\IModel::PROPS_PROTECTED;
		
		$metaData = static::_getMetaData($propsFlags);
		
		$phpWithTypes = PHP_VERSION_ID >= 70400;
		$keyConversionsMethod = NULL;
		$caseSensitiveKeysMap = '';
		$stringKeyConversions = $propsFlags > 127;
		if ($stringKeyConversions) {
			$keyConversionsMethod = static::getKeyConversionMethod($propsFlags);
			$toolsClass = \MvcCore\Application::GetInstance()->GetToolClass();
			if ($propsFlags > 8191)
				$caseSensitiveKeysMap = ','.implode(',', array_keys($metaData)).',';
		};

		$result = [];
		
		foreach ($metaData as $propertyName => $propData) {
			$propValue = NULL;
			list (
				/*$propTypes*/, $propIsPublic, $propIsPrivate/*, $propAllowNulls*/
			) = $propData;
			if ($propIsPublic) {
				/**
				 * If property is public, it's ok to ask only by
				 * `isset($this->{$propertyName})`, because then it works
				 * in the same way like: `$prop->isInitialized($this)`
				 * for older PHP versions and also for PHP >= 7.4 and typed
				 * properties.
				 */
				if (isset($this->{$propertyName})) 
					$propValue = $this->{$propertyName};
			} else if ($propIsPrivate) {
				/**
				 * If property is private, there is only way to get
				 * it's value by reflection property object. But for
				 * PHP >= 7.4 and typed properties, there is necessary
				 * to ask `$prop->isInitialized($this)` first before
				 * calling `$prop->getValue($this);`.
				 */
				$prop = new \ReflectionProperty($this, $propertyName);
				$prop->setAccessible(TRUE);
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
			if ($stringKeyConversions) 
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
	 * @throws \InvalidArgumentException
	 * @return \MvcCore\Model Current `$this` context.
	 */
	public function SetUp ($data = [], $propsFlags = 0) {
		/** @var $this \MvcCore\Model */
		if ($propsFlags === 0) 
			$propsFlags = \MvcCore\IModel::PROPS_INHERIT | \MvcCore\IModel::PROPS_PROTECTED;
		
		$completeInitialValues = ($propsFlags & \MvcCore\IModel::PROPS_INITIAL_VALUES) != 0;
		if ($completeInitialValues) 
			$propsFlags ^= \MvcCore\IModel::PROPS_INITIAL_VALUES;

		$metaData = static::_getMetaData($propsFlags);

		$keyConversionsMethod = NULL;
		$caseSensitiveKeysMap = '';
		$stringKeyConversions = $propsFlags > 127;
		if ($stringKeyConversions) {
			$keyConversionsMethod = static::getKeyConversionMethod($propsFlags);
			$toolsClass = \MvcCore\Application::GetInstance()->GetToolClass();
			if ($propsFlags > 8191)
				$caseSensitiveKeysMap = ','.implode(',', array_keys($metaData)).',';
		};

		foreach ($data as $dbKey => $dbValue) {
			$propertyName = $dbKey;
			if ($stringKeyConversions) 
				$propertyName = static::{$keyConversionsMethod}(
					$propertyName, $toolsClass, $caseSensitiveKeysMap
				);
			$isNull = $dbValue === NULL;
			$propIsPrivate = NULL;
			if (isset($metaData[$propertyName])) {
				list (
					$propTypes, /*$propIsPublic*/, $propIsPrivate, $propAllowNulls
				) = $metaData[$propertyName];
				if (!$propAllowNulls && $isNull) continue;
				if ($isNull) {
					$value = $dbValue;
				} else {
					$value = static::convertToTypes($dbValue, $propTypes);	
				}
			} else {
				$value = $dbValue;
			}

			if ($propIsPrivate) {
				$prop = new \ReflectionProperty($this, $propertyName);
				$prop->setAccessible(TRUE);
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
	 * @param int $propsFlags	All properties flags are available except flags: 
	 *							- `\MvcCore\IModel::PROPS_INITIAL_VALUES`,
	 *							- `\MvcCore\IModel::PROPS_CONVERT_CASE_INSENSITIVE`.
	 * @throws \InvalidArgumentException
	 * @return array 
	 */
	public function GetTouched ($propsFlags = 0) {
		/** @var $this \MvcCore\Model */
		if ($propsFlags === 0) 
			$propsFlags = \MvcCore\IModel::PROPS_INHERIT | \MvcCore\IModel::PROPS_PROTECTED;
		
		$metaData = static::_getMetaData($propsFlags);
		
		$phpWithTypes = PHP_VERSION_ID >= 70400;
		$keyConversionsMethod = NULL;
		$caseSensitiveKeysMap = '';
		$stringKeyConversions = $propsFlags > 127;
		if ($stringKeyConversions) {
			$keyConversionsMethod = static::getKeyConversionMethod($propsFlags);
			$toolsClass = \MvcCore\Application::GetInstance()->GetToolClass();
			if ($propsFlags > 8191)
				$caseSensitiveKeysMap = ','.implode(',', array_keys($metaData)).',';
		};

		$result = [];

		foreach ($metaData as $propertyName => $propData) {
			list(
				/*$propTypes*/, $propIsPublic, $propIsPrivate/*, $propAllowNulls*/
			) = $propData;
			$initialValue = NULL;
			$currentValue = NULL;
			if (array_key_exists($propertyName, $this->initialValues))
				$initialValue = $this->initialValues[$propertyName];
			if ($propIsPublic) {
				/**
				 * If property is public, it's ok to ask only by
				 * `isset($this->{$propertyName})`, because then it works
				 * in the same way like: `$prop->isInitialized($this)`
				 * for older PHP versions and also for PHP >= 7.4 and typed
				 * properties.
				 */
				if (isset($this->{$propertyName})) 
					$currentValue = $this->{$propertyName};
			} else if ($propIsPrivate) {
				/**
				 * If property is private, there is only way to get
				 * it's value by reflection property object. But for
				 * PHP >= 7.4 and typed properties, there is necessary
				 * to ask `$prop->isInitialized($this)` first before
				 * calling `$prop->getValue($this);`.
				 */
				$prop = new \ReflectionProperty($this, $propertyName);
				$prop->setAccessible(TRUE);
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

			if (static::isEqual($initialValue, $currentValue)) continue;
			
			$resultKey = $propertyName;
			if ($stringKeyConversions) 
				$resultKey = static::{$keyConversionsMethod}(
					$resultKey, $toolsClass, $caseSensitiveKeysMap
				);
			
			$result[$resultKey] = $currentValue;
		}

		return $result;
	}
}