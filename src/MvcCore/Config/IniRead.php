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

namespace MvcCore\Config;

/**
 * @mixin \MvcCore\Config
 */
trait IniRead {

	/**
	 * @inheritDocs
	 * @return bool
	 */
	public function Read () {
		if ($this->envData) return TRUE;
		/**
		 * INI scanner mode. For old PHP versions, lower than `5.6.1`
		 * is automatically set to `1`, for higher, where is possible to
		 * get INI data automatically type, is set to `2`.
		 * Possible values: `1 => INI_SCANNER_RAW, 2 => INI_SCANNER_TYPED`.
		 * @var int
		 */
		$iniScannerMode = \PHP_VERSION_ID < 50610 ? 1 : 2;
		clearstatcache(TRUE, $this->fullPath);
		$this->lastChanged = filemtime($this->fullPath);
		$rawIniData = parse_ini_file(
			$this->fullPath, TRUE, $iniScannerMode
		);
		if ($rawIniData === FALSE) return FALSE;
		$this->envData = [];
		$allEnvIniDataPlain = static::readAllEnvironmentsSections($rawIniData);
		foreach ($allEnvIniDataPlain as $envName => $envIniData) {
			list($data, $objectTypes) = static::readExpandLevelsAndReType(
				$envIniData, $iniScannerMode
			);
			foreach ($objectTypes as & $objectType)
				if ($objectType[0] && isset($objectType[1]))
					$objectType[1] = (object) $objectType[1];
			$this->envData[$envName] = $data;
		}
		
		return TRUE;
	}

	/**
	 * Explode all possible sections into environment ini data collections,
	 * keyed by environment name. Data, not targeted into any environment,
	 * explode into default ini data collection keyed under empty string.
	 * @param  array $rawIniData
	 * @return array
	 */
	protected static function & readAllEnvironmentsSections (array & $rawIniData) {
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
	 * @param  array $iniData
	 * @param  int   $iniScannerMode
	 * @return array
	 */
	protected static function readExpandLevelsAndReType (array & $iniData, $iniScannerMode) {
		$result = [];
		$objectTypes = [];
		//$objectTypes[''] = [0, & $result];
		$oldIniScannerMode = $iniScannerMode === 1;// 1 => INI_SCANNER_RAW
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
			$absoluteKey = '';
			$prevAbsoluteKey = '';
			foreach ($rawKeys as $key) {
				$prevAbsoluteKey = $absoluteKey;
				$absoluteKey .= ($absoluteKey ? '.' : '') . $key;
				if (!isset($current[$key])) {
					$keyIsNumeric = is_numeric($key);
					$current[$key] = [];
					// object type switch -> array by default:
					$objectTypes[$absoluteKey] = [0, & $current[$key]];
					if (isset($objectTypes[$prevAbsoluteKey])) {
						$objTypesRec = & $objectTypes[$prevAbsoluteKey];
						if (!$keyIsNumeric && !$objTypesRec[0])
							// object type switch -> not array anymore:
							$objTypesRec[0] = 1;
					}
				}
				$current = & $current[$key];
			}
			// set up value into levels structure and configure type into array if necessary
			if ($oldIniScannerMode) {
				$typedValue = static::readTypedValue($rawValue);
			} else {
				$typedValue = $rawValue;
			}
			if (isset($current[$lastRawKey])) {
				$current[$lastRawKey][] = $typedValue;
			} else {
				if (!is_array($current)) 
					$current = [$current];
				$current[$lastRawKey] = $typedValue;
			}
			if (!is_numeric($lastRawKey))
				$objectTypes[$absoluteKey][0] = 1;
		}
		return [$result, $objectTypes];
	}

	/**
	 * Retype raw INI value into `array` with retyped it's own values or
	 * retype raw INI value into `float`, `int` or `string`.
	 * @param  string|array $rawValue
	 * @return array|float|int|string
	 */
	protected static function readTypedValue ($rawValue) {
		if (gettype($rawValue) == "array") {
			foreach ($rawValue as $key => $value) {
				$rawValue[$key] = static::readTypedValue($value);
			}
			return $rawValue; // array
		} else if (mb_strlen($rawValue) > 0) {
			if (is_numeric($rawValue)) {
				return static::readTypedValueFloatOrInt($rawValue);
			} else {
				return static::readTypedSpecialValueOrString($rawValue);
			}
		} else {
			return static::readTypedSpecialValueOrString($rawValue);
		}
	}

	/**
	 * Retype raw INI value into `float` or `int`.
	 * @param  string $rawValue
	 * @return float|int|string
	 */
	protected static function readTypedValueFloatOrInt ($rawValue) {
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
	 * @param  string $rawValue
	 * @return bool|NULL|string
	 */
	protected static function readTypedSpecialValueOrString ($rawValue) {
		$lowerRawValue = strtolower($rawValue);
		if (isset(static::$specialValues[$lowerRawValue])) {
			return static::$specialValues[$lowerRawValue]; // bool or null
		} else {
			return trim($rawValue); // string
		}
	}
}
