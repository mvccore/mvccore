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
	 * @param bool $systemConfig
	 * @return bool
	 */
	public function Read ($fullPath, $systemConfig = FALSE) {
		if ($this->data) return $this->data;
		$this->fullPath = $fullPath;
		$this->system = $systemConfig;
		if (!$this->_iniScannerMode)
			// 1 => INI_SCANNER_RAW, 2 => INI_SCANNER_TYPED
			$this->_iniScannerMode = \PHP_VERSION_ID < 50610 ? 1 : 2;
		clearstatcache(TRUE, $fullPath);
		$this->lastChanged = filemtime($fullPath);
		$rawIniData = parse_ini_file(
			$this->fullPath, TRUE, $this->_iniScannerMode
		);
		if ($rawIniData === FALSE) return FALSE;
		$this->data = [];
		$envsSectionName = static::$environmentsSectionName;
		$environmentsData = NULL;
		if ($this->system && isset($rawIniData[$envsSectionName])) {
			$rawIniEnvSectionData = array_merge([], $rawIniData[$envsSectionName]);
			$this->iniReadExpandLevelsAndReType($rawIniEnvSectionData);
			$environmentsData = array_merge([], $this->data);
			$environment = static::EnvironmentDetectBySystemConfig($environmentsData);//production
			foreach ($this->objectTypes as & $objectType)
				if ($objectType[0]) $objectType[1] = (object) $objectType[1];
			unset($rawIniData[$envsSectionName]);
			$this->data = [];
			$this->objectTypes = [];
		} else {
			$environment = static::$environment;
		}
		$iniData = $this->iniReadFilterEnvironmentSections($rawIniData, $environment);
		$this->iniReadExpandLevelsAndReType($iniData);
		if ($environmentsData !== NULL)
			$this->data[$envsSectionName] = (object) $environmentsData;
		foreach ($this->objectTypes as & $objectType)
			if ($objectType[0]) $objectType[1] = (object) $objectType[1];
		unset($this->objectTypes);
		return TRUE;
	}

	/**
	 * Align all raw INI data to single level array,
	 * filtered for only current environment data items.
	 * @param array  $rawIniData
	 * @param string $environment
	 * @return array
	 */
	protected function & iniReadFilterEnvironmentSections (array & $rawIniData, $environment) {
		$iniData = [];
		foreach ($rawIniData as $keyOrSectionName => $valueOrSectionValues) {
			if (is_array($valueOrSectionValues)) {
				if (strpos($keyOrSectionName, '>') !== FALSE) {
					list($envNamesStrLocal, $keyOrSectionName) = explode('>', str_replace(' ', '', $keyOrSectionName));
					if (!in_array($environment, explode(',', $envNamesStrLocal))) continue;
				}
				$sectionValues = [];
				foreach ($valueOrSectionValues as $key => $value) $sectionValues[$keyOrSectionName.'.'.$key] = $value;
				$iniData = array_merge($iniData, $sectionValues);
			} else {
				$iniData[$keyOrSectionName] = $valueOrSectionValues;
			}
		}
		return $iniData;
	}

	/**
	 * Process single level array with dotted keys into tree structure
	 * and complete object type switches about tree records
	 * to complete journal about final `\stdClass`es or `array`s types.
	 * @param array $iniData
	 * @return void
	 */
	protected function iniReadExpandLevelsAndReType (array & $iniData) {
		//$this->objectTypes[''] = [0, & $this->data];
		$oldIniScannerMode = $this->_iniScannerMode === 1;
		foreach ($iniData as $rawKey => $rawValue) {
			$current = & $this->data;
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
					$this->objectTypes[$levelKey] = [1, & $current[$key]]; // object type switch -> object by default
					if (is_numeric($key) && isset($this->objectTypes[$prevLevelKey])) {
						$this->objectTypes[$prevLevelKey][0] = 0; // object type switch -> set array if it was object
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
				$this->objectTypes[$levelKey ? $levelKey : $lastRawKey][0] = 0; // object type switch -> set array
			} else {
				if (!is_array($current)) {
					$current = [$current];
					$this->objectTypes[$levelKey] = [0, & $current]; // object type switch -> set array
				}
				$current[$lastRawKey] = $typedValue;
				if (is_numeric($lastRawKey)) $this->objectTypes[$levelKey][0] = 0; // object type switch -> set array
			}
		}
	}

	/**
	 * Retype raw INI value into `array` with retyped it's own values or
	 * retype raw INI value into `float`, `int` or `string`.
	 * @param string|array $rawValue
	 * @return array|float|int|string
	 */
	protected function getTypedValue ($rawValue) {
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
		$lowerRawValue = strtolower($rawValue);
		if (isset(static::$specialValues[$lowerRawValue])) {
			return static::$specialValues[$lowerRawValue]; // bool or null
		} else {
			return trim($rawValue); // string
		}
	}
}
