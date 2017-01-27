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

require_once('Config.php');

/**
 * Core model
 * - reading 'db' section from system config.ini
 * - database PDO connecting by config settings and index
 * - instance loaded variables initializing
 * - instance initialized values reading
 * - virtual calls/sets and gets handling
 */
abstract class Model {
	/**
	 * PDO connection arguments.
	 * 
	 * If you need to reconfigure connection string for any other special
	 * PDO database implementation or you specific needs, patch this array
	 * in extended application base model class in base __construct method by:
	 *	 static::$connectionArguments = array_merge(static::$connectionArguments, array(...));
	 * or by:
	 *	 static::$connectionArguments['driverName]['dsn'] = '...';
	 * 
	 * Every key in this field shoud be driver name, you can use:
	 * - 4D, cubrid, firebird, ibm, informix, mysql, oci, pgsql, sqlite, sqlsrv (mssql), sysbase, dblib
	 *	 (cubrid, oci, pgsql, sysbase and dblib shoud be used with defaults)
	 * 
	 * Every value in this field shoud be defined as:
	 * - connection query - as first PDO contructor argument with
	 *						database config replacements.
	 * - required to use database credentials for connecting
	 * - any additional arguments array
	 * @var array
	 */
	protected static $connectionArguments = array(
		'4D'			=> array(
			'dsn'		=> '{driver}:host={host};charset=UTF-8',
			'auth'		=> TRUE,
			'fileDb'	=> FALSE,
			'options'	=> array(),
		),
		'firebird'		=> array(
			'dsn'		=> '{driver}:host={host};dbname={dbname};charset=UTF8',
			'auth'		=> TRUE,
			'fileDb'	=> TRUE,
			'options'	=> array()
		),
		'ibm'			=> array(
			'dsn'		=> 'ibm:DRIVER={IBM DB2 ODBC DRIVER};DATABASE={dbname};HOSTNAME={host};PORT={port};PROTOCOL=TCPIP;',
			'auth'		=> TRUE,
			'fileDb'	=> FALSE,
			'options'	=> array(),
		),
		'informix'		=> array(
			'dsn'		=> '{driver}:host={host};service={service};database={dbname};server={server};protocol={protocol};EnableScrollableCursors=1',
			'auth'		=> TRUE,
			'fileDb'	=> FALSE,
			'options'	=> array(),
		),
		'mysql'			=> array(
			'dsn'		=> '{driver}:host={host};dbname={dbname}',
			'auth'		=> TRUE,
			'fileDb'	=> FALSE,
			'options'	=> array(
				'\PDO::ATTR_EMULATE_PREPARES'		=> FALSE, // let params inserting on database
				'\PDO::MYSQL_ATTR_MULTI_STATEMENTS'	=> TRUE,
				'\PDO::MYSQL_ATTR_INIT_COMMAND'		=> "SET NAMES 'UTF8'",
			),
		),
		'sqlite'		=> array(
			'dsn'		=> '{driver}:{dbname}',
			'auth'		=> FALSE,
			'fileDb'	=> TRUE,
			'options'	=> array(),
		),
		'sqlsrv'		=> array(
			'dsn'		=> '{driver}:Server={host};Database={dbname}',
			'auth'		=> TRUE,
			'fileDb'	=> FALSE,
			'options'	=> array(),
		),
		'default'		=> array(
			'dsn'		=> '{driver}:host={host};dbname={dbname}',
			'auth'		=> TRUE,
			'fileDb'	=> FALSE,
			'options'	=> array(),
		),
	);

	/**
	 * Default database connection index, in config ini defined in section db.defaultDbIndex = 0.
	 * In extended classes - use this for connection index of current model if different.
	 * @var int
	 */
	protected static $connectionIndex = -1;

	/**
	 * PDO connections array, keyed by connection indexes from system config
	 * @var array
	 */
	protected static $connections = array();

	/**
	 * Instance of current class if there is necessary to use it as singleton
	 * @var array
	 */
	protected static $instances = array();

	/**
	 * System config sections array with stdClass objects, keyed by connection indexes
	 * @var array
	 */
	protected static $configs = array();

	/**
	 * Automaticly initialize config, db connection and resource class
	 * @var bool
	 */
	protected $autoInit = TRUE;

    /**
     * PDO instance
     * @var \PDO
     */
    protected $db;

	/**
	 * System config section for database under called connection index in constructor
	 * @var \stdClass
	 */
	protected $cfg;

	/**
	 * Resource model class with SQL statements
	 * @var \MvcCore\Model
	 */
	protected $resource;

