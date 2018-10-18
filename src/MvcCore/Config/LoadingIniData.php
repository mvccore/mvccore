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

trait LoadingIniData
{
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
	protected function & load ($configPath = '', $systemConfig = FALSE) {
		$cfgFullPath = \MvcCore\Application::GetInstance()->GetRequest()->GetAppRoot() . $configPath;
		if (!file_exists($cfgFullPath)) return $this->result;
		$rawIniData = parse_ini_file($cfgFullPath, TRUE);
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
					if ($this->isKeyNumeric($key) && isset($this->objectTypes[$prevLevelKey])) {
						$this->objectTypes[$prevLevelKey][0] = 0; // object type switch -> set array of it was object
					}
				}
				$current = & $current[$key];
			}
			// set up value into levels structure and confgure type into array if necessary
			$typedValue = $this->getTypedValue($rawValue);
			if (isset($current[$lastRawKey])) {
				$current[$lastRawKey][] = $typedValue;
				$this->objectTypes[$levelKey ? $levelKey : $lastRawKey][0] = 0; // object type switch -> set array
			} else {
				if (!is_array($current)) {
					$current = [$current];
					$this->objectTypes[$levelKey] = [0, & $current]; // object type switch -> set array
				}
				$current[$lastRawKey] = $typedValue;
				if ($this->isKeyNumeric($lastRawKey)) $this->objectTypes[$levelKey][0] = 0; // object type switch -> set array
			}
		}
	}
}
