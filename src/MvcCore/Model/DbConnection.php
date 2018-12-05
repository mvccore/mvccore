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
	 * @param string|int|array|NULL $connectionNameOrConfig
	 * @return \PDO
	 */
	public static function GetDb ($connectionNameOrConfig = NULL) {
		if (is_array($connectionNameOrConfig)) {
			// if first argument is database connection configuration - set it up and return new connection name
			if (static::$configs === NULL) static::loadConfigs(FALSE);
			$connectionName = static::SetConfig($connectionNameOrConfig);
		} else {
			// if no connection index specified, try to get from class or from base model
			if (static::$configs === NULL) static::loadConfigs(TRUE);
			$connectionName = $connectionNameOrConfig;
			if ($connectionName == NULL) $connectionName = static::$connectionName;
			if ($connectionName == NULL) $connectionName = self::$connectionName;
		}
		// if no connection exists under connection name key - connect to database
		if (!isset(static::$connections[$connectionName])) {
			// get system config 'db' data
			// and get predefined constructor arguments by driver value from config
			$cfg = static::GetConfig($connectionName);
			if ($cfg === NULL)
				$cfg = current(static::$configs); // if still nothing - take first database record
			$conArgs = (object) self::$connectionArguments[isset(self::$connectionArguments[$cfg->driver]) ? $cfg->driver : 'default'];
			$connection = NULL;
			// If database is file system based, complete app root and extend
			// relative path in $cfg->database to absolute path
			if ($conArgs->fileDb) {
				$appRoot = \MvcCore\Application::GetInstance()->GetRequest()->GetAppRoot();
				if (strpos($appRoot, 'phar://') !== FALSE) {
					$lastSlashPos = strrpos($appRoot, '/');
					$appRoot = substr($appRoot, 7, $lastSlashPos - 7);
				}
				$cfg->database = str_replace('\\', '/', realpath($appRoot . $cfg->database));
			}
			// Process connection string (dsn) with config replacements
			$dsn = $conArgs->dsn;
			foreach ((array) $cfg as $key => $value)
				$dsn = str_replace('{'.$key.'}', $value, $dsn);
			// If database required user and password credentials,
			// connect with full arguments count or only with one (sqlite only)
			if ($conArgs->auth) {
				$connection = new \PDO($dsn, $cfg->user, $cfg->password, $conArgs->options);
			} else {
				$connection = new \PDO($dsn);
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
		if (static::$configs === NULL) static::loadConfigs(TRUE);
		return static::$configs;
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
		static::$configs = [];
		foreach ($configs as $key => $value) static::$configs[$key] = (object) $value;
		static::$configs = & $configs;
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
		if (static::$configs === NULL) static::loadConfigs(TRUE);
		if ($connectionName == NULL) $connectionName = static::$connectionName;
		if ($connectionName == NULL) $connectionName = self::$connectionName;
		return static::$configs[$connectionName];
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
		if (static::$configs === NULL) static::loadConfigs(FALSE);
		if ($connectionName === NULL) {
			if (isset($config['name'])) {
				$connectionName = $config['name'];
			} else if (isset($config['index'])) {
				$connectionName = $config['index'];
			}
		}
		if ($connectionName === NULL) {
			$configNumericKeys = array_filter(array_keys(static::$configs), 'is_numeric');
			if ($configNumericKeys) {
				sort($configNumericKeys);
				$connectionName = $configNumericKeys[count($configNumericKeys) - 1] + 1; // last + 1
			} else {
				$connectionName = 0;
			}
		}
		static::$configs[$connectionName] = (object) $config;
		return $connectionName;
	}

	/**
	 * Initializes configuration data from system config if any
	 * into local `static::$configs` array, keyed by connection name or index.
	 * @throws \Exception
	 * @return void
	 */
	protected static function loadConfigs ($throwExceptionIfNoSysConfig = TRUE) {
		$configClass = \MvcCore\Application::GetInstance()->GetConfigClass();
		$systemCfg = $configClass::GetSystem();
		if ($systemCfg === FALSE && $throwExceptionIfNoSysConfig) {
			$selfClass = version_compare(PHP_VERSION, '5.5', '>') ? self::class : __CLASS__;
			throw new \Exception(
				"[".$selfClass."] System config not found in '" 
				. $configClass::GetSystemConfigPath() . "'."
			);
		}
		if (!isset($systemCfg->db) && $throwExceptionIfNoSysConfig) {
			$selfClass = version_compare(PHP_VERSION, '5.5', '>') ? self::class : __CLASS__;
			throw new \Exception(
				"[".$selfClass."] No [db] section and no records matched "
				."'db.*' found in system config.ini."
			);
		}
		$systemCfgDb = & $systemCfg->db;
		$cfgType = gettype($systemCfgDb);
		$configs = [];
		$defaultConnectionName = NULL;
		// db.defaultName - default connection index for models, where is no connection name/index defined inside class.
		if ($cfgType == 'array') {
			// multiple connections defined, indexed by some numbers, maybe default connection specified.
			if (isset($systemCfgDb['defaultName'])) 
				$defaultConnectionName = $systemCfgDb['defaultName'];
			foreach ($systemCfgDb as $key => $value) {
				if ($key === 'defaultName') continue;
				$configs[$key] = (object) $value;
			}
		} else if ($cfgType == 'object') {
			// Multiple connections defined or single connection defined:
			// - Single connection defined - `$systemCfg->db` contains directly record for `driver`.
			// - Multiple connections defined - indexed by strings, maybe default connection specified.
			if (isset($systemCfgDb->defaultName)) 
				$defaultConnectionName = $systemCfgDb->defaultName;
			if (isset($systemCfgDb->driver)) {
				$configs[0] = $systemCfgDb;
			} else {
				foreach ($systemCfgDb as $key => $value) {
					if ($key === 'defaultName') continue;
					$configs[$key] = (object) $value;
				}
			}
		}
		if ($defaultConnectionName !== NULL) {
			if ($configs) {
				reset($configs);
				$defaultConnectionName = key($configs);
			}
			if (!isset($configs[$defaultConnectionName])) {
				$selfClass = version_compare(PHP_VERSION, '5.5', '>') ? self::class : __CLASS__;
				throw new \Exception(
					"[".$selfClass."] No default connection name '$defaultConnectionName'"
					." found in 'db.*' section in system config.ini."
				);
			}
			self::$connectionName = $defaultConnectionName;
		}
		static::$configs = & $configs;
	}
}
