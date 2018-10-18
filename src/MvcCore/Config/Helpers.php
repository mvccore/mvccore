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

namespace MvcCore\Config;

trait Helpers
{
	/**
	 * Return `TRUE` if `$rawKey` is numeric.
	 * @param string $rawKey
	 * @return bool
	 */
	protected function isKeyNumeric ($rawKey) {
		$numericRawKey = preg_replace("#[^0-9\-]#", '', $rawKey);
		return $numericRawKey == $rawKey;
	}

	/**
	 * Retype raw ini value into `array` with retyped it's own values or
	 * retype raw ini value into `float`, `int` or `string`.
	 * @param string|array $rawValue
	 * @return array|float|int|string
	 */
	protected function getTypedValue ($rawValue) {
		if (gettype($rawValue) == "array") {
			foreach ($rawValue as $key => $value) {
				$rawValue[$key] = $this->getTypedValue($value);
			}
			return $rawValue; // array
		} else {
			$numericRawVal = preg_replace("#[^0-9\-\.]#", '', $rawValue);
			if ($numericRawVal == $rawValue) {
				return $this->getTypedValueFloatIpOrInt($rawValue);
			} else {
				return $this->getTypedValueBoolOrString($rawValue);
			}
		}
	}

	/**
	 * Retype raw ini value into `float`, `IP` or `int`.
	 * @param string $rawValue
	 * @return float|string|int
	 */
	protected function getTypedValueFloatIpOrInt ($rawValue) {
		if (strpos($rawValue, '.') !== FALSE) {
			if (substr_count($rawValue, '.') === 1) {
				return floatval($rawValue); // float
			} else {
				return $rawValue; // ip
			}
		} else {
			$intVal = intval($rawValue); // int or string if integer is too high (more then PHP max/min: 2147483647/-2147483647)
			return (string) $intVal === $rawValue ? $intVal : $rawValue;
		}
	}

	/**
	 * Retype raw ini value into `bool` or `string`.
	 * @param string $rawValue
	 * @return bool|string
	 */
	protected function getTypedValueBoolOrString ($rawValue) {
		$lowerRawValue = strtolower($rawValue);
		if (isset(static::$booleanValues[$lowerRawValue])) {
			return static::$booleanValues[$lowerRawValue]; // bool
		} else {
			return trim($rawValue); // string
		}
	}
}
