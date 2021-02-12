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

namespace MvcCore\Model;

trait Config {

	/**
	 * @inheritDocs
	 * @return \stdClass
	 */
	public static function GetSysConfigProperties () {
		return (object) static::$sysConfigProperties;
	}

	/**
	 * @inheritDocs
	 * @return \stdClass[]
	 */
	public static function & GetConfigs () {
		if (self::$configs === NULL) static::loadConfigs(TRUE);
		return self::$configs;
	}

	/**
	 * @inheritDocs
	 * @param \stdClass[]|array[] $configs Configuration array with `\stdClass` objects or arrays with configuration data.
	 * @param string|int $defaultConnectionName
	 * @return bool
	 */
	public static function SetConfigs (array $configs = [], $defaultConnectionName = NULL) {
		self::$configs = [];
		foreach ($configs as $key => $value) self::$configs[$key] = (object) $value;
		self::$configs = & $configs;
		if ($defaultConnectionName !== NULL)
			self::$defaultConnectionName = $defaultConnectionName;
		return TRUE;
	}

	/**
	 * @inheritDocs
	 * @param int|string|NULL $connectionName
	 * @return \stdClass
	 */
	public static function & GetConfig ($connectionName = NULL) {
		if (self::$configs === NULL) static::loadConfigs(TRUE);
		if ($connectionName === NULL && isset(static::$connectionName)) $connectionName = static::$connectionName;
		if ($connectionName === NULL && isset(self::$connectionName)) $connectionName = self::$connectionName;
		if ($connectionName === NULL) $connectionName = self::$defaultConnectionName;
		if ($connectionName === NULL) {
			$result = NULL;
			return $result;
		}
		return self::$configs[$connectionName];
	}

	/**
	 * @inheritDocs
	 * @param \stdClass[]|array[] $config
	 * @param string|int|NULL $connectionName
	 * @return string|int
	 */
	public static function SetConfig (array $config = [], $connectionName = NULL) {
		if (self::$configs === NULL) static::loadConfigs(FALSE);
		$sysCfgProps = (object) static::$sysConfigProperties;
		if ($connectionName === NULL) {
			if (isset($config[$sysCfgProps->name])) {
				$connectionName = $config[$sysCfgProps->name];
			} else if (isset($config[$sysCfgProps->index])) {
				$connectionName = $config[$sysCfgProps->index];
			}
		}
		if ($connectionName === NULL) {
			$configNumericKeys = array_filter(array_keys(self::$configs), 'is_numeric');
			if ($configNumericKeys) {
				sort($configNumericKeys);
				$connectionName = $configNumericKeys[count($configNumericKeys) - 1] + 1; // last + 1
			} else {
				$connectionName = 0;
			}
		}
		self::$configs[$connectionName] = (object) $config;
		return $connectionName;
	}

	/**
	 * Initializes configuration data from system config if any
	 * into local `self::$configs` array, keyed by connection name or index.
	 * @param  bool $throwExceptionIfNoSysConfig 
	 *              If `TRUE`, there is thrown an `\Exception`
	 *              if there is no system config, if `FALSE`,
	 *              nothing happends. `TRUE` by default.
	 * @param  bool $strict
	 *              If `TRUE`, there is initialized static property 
	 *              `self::$defaultConnectionName` only by config record `db.defaultName`.
	 *              If `FALSE`, there is not initialized any default connection property.
	 * @throws \Exception
	 * @return void
	 */
	protected static function loadConfigs ($throwExceptionIfNoSysConfig = TRUE, $strict = TRUE) {
		$configClass = \MvcCore\Application::GetInstance()->GetConfigClass();
		$systemCfg = $configClass::GetSystem();
		if ($systemCfg === NULL) {
			if ($throwExceptionIfNoSysConfig) 
				throw new \Exception(
					"[".get_class()."] System config not found in `"
					. $configClass::GetSystemConfigPath() . "`."
				);
			return;
		}
		$sysCfgProps = (object) static::$sysConfigProperties;
		$dbSectionName = $sysCfgProps->sectionName;
		if (!isset($systemCfg->{$dbSectionName}) && $throwExceptionIfNoSysConfig)
			throw new \Exception(
				"[".get_class()."] No [" . $dbSectionName . "] section and no records matched "
				."`" . $dbSectionName . ".*` found in system config in: `" . $configClass::GetSystemConfigPath() . "`."
			);
		$systemCfgDb = (object) $systemCfg->{$dbSectionName};
		$configs = [];
		$defaultConnectionName = NULL;
		$defaultConnectionClass = NULL;
		$configsConnectionsNames = [];
		// `db.defaultName` - default connection index for models,
		// where is no connection name/index defined inside class.
		if (isset($systemCfgDb->{$sysCfgProps->defaultName}))
			$defaultConnectionName = $systemCfgDb->{$sysCfgProps->defaultName};
		// `db.defaultClass` - default connection class for all models extended from `\PDO`.
		if (isset($systemCfgDb->{$sysCfgProps->defaultClass}))
			$defaultConnectionClass = $systemCfgDb->{$sysCfgProps->defaultClass};
		if (isset($systemCfgDb->driver)) {
			$configs[0] = $systemCfgDb;
			$configsConnectionsNames[] = '0';
		} else {
			foreach ($systemCfgDb as $key => $value) {
				if (is_scalar($value)) {
					$configs[$key] = $value;
				} else {
					$configs[$key] = (object) $value;
					$configsConnectionsNames[] = (string) $key;
				}
			}
		}
		$configsConnectionsNamesCount = count($configsConnectionsNames);
		if ($defaultConnectionName === NULL) {
			if (
				($configsConnectionsNamesCount === 1) || 
				(!$strict && $configsConnectionsNamesCount > 0)
			) {
				$defaultConnectionName = $configsConnectionsNames[0];
			}
		}
		if ($defaultConnectionName !== NULL && !isset($configs[$defaultConnectionName]))
			throw new \Exception(
				"[".get_class()."] No default connection name '{$defaultConnectionName}'"
				." found in 'db.*' section in system config.ini."
			);
		if ($defaultConnectionName !== NULL)
			self::$defaultConnectionName = $defaultConnectionName;
		if ($defaultConnectionClass !== NULL)
			self::$defaultConnectionClass = $defaultConnectionClass;
		self::$configs = & $configs;
	}
}
