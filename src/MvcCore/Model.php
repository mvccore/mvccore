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

namespace MvcCore;

//include_once(__DIR__ . '/Interfaces/IModel.php');
//include_once('Config.php');

/**
 * Responsibility - static members for connections and by configuration,
 *				  instances members for active record pattern.
 * - Reading `db` section from system `config.ini` file.
 * - Database `\PDO` connecting by config settings and index.
 * - Instance loaded variables initializing.
 * - Instance initialized values reading.
 * - Virtual calls/sets and gets handling.
 */
class Model implements Interfaces\IModel {
	/**
	 * `\PDO` connection arguments.
	 *
	 * If you need to reconfigure connection string for any other special
	 * `\PDO` database implementation or you specific needs, patch this array
	 * in extended application base model class in base `__construct()` method by:
	 *	 `static::$connectionArguments = array_merge(static::$connectionArguments, array(...));`
	 * or by:
	 *	 `static::$connectionArguments['driverName']['dsn'] = '...';`
	 *
	 * Every key in this field is driver name, so you can use usual `\PDO` drivers:
	 * - `mysql`, `sqlite`, `sqlsrv` (mssql), `firebird`, `ibm`, `informix`, `4D`
	 * Following drivers shoud be used with defaults, no connection args from here are necessary:
	 * - `oci`, `pgsql`, `cubrid`, `sysbase`, `dblib`
	 *
	 * Every value in this configuration field shoud be defined as:
	 * - `dsn`		- connection query as first `\PDO` contructor argument
	 *				  with database config replacements.
	 * - `auth`		- if required to use database credentials for connecting or not.
	 * - `fileDb`	- if database if file database or not.
	 * - `options`	. any additional arguments array or empty array.
	 * @var array
	 */
	protected static $connectionArguments = [
		'4D'			=> [
			'dsn'		=> '{driver}:host={host};charset=UTF-8',
			'auth'		=> TRUE,
			'fileDb'	=> FALSE,
			'options'	=> [],
		],
		'firebird'		=> [
			'dsn'		=> '{driver}:host={host};dbname={database};charset=UTF8',
			'auth'		=> TRUE,
			'fileDb'	=> TRUE,
			'options'	=> []
		],
		'ibm'			=> [
			'dsn'		=> 'ibm:DRIVER={IBM DB2 ODBC DRIVER};DATABASE={database};HOSTNAME={host};PORT={port};PROTOCOL=TCPIP;',
			'auth'		=> TRUE,
			'fileDb'	=> FALSE,
			'options'	=> [],
		],
		'informix'		=> [
			'dsn'		=> '{driver}:host={host};service={service};database={database};server={server};protocol={protocol};EnableScrollableCursors=1',
			'auth'		=> TRUE,
			'fileDb'	=> FALSE,
			'options'	=> [],
		],
		'mysql'			=> [
			'dsn'		=> '{driver}:host={host};dbname={database}',
			'auth'		=> TRUE,
			'fileDb'	=> FALSE,
			'options'	=> [
				'\PDO::ATTR_EMULATE_PREPARES'		=> FALSE, // let params inserting on database
				'\PDO::MYSQL_ATTR_MULTI_STATEMENTS'	=> TRUE,
				'\PDO::MYSQL_ATTR_INIT_COMMAND'		=> "SET NAMES 'UTF8'",
			],
		],
		'sqlite'		=> [
			'dsn'		=> '{driver}:{database}',
			'auth'		=> FALSE,
			'fileDb'	=> TRUE,
			'options'	=> [],
		],
		'sqlsrv'		=> [
			'dsn'		=> '{driver}:Server={host};Database={database}',
			'auth'		=> TRUE,
			'fileDb'	=> FALSE,
			'options'	=> [],
		],
		'default'		=> [
			'dsn'		=> '{driver}:host={host};dbname={database}',
			'auth'		=> TRUE,
			'fileDb'	=> FALSE,
			'options'	=> [],
		],
	];