	/**
	 * Collect all model class public and inherit field values into array
	 * @param boolean $getNullValues			if true, include also values with NULLs, by default - FALSE
	 * @param boolean $includeInheritProperties if true, include only fields from current model class and from parent classes
	 * @param boolean $publicOnly               if true, include only public model fields
	 * @return array
	 */
	public function GetValues ($getNullValues = FALSE, $includeInheritProperties = TRUE, $publicOnly = TRUE) {
		$data = array();
		$systemProperties = array('autoInit' => 1, 'db' => 1, 'cfg' => 1, 'resource' => 1);
		$modelClassName = get_class($this);
		$classReflector = new \ReflectionClass($modelClassName);
		$properties = $publicOnly ? $classReflector->getProperties(\ReflectionProperty::IS_PUBLIC) : $classReflector->getProperties();
		foreach ($properties as $property) {
			if (!$includeInheritProperties && $property->class != $modelClassName) continue;
			$propertyName = $property->name;
			if (isset($systemProperties[$propertyName])) continue;
			if (!$getNullValues && is_null($this->$propertyName)) continue;
			$data[$propertyName] = $this->$propertyName;
		}
		return $data;
	}

	/**
	 * Set up given $data items into $this instance context 
	 * as typed properties by PHP doc comments, as properties
	 * with the same names as $data array keys - case sesitively by default.
	 * Do not set any $data items which are not declared in $this context.
	 * @param array   $data                     collection with data to set up
	 * @param boolean $keysInsensitive			if true, set up properties from $data with case insensivity
	 * @param boolean $includeInheritProperties if true, include only fields from current model class and from parent classes
	 * @param boolean $publicOnly               if true, include only public model fields
	 * @return \MvcCore\Model
	 */
	public function SetUp ($data = array(), $keysInsensitive = FALSE, $includeInheritProperties = TRUE, $publicOnly = TRUE) {
		$modelClassName = get_class($this);
		$classReflector = new \ReflectionClass($modelClassName);
		$properties = $publicOnly ? $classReflector->getProperties(\ReflectionProperty::IS_PUBLIC) : $classReflector->getProperties();
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
				settype($value, $type);
			}
			$this->$propertyName = $value;
		}
		return $this;
	}

	/**
	 * Returns (or creates and holds) instance from local store
	 * @param mixed $arg,... unlimited OPTIONAL variables to pass into __construct() method
	 * @return \MvcCore\Model|mixed
	 */
	public static function GetInstance (/* $arg1, $arg2, $arg, ... */) {
		// get 'ClassName' string from this call: ClassName::GetInstance();
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
	 * Returns (or creates if necessary) model resource instance
	 * @param array $args values array with variables to pass into __construct() method
	 * @param string $modelClassPath
	 * @param string $resourceClassPath
	 * @return \MvcCore\Model (|\MvcCore\Model\Resource)
	 */
	public static function GetResource ($args = array(), $modelClassName = '', $resourceClassPath = '\Resource') {
		$result = NULL;
		if (!$modelClassName) $modelClassName = get_called_class();
		// do not create resource instance in resource class (if current class name doesn't end with '_Resource' substring):
		if (strpos($modelClassName, '\Resource') === FALSE) {
			$resourceClassName = $modelClassName . $resourceClassPath;
			// do not create resource instance if resource class doesn't exist:
			if (class_exists($resourceClassName)) {
				$result = call_user_func_array(array($resourceClassName, 'GetInstance'), $args);
			}
		}
		return $result;
	}

	/**
	 * Creates an instance and inits cfg, db and resource properties
	 * @param int $connectionIndex
	 */
	public function __construct ($connectionIndex = -1) {
		if ($this->autoInit) $this->Init($connectionIndex);
    }

	/**
	 * Creates an instance and inits cfg, db and resource properties
	 * @param int $connectionIndex
	 */
	public function Init ($connectionIndex = -1) {
		$this->db = static::GetDb($connectionIndex);
		$this->cfg = static::GetCfg($connectionIndex);
		$this->resource = static::GetResource(array(), get_class($this));
	}

	/**
	 * Returns database connection by connection index (cached by local store)
	 * or create new connection of no connection cached.
	 * @param int $connectionIndex 
	 * @return \PDO
	 */
	public static function GetDb ($connectionIndex = -1) {
		if (!isset(static::$connections[$connectionIndex])) {
			static::loadConfigs();
			if ($connectionIndex == -1) $connectionIndex = static::$connectionIndex;
			if ($connectionIndex == -1) $connectionIndex = self::$connectionIndex;
			// get system config 'db' data 
			// and get predefined constructor arguments by driver value from config
			$cfg = static::GetCfg($connectionIndex);
			$conArgs = (object) self::$connectionArguments[isset(self::$connectionArguments[$cfg->driver]) ? $cfg->driver:'default'];
			$connection = NULL;
			// If database is filesystem based, complete app root and extend
			// relative path in $cfg->dbname to absolute path
			if ($conArgs->fileDb) {
				$appRoot = \MvcCore::GetInstance()->GetRequest()->AppRoot;
				if (strpos($appRoot, 'phar://') !== FALSE) {
					$lastSlashPos = strrpos($appRoot, '/');
					$appRoot = substr($appRoot, 7, $lastSlashPos - 7);
				}
				$cfg->dbname = realpath($appRoot . $cfg->dbname);
			}
			// Process connection string (dsn) with config replacements
			$dsn = $conArgs->dsn;
			foreach ($cfg as $key => $value) $dsn = str_replace('{'.$key.'}', $value, $dsn);
			// If database required username and password credentials,
			// connect with wull arguments count or only with one (sqllite only)
			if ($conArgs->auth) {
				$connection = new \PDO($dsn, $cfg->username, $cfg->password, $conArgs->options);
			} else {
				$connection = new \PDO($dsn);
			}
			// store new connection under config index for all other model classes
			static::$connections[$connectionIndex] = $connection;
        }
		return static::$connections[$connectionIndex];
	}

	/**
	 * Returns database config by connection index as stdClass (cached by local store)
	 * @param int $connectionIndex 
	 * @return object
	 */
	public static function GetCfg ($connectionIndex = -1) {
		static::loadConfigs();
		if ($connectionIndex == -1) $connectionIndex = static::$connectionIndex;
		if ($connectionIndex == -1) $connectionIndex = self::$connectionIndex;
		$baseType = gettype(static::$configs);
		if ($baseType == 'array' && isset(static::$configs[$connectionIndex])) {
			return static::$configs[$connectionIndex];
		} else if ($baseType == 'object' && isset(static::$configs->$connectionIndex)) {
			return static::$configs->$connectionIndex;
		} else {
			return static::$configs;
		}
	}

	/**
	 * Initialize configuration data
	 * @throws \Exception
	 * @return void
	 */
	protected static function loadConfigs () {
		if (empty(static::$configs)) {
			$cfg = \MvcCore\Config::GetSystem();
			if ($cfg === FALSE) {
				$cfgPath = \MvcCore\Config::$SystemConfigPath;
				throw new \Exception('['.__CLASS__."] System config.ini not found in '$cfgPath'.");
			}
			if (!isset($cfg->db)) {
				throw new \Exception('['.__CLASS__."] No [db] section and no records matched 'db.*' found in system config.ini.");
			}
			$cfgType = gettype($cfg->db);
			// db.defaultDbIndex - default connection index for modelses, where is no connection index strictly defined
			if ($cfgType == 'array' && isset($cfg->db['defaultDbIndex'])) {
				self::$connectionIndex = $cfg->db['defaultDbIndex'];
			} else if ($cfgType == 'object' && isset($cfg->db->defaultDbIndex)) {
				self::$connectionIndex = $cfg->db->defaultDbIndex;
			}
			static::$configs = $cfg->db;
		}
	}

	/**
	 * Sets any custom property ('PropertyName') by $model->SetPropertyName('value'),
	 * which is not necessary to define previously or gets previously defined 
	 * property ('PropertyName') by $model->GetPropertyName(); Throws exception 
	 * if no property defined by get call or if virtual call begins with anything 
	 * different from 'set' or 'get'.
	 * This method returns custom value for get and $model instance for set.
	 * @param string $rawName 
	 * @param array  $arguments 
	 * @throws \Exception 
	 * @return mixed|\MvcCore\Model
	 */
	public function __call ($rawName, $arguments = array()) {
		$nameBegin = strtolower(substr($rawName, 0, 3));
		$name = substr($rawName, 3);
		if ($nameBegin == 'get' && isset($this->$name)) {
			return $this->$name;
		} else if ($nameBegin == 'set') {
			$this->$name = isset($arguments[0]) ? $arguments[0] : NULL;
			return $this;
		} else {
			throw new \Exception('['.__CLASS__."] No property with name '$name' defined.");
		}
	}

	/**
	 * Set any custom property, not necessary to previously define.
	 * @param string $name 
	 * @param mixed  $value 
	 */
	public function __set ($name, $value) {
		$this->$name = $value;
	}

	/**
	 * Get any custom property, not necessary to previously define,
	 * if property is not defined, NULL is returned.
	 * @param string $name 
	 * @return mixed
	 */
	public function __get ($name) {
		return (isset($this->$name)) ? $this->$name : null;
	}
}