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

/**
 * Responsibility - static members for connections and by configuration,
 *					instances members for active record pattern.
 * - Reading `db` section from system `config.ini` file.
 * - Database `\PDO` connecting by config settings and index.
 * - Instance initialized values reading.
 * - Virtual calls/sets and gets handling.
 */
interface IModel {
	
	/**
	 * Set up instance instance properties initial values.
	 * @var int
	 */
	const PROPS_INIT_VALUES							= 1;


	/**
	 * Set up instance inherit properties.
	 * @var int
	 */
	const PROPS_INHERIT								= 2;


	/**
	 * Set up instance private properties.
	 * @var int
	 */
	const PROPS_PRIVATE								= 4;
	
	/**
	 * Set up instance protected properties.
	 * @var int
	 */
	const PROPS_PROTECTED							= 8;
	
	/**
	 * Set up instance public properties.
	 * @var int
	 */
	const PROPS_PUBLIC								= 16;


	/**
	 * Pass throught values with array keys in original case sensitive manner.
	 * @var int
	 */
	const PROPS_CONVERT_CASE_SENSITIVE				= 32;

	/**
	 * Pass throught values with array keys case insensitive.
	 * @var int
	 */
	const PROPS_CONVERT_CASE_INSENSITIVE			= 64;


	/**
	 * Pass throught values with array keys conversion from underscored case
	 * into pascal case.
	 * @var int
	 */
	const PROPS_CONVERT_UNDERSCORES_TO_PASCALCASE	= 128;

	/**
	 * Pass throught values with array keys conversion from underscored case
	 * into camel case.
	 * @var int
	 */
	const PROPS_CONVERT_UNDERSCORES_TO_CAMELCASE	= 256;

	/**
	 * Pass throught values with array keys conversion from pascal case
	 * into underscored case.
	 * @var int
	 */
	const PROPS_CONVERT_PASCALCASE_TO_UNDERSCORES	= 512;

	/**
	 * Pass throught values with array keys conversion from pascal case
	 * into camel case.
	 * @var int
	 */
	const PROPS_CONVERT_PASCALCASE_TO_CAMELCASE		= 1024;

	/**
	 * Pass throught values with array keys conversion from camel case
	 * into underscored case.
	 * @var int
	 */
	const PROPS_CONVERT_CAMELCASE_TO_UNDERSCORES	= 2048;

	/**
	 * Pass throught values with array keys conversion from camel case
	 * into pascal case.
	 * @var int
	 */
	const PROPS_CONVERT_CAMELCASE_TO_PASCALCASE		= 4096;



	/**
	 * TODO
	 * Collect all model class public and inherit field values into array.
	 * @param bool $includeInheritProperties If `TRUE`, include fields from current and all parent classes, if `FALSE`, include fields only from current model class, default - `TRUE`.
	 * @param bool $publicOnly			     If `TRUE`, include only public instance fields, if `FALSE`, include all instance fields, default - `TRUE`.
	 * @param bool $getNullValues			 If `TRUE`, include also values with `NULL`s, default - `FALSE`.
	 * @return array
	 */
	public function GetValues ($readingFlags = 0, $getNullValues = FALSE);

	/**
	 * TODO
	 * Set up given `$data` items into `$this` instance context
	 * as typed properties by PHP doc comments, as properties
	 * with the same names as `$data` array keys. Case sensitively by default.
	 * Do not set any `$data` items, which are not declared in `$this` context.
	 * @param array   $data						Collection with data to set up
	 * @param int	  $keysConversionFlags		`\MvcCore\IModel::PROPS_CONVERT_*` flags to process array keys conversion before set up into properties.
	 * @param bool    $completeInitialValues    Complete protected array `initialValues` to be able to compare them by calling method `GetTouched()` anytime later.
	 * @return \MvcCore\Model|\MvcCore\IModel
	 */
	public function SetUp ($data = [], $readingFlags = 0);

	/**
	 * TODO
	 * Get touched properties from initial moment called by `SetUp()` method.
	 * Get everything, what is different to `$this->initialValues` array.
	 * @param bool $includeInheritProperties If `TRUE`, include fields from current and all parent classes, if `FALSE`, include fields only from current model class, default - `TRUE`.
	 * @param bool $publicOnly			     If `TRUE`, include only public instance fields, if `FALSE`, include all instance fields, default - `TRUE`.
	 * @return array Keys are class properties names, values are changed values.
	 */
	public function GetTouched ($readingFlags = 0);

	/**
	 * Returns (or creates and holds) instance from local store.
	 * @param mixed $args,... unlimited OPTIONAL variables to pass into model `__construct()` method.
	 * @return \MvcCore\IModel
	 */
	public static function GetInstance ();

	/**
	 * Returns (or creates if necessary) model resource instance.
 	 * @param array|NULL	$args				Values array with variables to pass into resource `__construct()` method.
	 * @param string		$resourceClassPath	Automatically initialized with string replaced with `%SELF%` by `static::class` (or by `get_called_class()`).
	 * @return \MvcCore\Model|\MvcCore\IModel
	 */
	public static function GetResource (
		$args = [], $resourceClassPath = '%SELF%s\Resource'
	);