	/**
	 * Default database connection name/index, in config ini defined in section `db.default = name`.
	 * In extended classes - use this for connection name/index of current model if different.
	 * @var string|int|NULL
	 */
	protected static $connectionName = NULL;

	/**
	 * `\PDO` connections array, keyed by connection indexes from system config.
	 * @var \PDO[]
	 */
	protected static $connections = [];

	/**
	 * Instance of current class, if there is necessary to use it as singleton.
	 * @var \MvcCore\Model[]|\MvcCore\Interfaces\IModel[]
	 */
	protected static $instances = [];

	/**
	 * System config sections array with `\stdClass` objects, keyed by connection indexes.
	 * @var \stdClass[]
	 */
	protected static $configs = NULL;

	/**
	 * Automaticly initialize config, db connection and resource class.
	 * @var bool
	 */
	protected $autoInit = TRUE;

	/**
	 * `\PDO` instance.
	 * @var \PDO
	 */
	protected $db;

	/**
	 * System config section for database under called connection index in constructor.
	 * @var \stdClass
	 */
	protected $config;

	/**
	 * Resource model class with SQL statements.
	 * @var \MvcCore\Model|\MvcCore\Interfaces\IModel
	 */
	protected $resource;

	/**
	 * Originaly declared internal model properties to protect their
	 * possible overwriting by `__set()` or `__get()` magic methods.
	 * @var array
	 */
	protected static $protectedProperties = [
		'autoInit'	=> 1,
		'db'		=> 1,
		'config'	=> 1,
		'resource'	=> 1,
	];

	/**
	 * Collect all model class public and inherit field values into array.
	 * @param boolean $getNullValues			If `TRUE`, include also values with `NULL`s, by default - `FALSE`.
	 * @param boolean $includeInheritProperties If `TRUE`, include only fields from current model class and from parent classes.
	 * @param boolean $publicOnly			   If `TRUE`, include only public model fields.
	 * @return array
	 */
	public function GetValues ($getNullValues = FALSE, $includeInheritProperties = TRUE, $publicOnly = TRUE) {
		$data = [];
		$modelClassName = get_class($this);
		$classReflector = new \ReflectionClass($modelClassName);
		$properties = $publicOnly ? $classReflector->getProperties(\ReflectionProperty::IS_PUBLIC) : $classReflector->getProperties();
		foreach ($properties as $property) {
			if (!$includeInheritProperties && $property->class != $modelClassName) continue;
			$propertyName = $property->name;
			if (isset(static::$protectedProperties[$propertyName])) continue;
			if (!$getNullValues && $this->$propertyName === NULL) continue;
			$data[$propertyName] = $this->$propertyName;
		}
		return $data;
	}

	/**
	 * Set up given `$data` items into `$this` instance context
	 * as typed properties by PHP doc comments, as properties
	 * with the same names as `$data` array keys. Case sesitively by default.
	 * Do not set any `$data` items, which are not declared in `$this` context.
	 * @param array   $data					 Collection with data to set up
	 * @param boolean $keysInsensitive			If `TRUE`, set up properties from `$data` with case insensivity.
	 * @param boolean $includeInheritProperties If `TRUE`, include only fields from current model class and from parent classes.
	 * @param boolean $publicOnly			   If `TRUE`, include only public model fields.
	 * @return \MvcCore\Model|\MvcCore\Interfaces\IModel
	 */
	public function & SetUp ($data = [], $keysInsensitive = FALSE, $includeInheritProperties = TRUE, $publicOnly = TRUE) {
		$modelClassName = get_class($this);
		$classReflector = new \ReflectionClass($modelClassName);
		$properties = $publicOnly
			? $classReflector->getProperties(\ReflectionProperty::IS_PUBLIC)
			: $classReflector->getProperties();
		$dataKeys = $keysInsensitive ? ','.implode(',', array_keys($data)).',' : '' ;
		foreach ($properties as $property) {
			if (!$includeInheritProperties && $property->class != $modelClassName) continue;
			$propertyName = $property->name;
			if (isset($data[$propertyName])) {
				$value = $data[$propertyName];
			} else if ($keysInsensitive) {
				// try to search with not case sensitively same property name
				$dataKeyPos = stripos($dataKeys, ','.$propertyName.',');
				if ($dataKeyPos === FALSE) continue;
				$dataKey = substr($dataKeys, $dataKeyPos + 1, strlen($propertyName));
				$value = $data[$dataKey];
			} else {
				continue;
			}
			if (preg_match('/@var\s+([^\s]+)/', $property->getDocComment(), $matches)) {
				list(, $type) = $matches;
				$pipePos = strpos($type, '|');
				if ($pipePos !== FALSE) $type = substr($type, 0, $pipePos);
				settype($value, $type);
			}
			$this->$propertyName = $value;
		}
		return $this;
	}

