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

trait IniRead
{
	/**
	 * Load config file and return `TRUE` for success or `FALSE` in failure.
	 * - Second environment value setup:
	 *   - Only if `\MvcCore\Config::$system` property is defined as `TRUE`.
	 *   - By defined client IPs, server hostnames or environment variables
	 *     in `environments` section. By values or regular expressions.
	 * - Load only sections for current environment name.
	 * - Retype all `raw string` values into `array`, `float`, `int` or `boolean` types.
	 * - Retype whole values level into `\stdClass`, if there are no numeric keys.
	 * @param string $fullPath
	 * @return bool
	 */
	protected function read ($fullPath) {
		/** @var $this \MvcCore\Config */
		if ($this->envData) return TRUE;
		$this->fullPath = $fullPath;
		if (!$this->_iniScannerMode)
			// 1 => INI_SCANNER_RAW, 2 => INI_SCANNER_TYPED
			$this->_iniScannerMode = \PHP_VERSION_ID < 50610 ? 1 : 2;
		clearstatcache(TRUE, $fullPath);
		$this->lastChanged = filemtime($fullPath);
		$rawIniData = parse_ini_file(
			$this->fullPath, TRUE, $this->_iniScannerMode
		);
		if ($rawIniData === FALSE) return FALSE;
		$this->envData = [];
		$this->mergedData = [];
		$this->currentData = [];
		$allEnvIniDataPlain = $this->iniReadAllEnvironmentsSections($rawIniData);
		foreach ($allEnvIniDataPlain as $envName => $envIniData) {
			list($data, $objectTypes) = $this->iniReadExpandLevelsAndReType(
				$envIniData
			);
			foreach ($objectTypes as & $objectType)
				if ($objectType[0])
					$objectType[1] = (object) $objectType[1];
			$this->envData[$envName] = $data;
		}
		return TRUE;
	}

	/**
	 * Explode all possible sections into environment ini data collections,
	 * keyed by environment name. Data, not targeted into any environment,
	 * explode into default ini data collection keyed under empty string.
	 * @param array $rawIniData
	 * @return array
	 */
	protected function & iniReadAllEnvironmentsSections (array & $rawIniData) {
		/** @var $this \MvcCore\Config */
		$allEnvsIniData = [];
		$commonEnvDataKey = static::$commonEnvironmentDataKey;
		$environmentNamesFilter = static::$environmentNamesFilter;
		$sectionNamesFilter = static::$sectionNamesFilter;
		foreach ($rawIniData as $keyOrSectionName => $valueOrSectionValues) {
			$parsedEnvNames = [];
			if (is_array($valueOrSectionValues)) {
				$pos = mb_strpos($keyOrSectionName, '>');
				if ($pos === FALSE) {
					$key = $keyOrSectionName;
				} else {
					$envNames = mb_substr($keyOrSectionName, 0, $pos);
					$envNames = preg_replace($environmentNamesFilter, '', $envNames);
					$key = mb_substr($keyOrSectionName, $pos + 1);
					$key = preg_replace($sectionNamesFilter, '', $key);
					$parsedEnvNames = explode(',', $envNames);
				}
				$newValues = [];
				foreach ($valueOrSectionValues as $subKey => $subValue)
					$newValues[$key.'.'.$subKey] = $subValue;
			} else {
				$newValues = [$keyOrSectionName => $valueOrSectionValues];
			}
			if (!$parsedEnvNames)
				$parsedEnvNames = [$commonEnvDataKey];
			foreach ($parsedEnvNames as $parsedEnvName) {
				if (!array_key_exists($parsedEnvName, $allEnvsIniData))
					$allEnvsIniData[$parsedEnvName] = [];
				$allEnvsIniData[$parsedEnvName] = array_merge($allEnvsIniData[$parsedEnvName], $newValues);
			}
		}
		return $allEnvsIniData;
	}


