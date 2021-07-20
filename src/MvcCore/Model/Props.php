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
trait Props {

	/**
	 * `\PDO` connection arguments.
	 *
	 * If you need to reconfigure connection string for any other special
	 * `\PDO` database implementation or you specific needs, patch this array
	 * in extended application base model class in base `__construct()` method by:
	 *   `static::$connectionArguments = array_merge(static::$connectionArguments, array(...));`
	 * or by:
	 *   `static::$connectionArguments['driverName']['dsn'] = '...';`
	 *
	 * Every key in this field is driver name, so you can use usual `\PDO` drivers:
	 * - `mysql`, `sqlite`, `sqlsrv` (mssql), `firebird`, `ibm`, `informix`, `4D`
	 * Following drivers should be used with defaults, no connection args from here are necessary:
	 * - `oci`, `pgsql`, `cubrid`, `sysbase`, `dblib`
	 *
	 * Every value in this configuration field should be defined as:
	 * - `dsn`      - connection query as first `\PDO` constructor argument
	 *                with database config replacements.
	 * - `auth`     - if required to use database credentials for connecting or not.
	 * - `fileDb`   - if database if file database or not.
	 * - `options`  - any additional arguments array or empty array.
	 * @var array
	 */
	protected static $connectionArguments = [
		'cubrid'			=> [
			'dsn'		=> '{driver}:host={host};port={port};dbname={database}',
			'auth'		=> TRUE,
			'fileDb'	=> FALSE,
			'options'	=> [],
			'defaults'	=> ['port' => 33000,],
		],
		'firebird'		=> [
			'dsn'		=> '{driver}:dbname={host}:{database};charset={charset}',
			'auth'		=> TRUE,
			'fileDb'	=> TRUE,
			'options'	=> [],
			'defaults'	=> ['charset' => 'UTF-8',],
		],
		'ibm'			=> [
			'dsn'		=> 'ibm:DRIVER={IBM DB2 ODBC DRIVER};DATABASE={database};HOSTNAME={host};PORT={port};PROTOCOL={protocol};UID={user};PWD={password}',
			'auth'		=> TRUE,
			'fileDb'	=> FALSE,
			'options'	=> [],
			'defaults'	=> ['port' => 56789, 'protocol' => 'TCPIP',],
		],
		'informix'		=> [
			'dsn'		=> "{driver}:host={host}; service={service}; \ndatabase={database}; server={server}; protocol={protocol}; \nEnableScrollableCursors=1",
			'auth'		=> TRUE,
			'fileDb'	=> FALSE,
			'options'	=> [],
			'defaults'	=> ['service' => 9800, 'protocol' => 'onsoctcp',],
		],
		'mysql'			=> [
			'dsn'		=> '{driver}:host={host};dbname={database};port={port}',
			'auth'		=> TRUE,
			'fileDb'	=> FALSE,
			'options'	=> [
				'\PDO::ATTR_TIMEOUT'				=> 30,
				'\PDO::ATTR_EMULATE_PREPARES'		=> TRUE,
				'\PDO::MYSQL_ATTR_MULTI_STATEMENTS'	=> TRUE,
				'\PDO::MYSQL_ATTR_INIT_COMMAND'		=> "SET NAMES 'UTF8'",
			],
			'defaults'	=> ['port' => 3306,],
		],
		'sqlite'		=> [
			'dsn'		=> '{driver}:{database}',
			'auth'		=> FALSE,
			'fileDb'	=> TRUE,
			'options'	=> [],
			'defaults'	=> [
				'\PDO::ATTR_TIMEOUT'				=> 30,
				'\PDO::ATTR_EMULATE_PREPARES'		=> TRUE,
			],
		],
		'pgsql'		=> [
			'dsn'		=> '{driver}:host={host};port={port};dbname={database};user={user};password={password}',
			'auth'		=> TRUE,
			'fileDb'	=> FALSE,
			'options'	=> [],
			'defaults'	=> ['port' => 5432,],
		],
		'sqlsrv'		=> [
			'dsn'		=> '{driver}:Server={host};Database={database};MultipleActiveResultSets=False',
			'auth'		=> TRUE,
			'fileDb'	=> FALSE,
			'options'	=> [
				'\PDO::SQLSRV_ATTR_QUERY_TIMEOUT'	=> 30,
				'\PDO::SQLSRV_ATTR_DIRECT_QUERY'	=> FALSE,
			],
			'defaults'	=> ['port' => 1433,],
		],
		'default'		=> [
			'dsn'		=> '{driver}:host={host};dbname={database}',
			'auth'		=> TRUE,
			'fileDb'	=> FALSE,
			'options'	=> [
				'\PDO::ATTR_ERRMODE'				=> '\PDO::ERRMODE_EXCEPTION',
			],
			'defaults'	=> [],
		],
	];

	/**
	 * System configuration file database section properties names.
	 * @var array
	 */
	protected static $sysConfigProperties = [
		'sectionName'		=> 'db',				// db section root node
		'defaultName'		=> 'defaultName',		// default db connection name
		'defaultClass'		=> 'defaultClass',		// custom \PDO implementation full class name for all connections
		'defaultDebugger'	=> 'defaultDebugger',	// custom \PDO implementation full class name for all connections
		'retryAttempts'		=> 'retryAttempts',		// reconnection tries count if connection has been lost, extension required
		'retryDelay'		=> 'retryDelay',		// delay before every reconnection, extension required
		'config'			=> 'config',				// connection options key for used config values
		'name'				=> 'name',				// runtime configuration definition property for connection name
		'driver'			=> 'driver',			// connection driver
		'host'				=> 'host',				// connection host
		'port'				=> 'port',				// connection port
		'user'				=> 'user',				// connection user
		'password'			=> 'password',			// connection password
		'database'			=> 'database',			// connection database
		'options'			=> 'options',			// connection options
		'class'				=> 'class',				// custom \PDO implementation full class name for single connections
		'debugger'			=> 'debugger',			// debugger class implementing `\MvcCore\Ext\Models\Db\IDebugger`, you need to install extension `mvccore/ext-model-db-*`.
	];

	/**
	 * Default database connection name/index, in system config defined in section `db.default = name`.
	 * In extended classes - use this for connection name/index of current model if different.
	 * @var string|int|NULL
	 */
	protected static $defaultConnectionName = NULL;

	/**
	 * Default database connection class name.
	 * @var string
	 */
	protected static $defaultConnectionClass = '\\PDO';

	/**
	 * `\PDO` connections array, keyed by connection indexes from system config.
	 * @var \PDO[]
	 */
	protected static $connections = [];

	/**
	 * System config sections array with `\stdClass` objects, keyed by connection indexes.
	 * @var \stdClass[]
	 */
	protected static $configs = NULL;

	/**
	 * Originally declared internal model properties to protect their
	 * possible overwriting by `__set()` or `__get()` magic methods.
	 * Keys are properties names, values are bools, if to serialize their values
	 * or not to.
	 * @var array
	 */
	protected static $protectedProperties = [
		'initialValues'	=> FALSE,
	];

	/**
	 * Array with values initialized by `SetValues()` method.
	 * Usefull to recognize changed values bafore `Save()`.
	 * @var array
	 */
	protected $initialValues = [];
}
