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

namespace MvcCore;

/**
 * Responsibility - static methods for connections, configuration
 *                  and for active record properties manipulation.
 * - Database `\PDO` connecting by config settings.
 * - Reading `db` section configuration(s) from system `config.ini` file.
 * - Resource class with SQL queries localization, instancing and caching.
 * - Data methods for manipulating properties based on active record pattern.
 * - Meta data about properties parsing and caching.
 * - Magic methods handling.
 * @phpstan-type RawValue int|float|string|bool|\DateTime|\DateTimeImmutable|array<mixed,mixed>|object
 * @phpstan-type ParserArgs array<int|string,mixed>|NULL
 */
interface IModel extends \MvcCore\Model\IConstants {
	
	/**
	 * Returns (or creates if necessary) model resource instance.
	 * Common resource instance is stored all the time in static store
	 * under key from resource full class name and constructor arguments.
	 * @param  array<int,mixed>|NULL $args
	 * Values array with variables to pass into resource `__construct()` method.
	 * If `NULL`, recource class will be created without `__construct()` method call.
	 * @param  string                $classPath
	 * Relative namespace path to resource class. It could contains `.` or `..`
	 * to traverse over namespaces (directories) and it could contains `{self}` 
	 * keyword, which is automatically replaced with current class name.
	 * @throws \InvalidArgumentException Class `{$resourceClassName}` doesn't exist.
	 * @return \MvcCore\IModel
	 */
	public static function GetCommonResource ($args = NULL, $classPath = '{self}s\CommonResource');

	/**
	 * Return system configuration file database section properties names.
	 * @return \stdClass
	 */
	public static function GetSysConfigProperties ();

	/**
	 * Returns `\PDO` database connection by connection name/index,
	 * usually by system config values (cached by local store)
	 * or create new connection if no connection cached.
	 * @param  string|int|array<string,mixed>|\stdClass|NULL $connectionNameOrConfig
	 * @param  bool                                          $strict
	 * If `TRUE` and no connection under given name or given
	 * index found, exception is thrown. `TRUE` by default.
	 * If `FALSE`, there could be returned connection by
	 * first available configuration.
	 * @throws \InvalidArgumentException|\PDOException|\Throwable
	 * @return \PDO
	 */
	public static function GetConnection ($connectionNameOrConfig = NULL, $strict = TRUE);

	/**
	 * Set up connection instance into connection store to be available for all other model classes.
	 * @param  string|int $connectionName
	 * @param  \PDO       $connection
	 * @return \PDO
	 */
	public static function SetConnection ($connectionName, $connection);
	
	/**
	 * Return `TRUE` if any database connection exists under given index.
	 * @param  string|int $connectionName
	 * @return bool
	 */
	public static function HasConnection ($connectionName);
	
	/**
	 * Unsets connection from the global connections store and calls `Close()` 
	 * method (if exists) on connection instance to close the connection.
	 * @param  string|int|NULL $connectionName
	 * @throws \InvalidArgumentException
	 * @return bool
	 */
	public static function CloseConnection ($connectionName = NULL);

	/**
	 * Get all known database connection config records as indexed/named array with `\stdClass` objects.
	 * Keys in array are connection config names/indexes and `\stdClass` values are config values.
	 * @return \stdClass[]
	 */
	public static function & GetConfigs ();

	/**
	 * Set all known configuration at once, optionally set default connection name/index.
	 * Example:
	 * ````
	 *   \MvcCore\Model::SetConfigs([
	 *       // connection name: 'mysql-cdcol':
	 *       'mysql-cdcol' => [
	 *           'driver'  => 'mysql',  'host'     => 'localhost',
	 *           'user'    => 'root',   'password' => '1234',         'database' => 'cdcol',
	 *       ],
	 *       // connection name: 'mssql-tests':
	 *       'mssql-tests' => [
	 *           'driver'  => 'sqlsrv', 'host'     => '.\SQLEXPRESS',
	 *           'user'    => 'sa',     'password' => '1234',         'database' => 'tests',
	 *       ]
	 *   ]);
	 * ````
	 * or:
	 * ````
	 *   \MvcCore\Model::SetConfigs([
	 *       [   // connection index: 0:
	 *           'driver' => 'mysql',   'host'     => 'localhost',
	 *           'user'   => 'root',    'password' => '1234',         'database' => 'cdcol',
	 *       ],
	 *       [   // connection index: 1:
	 *           'driver' => 'sqlsrv',  'host'     => '.\SQLEXPRESS',
	 *           'user'   => 'sa',      'password' => '1234',         'database' => 'tests',
	 *       ]
	 *   ]);
	 * ````
	 * @param  array<int|string,int|string|array<string,mixed>> $configs
	 * Configuration array with `\stdClass` objects or arrays with configuration data.
	 * @param  string|int|NULL                                  $defaultConnectionName
	 * @return bool
	 */
	public static function SetConfigs (array $configs = [], $defaultConnectionName = NULL);