	/**
	 * Process single level array with dotted keys into tree structure
	 * and complete object type switches about tree records
	 * to complete journal about final `\stdClass`es or `array`s types.
	 * @param array $iniData
	 * @return array
	 */
	protected function iniReadExpandLevelsAndReType (array & $iniData) {
		/** @var $this \MvcCore\Config */
		$result = [];
		$objectTypes = [];
		//$objectTypes[''] = [0, & $result];
		$oldIniScannerMode = $this->_iniScannerMode === 1;
		foreach ($iniData as $rawKey => $rawValue) {
			$current = & $result;
			// prepare keys to build levels and configure stdClass/array types
			$rawKeys = [];
			$lastRawKey = $rawKey;
			$lastDotPos = strrpos($rawKey, '.');
			if ($lastDotPos !== FALSE) {
				$rawKeys = explode('.', substr($rawKey, 0, $lastDotPos));
				$lastRawKey = substr($rawKey, $lastDotPos + 1);
			}
			// prepare levels structure and configure stdClass or array type change where necessary
			$levelKey = '';
			$prevLevelKey = '';
			foreach ($rawKeys as $key) {
				$prevLevelKey = $levelKey;
				$levelKey .= ($levelKey ? '.' : '') . $key;
				if (!isset($current[$key])) {
					$current[$key] = [];
					$objectTypes[$levelKey] = [1, & $current[$key]]; // object type switch -> object by default
					if (is_numeric($key) && isset($objectTypes[$prevLevelKey])) {
						$objectTypes[$prevLevelKey][0] = 0; // object type switch -> set array if it was object
					}
				}
				$current = & $current[$key];
			}
			// set up value into levels structure and configure type into array if necessary
			if ($oldIniScannerMode) {
				$typedValue = $this->getTypedValue($rawValue);
			} else {
				$typedValue = $rawValue;
			}
			if (isset($current[$lastRawKey])) {
				$current[$lastRawKey][] = $typedValue;
				$objectTypes[$levelKey ? $levelKey : $lastRawKey][0] = 0; // object type switch -> set array
			} else {
				if (!is_array($current)) {
					$current = [$current];
					$objectTypes[$levelKey] = [0, & $current]; // object type switch -> set array
				}
				$current[$lastRawKey] = $typedValue;
				if (is_numeric($lastRawKey)) $objectTypes[$levelKey][0] = 0; // object type switch -> set array
			}
		}
		return [$result, $objectTypes];
	}

	/**
	 * Retype raw INI value into `array` with retyped it's own values or
	 * retype raw INI value into `float`, `int` or `string`.
	 * @param string|array $rawValue
	 * @return array|float|int|string
	 */
	protected function getTypedValue ($rawValue) {
		/** @var $this \MvcCore\Config */
		if (gettype($rawValue) == "array") {
			foreach ($rawValue as $key => $value) {
				$rawValue[$key] = $this->getTypedValue($value);
			}
			return $rawValue; // array
		} else if (mb_strlen($rawValue) > 0) {
			if (is_numeric($rawValue)) {
				return $this->getTypedValueFloatOrInt($rawValue);
			} else {
				return $this->getTypedSpecialValueOrString($rawValue);
			}
		} else {
			return $this->getTypedSpecialValueOrString($rawValue);
		}
	}

	/**
	 * Retype raw INI value into `float` or `int`.
	 * @param string $rawValue
	 * @return float|int|string
	 */
	protected function getTypedValueFloatOrInt ($rawValue) {
		/** @var $this \MvcCore\Config */
		if (strpos($rawValue, '.') !== FALSE || strpos($rawValue, 'e') !== FALSE || strpos($rawValue, 'E') !== FALSE) {
			return floatval($rawValue); // float
		} else {
			// int or string if integer is too high (more then PHP max/min: 2147483647/-2147483647)
			$intVal = intval($rawValue);
			return (string) $intVal === $rawValue
				? $intVal
				: $rawValue;
		}
	}

	/**
	 * Retype raw INI value into `bool`, `NULL` or `string`.
	 * @param string $rawValue
	 * @return bool|NULL|string
	 */
	protected function getTypedSpecialValueOrString ($rawValue) {
		/** @var $this \MvcCore\Config */
		$lowerRawValue = strtolower($rawValue);
		if (isset(static::$specialValues[$lowerRawValue])) {
			return static::$specialValues[$lowerRawValue]; // bool or null
		} else {
			return trim($rawValue); // string
		}
	}
}