	/**
	 * Initialize `$this->config`, `$this->connection` and `$this->resource` properties.
	 * If no `$connectionName` specified by first argument, return connection
	 * config by connection name defined first in `static::$connectionName`
	 * and if there is nothing, return connection config by connection name
	 * defined in `\MvcCore\Model::$connectionName`.
	 * @param string|int|bool $args... Optional.
	 * If there is any `string` or `int`, it's used as connection name or index.
	 * If there is any `bool`, it's used as boolean to initialize resource or not.
	 * If there is no connection name or index, i't used from `static::$connectionName`.
	 * If there is not boolean, resource class is not initialized by default.
	 * @return void
	 */
	public function Init ($args = []);

	/**
	 * Return system configuration file database section properties names.
	 * @return \stdClass
	 */
	public static function GetSysConfigProperties ();

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
	public static function GetConnection ($connectionNameOrConfig = NULL, $strict = TRUE);

	/**
	 * Get all known database connection config records as indexed/named array with `\stdClass` objects.
	 * Keys in array are connection config names/indexes and `\stdClass` values are config values.
	 * @return \stdClass[]
	 */
	public static function & GetConfigs ();

	/**
	 * Set all known configuration at once, optionally set default connection name/index.
	 * Example:
	 *	`\MvcCore\Model::SetConfigs([
	 *		// connection name: 'mysql-cdcol':
	 *		'mysql-cdcol'	=> [
	 *			'driver'	=> 'mysql',		'host'		=> 'localhost',
	 *			'user'		=> 'root',		'password'	=> '1234',		'database' => 'cdcol',
	 *		],
	 *		// connection name: 'mssql-tests':
	 *		'mssql-tests'	=> [
	 *			'driver'	=> 'sqlsrv',	'host'		=> '.\SQLEXPRESS',
	 *			'user'		=> 'sa',		'password'	=> '1234',		'database' => 'tests',
	 *		]
	 *	]);`
	 * or:
	 *	`\MvcCore\Model::SetConfigs([
	 *		// connection index: 0:
	 *		[
	 *			'driver'	=> 'mysql',		'host'		=> 'localhost',
	 *			'user'		=> 'root',		'password'	=> '1234',		'database' => 'cdcol',
	 *		],
	 *		// connection index: 1:
	 *		[
	 *			'driver'	=> 'sqlsrv',	'host'		=> '.\SQLEXPRESS',
	 *			'user'		=> 'sa',		'password'	=> '1234',		'database' => 'tests',
	 *		]
	 *	]);`
	 * @param \stdClass[]|array[] $configs               Configuration array with `\stdClass` objects or arrays with configuration data.
	 * @param string|int          $defaultConnectionName
	 * @return bool
	 */
	public static function SetConfigs (array $configs = []);

	/**
	 * Returns database connection config by connection index (integer)
	 * or by connection name (string) as `\stdClass` (cached by local store).
	 * @param int|string|NULL $connectionName
	 * @return \stdClass
	 */
	public static function & GetConfig ($connectionName = NULL);

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
	public static function SetConfig (array $config = [], $connectionName = NULL);

	/**
	 * Sets any custom property `"PropertyName"` by `\MvcCore\IModel::SetPropertyName("value")`,
	 * which is not necessary to define previously or gets previously defined
	 * property `"PropertyName"` by `\MvcCore\IModel::GetPropertyName();`.
	 * Throws exception if no property defined by get call
	 * or if virtual call begins with anything different from `Set` or `Get`.
	 * This method returns custom value for get and `\MvcCore\IModel` instance for set.
	 * @param string $rawName
	 * @param array  $arguments
	 * @throws \InvalidArgumentException If `strtolower($rawName)` doesn't begin with `"get"` or with `"set"`.
	 * @return mixed|\MvcCore\Model|\MvcCore\IModel
	 */
	public function __call ($rawName, $arguments = []);

	/**
	 * Set any custom property, not necessary to previously defined.
	 * @param string $name
	 * @param mixed  $value
	 * @throws \InvalidArgumentException If name is `"autoInit" || "db" || "config" || "resource"`
	 * @return bool
	 */
	public function __set ($name, $value);

	/**
	 * Get any custom property, not necessary to previously defined,
	 * if property is not defined, NULL is returned.
	 * @param string $name
	 * @throws \InvalidArgumentException If name is `"autoInit" || "db" || "config" || "resource"`
	 * @return mixed
	 */
	public function __get ($name);

	/**
	 * Collect all properties names to serialize them by `serialize()` method.
	 * Collect all instance properties declared as private, protected and public
	 * and if there is configured in `static::$protectedProperties` anything as
	 * `TRUE` (under key by property name), also return those properties in
	 * result array.
	 * @return \string[]
	 */
	public function __sleep ();

	/**
	 * Run `$this->Init()` method if there is `$this->autoInit` property defined
	 * and if the property is `TRUE`.
	 * @return void
	 */
	public function __wakeup ();
}
