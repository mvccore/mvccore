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

trait DbConnection
{
	/**
	 * Returns `\PDO` database connection by connection name/index,
	 * usually by system config values (cached by local store)
	 * or create new connection of no connection cached.
	 * @param string|int|array|\stdClass|NULL $connectionNameOrConfig
	 * @param bool $strict	If `TRUE` and no connection under given name or given
	 *						index found, exception is thrown. `FALSE` by default.
	 * @throws \InvalidArgumentException
	 * @return \PDO
	 */
	public static function GetDb ($connectionNameOrConfig = NULL, $strict = FALSE) {
		if (is_array($connectionNameOrConfig) || $connectionNameOrConfig instanceof \stdClass) {
			// if first argument is database connection configuration - set it up and return new connection name
			if (self::$configs === NULL) static::loadConfigs(FALSE);
			$connectionName = static::SetConfig((array) $connectionNameOrConfig);
		} else {
			// if no connection index specified, try to get from class or from base model
			if (self::$configs === NULL) static::loadConfigs(TRUE);
			$connectionName = $connectionNameOrConfig;
			if ($connectionName === NULL) $connectionName = static::$connectionName;
			if ($connectionName === NULL) $connectionName = self::$connectionName;
		}
		if ($connectionName === NULL) {
			$selfClass = \PHP_VERSION_ID >= 50500 ? self::class : __CLASS__;
			throw new \InvalidArgumentException("[$selfClass] No connection name or connection config specified.");
		}
		// if no connection exists under connection name key - connect to database
		if (!isset(static::$connections[$connectionName])) {
			// get system config 'db' data
			// and get predefined constructor arguments by driver value from config
			$cfg = static::GetConfig($connectionName);
			if ($strict) throw new \InvalidArgumentException(
				"No connection found under given name/index: `$connectionNameOrConfig`."
			);
			if ($cfg === NULL)
				$cfg = current(self::$configs); // if nothing found under connection name - take first database record
			$sysCfgProps = (object) static::$systemConfigModelProps;
			$conArgsKey = isset(self::$connectionArguments[$cfg->{$sysCfgProps->driver}])
				? $cfg->{$sysCfgProps->driver}
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
				$cfg->{$sysCfgProps->database} = str_replace(
					'\\', '/', realpath($appRoot . $cfg->{$sysCfgProps->database})
				);
			}
			// Process connection string (dsn) with config replacements
			$dsn = $conArgs->dsn;
			$cfgArr = array_merge($conArgs->defaults, (array) $cfg);
			foreach ($cfgArr as $key => $value) {
				if (is_numeric($key)) continue;
				if (isset($sysCfgProps->{$key})) {
					$prop = $sysCfgProps->{$key};
					$value = isset($cfg->{$prop})
						? $cfg->{$prop}
						: $value;
				}
				$dsn = str_replace('{'.$key.'}', $value, $dsn);
			}
			// If database required user and password credentials,
			// connect with full arguments count or only with one (sqlite only)
			$connectionClass = isset($cfg->{$sysCfgProps->class})
				? $cfg->{$sysCfgProps->class}
				: self::$connectionClass;
			if ($conArgs->auth) {
				$rawOptions = isset($cfg->{$sysCfgProps->options})
					? array_merge($conArgs->options, $cfg->{$sysCfgProps->options} ?: [])
					: $conArgs->options;
				$options = [];
				foreach ($rawOptions as $optionKey => $optionValue) {
					if (is_string($optionKey)) {
						if (defined($optionKey))
							$options[constant($optionKey)] = $optionValue;
					} else {
						$options[$optionKey] = $optionValue;
					}
				}
				$connection = new $connectionClass(
					$dsn,
					$cfg->{$sysCfgProps->user},
					$cfg->{$sysCfgProps->password},
					$options
				);
			} else {
				$connection = new $connectionClass($dsn);
			}
			// store new connection under config index for all other model classes
			static::$connections[$connectionName] = $connection;
		}
		return static::$connections[$connectionName];
	}

	/**
	 * Get all known database connection config records as indexed/named array with `\stdClass` objects.
	 * Keys in array are connection config names/indexes and `\stdClass` values are config values.
	 * @return \stdClass[]
	 */
	public static function & GetConfigs () {
		if (self::$configs === NULL) static::loadConfigs(TRUE);
		return self::$configs;
	}

	/**
	 * Set all known configuration at once, optionally set default connection name/index.
	 * Example:
	 *	`\MvcCore\Model::SetConfigs(array(
	 *		// connection name: 'mysql-cdcol':
	 *		'mysql-cdcol'	=> array(
	 *			'driver'	=> 'mysql',	'host'		=> 'localhost',
	 *			'user'		=> 'root',	'password'	=> '1234',		'database' => 'cdcol',
	 *		),
	 *		// connection name: 'mssql-tests':
	 *		'mssql-tests' => array(
	 *			'driver'	=> 'sqlsrv',	'host' => '.\SQLEXPRESS',
	 *			'user'		=> 'sa',	'password' => '1234', 'database' => 'tests',
	 *		)
	 *	);`
	 * or:
	 *	`\MvcCore\Model::SetConfigs(array(
	 *		// connection index: 0:
	 *		array(
	 *			'driver'	=> 'mysql',	'host'		=> 'localhost',
	 *			'user'		=> 'root',	'password'	=> '1234',		'database' => 'cdcol',
	 *		),
	 *		// connection index: 1:
	 *		array(
	 *			'driver'	=> 'sqlsrv',	'host' => '.\SQLEXPRESS',
	 *			'user'		=> 'sa',	'password' => '1234', 'database' => 'tests',
	 *		)
	 *	);`
	 * @param \stdClass[]|array[] $configs Configuration array with `\stdClass` objects or arrays with configuration data.
	 * @return bool
	 */
	public static function SetConfigs (array $configs = [], $defaultConnectionName = NULL) {
		self::$configs = [];
		foreach ($configs as $key => $value) self::$configs[$key] = (object) $value;
		self::$configs = & $configs;
		if ($defaultConnectionName !== NULL) self::$defaultConnectionName = $defaultConnectionName;
		return TRUE;
	}

	/**
	 * Returns database connection config by connection index (integer)
	 * or by connection name (string) as `\stdClass` (cached by local store).
	 * @param int|string|NULL $connectionName
	 * @return \stdClass
	 */
	public static function & GetConfig ($connectionName = NULL) {
		if (self::$configs === NULL) static::loadConfigs(TRUE);
		if ($connectionName === NULL) $connectionName = static::$connectionName;
		if ($connectionName === NULL) $connectionName = self::$connectionName;
		return self::$configs[$connectionName];
	}

	/**
	 * Set configuration array with optional connection name/index.
	 * If there is array key `name` or `index` inside config `array` or `\stdClass`,
	 * it's value is used for connection name or index or there is no param `$connectionName` defined.
	 * Example:
	 *	`\MvcCore\Model::SetConfig(array(
	 *		'name'		=> 'mysql-cdcol',
	 *		'driver'	=> 'mysql',		'host'		=> 'localhost',
	 *		'user'		=> 'root',		'password'	=> '1234',		'database' => 'cdcol',
	 *	));`
	 * or:
	 *	`\MvcCore\Model::SetConfig(array(
	 *		'index'		=> 0,
	 *		'driver'	=> 'mysql',	'host'		=> 'localhost',
	 *		'user'		=> 'root',	'password'	=> '1234',		'database' => 'cdcol',
	 *	));`
	 * or:
	 *	`\MvcCore\Model::SetConfig(array(
	 *		'driver'	=> 'mysql',	'host'		=> 'localhost',
	 *		'user'		=> 'root',	'password'	=> '1234',		'database' => 'cdcol',
	 *	), 'mysql-cdcol');`
	 * or:
	 *	`\MvcCore\Model::SetConfig(array(
	 *		'driver'	=> 'mysql',	'host'		=> 'localhost',
	 *		'user'		=> 'root',	'password'	=> '1234',		'database' => 'cdcol',
	 *	), 0);`
	 * @param \stdClass[]|array[] $config
	 * @param string|int|NULL $connectionName
	 * @return string|int
	 */
	public static function SetConfig (array $config = [], $connectionName = NULL) {
		if (self::$configs === NULL) static::loadConfigs(FALSE);
		$sysCfgProps = (object) static::$systemConfigModelProps;
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
	 * @throws \Exception
	 * @return void
	 */
	protected static function loadConfigs ($throwExceptionIfNoSysConfig = TRUE) {
		$configClass = \MvcCore\Application::GetInstance()->GetConfigClass();
		$systemCfg = $configClass::GetSystem();
		if ($systemCfg === FALSE && $throwExceptionIfNoSysConfig) {
			$selfClass =\PHP_VERSION_ID >= 50500 ? self::class : __CLASS__;
			throw new \Exception(
				"[".$selfClass."] System config not found in '"
				. $configClass::GetSystemConfigPath() . "'."
			);
		}
		if (!isset($systemCfg->db) && $throwExceptionIfNoSysConfig) {
			$selfClass = \PHP_VERSION_ID >= 50500 ? self::class : __CLASS__;
			throw new \Exception(
				"[".$selfClass."] No [db] section and no records matched "
				."'db.*' found in system config.ini."
			);
		}
		$sysCfgProps = (object) static::$systemConfigModelProps;
		$systemCfgDb = & $systemCfg->{$sysCfgProps->sectionName};
		$cfgType = gettype($systemCfgDb);
		$configs = [];
		$defaultConnectionName = NULL;
		$defaultConnectionClass = NULL;
		// db.defaultName - default connection index for models, where is no connection name/index defined inside class.
		if ($cfgType == 'array') {
			// multiple connections defined, indexed by some numbers, maybe default connection specified.
			if (isset($systemCfgDb[$sysCfgProps->defaultName]))
				$defaultConnectionName = $systemCfgDb[$sysCfgProps->defaultName];
			if (isset($systemCfgDb[$sysCfgProps->defaultClass]))
				$defaultConnectionClass = $systemCfgDb[$sysCfgProps->defaultClass];
			foreach ($systemCfgDb as $key => $value) {
				if (is_scalar($value)) {
					$configs[$key] = $value;
				} else {
					$configs[$key] = (object) $value;
				}
			}
		} else if ($cfgType == 'object') {
			// Multiple connections defined or single connection defined:
			// - Single connection defined - `$systemCfg->db` contains directly record for `driver`.
			// - Multiple connections defined - indexed by strings, maybe default connection specified.
			if (isset($systemCfgDb->{$sysCfgProps->defaultName}))
				$defaultConnectionName = $systemCfgDb->{$sysCfgProps->defaultName};
			if (isset($systemCfgDb->{$sysCfgProps->defaultClass}))
				$defaultConnectionClass = $systemCfgDb->{$sysCfgProps->defaultClass};
			if (isset($systemCfgDb->driver)) {
				$configs[0] = $systemCfgDb;
			} else {
				foreach ($systemCfgDb as $key => $value) {
					if (is_scalar($value)) {
						$configs[$key] = $value;
					} else {
						$configs[$key] = (object) $value;
					}
				}
			}
		}
		if ($defaultConnectionName === NULL) {
			if ($configs) {
				reset($configs);
				$defaultConnectionName = key($configs);
			}
		}
		if (!isset($configs[$defaultConnectionName])) {
			$selfClass = \PHP_VERSION_ID >= 50500 ? self::class : __CLASS__;
			throw new \Exception(
				"[".$selfClass."] No default connection name '$defaultConnectionName'"
				." found in 'db.*' section in system config.ini."
			);
		}
		self::$connectionName = $defaultConnectionName;
		if ($defaultConnectionClass !== NULL)
			self::$connectionClass = $defaultConnectionClass;
		self::$configs = & $configs;
	}
}