	/**
	 * Returns database connection config by connection index (integer)
	 * or by connection name (string) as `\stdClass` (cached by local store).
	 * @param  int|string|NULL $connectionName
	 * @return \stdClass|NULL
	 */
	public static function & GetConfig ($connectionName = NULL);

	/**
	 * Set configuration array with optional connection name/index.
	 * If there is array key `name` or `index` inside config `array` or `\stdClass`,
	 * it's value is used for connection name or index or there is no param `$connectionName` defined.
	 * Example:
	 * ````
	 *   \MvcCore\Model::SetConfig([
	 *       'name'   => 'mysql-cdcol',
	 *       'driver' => 'mysql',      'host'        => 'localhost',
	 *       'user'   => 'root',       'password'    => '1234',      'database' => 'cdcol',
	 *   ]);
	 * ````
	 * or:
	 * ````
	 *   \MvcCore\Model::SetConfig([
	 *       'index'  => 0,
	 *       'driver' => 'mysql',      'host'        => 'localhost',
	 *       'user'   => 'root',       'password'    => '1234',      'database' => 'cdcol',
	 *   ]);
	 * ````
	 * or:
	 * ````
	 *   \MvcCore\Model::SetConfig([
	 *       'driver' => 'mysql',      'host'        => 'localhost',
	 *       'user'   => 'root',       'password'    => '1234',      'database' => 'cdcol',
	 *   ], 'mysql-cdcol');
	 * ````
	 * or:
	 * ````
	 *   \MvcCore\Model::SetConfig([
	 *       'driver' => 'mysql',      'host'        => 'localhost',
	 *       'user'   => 'root',       'password'    => '1234',      'database' => 'cdcol',
	 *   ], 0);
	 * ````
	 * @param  array<string,mixed> $config
	 * @param  string|int|NULL     $connectionName
	 * @return string|int
	 */
	public static function SetConfig (array $config = [], $connectionName = NULL);

	/**
	 * Return cached array of arrays about properties in current class to not create
	 * and parse reflection objects every time. Be carefull, meta data are in lowest 
	 * level as it could be - only in array types, to serialize/unserialize them 
	 * into/from cache as fast as possible instead of serializing PHP objects. 
	 * 
	 * Every key in array is property name, every value is array with metadata:
	 * - `0`	`boolean`	`TRUE` for private property.
	 * - `1'	`boolean`	`TRUE` to allow `NULL` values.
	 * - `2`	`string[]`	Property types from code or from doc comments or empty array.
	 * 
	 * Possible reading flags:
	 *  - `\MvcCore\IModel::PROPS_INHERIT`
	 *  - `\MvcCore\IModel::PROPS_PRIVATE`
	 *  - `\MvcCore\IModel::PROPS_PROTECTED`
	 *  - `\MvcCore\IModel::PROPS_PUBLIC`
	 * @param  int        $propsFlags
	 * @param  array<int> $additionalMaps Compatible format for extension `mvccore/ext-model-db`.
	 * @return array<string,array{0:bool,1:bool,2:array<string>}>
	 */
	public static function GetMetaData ($propsFlags = 0, $additionalMaps = []);

	/**
	 * Compare two values. Supported types are:
	 *  - NULL
	 *  - scalar (int, float, string, bool)
	 *  - array
	 *  - \stdClass
	 *  - \DateTimeInterface, \DateInterval, \DateTimeZone, \DatePeriod
	 *  - resource (only by `intval($value1) == intval($value2)`)
	 *  - object instances (only by `===` comparison)
	 * @param  mixed $value1 
	 * @param  mixed $value2 
	 * @return bool
	 */
	public static function IsEqual ($value1, $value2);

	/**
	 * Try to convert raw database value into first type in target types.
	 * @param  RawValue      $rawValue
	 * @param  array<string> $typesString
	 * @param  array<mixed>  $parserArgs
	 * This argument is used in extended model only.
	 * @return RawValue Converted result.
	 */
	public static function ParseToTypes ($rawValue, $typesString, $parserArgs = []);
	
		
	/**
	 * Returns (or creates if doesn`t exist) model resource instance.
	 * Resource instance is stored in protected instance property `resource`.
	 * @param  array<int,mixed>|NULL $args
	 * Values array with variables to pass into resource `__construct()` method.
	 * If `NULL`, recource class will be created without `__construct()` method call.
	 * @param  string                $classPath
	 * Relative namespace path to resource class. It could contains `.` or `..`
	 * to traverse over namespaces (directories) and it could contains `{self}` 
	 * keyword, which is automatically replaced with current class name.
	 * @throws \InvalidArgumentException Class `{$resourceClassName}` doesn't exist.
	 * @return \MvcCore\IModel
	 */
	public function GetResource ($args = NULL, $classPath = '{self}s\Resource');