	/**
	 * Returns (or creates and holds) instance from local store.
	 * @param mixed $args,... unlimited OPTIONAL variables to pass into model `__construct()` method.
	 * @return \MvcCore\Model|\MvcCore\Interfaces\IModel
	 */
	public static function GetInstance (/* ...$args */) {
		// get `"ClassName"` string from this call: `ClassName::GetInstance();`
		$className = get_called_class();
		$args = func_get_args();
		$instanceIndex = md5($className . '_' . serialize($args));
		if (!isset(self::$instances[$instanceIndex])) {
			$reflectionClass = new \ReflectionClass($className);
			$instance = $reflectionClass->newInstanceArgs($args);
			self::$instances[$instanceIndex] = $instance;
		}
		return self::$instances[$instanceIndex];
	}

	/**
	 * Returns (or creates if necessary) model resource instance.
	 * @param array  $args			  Values array with variables to pass into resource `__construct()` method.
	 * @param string $modelClassPath
	 * @param string $resourceClassPath
	 * @return \MvcCore\Model|\MvcCore\Interfaces\IModel
	 */
	public static function GetResource ($args = [], $modelClassName = '', $resourceClassPath = '\Resource') {
		$result = NULL;
		if (!$modelClassName) $modelClassName = get_called_class();
		// do not create resource instance in resource class (if current class name doesn't end with '_Resource' substring):
		if (strpos($modelClassName, '\Resource') === FALSE) {
			$resourceClassName = $modelClassName . $resourceClassPath;
			// do not create resource instance if resource class doesn't exist:
			if (class_exists($resourceClassName)) {
				$result = call_user_func_array([$resourceClassName, 'GetInstance'], $args);
			}
		}
		return $result;
	}

	/**
	 * Automaticly initialize `$this-config`, `$this->db` and `$this->resource` properties
	 * if local protected property `$this->autoInit` is still `TRUE` (`TRUE` as default in `\MvcCore\Model`).
	 * @param string|int|NULL $connectionName Optional. If not set, there is used value from `static::$connectionName`.
	 * @return void
	 */
	public function __construct ($connectionName = NULL) {
		if ($this->autoInit) $this->Init($connectionName);
	}

	/**
	 * Initialize `$this->config`, `$this->db` and `$this->resource` properties.
	 * If no `$connectionName` specified by first argument, return connection
	 * config by connection name defined first in `static::$connectionName`
	 * and if there is nothing, return connection config by connection name
	 * defined in `\MvcCore\Model::$connectionName`.
	 * @param string|int|NULL $connectionName Optional. If not set, there is used value from `static::$connectionName`.
	 * @return void
	 */
	public function Init ($connectionName = NULL) {
		if ($connectionName === NULL) $connectionName = static::$connectionName;
		if ($connectionName === NULL) $connectionName = self::$connectionName;
		$this->db = static::GetDb($connectionName);
		$this->config = static::GetConfig($connectionName);
		$this->resource = static::GetResource([], get_class($this));
	}

