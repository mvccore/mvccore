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

interface IConfig {
	
	/**
	 * Return system configuration file database section properties names.
	 * @return \stdClass
	 */
	public static function GetSysConfigProperties ();
	
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

}