	/**
	 * Collect all model class properties values into array.
	 * Result keys could be converted by any conversion flag.
	 * @param  int  $propsFlags    All properties flags are available except flags: 
	 *                             - `\MvcCore\IModel::PROPS_INITIAL_VALUES`,
	 *                             - `\MvcCore\IModel::PROPS_CONVERT_CASE_INSENSITIVE`,
	 *                             - `\MvcCore\IModel::PROPS_NAMES_BY_*`.
	 * @param  bool $getNullValues If `TRUE`, include also values with `NULL`s, 
	 *                             `FALSE` by default.
	 * @throws \InvalidArgumentException
	 * @return array<string,mixed>
	 */
	public function GetValues ($propsFlags = 0, $getNullValues = FALSE);

	/**
	 * Set up given `$data` items into `$this` instance context
	 * as typed properties by PHP types (or by PhpDocs comments in PHP < 7.4) 
	 * as properties with the same names as `$data` array keys or converted
	 * by properties flags. Case sensitivelly by default.
	 * Any `$data` items, which are not declared in `$this` context are 
	 * initialized by  `__set()` method.
	 * @param  array<string,mixed> $data
	 * Raw data from database (row) or from form fields.
	 * @param  int                 $propsFlags
	 * All properties flags are available.
	 * @throws \InvalidArgumentException
	 * @return \MvcCore\Model Current `$this` context.
	 */
	public function SetValues ($data = [], $propsFlags = 0);

	/**
	 * Get touched properties from `$this` context.
	 * Touched properties are properties with different value than value under 
	 * property name key in `$this->initialValues` (initial array is optionally 
	 * completed in `SetValues()` method). Result keys could be converted by any 
	 * conversion flag.
	 * @param  int $propsFlags
	 * All properties flags are available except flags: 
	 * - `\MvcCore\IModel::PROPS_INITIAL_VALUES`,
	 * - `\MvcCore\IModel::PROPS_CONVERT_CASE_INSENSITIVE`.
	 * @throws \InvalidArgumentException
	 * @return array<string,mixed>
	 */
	public function GetTouched ($propsFlags = 0);

	/**
	 * Return original initial values completed in model creation.
	 * @param  int $propsFlags
	 * All properties flags are available except flags: 
	 * - `\MvcCore\IModel::PROPS_INITIAL_VALUES`,
	 * - `\MvcCore\IModel::PROPS_CONVERT_CASE_INSENSITIVE`,
	 * - `\MvcCore\IModel::PROPS_SET_DEFINED_ONLY`.
	 * @return array<string,mixed>
	 */
	public function GetInitialValues ($propsFlags = 0);

	/**
	 * Sets any custom property `"PropertyName"` by `\MvcCore\Model::SetPropertyName("value")`,
	 * which is not necessary to define previously or gets previously defined
	 * property `"PropertyName"` by `\MvcCore\Model::GetPropertyName();`.
	 * Throws exception if no property defined by get call
	 * or if virtual call begins with anything different from `Set` or `Get`.
	 * This method returns custom value for get and `\MvcCore\Model` instance for set.
	 * @param  string            $rawName
	 * @param  array<int,mixed>  $arguments
	 * @throws \InvalidArgumentException If `strtolower($rawName)` doesn't begin with `"get"` or with `"set"`.
	 * @return mixed|\MvcCore\Model
	 */
	public function __call ($rawName, $arguments = []);

	/**
	 * Set any custom property, not necessary to previously defined.
	 * @param  string $name
	 * @param  mixed  $value
	 * @throws \InvalidArgumentException If name is `initialValues` or any custom name in extended class.
	 * @return void
	 */
	public function __set ($name, $value);

	/**
	 * Get any custom property, not necessary to previously defined,
	 * if property is not defined, NULL is returned.
	 * @param  string $name
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
	 * @return array<string>
	 */
	public function __sleep ();

	/**
	 * Returns data which can be serialized by `json_encode()`, 
	 * which is a value of any type other than a resource.
	 * Possible reading flags:
	 *  - `\MvcCore\IModel::PROPS_INHERIT`	- default
	 *  - `\MvcCore\IModel::PROPS_PRIVATE`
	 *  - `\MvcCore\IModel::PROPS_PROTECTED`- default
	 *  - `\MvcCore\IModel::PROPS_PUBLIC`
	 * @param  int $propsFlags Flags used by default:
	 * `\MvcCore\IModel::PROPS_PROTECTED | \MvcCore\IModel::PROPS_INHERIT`
	 * @return mixed
	 */
	public function jsonSerialize ($propsFlags = 0);
}