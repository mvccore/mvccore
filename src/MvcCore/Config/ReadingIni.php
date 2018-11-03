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

trait ReadingIni
{
	/**
	 * INI scanner mode. For old PHP versions, lower than `5.6.1`
	 * is automatically set to `1`, for higher, where is possible to 
	 * get INI data automatically type, is set to `2`.
	 * @var int
	 */
	protected $iniScannerMode = 0;

	/**
	 * INI special values to type into `bool` or `NULL`.
	 * @var array
	 */
	protected static $specialValues = [
		'true'	=> TRUE,
		'on'	=> TRUE,
		'yes'	=> TRUE,
		'false'	=> FALSE,
		'off'	=> FALSE,
		'no'	=> FALSE,
		'none'	=> FALSE,
		'null'	=> NULL,
	];

	/**
	 * Load ini file and return parsed configuration or `FALSE` in failure.
	 * - Second environment value setup:
	 *   - Only if `$systemConfig` param is defined as `TRUE`.
	 *   - By defined IPs or computer names in ini `[environments]` section.
	 * - Load only sections for current environment name.
	 * - Retype all `raw string` values into `array`, `float`, `int` or `boolean` types.
	 * - Retype whole values level into `\stdClass`, if there are no numeric keys.
	 * @param string $configPath
	 * @param bool   $systemConfig
	 * @return array|bool
	 */
	protected function & read ($configPath = '', $systemConfig = FALSE) {
		$cfgFullPath = \MvcCore\Application::GetInstance()->GetRequest()->GetAppRoot() . $configPath;
		if (!file_exists($cfgFullPath)) return $this->result;
		if (!$this->iniScannerMode) 
			// 1 => INI_SCANNER_RAW, 2 => INI_SCANNER_TYPED
			$this->iniScannerMode = version_compare(PHP_VERSION, '5.6.1', '<') ? 1 : 2;
		$rawIniData = parse_ini_file(
			$cfgFullPath, TRUE, $this->iniScannerMode
		);
		if ($rawIniData === FALSE) return $this->result;
		$this->result = [];
		$environment = $systemConfig
			? $this->initDataDetectEnvironmentBySystemConfig($rawIniData)
			: static::$environment;
		$iniData = $this->iniPrepareToParse($rawIniData, $environment);
		$this->iniDataProcess($iniData);
		foreach ($this->objectTypes as & $objectType) {
			if ($objectType[0]) $objectType[1] = (object) $objectType[1];
		}
		unset($this->objectTypes);
		return $this->result;
	}

	/**
	 * Align all raw ini data to single level array,
	 * filtered for only current environment data items.
	 * @param array  $rawIniData
	 * @param string $environment
	 * @return array
	 */
	protected function & iniPrepareToParse (array & $rawIniData, $environment) {
		$iniData = [];
		foreach ($rawIniData as $keyOrSectionName => $valueOrSectionValues) {
			if (is_array($valueOrSectionValues)) {
				if (strpos($keyOrSectionName, '>') !== FALSE) {
					list($envNameLocal, $keyOrSectionName) = explode('>', str_replace(' ', '', $keyOrSectionName));
					if ($envNameLocal !== $environment) continue;
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
	 * Detect environment name in system config
	 * to load proper config sections later.
	 * @param array $rawIni
	 * @return string|NULL
	 */
	protected function initDataDetectEnvironmentBySystemConfig (array & $rawIni = []) {
		$environment = NULL;
		if (isset($rawIni['environments'])) {
			$environments = & $rawIni['environments'];
			$serverAddress = ','.\MvcCore\Application::GetInstance()->GetRequest()->GetServerIp().',';
			$serverComputerName = ','.gethostname().',';
			foreach ($environments as $environmentName => $environmentComputerNamesOrIps) {
				$environmentComputerNamesOrIps = ','.$environmentComputerNamesOrIps.',';
				if (
					strpos($environmentComputerNamesOrIps, $serverAddress) !== FALSE ||
					strpos($environmentComputerNamesOrIps, $serverComputerName) !== FALSE
				) {
					$environment = $environmentName;
					break;
				}
			}
		}
		if ($environment && !static::$environment) static::SetEnvironment($environment);
		return static::$environment;
	}

	/**
	 * Process single level array with dotted keys into tree structure
	 * and complete object type switches about tree records
	 * to set final `\stdClass`es or `array`s.
	 * @param array $iniData
	 * @return void
	 */
	protected function iniDataProcess (array & $iniData) {
		$this->objectTypes[''] = [1, & $this->result];
		$oldIniScannerMode = $this->iniScannerMode === 1;
		foreach ($iniData as $rawKey => $rawValue) {
			$current = & $this->result;
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
		} else if (mb_strlen($rawValue) > 0) {
			if (is_numeric($rawValue)) {
				return $this->getTypedValueFloatIpOrInt($rawValue);
			} else {
				return $this->getTypedSpecialValueOrString($rawValue);
			}
		} else {
			return $this->getTypedSpecialValueOrString($rawValue);
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
		} else if (strpos($rawValue, 'e') !== FALSE || strpos($rawValue, 'E') !== FALSE) {
			return floatval($rawValue); // float
		} else {
			$intVal = intval($rawValue); // int or string if integer is too high (more then PHP max/min: 2147483647/-2147483647)
			return (string) $intVal === $rawValue 
				? $intVal 
				: $rawValue;
		}
	}

	/**
	 * Retype raw ini value into `bool`, `NULL` or `string`.
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
