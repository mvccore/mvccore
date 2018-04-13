<?php

/**
 * MvcCore
 *
 * This source file is subject to the BSD 3 License
 * For the full copyright and license information, please view
 * the LICENSE.md file that are distributed with this source code.
 *
 * @copyright	Copyright (c) 2016 Tom Flídr (https://github.com/mvccore/mvccore)
 * @license		https://mvccore.github.io/docs/mvccore/4.0.0/LICENCE.md
 */

namespace MvcCore;


include_once(__DIR__ . '/Application.php');
include_once(__DIR__ . '/Tool.php'); // because of static init
//include_once(__DIR__ . '/Interfaces/IConfig.php');

/**
 * Responsibility - reading config file(s), detecting environment in system config.
 * - Config file(s) reading:
 *   - Reading any `config.ini` file by relative path.
 *   - Parsing and typing ini data into `stdClass|array` by key types or typing
 *     ini values into `int|float|bool|string` for all other detected primitives.
 * - Environment management:
 *   - Simple environment name detection by comparing server and client ip.
 *   - Environment name detection by config records about computer name or ip.
 */
class Config implements Interfaces\IConfig
{
	/**
	 * System config relative path from app root.
	 * This value could be changed to any value at the very application start.
	 * @var string
	 */
	public static $SystemConfigPath = '/%appPath%/config.ini';

	/**
	 * Environment name. Usual values:
	 * - `"development"`
	 * - `"beta"`
	 * - `"alpha"`
	 * - `"production"`
	 * @var string
	 */
	protected static $environment = '';

	/**
	 * System config object placed by default in: `"/App/config.ini"`.
	 * @var \stdClass|array|boolean
	 */
	protected static $systemConfig = NULL;

	/**
	 * Ini file values to convert into booleans.
	 * @var mixed
	 */
	protected static $booleanValues = array(
		'yes'	=> TRUE,
		'no'	=> FALSE,
		'true'	=> TRUE,
		'false'	=> FALSE,
	);

	/**
	 * Temporary variable used when ini file is parsed and loaded
	 * to store complete result to return.
	 * @var array|\stdClass
	 */
	protected $result = array();

	/**
	 * Temporary variable used when ini file is parsed and loaded,
	 * to store information about final retyping. Keys are addresses
	 * into result level to be retyped or not, values are arrays.
	 * First index in values is boolean to define if result level will
	 * be retyped into `\stdClass` or not, second index in values is reference
	 * link to object retyped at the end or not.
	 * @var array
	 */
	protected $objectTypes = array();

	/**
	 * Static initialization.
	 * - Called when file is loaded into memory.
	 * - First environment value setup - by server and client ip address.
	 * @return void
	 */
	public static function StaticInit () {
		if (!static::$environment) {
			$toolClass = \MvcCore\Application::GetInstance()->GetToolClass();
			$serverAddress = $toolClass::GetServerIp();
			$remoteAddress = $toolClass::GetClientIp();
			if ($serverAddress == $remoteAddress) {
				static::$environment = static::ENVIRONMENT_DEVELOPMENT;
			} else {
				static::$environment = static::ENVIRONMENT_PRODUCTION;
			}
		}
	}

	/**
	 * Return `TRUE` if environment is `"development"`.
	 * @return bool
	 */
	public static function IsDevelopment () {
		return static::$environment == static::ENVIRONMENT_DEVELOPMENT;
	}

	/**
	 * Return `TRUE` if environment is `"beta"`.
	 * @return bool
	 */
	public static function IsBeta () {
		return static::$environment == static::ENVIRONMENT_BETA;
	}

	/**
	 * Return `TRUE` if environment is `"alpha"`.
	 * @return bool
	 */
	public static function IsAlpha () {
		return static::$environment == static::ENVIRONMENT_ALPHA;
	}

	/**
	 * Return `TRUE` if environment is `"production"`.
	 * @return bool
	 */
	public static function IsProduction () {
		return static::$environment == static::ENVIRONMENT_PRODUCTION;
	}

	/**
	 * Get environment name as string,
	 * defined by constants: `\MvcCore\Interfaces\IConfig::ENVIRONMENT_<environment>`.
	 * @return string
	 */
	public static function GetEnvironment () {
		return static::$environment;
	}

	/**
	 * Set environment name as string,
	 * defined by constants: `\MvcCore\Interfaces\IConfig::ENVIRONMENT_<environment>`.
	 * @param string $environment
	 * @return string
	 */
	public static function SetEnvironment ($environment = \MvcCore\Interfaces\IConfig::ENVIRONMENT_PRODUCTION) {
		static::$environment = $environment;
	}

	/**
	 * Get system config ini file as `stdClass`es and `array`s,
	 * placed by default in: `"/App/config.ini"`.
	 * @return \stdClass|array|boolean
	 */
	public static function & GetSystem () {
		if (!static::$systemConfig) {
			$app = \MvcCore\Application::GetInstance();
			$systemConfigClass = $app->GetConfigClass();
			$instance = new $systemConfigClass;
			static::$systemConfig = $instance->Load(str_replace(
				'%appPath%',
				$app->GetAppDir(),
				$systemConfigClass::$SystemConfigPath
			), TRUE);
		}
		return static::$systemConfig;
	}

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
	 * @return array|boolean
	 */
	public function & Load ($configPath = '', $systemConfig = FALSE) {
		$cfgFullPath = \MvcCore\Application::GetInstance()->GetRequest()->GetAppRoot() . $configPath;
		if (!file_exists($cfgFullPath)) return FALSE;
		$rawIniData = parse_ini_file($cfgFullPath, TRUE);
		$environment = $systemConfig
			? $this->detectEnvironmentBySystemConfig($rawIniData)
			: static::$environment;
		if ($rawIniData === FALSE) return FALSE;
		$iniData = $this->prepareIniDataToParse($rawIniData, $environment);
		$this->processIniData($iniData);
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
	protected function & prepareIniDataToParse (array & $rawIniData, $environment) {
		$iniData = array();
		foreach ($rawIniData as $keyOrSectionName => $valueOrSectionValues) {
			if (gettype($valueOrSectionValues) == 'array') {
				if (strpos($keyOrSectionName, '>') !== FALSE) {
					list($envNameLocal, $keyOrSectionName) = explode('>', str_replace(' ', '', $keyOrSectionName));
					if ($envNameLocal !== $environment) continue;
				}
				$sectionValues = array();
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
	 * @return string
	 */
	protected function detectEnvironmentBySystemConfig (array & $rawIni = array()) {
		$environment = '';
		if (isset($rawIni['environments'])) {
			$environments = & $rawIni['environments'];
			$serverAddress = ','.static::getServerIp().',';
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
	protected function processIniData (array & $iniData) {
		$this->objectTypes[''] = array(1, & $this->result);
		foreach ($iniData as $rawKey => $rawValue) {
			$current = & $this->result;
			// prepare keys to build levels and configure stdClass/array types
			$rawKeys = array();
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
					$current[$key] = array();
					$this->objectTypes[$levelKey] = array(1, & $current[$key]); // object type switch -> object by default
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
				if (gettype($current) != 'array') {
					$current = array($current);
					$this->objectTypes[$levelKey] = array(0, & $current); // object type switch -> set array
				}
				$current[$lastRawKey] = $typedValue;
				if ($this->isKeyNumeric($lastRawKey)) $this->objectTypes[$levelKey][0] = 0; // object type switch -> set array
			}
		}
	}

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
\MvcCore\Config::StaticInit();
