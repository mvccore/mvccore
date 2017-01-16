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

require_once('Config.php');

abstract class MvcCore_Model
{
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
				'PDO::MYSQL_ATTR_MULTI_STATEMENTS'	=> TRUE,
				'PDO::MYSQL_ATTR_INIT_COMMAND'		=> "SET NAMES 'UTF8'",
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
	protected static $connectionIndex = 0;

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
     * @var PDO
     */
    protected $db;

	/**
	 * System config section for database under called connection index in constructor
	 * @var stdClass
	 */
	protected $cfg;

	/**
	 * Resource model class with SQL statements
	 * @var MvcCore_Model
	 */
	protected $resource;

	/**
	 * Static initialization
	 * @return void
	 */
	public static function StaticContructor () {
		if (empty(static::$configs)) {
			$cfg = MvcCore_Config::GetSystem();
			if ($cfg === FALSE) return;
			$cfgType = gettype($cfg->db);
			if ($cfgType == 'array' && isset($cfg->db['defaultDbIndex'])) {
				static::$connectionIndex = $cfg->db['defaultDbIndex'];
			} else if ($cfgType == 'object' && isset($cfg->db->defaultDbIndex)) {
				static::$connectionIndex = $cfg->db->defaultDbIndex;
			}
		}
	}

	/**
	 * Creates an instance and inits cfg, db and resource properties
	 * @param mixed $connectionIndex 
	 */
	public function __construct ($connectionIndex = -1) {
		if ($this->autoInit) $this->Init($connectionIndex);
    }

	/**
	 * Creates an instance and inits cfg, db and resource properties
	 * @param mixed $connectionIndex
	 */
	public function Init ($connectionIndex = -1) {
		if ($connectionIndex == -1) $connectionIndex = static::$connectionIndex;
		$this->cfg = static::getCfg($connectionIndex);
		$this->db = static::getDb($connectionIndex);
		$this->resource = static::getResource(array(), get_class($this));
	}

	/**
	 * Collect all model class public and inherit field values into array
	 * @param boolean $includeInheritProperties if true, include only fields from current model class and from parent classes
	 * @param boolean $publicOnly               if true, include only public model fields
	 * @return array
	 */
	protected function getValues ($includeInheritProperties = TRUE, $publicOnly = TRUE) {
		$data = array();
		$modelClassName = get_class($this);
		$classReflector = new ReflectionClass($modelClassName);
		$properties = $publicOnly ? $classReflector->getProperties(ReflectionProperty::IS_PUBLIC) : $classReflector->getProperties();
		foreach ($properties as $property) {
			if (!$includeInheritProperties && $property->class != $modelClassName) continue;
			$propertyName = $property->name;
			$data[$propertyName] = $this->$propertyName;
		}
		return $data;
	}

	/**
	 * Set up given $data items into $this instance context 
	 * as typed properties by PHP doc comments, as properties
	 * with the same names as $data array keys - case sensitive.
	 * @param array   $data                     collection with data to set up
	 * @param boolean $includeInheritProperties if true, include only fields from current model class and from parent classes
	 * @param boolean $publicOnly               if true, include only public model fields
	 * @return MvcCore_Model
	 */
	protected function setUp ($data = array(), $includeInheritProperties = TRUE, $publicOnly = TRUE) {
		$modelClassName = get_class($this);
		$classReflector = new ReflectionClass($modelClassName);
		$properties = $publicOnly ? $classReflector->getProperties(ReflectionProperty::IS_PUBLIC) : $classReflector->getProperties();
		foreach ($properties as $property) {
			if (!$includeInheritProperties && $property->class != $modelClassName) continue;
			$propertyName = $property->name;
			if (preg_match('/@var\s+([^\s]+)/', $property->getDocComment(), $matches)) {
				list(, $type) = $matches;
				settype($data[$propertyName], $type);
			}
			if (isset($data[$propertyName])) {
				$this->$propertyName =  $data[$propertyName];
			}
		}
		return $this;
	}

	/**
	 * Returns (or creates and holds) instance from local store
	 * @param mixed $arg,... unlimited OPTIONAL variables to pass into __construct() method
	 * @return App_Models_Base|mixed
	 */
	public static function GetInstance (/* $arg1, $arg2, $arg, ... */) {
		// get 'ClassName' string from this call: ClassName::GetInstance();
		$className = get_called_class();
		$args = func_get_args();
		$instanceIndex = md5($className . '_' . serialize($args));
		if (!isset(self::$instances[$instanceIndex])) {
			$reflectionClass = new ReflectionClass($className);
			$instance = $reflectionClass->newInstanceArgs($args);
			self::$instances[$instanceIndex] = $instance;
		}
		return self::$instances[$instanceIndex];
	}

	/**
	 * Returns database connection by connection index (cached by local store)
	 * or create new connection of no connection cached.
	 * @param int $connectionIndex 
	 * @return PDO
	 */
	protected static function getDb ($connectionIndex = -1) {
		if ($connectionIndex == -1) $connectionIndex = static::$connectionIndex;
		if (!isset(static::$connections[$connectionIndex])) {
			// get system config 'db' data 
			// and get predefined constructor arguments by driver value from config
			$cfg = static::getCfg($connectionIndex);
			$conArgs = (object) self::$connectionArguments[isset(self::$connectionArguments[$cfg->driver]) ? $cfg->driver:'default'];
			$connection = NULL;
			// If database is filesystem based, complete app root and extend
			// relative path in $cfg->dbname to absolute path
			if ($conArgs->fileDb) {
				$appRoot = MvcCore::GetRequest()->appRoot;
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
				$connection = new PDO($dsn, $cfg->username, $cfg->password, $conArgs->options);
			} else {
				$connection = new PDO($dsn);
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
	protected static function getCfg ($connectionIndex = -1) {
		if ($connectionIndex == -1) $connectionIndex = static::$connectionIndex;
		if (!isset(static::$configs[$connectionIndex])) {
			$cfg = MvcCore_Config::GetSystem();
			if (gettype($cfg->db) == 'array') {
				static::$configs[$connectionIndex] = $cfg->db[$connectionIndex];
			} else {
				static::$configs[$connectionIndex] = $cfg->db;
			}
        }
		return static::$configs[$connectionIndex];
	}

	/**
	 * Returns (or creates if necessary) model resource instance
	 * @param array $args values array with variables to pass into __construct() method
	 * @param string $modelClassPath
	 * @param string $resourceClassPath
	 * @return App_Models_Base(_Resource)
	 */
	protected static function getResource ($args = array(), $modelClassName = '', $resourceClassPath = '_Resource') {
		$result = NULL;
		if (!$modelClassName) $modelClassName = get_called_class();
		// do not create resource instance in resource class (if current class name doesn't end with '_Resource' substring):
		if (strpos($modelClassName, '_Resource') === FALSE) {
			$resourceClassName = $modelClassName . $resourceClassPath;
			// do not create resource instance if resource class doesn't exist:
			if (class_exists($resourceClassName)) {
				$result = call_user_func_array(array($resourceClassName, 'GetInstance'), $args);
			}
		}
		return $result;
	}

	public function __set ($name, $value) {
		$this->$name = $value;
	}

	public function __get ($name) {
		return (isset($this->$name)) ? $this->$name : null;
	}
}
MvcCore_Model::StaticContructor();