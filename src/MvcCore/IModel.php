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
 * Responsibility - static methods for connections, configuration
 *					and for active record properties manipulation.
 * - Database `\PDO` connecting by config settings.
 * - Reading `db` section configuration(s) from system `config.ini` file.
 * - Resource class with SQL queries localization, instancing and caching.
 * - Data methods for manipulating properties based on active record pattern.
 * - Meta data about properties parsing and caching.
 * - Magic methods handling.
 */
interface IModel {
	
	/**
	 * Set up instance instance properties initial values.
	 * @var int
	 */
	const PROPS_INITIAL_VALUES						= 1;


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
	 * Pass throught values with array keys conversion from underscored case
	 * into pascal case.
	 * @var int
	 */
	const PROPS_CONVERT_UNDERSCORES_TO_PASCALCASE	= 32;

	/**
	 * Pass throught values with array keys conversion from underscored case
	 * into camel case.
	 * @var int
	 */
	const PROPS_CONVERT_UNDERSCORES_TO_CAMELCASE	= 64;

	/**
	 * Pass throught values with array keys conversion from pascal case
	 * into underscored case.
	 * @var int
	 */
	const PROPS_CONVERT_PASCALCASE_TO_UNDERSCORES	= 128;

	/**
	 * Pass throught values with array keys conversion from pascal case
	 * into camel case.
	 * @var int
	 */
	const PROPS_CONVERT_PASCALCASE_TO_CAMELCASE		= 256;

	/**
	 * Pass throught values with array keys conversion from camel case
	 * into underscored case.
	 * @var int
	 */
	const PROPS_CONVERT_CAMELCASE_TO_UNDERSCORES	= 512;

	/**
	 * Pass throught values with array keys conversion from camel case
	 * into pascal case.
	 * @var int
	 */
	const PROPS_CONVERT_CAMELCASE_TO_PASCALCASE		= 1024;
	

	/**
	 * Pass throught values with array keys case insensitive.
	 * @var int
	 */
	const PROPS_CONVERT_CASE_INSENSITIVE			= 2048;


	/**
	 * Returns (or creates if necessary) model resource instance.
 	 * @param array|NULL	$args				Values array with variables to pass into resource `__construct()` method.
	 * @param string		$resourceClassPath	Automatically initialized with string replaced with `%SELF%` by `static::class` (or by `get_called_class()`).
	 * @return \MvcCore\Model
	 */
	public static function GetResource ($args = [], $resourceClassPath = '%SELF%s\Resource');

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
	 * @param \stdClass[]|array[] $configs Configuration array with `\stdClass` objects or arrays with configuration data.
	 * @param string|int $defaultConnectionName
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
	 * Collect all model class properties values into array.
	 * Result keys could be converted by any conversion flag.
	 * @param int $propsFlags All properties flags are available except flags 
	 *						  `\MvcCore\IModel::PROPS_INITIAL_VALUES` and 
	 *						  `\MvcCore\IModel::PROPS_CONVERT_CASE_INSENSITIVE`.
	 * @param bool $getNullValues If `TRUE`, include also values with `NULL`s, default - `FALSE`.
	 * @return array
	 */
	public function GetValues ($propsFlags = 0, $getNullValues = FALSE);

	/**
	 * Set up given `$data` items into `$this` instance context
	 * as typed properties by PHP types (or by PhpDocs comments in PHP < 7.4) 
	 * as properties with the same names as `$data` array keys or converted
	 * by properties flags. Case sensitivelly by default.
	 * Do not set any `$data` items, which are not declared in `$this` context.
	 * @param array $data Raw row data from database.
	 * @param int $propsFlags All properties flags are available.
	 * @return \MvcCore\Model Current `$this` context.
	 */
	public function SetUp ($data = [], $propsFlags = 0);

	/**
	 * Get touched properties from `$this` context.
	 * Touched properties are properties with different value than key 
	 * in `$this->initialValues` (initial array completed in `SetUp()` method).
	 * Result keys could be converted by any conversion flag.
	 * @param int $propsFlags All properties flags are available except flags 
	 *						  `\MvcCore\IModel::PROPS_INITIAL_VALUES` and 
	 *						  `\MvcCore\IModel::PROPS_CONVERT_CASE_INSENSITIVE`.
	 * @return array 
	 */
	public function GetTouched ($propsFlags = 0);

	/**
	 * Sets any custom property `"PropertyName"` by `\MvcCore\Model::SetPropertyName("value")`,
	 * which is not necessary to define previously or gets previously defined
	 * property `"PropertyName"` by `\MvcCore\Model::GetPropertyName();`.
	 * Throws exception if no property defined by get call
	 * or if virtual call begins with anything different from `Set` or `Get`.
	 * This method returns custom value for get and `\MvcCore\Model` instance for set.
	 * @param string $rawName
	 * @param array  $arguments
	 * @throws \InvalidArgumentException If `strtolower($rawName)` doesn't begin with `"get"` or with `"set"`.
	 * @return mixed|\MvcCore\Model
	 */
	public function __call ($rawName, $arguments = []);

	/**
	 * Set any custom property, not necessary to previously defined.
	 * @param string $name
	 * @param mixed  $value
	 * @throws \InvalidArgumentException If name is `initialValues` or any custom name in extended class.
	 * @return bool
	 */
	public function __set ($name, $value);

	/**
	 * Get any custom property, not necessary to previously defined,
	 * if property is not defined, NULL is returned.
	 * @param string $name
	 * @throws \InvalidArgumentException If name is `initialValues` or any custom name in extended class.
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
}
