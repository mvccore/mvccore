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

/**
 * @mixin \MvcCore\Model
 */
trait Connection {
	
	/**
	 * @inheritDoc
	 * @param  string|int|array<string,mixed>|\stdClass|NULL $connectionNameOrConfig
	 * @param  bool                                          $strict
	 *         If `TRUE` and no connection under given name or given
	 *         index found, exception is thrown. `TRUE` by default.
	 *         If `FALSE`, there could be returned connection by
	 *         first available configuration.
	 * @throws \InvalidArgumentException|\PDOException|\Throwable
	 * @return \PDO
	 */
	public static function GetConnection ($connectionNameOrConfig = NULL, $strict = TRUE) {
		$connectionName = (is_string($connectionNameOrConfig) || is_int($connectionNameOrConfig))
			? $connectionNameOrConfig
			: static::resolveConnectionName($connectionNameOrConfig, $strict);
		// if no connection exists under connection name key - connect to database
		if (isset(self::$connections[$connectionName])) 
			return self::$connections[$connectionName];

		// get system config 'db' data
		// and get predefined constructor arguments by driver value from config
		$cfg = static::GetConfig($connectionName);
		if ($cfg === NULL) throw new \InvalidArgumentException(
			"[".get_called_class()."] No connection found under given name/index: `{$connectionName}`."
		);
		// connect:
		$connection = static::connect($cfg);
		// store new connection under config index for all other model classes:
		return self::SetConnection($connectionName, $connection);
	}
	
	/**
	 * @inheritDoc
	 * @param  string|int $connectionName
	 * @param  \PDO       $connection
	 * @return \PDO
	 */
	public static function SetConnection ($connectionName, $connection) {
		return self::$connections[$connectionName] = $connection;
	}
	
	/**
	 * @inheritDoc
	 * @param  string|int $connectionName
	 * @return bool
	 */
	public static function HasConnection ($connectionName) {
		return isset(self::$connections[$connectionName]);
	}

	/**
	 * @inheritDoc
	 * @param  string|int|NULL $connectionName
	 * @throws \InvalidArgumentException
	 * @return bool
	 */
	public static function CloseConnection ($connectionName = NULL) {
		$connectionName = (is_string($connectionName) || is_int($connectionName))
			? $connectionName
			: static::resolveConnectionName($connectionName);
		if (!isset(self::$connections[$connectionName]))
			return FALSE;
		$connection = self::$connections[$connectionName];
		unset(self::$connections[$connectionName]);
		try {
			$closeMethod = new \ReflectionMethod($connection, 'close');
			if (!$closeMethod->isPublic()) $closeMethod->setAccessible(TRUE);
			$closeMethod->invoke($connection);
		} catch (\Throwable $e) {}
		$connection = NULL;
		return TRUE;
	}

	/**
	 * Resolve connection name or connection index or connection 
	 * configuration into single string or integer coresponding to 
	 * database config record.
	 * @param  string|int|array<string,mixed>|\stdClass|NULL $connectionNameOrConfig 
	 * @param  bool                                          $strict 
	 * @throws \InvalidArgumentException 
	 * @return string|int
	 */
	protected static function resolveConnectionName ($connectionNameOrConfig = NULL, $strict = TRUE) {
		if (is_array($connectionNameOrConfig) || $connectionNameOrConfig instanceof \stdClass) {
			// if first argument is database connection configuration - set it up and return new connection name
			if (self::$configs === NULL) static::loadConfigs(FALSE, $strict);
			$connectionName = static::SetConfig((array) $connectionNameOrConfig);
		} else {
			// if no connection index specified, try to get from class or from base model
			if (self::$configs === NULL) static::loadConfigs(TRUE, TRUE);
			$connectionName = $connectionNameOrConfig;
			if ($connectionName === NULL && isset(static::$connectionName)) // @phpstan-ignore-line
				$connectionName = static::$connectionName;
			if ($connectionName === NULL) 
				$connectionName = self::$defaultConnectionName;
		}
		if ($connectionName === NULL) 
			throw new \InvalidArgumentException(
				"[".get_called_class()."] No connection name or connection config specified."
			);
		return $connectionName;
	}

	/**
	 * Always create new `\PDO` database connection.
	 * @param  \stdClass $dbConfig `\stdClass` with members:
	 *                             driver, host, user, password, database, options, class
	 * @throws \PDOException|\Throwable
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
			if (class_exists('\Phar') && strlen(\Phar::running()) > 0) {
				$lastSlashPos = strrpos($appRoot, '/');
				$appRoot = substr($appRoot, 7, $lastSlashPos - 7);
			}
			$dbConfig = (object) array_merge([], (array) $dbConfig); // clone the `\stdClass` before change
			$dbFileFullPath = realpath($appRoot . $dbConfig->{$sysCfgProps->database});
			if ($dbFileFullPath === FALSE) throw new \InvalidArgumentException(
				"[".get_called_class()."] Database file doesn't exists: `{$dbFileFullPath}`."
			);
			$dbConfig->{$sysCfgProps->database} = str_replace('\\', '/', realpath($dbFileFullPath));
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
				$value === NULL ||
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
		$options[$sysCfgProps->config] = $dbConfig;
		if ($conArgs->auth && !$credentialsInDsn) {
			$connection = new $connectionClass(
				$dsn,
				strval($dbConfig->{$sysCfgProps->user}),
				strval($dbConfig->{$sysCfgProps->password}),
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
