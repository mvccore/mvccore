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
		$metaData = static::getMetaData($propsFlags);
		
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
			//list ($propIsPrivate, $propAllowNulls, $propTypes) = $propData;
			if ($propData[0]) {
				$prop = new \ReflectionProperty($this, $propertyName);
				$prop->setAccessible(TRUE);
				if ($phpWithTypes) {
					if ($prop->isInitialized($this))
						$propValue = $prop->getValue($this);
				} else {
					$propValue = $prop->getValue($this);
				}
			} else if (isset($this->{$propertyName})) {
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
	 * @param array $data Raw data from database (row) or from form fields.
	 * @param int $propsFlags All properties flags are available.
	 * @throws \InvalidArgumentException
	 * @return \MvcCore\Model Current `$this` context.
	 */
	public function SetValues ($data = [], $propsFlags = 0) {
		/** @var $this \MvcCore\Model */
		$completeInitialValues = ($propsFlags & \MvcCore\IModel::PROPS_INITIAL_VALUES) != 0;

		$metaData = static::getMetaData($propsFlags);

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
					$propIsPrivate, $propAllowNulls, $propTypes
				) = $metaData[$propertyName];
				if (!$propAllowNulls && $isNull) continue;
				if ($isNull) {
					$value = $dbValue;
				} else {
					$value = static::parseToTypes($dbValue, $propTypes);	
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
		$metaData = static::getMetaData($propsFlags);
		
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
			$initialValue = NULL;
			$currentValue = NULL;
			if (array_key_exists($propertyName, $this->initialValues))
				$initialValue = $this->initialValues[$propertyName];
			//list ($propIsPrivate, $propAllowNulls, $propTypes) = $propData;
			if ($propData[0]) {
				$prop = new \ReflectionProperty($this, $propertyName);
				$prop->setAccessible(TRUE);
				if ($phpWithTypes) {
					if ($prop->isInitialized($this))
						$currentValue = $prop->getValue($this);
				} else {
					$currentValue = $prop->getValue($this);
				}
			} else if (isset($this->{$propertyName})) {
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