	/**
	 * Returns `\PDO` database connection by connection name/index,
	 * usually by system ini config values (cached by local store)
	 * or create new connection of no connection cached.
	 * @param string|int|array|NULL $connectionNameOrConfig
	 * @return \PDO
	 */
	public static function GetDb ($connectionNameOrConfig = NULL) {
		if (gettype($connectionNameOrConfig) == 'array') {
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
			// If database is filesystem based, complete app root and extend
			// relative path in $cfg->dbname to absolute path
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
			// connect with wull arguments count or only with one (sqllite only)
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
	 * Set all known configuration at once, optionaly set default connection name/index.
	 * Example:
	 *	`\MvcCore\Model::SetConfigs(array(
	 *		// connection name: 'mysql-cdcol':
	 *		'mysql-cdcol'	=> array(
	 *			'driver'	=> 'mysql',	'host'		=> 'localhost',
	 *			'user'		=> 'root',	'password'	=> '1234',		'database' => 'cdcol',
	 *		),
	 *		// connection name: 'mssql-tests':
	 *		'mssql-tests' => array(
	 *			'driver'	=> 'mssql',	'host' => '.\SQLEXPRESS',
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
	 *			'driver'	=> 'mssql',	'host' => '.\SQLEXPRESS',
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
		if ($systemCfg === FALSE && $throwExceptionIfNoSysConfig) throw new \Exception(
			"[".__CLASS__."] System config.ini not found in '" . $configClass::$SystemConfigPath . "'."
		);
		if (!isset($systemCfg->db) && $throwExceptionIfNoSysConfig) throw new \Exception(
			"[".__CLASS__."] No [db] section and no records matched 'db.*' found in system config.ini."
		);
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
			if (!isset($configs[$defaultConnectionName])) throw new \Exception(
				"[".__CLASS__."] No default connection name '$defaultConnectionName' found in 'db.*' section in system config.ini."
			);
			self::$connectionName = $defaultConnectionName;
		}
		static::$configs = & $configs;
	}

	/**
	 * Sets any custom property `"PropertyName"` by `\MvcCore\Interfaces\IModel::SetPropertyName("value")`,
	 * which is not necessary to define previously or gets previously defined
	 * property `"PropertyName"` by `\MvcCore\Interfaces\IModel::GetPropertyName();`.
	 * Throws exception if no property defined by get call
	 * or if virtual call begins with anything different from `Set` or `Get`.
	 * This method returns custom value for get and `\MvcCore\Interfaces\IModel` instance for set.
	 * @param string $rawName
	 * @param array  $arguments
	 * @throws \InvalidArgumentException If `strtolower($rawName)` doesn't begin with `"get"` or with `"set"`.
	 * @return mixed|\MvcCore\Model|\MvcCore\Interfaces\IModel
	 */
	public function __call ($rawName, $arguments = []) {
		$nameBegin = strtolower(substr($rawName, 0, 3));
		$name = substr($rawName, 3);
		if ($nameBegin == 'get' && isset($this->$name)) {
			return $this->$name;
		} else if ($nameBegin == 'set') {
			$this->$name = isset($arguments[0]) ? $arguments[0] : NULL;
			return $this;
		} else {
			throw new \InvalidArgumentException('['.__CLASS__."] No property with name '$name' defined.");
		}
	}

	/**
	 * Set any custom property, not necessary to previously defined.
	 * @param string $name
	 * @param mixed  $value
	 * @throws \InvalidArgumentException If name is `"autoInit" || "db" || "config" || "resource"`
	 * @return bool
	 */
	public function __set ($name, $value) {
		if (isset(static::$protectedProperties[$name])) {
			throw new \InvalidArgumentException(
				'['.__CLASS__."] It's not possible to change property: '$name' originaly declared in class ".__CLASS__.'.'
			);
		}
		return $this->$name = $value;
	}

	/**
	 * Get any custom property, not necessary to previously defined,
	 * if property is not defined, NULL is returned.
	 * @param string $name
	 * @throws \InvalidArgumentException If name is `"autoInit" || "db" || "config" || "resource"`
	 * @return mixed
	 */
	public function __get ($name) {
		if (isset(static::$protectedProperties[$name])) {
			throw new \InvalidArgumentException(
				'['.__CLASS__."] It's not possible to get property: '$name' originaly declared in class ".__CLASS__.'.'
			);
		}
		return (isset($this->$name)) ? $this->$name : null;
	}
}
