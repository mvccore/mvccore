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
	 * @param mixed $connectionIndex 
	 * @return PDO
	 */
	protected static function getDb ($connectionIndex = -1) {
		if ($connectionIndex == -1) $connectionIndex = static::$connectionIndex;
		if (!isset(static::$connections[$connectionIndex])) {
			$cfg = static::getCfg($connectionIndex);
			$connection = NULL;
			if ($cfg->driver == 'mssql') {
				$connection = new PDO("sqlsrv:Server={$cfg->server};Database={$cfg->dbname}", $cfg->username, $cfg->password);
			} else if ($cfg->driver == 'mysql') {
				$options = array();
				if (defined('PDO::MYSQL_ATTR_MULTI_STATEMENTS')) $options[PDO::MYSQL_ATTR_MULTI_STATEMENTS] = TRUE;
				if (defined('PDO::MYSQL_ATTR_INIT_COMMAND')) $options[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES 'UTF8'";
				$connection = new PDO("mysql:host={$cfg->server};dbname={$cfg->dbname}", $cfg->username, $cfg->password, $options);
			}
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