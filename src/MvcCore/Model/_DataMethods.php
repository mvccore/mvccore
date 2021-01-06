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

trait _DataMethods {

	/**
	 * Collect all model class public and inherit field values into array.
	 * @param bool $includeInheritProperties If `TRUE`, include fields from current and all parent classes, if `FALSE`, include fields only from current model class, default - `TRUE`.
	 * @param bool $publicOnly			     If `TRUE`, include only public instance fields, if `FALSE`, include all instance fields, default - `TRUE`.
	 * @param bool $getNullValues			 If `TRUE`, include also values with `NULL`s, default - `FALSE`.
	 * @return array
	 */
	public function GetValues ($includeInheritProperties = TRUE, $publicOnly = TRUE, $getNullValues = FALSE) {
		/**
		 * @var $this \MvcCore\Model
		 * @var $prop \ReflectionProperty
		 */
		$data = [];
		if ($publicOnly) {
			$properties = static::__getPropsDataPublic();
			foreach ($properties as $propertyName => $ownedByCurrent) {
				if (!$includeInheritProperties && !$ownedByCurrent)
					continue;
				$propValue = NULL;
				if (
					/**
					 * If property is public, it's ok to ask only by
					 * `isset($this->{$propertyName})`, because then it works
					 * in the same way like: `$prop->isInitialized($this)`
					 * for older PHP versions and also for PHP >= 7.4 and typed
					 * properties.
					 */
					isset($this->{$propertyName})
				) $propValue = $this->{$propertyName};
				if (!$getNullValues && $propValue === NULL)
					continue;
				$data[$propertyName] = $propValue;
			}
		} else {
			$properties = static::__getPropsDataAll();
			$phpWithTypes = PHP_VERSION_ID >= 70400;
			foreach ($properties as $propertyName => $propData) {
				list($ownedByCurrent, /*$types*/, $prop, $isPrivate) = $propData;
				if (!$includeInheritProperties && !$ownedByCurrent)
					continue;
				$propValue = NULL;
				if ($isPrivate) {
					/**
					 * If property is private, there is only way to get
					 * it's value by reflection rpoperty object. But for
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
				$data[$propertyName] = $propValue;
			}
		}
		return $data;
	}

	/**
	 * Set up given `$data` items into `$this` instance context
	 * as typed properties by PHP doc comments, as properties
	 * with the same names as `$data` array keys. Case sensitively by default.
	 * Do not set any `$data` items, which are not declared in `$this` context.
	 * @param array   $data						Collection with data to set up
	 * @param int	  $keysConversionFlags		`\MvcCore\IModel::PROPS_CONVERT_*` flags to process array keys conversion before set up into properties.
	 * @param bool    $completeInitialValues    Complete protected array `initialValues` to be able to compare them by calling method `GetTouched()` anytime later.
	 * @return \MvcCore\Model|\MvcCore\IModel
	 */
	public function SetUp ($data = [], $keysConversionFlags = NULL, $completeInitialValues = TRUE) {
		/**
		 * @var $this \MvcCore\Model
		 * @var $typeStrings \string[]
		 * @var $prop \ReflectionProperty
		 */
		$propsData = static::__getPropsDataAll();
		$caseSensitiveKeysMap = ','.implode(',', array_keys($propsData)).',';
		$keyConversionsMethods = $keysConversionFlags !== NULL
			? static::getKeyConversionMethods($keysConversionFlags)
			: [];
		$toolsClass = \MvcCore\Application::GetInstance()->GetToolClass();
		foreach ($data as $dbKey => $value) {
			$propertyName = $dbKey;
			foreach ($keyConversionsMethods as $keyConversionsMethod)
				$propertyName = static::{$keyConversionsMethod}(
					$propertyName, $toolsClass, $caseSensitiveKeysMap
				);
			$isNotNull = $value !== NULL;
			$isPrivate = NULL;
			if ($isNotNull && isset($propsData[$propertyName])) {
				list(/*$ownedByCurrent*/, $types, $prop, $isPrivate) = $propsData[$propertyName];
				$targetTypeValue = NULL;
				foreach ($types as $typeString) {
					if (substr($typeString, -2, 2) === '[]') {
						if (!is_array($value)) {
							$value = trim(strval($value));
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
						) = static::convertToType($value, $typeString);
					}
					if ($conversionResult) {
						$value = $targetTypeValue;
						break;
					}
				}
			}
			if ($isPrivate) {
				//$prop->setAccessible(TRUE);
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
	 * Get touched properties from initial moment called by `SetUp()` method.
	 * Get everything, what is different to `$this->initialValues` array.
	 * @param bool $includeInheritProperties If `TRUE`, include fields from current and all parent classes, if `FALSE`, include fields only from current model class, default - `TRUE`.
	 * @param bool $publicOnly			     If `TRUE`, include only public instance fields, if `FALSE`, include all instance fields, default - `TRUE`.
	 * @return array Keys are class properties names, values are changed values.
	 */
	public function GetTouched ($includeInheritProperties = TRUE, $publicOnly = FALSE) {
		/**
		 * @var $this \MvcCore\Model
		 * @var $prop \ReflectionProperty
		 */
		$touchedValues = [];
		if ($publicOnly) {
			$properties = static::__getPropsDataPublic();
			foreach ($properties as $propertyName => $ownedByCurrent) {
				if (!$includeInheritProperties && !$ownedByCurrent)
					continue;
				$initialValue = NULL;
				$currentValue = NULL;
				if (array_key_exists($propertyName, $this->initialValues))
					$initialValue = $this->initialValues[$propertyName];
				$currentValue = NULL;
				if (
					/**
					 * If property is public, it's ok to ask only by
					 * `isset($this->{$propertyName})`, because then it works
					 * in the same way like: `$prop->isInitialized($this)`
					 * for older PHP versions and also for PHP >= 7.4 and typed
					 * properties.
					 */
					isset($this->{$propertyName})
				) $currentValue = $this->{$propertyName};
				if ($initialValue !== $currentValue)
					$touchedValues[$propertyName] = $currentValue;
			}
		} else {
			$properties = static::__getPropsDataAll();
			$phpWithTypes = PHP_VERSION_ID >= 70400;
			foreach ($properties as $propertyName => $propData) {
				list($ownedByCurrent, /*$types*/, $prop, $isPrivate) = $propData;
				if (!$includeInheritProperties && !$ownedByCurrent)
					continue;
				$initialValue = NULL;
				$currentValue = NULL;
				if (array_key_exists($propertyName, $this->initialValues))
					$initialValue = $this->initialValues[$propertyName];
				$currentValue = NULL;
				if ($isPrivate) {
					/**
					 * If property is private, there is only way to get
					 * it's value by reflection rpoperty object. But for
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
					 * If property is not private, it's ok to ask only by
					 * `isset($this->{$propertyName})`, because then it works
					 * in the same way like: `$prop->isInitialized($this)`
					 * for older PHP versions and also for PHP >= 7.4 and typed
					 * properties.
					 */
					isset($this->{$propertyName})
				) {
					$currentValue = $this->{$propertyName};
				}
				if ($initialValue !== $currentValue)
					$touchedValues[$propertyName] = $currentValue;
			}
		}
		return $touchedValues;
	}

}
