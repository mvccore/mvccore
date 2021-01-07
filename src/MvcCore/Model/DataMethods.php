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
	 * Set up given `$data` items into `$this` instance context
	 * as typed properties by PHP types (or by PhpDocs comments in PHP < 7.4) 
	 * as properties with the same names as `$data` array keys or converted
	 * by properties flags. Case sensitivelly by default.
	 * Do not set any `$data` items, which are not declared in `$this` context.
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

	/**
	 * Compare two values. Supported types are:
	 *  - NULL
	 *  - scalar (int, float, string, bool)
	 *  - array
	 *  - \stdClass
	 *  - \DateTimeInterface, \DateInterval, \DateTimeZone, \DatePeriod
	 *  - resource (only by `intval($value1) == intval($value2)`)
	 *  - object instances (only by `===` comparison)
	 * @param mixed $value1 
	 * @param mixed $value2 
	 * @return bool
	 */
	protected static function compareValues ($value1, $value2) {
		$valuasAreTheSame = FALSE;
		$value1IsNull = $value1 === NULL;
		$value2IsNull = $value2 === NULL;
		if ($value1IsNull && $value2IsNull) {
			$valuasAreTheSame = TRUE;
		} else if (!$value1IsNull && !$value2IsNull) {
			if (is_float($value1) && is_float($value2)) {
				$valuasAreTheSame = abs($value1 - $value2) < PHP_FLOAT_EPSILON;
				
			} else if (
				(is_scalar($value1) && is_scalar($value2)) ||
				(is_array($value1) && is_array($value2)) ||
				($value1 instanceof \stdClass && $value2 instanceof \stdClass)
			) {
				$valuasAreTheSame = $value1 === $value2;
				
			} else if ($value1 instanceof \DateTimeInterface && $value2 instanceof \DateTimeInterface) {
				$valuasAreTheSame = $value1 == $value2;
				
			} else if ($value1 instanceof \DateInterval && $value2 instanceof \DateInterval) {
				$valuasAreTheSame = abs(
					self::_convertIntervalToFloat($value1) - 
					self::_convertIntervalToFloat($value2)
				) < PHP_FLOAT_EPSILON;

			} else if ($value1 instanceof \DateTimeZone && $value2 instanceof \DateTimeZone) {
				$now = new \DateTime('now');
				$valuasAreTheSame = $value1->getOffset($now) === $value2->getOffset($now);

			} else if ($value1 instanceof \DatePeriod && $value2 instanceof \DatePeriod) {
				$valuasAreTheSame = (
					$value1->getStartDate() == $value2->getStartDate() && 
					$value1->getEndDate() == $value2->getEndDate() && 
					abs(
						self::_convertIntervalToFloat($value1->getDateInterval()) - 
						self::_convertIntervalToFloat($value2->getDateInterval())
					) < PHP_FLOAT_EPSILON
				);

			} else if (is_resource($value1) && is_resource($value2)) {
				$valuasAreTheSame = intval($value1) == intval($value2);

			} else {
				// compare if object instances are the same (do not process any reflection comparison):
				$valuasAreTheSame = $value1 === $value2;

			}
		}
		return $valuasAreTheSame;
	}

	/**
	 * Convert date interval to total microseconds float.
	 * @param \DateInterval $interval 
	 * @return float
	 */
	private static function _convertIntervalToFloat ($interval) {
		$result = floatval(
			($interval->days * 86400) + 
			($interval->h * 3600) + 
			($interval->i * 60) + 
			($interval->s)
		);
		if (PHP_VERSION_ID >= 70100)
			$result += $interval->f;
		return $result;
	}
}