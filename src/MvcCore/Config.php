<?php

/**
 * MvcCore
 *
 * This source file is subject to the BSD 3 License
 * For the full copyright and license information, please view 
 * the LICENSE.md file that are distributed with this source code.
 *
 * @copyright	Copyright (c) 2016 Tom Flídr (https://github.com/mvccore/mvccore)
 * @license		https://mvccore.github.io/docs/mvccore/3.0.0/LICENCE.md
 */

require_once(__DIR__.'/../MvcCore.php');

class MvcCore_Config
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
	 * @var stdClass|array|boolean
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
	 * @var array|stdClass
	 */
	protected $result = array();

	/**
	 * Temporary variable used when ini file is parsed and loaded
	 * to store currently completed values
	 * @var array|stdClass
	 */
	protected $current = array();

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
	 * Get environment name as string, defined by constants: MvcCore_Config::ENVIRONMENT_<environment>
	 * @return string
	 */
	public static function GetEnvironment () {
		return static::$environment;
	}
	
	/**
	 * Set environment name as string, defined by constants: MvcCore_Config::ENVIRONMENT_<environment>
	 * @return void
	 */
	public static function SetEnvironment ($environment = self::ENVIRONMENT_PRODUCTION) {
		static::$environment = $environment;
	}

	/**
	 * Get system config ini file as stdClasses and arrays, palced in /App/config.ini
	 * @return stdClass|array|boolean
	 */
	public static function & GetSystem () {
		if (!static::$systemConfig) {
			$systemConfigClass = MvcCore::GetInstance()->GetConfigClass();
			$instance = new $systemConfigClass;
			static::$systemConfig = $instance->Load(
				str_replace('%appPath%', MvcCore::GetInstance()->GetAppDir(), $systemConfigClass::$SystemConfigPath)
			);
		}
		return static::$systemConfig;
	}

	/**
	 * Get server IP from $_SERVER
	 * @return string
	 */
	protected static function getServerIp () {
		return isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : $_SERVER['LOCAL_ADDR'] ;
	}

	/**
	 * Get client IP from $_SERVER
	 * @return string
	 */
	protected static function getClientIp () {
		return isset($_SERVER['HTTP_X_CLIENT_IP']) ? $_SERVER['HTTP_X_CLIENT_IP'] : $_SERVER['REMOTE_ADDR'];
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
		$cfgFullPath = MvcCore::GetInstance()->GetRequest()->AppRoot . $configPath;
		if (!file_exists($cfgFullPath)) return FALSE;
		$rawIni = parse_ini_file($cfgFullPath, TRUE);
		$environment = $this->detectEnvironmentBySystemConfig($rawIni);
		if ($rawIni === FALSE) return FALSE;
		$noSectionData = array();
		foreach ($rawIni as $sectionName => $sectionContent) {
			if (gettype($sectionContent) == 'array') {
				if (strpos($sectionName, '>') !== FALSE) {
					list($envNameLocal, $sectionName) = explode('>', str_replace(' ', '', $sectionName));
					if ($envNameLocal !== $environment) continue;
				}
				$this->processSection($sectionName, $sectionContent);
			} else {
				$noSectionData[$sectionName] = $sectionContent;
			}
		}
		if ($noSectionData) {
			$this->processSection('', $noSectionData);
		} else {
			$this->objectTypes[''] = array(TRUE, & $this->result);
		}
		foreach ($this->objectTypes as & $objectType) {
			if ($objectType[0]) $objectType[1] = (object) $objectType[1];
		}
		unset($this->current, $this->objectTypes);
		return $this->result;
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
		static::SetEnvironment($environment);
		return $environment;
	}

	/**
	 * Process ini section
	 * - retype all raw string values into array, float, int or boolean
	 * - store boolean value about possibility to retype whole values level into stdClass later
	 * @param string $sectionName
	 * @param array  $sectionContent
	 * @return void
	 */
	protected function processSection ($sectionName = '', array $sectionContent) {
		$sectionNameKeys = $sectionName ? explode('.', $sectionName) : array();
		$currentKeysAddress = NULL;
		$lastKeysAddress = NULL;
		foreach ($sectionContent as $rawKey => $rawValue) {
			$rawKeys = explode('.', $rawKey);
			$lastRawKey = array_pop($rawKeys);
			$keys = array_values($sectionNameKeys);
			array_walk($rawKeys, function ($rawKey) use (& $keys) {
				$keys[] = $rawKey;
			});
			$currentKeysAddress = implode('.', $keys);
			if ($currentKeysAddress !== $lastKeysAddress) {
				$this->moveCurrentLevel($keys);
				$this->objectTypes[$currentKeysAddress] = array(TRUE, & $this->current);
				$lastKeysAddress = $currentKeysAddress;
			}
			if ($this->isKeyNumeric($lastRawKey)) {
				$this->objectTypes[$currentKeysAddress][0] = FALSE;
			}
			$this->current[$lastRawKey] = $this->getTypedValue($rawValue);
		}
	}

	/**
	 * Move $this->current object into currently completed memory space
	 * @param array $keys 
	 * @return void
	 */
	protected function moveCurrentLevel (array & $keys) {
		$this->current = & $this->result;
		$parentAddress = '';
		$currentAddress = '';
		for ($i = 0, $l = count($keys); $i < $l; $i += 1) {
			$key = $keys[$i];
			$currentAddress .= (($i > 0) ? '.' : '') . $key;
			$keyIsNumeric = $this->isKeyNumeric($key);
			if (!isset($this->current[$key])) {
				$this->current[$key] = array();
				$this->objectTypes[$currentAddress] = array(!$keyIsNumeric, & $this->current[$key]);
			}
			if ($keyIsNumeric) $this->objectTypes[$parentAddress][0] = FALSE;
			$this->current = & $this->current[$key];
			$parentAddress = $currentAddress;
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
MvcCore_Config::StaticInit();