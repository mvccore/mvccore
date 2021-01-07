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

namespace MvcCore\Model;

trait Connection {
	
	/**
	 * Returns `\PDO` database connection by connection name/index,
	 * usually by system config values (cached by local store)
	 * or create new connection if no connection cached.
	 * @param string|int|array|\stdClass|NULL $connectionNameOrConfig
	 * @param bool $strict	If `TRUE` and no connection under given name or given
	 *						index found, exception is thrown. `TRUE` by default.
	 *						If `FALSE`, there could be returned connection by
	 *						first available configuration.
	 * @throws \InvalidArgumentException
	 * @return \PDO
	 */
	public static function GetConnection ($connectionNameOrConfig = NULL, $strict = TRUE) {
		if (is_array($connectionNameOrConfig) || $connectionNameOrConfig instanceof \stdClass) {
			// if first argument is database connection configuration - set it up and return new connection name
			if (self::$configs === NULL) static::loadConfigs(FALSE, $strict);
			$connectionName = static::SetConfig((array) $connectionNameOrConfig);
		} else {
			// if no connection index specified, try to get from class or from base model
			if (self::$configs === NULL) static::loadConfigs(TRUE, TRUE);
			$connectionName = $connectionNameOrConfig;
			if ($connectionName === NULL && isset(static::$connectionName)) 
				$connectionName = static::$connectionName;
			if ($connectionName === NULL && isset(self::$connectionName)) 
				$connectionName = self::$connectionName;
			if ($connectionName === NULL) 
				$connectionName = self::$defaultConnectionName;
		}
		if ($connectionName === NULL) throw new \InvalidArgumentException(
			"[".get_called_class()."] No connection name or connection config specified."
		);
		// if no connection exists under connection name key - connect to database
		if (!isset(static::$connections[$connectionName])) {
			// get system config 'db' data
			// and get predefined constructor arguments by driver value from config
			$cfg = static::GetConfig($connectionName);
			$cfgIsNull = $cfg === NULL;
			if ($strict && $cfgIsNull) throw new \InvalidArgumentException(
				"No connection found under given name/index: `{$connectionNameOrConfig}`."
			);
			if ($cfgIsNull) {
				// if nothing found under connection name - take first database record
				foreach (self::$configs as $value) {
					if (is_object($value)) {
						$cfg = $value;
						break;
					}
				}
			}
			// store new connection under config index for all other model classes
			static::$connections[$connectionName] = static::connect($cfg);
		}
		return static::$connections[$connectionName];
	}

	/**
	 * Always create new `\PDO` database connection.
	 * @param \stdClass $dbConfig `\stdClass` with members:
	 *							  driver, host, user, password, database, options, class
	 * @return \PDO
	 */
	protected static function connect ($dbConfig) {
		$sysCfgProps = (object) static::$sysConfigProperties;
		$conArgsKey = isset(self::$connectionArguments[$dbConfig->{$sysCfgProps->driver}])
			? $dbConfig->{$sysCfgProps->driver}
			: 'default';
		$conArgs = (object) self::$connectionArguments[$conArgsKey];
		$connection = NULL;
		// If database is file system based, complete app root and extend
		// relative path in $cfg->database to absolute path
		if ($conArgs->fileDb) {
			$appRoot = \MvcCore\Application::GetInstance()->GetRequest()->GetAppRoot();
			if (strpos($appRoot, 'phar://') !== FALSE) {
				$lastSlashPos = strrpos($appRoot, '/');
				$appRoot = substr($appRoot, 7, $lastSlashPos - 7);
			}
			$dbConfig->{$sysCfgProps->database} = str_replace(
				'\\', '/', realpath($appRoot . $dbConfig->{$sysCfgProps->database})
			);
		}
		// Process connection string (dsn) with config replacements
		$dsn = $conArgs->dsn;
		$cfgArr = array_merge($conArgs->defaults, (array) $dbConfig);
		$credentialsInDsn = (
			mb_strpos($dsn, '{user}') !== FALSE &&
			mb_strpos($dsn, '{password}') !== FALSE
		);
		foreach ($cfgArr as $key => $value) {
			if (
				is_numeric($key) || 
				mb_strpos($key, '\\PDO::') === 0 ||
				$key == 'options'
			) continue;
			if (isset($sysCfgProps->{$key})) {
				$prop = $sysCfgProps->{$key};
				$value = isset($dbConfig->{$prop})
					? $dbConfig->{$prop}
					: $value;
			}
			$dsn = str_replace('{'.$key.'}', $value, $dsn);
		}
		// If database required user and password credentials,
		// connect with full arguments count or only with one (sqlite only)
		$connectionClass = isset($dbConfig->{$sysCfgProps->class})
			? $dbConfig->{$sysCfgProps->class}
			: self::$defaultConnectionClass;
		$defaultOptions = self::$connectionArguments['default']['options'];
		$rawOptions = isset($dbConfig->{$sysCfgProps->options})
			? array_merge([], $defaultOptions, $conArgs->options, $dbConfig->{$sysCfgProps->options} ?: [])
			: array_merge([], $defaultOptions, $conArgs->options);
		$options = [];
		foreach ($rawOptions as $optionKey => $optionValue) {
			if (is_string($optionValue) && mb_strpos($optionValue, '\\PDO::') === 0)
				if (defined($optionValue))
					$optionValue = constant($optionValue);
			if (is_string($optionKey) && mb_strpos($optionKey, '\\PDO::') === 0) {
				if (defined($optionKey))
					$options[constant($optionKey)] = $optionValue;
			} else {
				$options[$optionKey] = $optionValue;
			}
		}
		if ($conArgs->auth && !$credentialsInDsn) {
			$connection = new $connectionClass(
				$dsn,
				$dbConfig->{$sysCfgProps->user},
				(string) $dbConfig->{$sysCfgProps->password},
				$options
			);
		} else {
			$connection = new $connectionClass(
				$dsn, NULL, NULL, $options
			);
		}
		return $connection;
	}
}
