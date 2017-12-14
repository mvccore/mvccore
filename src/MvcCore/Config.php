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

require_once(__DIR__.'/../MvcCore.php');

/**
 * Core configuration:
 * - config files reading:
 *   - reading config.ini file by relative path
 *   - parsing and typing ini data into stdClass/array by key types
 *   - typing ini values into integers, floats, booleans or strings
 * - environment management:
 *   - simple environment name detection by comparing server and client ip
 *   - environment name detection by config records about computer name or ip
 */
class Config
{
	const ENVIRONMENT_DEVELOPMENT = 'development';
	const ENVIRONMENT_BETA = 'beta';
	const ENVIRONMENT_ALPHA = 'alpha';
	const ENVIRONMENT_PRODUCTION = 'production';

	/**
	 * System config relative path from app root
	 * @var string
	 */
	public static $SystemConfigPath = '/%appPath%/config.ini';

	/**
	 * Environment name - development, beta, alpha, production
	 * @var string
	 */
	protected static $environment = '';

	/**
	 * System config object placed in /App/config.ini
	 * @var \stdClass|array|boolean
	 */
	protected static $systemConfig = NULL;

	/**
	 * Ini file values to convert into booleans
	 * @var mixed
	 */
	protected static $booleanValues = array('yes' => TRUE, 'no' => FALSE, 'true' => TRUE, 'false' => FALSE,);

	/**
	 * Temporary variable used when ini file is parsed and loaded
	 * to store complete result to return
	 * @var array|\stdClass
	 */
	protected $result = array();

	/**
	 * Temporary variable used when ini file is parsed and loaded,
	 * to store information about final retyping. Keys are addresses
	 * into result level to by retyped or not, values are arrays.
	 * First index in values is boolean to define if result level will
	 * be retyped into stdClass or not, second index in values is reference
	 * link to object retyped at the end or not.
	 * @var array
	 */
	protected $objectTypes = array();

	/**
	 * Static initialization - called when file is loaded into memory
	 * @return void
	 */
	public static function StaticInit () {
		if (!static::$environment) {
			$serverAddress = static::getServerIp();
			$remoteAddress = static::getClientIp();
			if ($serverAddress == $remoteAddress) {
				static::$environment = static::ENVIRONMENT_DEVELOPMENT;
			} else {
				static::$environment = static::ENVIRONMENT_PRODUCTION;
			}
		}
	}

	/**
	 * Return true if environment is 'development'
	 * @return bool
	 */
	public static function IsDevelopment () {
		return static::$environment == static::ENVIRONMENT_DEVELOPMENT;
	}

	/**
	 * Return true if environment is 'beta'
	 * @return bool
	 */
	public static function IsBeta () {
		return static::$environment == static::ENVIRONMENT_BETA;
	}

	/**
	 * Return true if environment is 'alpha'
	 * @return bool
	 */
	public static function IsAlpha () {
		return static::$environment == static::ENVIRONMENT_ALPHA;
	}

	/**
	 * Return true if environment is 'production'
	 * @return bool
	 */
	public static function IsProduction () {
		return static::$environment == static::ENVIRONMENT_PRODUCTION;
	}

	/**
	 * Get environment name as string, defined by constants: \MvcCore\Config::ENVIRONMENT_<environment>
	 * @return string
	 */
	public static function GetEnvironment () {
		return static::$environment;
	}

	/**
	 * Set environment name as string, defined by constants: \MvcCore\Config::ENVIRONMENT_<environment>
	 * @return void
	 */
	public static function SetEnvironment ($environment = self::ENVIRONMENT_PRODUCTION) {
		static::$environment = $environment;
	}

	/**
	 * Get system config ini file as stdClasses and arrays, palced in /App/config.ini
	 * @return \stdClass|array|boolean
	 */
	public static function & GetSystem () {
		if (!static::$systemConfig) {
			$systemConfigClass = \MvcCore::GetInstance()->GetConfigClass();
			$instance = new $systemConfigClass;
			static::$systemConfig = $instance->Load(
				str_replace('%appPath%', \MvcCore::GetInstance()->GetAppDir(), $systemConfigClass::$SystemConfigPath)
			);
		}
		return static::$systemConfig;
	}

	/**
	 * Get server IP from $_SERVER
	 * @return string
	 */
	protected static function getServerIp () {
		return isset($_SERVER['SERVER_ADDR'])
			? $_SERVER['SERVER_ADDR']
			: isset($_SERVER['LOCAL_ADDR'])
				? $_SERVER['LOCAL_ADDR']
				: '';
	}

	/**
	 * Get client IP from $_SERVER
	 * @return string
	 */
	protected static function getClientIp () {
		return isset($_SERVER['HTTP_X_CLIENT_IP'])
			? $_SERVER['HTTP_X_CLIENT_IP']
			: isset($_SERVER['REMOTE_ADDR'])
				? $_SERVER['REMOTE_ADDR']
				: '';
	}

	/**
	 * Load ini file and return system configuration
	 * - load only sections for current environment name
	 * - retype all raw string values into array, float, int or boolean
	 * - where is possible - retype whole values level into stdClass, if there are no numeric keys
	 * @param string  $filename
	 * @param string  $environment
	 * @return array|boolean
	 */
	public function & Load ($configPath = '') {
		$cfgFullPath = \MvcCore::GetInstance()->GetRequest()->AppRoot . $configPath;
		if (!file_exists($cfgFullPath)) return FALSE;
		$rawIniData = parse_ini_file($cfgFullPath, TRUE);
		$environment = $this->detectEnvironmentBySystemConfig($rawIniData);
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
	 * Aline all raw ini data to single level array,
	 * filtered for only current environment data items.
	 * @param array $rawIniData
	 * @param string $environment
	 * @return array
	 */
	protected function & prepareIniDataToParse (& $rawIniData, $environment) {
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
	 * Detect environment name to load proper config sections
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
	 * and complete object type switches about tree records to set final stdClasses or arrays
	 * @param array $iniData
	 * @return void
	 */
	protected function processIniData (& $iniData) {
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
	 * Return if $rawKey is numeric
	 * @param string $rawKey
	 * @return bool
	 */
	protected function isKeyNumeric ($rawKey) {
		$numericRawKey = preg_replace("#[^0-9\-]#", '', $rawKey);
		return $numericRawKey == $rawKey;
	}

	/**
	 * Retype raw ini value into array wth retyped it's own values or to float, int or string
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
	 * Retype raw ini value into float ip or int
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
	 * Retype raw ini value into bool or string
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