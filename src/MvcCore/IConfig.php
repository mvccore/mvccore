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
 * Responsibility - reading/writing config file(s).
 * - Config file(s) reading:
 *   - Reading any `config.ini` file by relative path.
 *   - Parsing and typing INI data into `stdClass|array` by key types or typing
 *     INI values into `int|float|bool|string` for all other detected primitives.
 * - Config file(s) writing:
 *   - Dumping `stdClass`es and `array`s into INI syntax string with
 *     all other environment records.
 *   - Storing serialized config data in single process.
 */
interface IConfig {

	/**
	 * Get system config relative path from app root.
	 * @return string
	 */
	public static function GetSystemConfigPath ();

	/**
	 * Set system config relative path from app root.
	 * @param  string $systemConfigPath
	 * @return string
	 */
	public static function SetSystemConfigPath ($systemConfigPath);

	/**
	 * Set system config relative path from app root.
	 * @param  string          $appRootRelativePath
	 * @param  \MvcCore\Config $config
	 * @return \MvcCore\Config
	 */
	public static function SetConfigCache ($appRootRelativePath, \MvcCore\IConfig $config);

	/**
	 * Clear configs memory cache by relative path from app root 
	 * or clear whole configs memory cache if `NULL` specified.
	 * @param  string|NULL $appRootRelativePath
	 * @return bool
	 */
	public static function ClearConfigCache ($appRootRelativePath = NULL);

	/**
	 * Return environment configuration data from system config. Environment
	 * configuration data are always stored under root level section `[environments]`.
	 * @param  \MvcCore\Config $config
	 * @return array|\stdClass
	 */
	public static function & GetEnvironmentDetectionData (\MvcCore\IConfig $config);

	/**
	 * Set up config with current environment data immediately after
	 * environment name is detected. This method is used INTERNALLY!
	 * @param  \MvcCore\Config $config
	 * @param  string          $environmentName
	 * @return void
	 */
	public static function SetUpEnvironmentData (\MvcCore\IConfig $config, $environmentName);

	/**
	 * This is INTERNAL method.
	 * Return always new instance of statically called class, no singleton.
	 * Always called from `\MvcCore\Config::GetSystem()` before system config is read.
	 * This is place where to customize any config creation process,
	 * before it's created by MvcCore framework.
	 * @param  array  $mergedData          Configuration data for all environments.
	 * @param  string $appRootRelativePath Relative config path from app root.
	 * @return \MvcCore\Config
	 */
	public static function CreateInstance (array $mergedData = [], $appRootRelativePath = NULL);

	/**
	 * Get (optionally cached) system config INI file as `stdClass` or `array`,
	 * placed by default in: `"/App/config.ini"`.
	 * @throws \RuntimeException
	 * @return \MvcCore\Config|NULL
	 */
	public static function GetSystem ();

	/**
	 * Get (optionally cached) config INI file as `stdClass` or `array`,
	 * placed relatively from application document root.
	 * @param  string $appRootRelativePath Any config relative path from application root dir like `'~/%appPath%/website.ini'`.
	 * @throws \RuntimeException
	 * @return \MvcCore\Config|NULL
	 */
	public static function GetConfig ($appRootRelativePath);

	/**
	 * @inheritDocs
	 * @param  string $vendorAppRootRelativePath Any config relative path from vendor package root dir like `'~/%appPath%/website.ini'`.
	 * @throws \RuntimeException
	 * @return \MvcCore\Config|NULL
	 */
	public static function GetVendorConfig ($vendorAppRootRelativePath);

	/**
	 * @inheritDocs
	 * @param  string $configFullPath Full path to config file.
	 * @param  bool   $isSystem       `TRUE` for system config, false otherwise.
	 * @throws \RuntimeException
	 * @return \MvcCore\Config|NULL
	 */
	public static function GetConfigByFullPath ($configFullPath, $isSystem = FALSE);

	/**
	 * Try to load and parse config file by absolute path.
	 * @param  string $configFullPath
	 * @param  string $systemConfigClass
	 * @param  bool   $isSystemConfig
	 * @return \MvcCore\Config|bool
	 */
	public static function LoadConfig ($configFullPath, $systemConfigClass, $isSystemConfig = FALSE);

	/**
	 * Encode all data into string and store it in `\MvcCore\Config::$fullPath`.
	 * @throws \Exception Configuration data was not possible to dump or write.
	 * @return bool
	 */
	public function Save ();

	/**
	 * Get internal array store as reference.
	 * @param  string|NULL $environmentName Return configuration data only for specific
	 *                                      environment name. If `NULL`, there are
	 *                                      returned data for current environment.
	 * @return array
	 */
	public function & GetData ($environmentName = NULL);

	/**
	 * Set whole internal array store.
	 * @param  array       $data            Data to set into configuration store(s). If second
	 *                                      param is `NULL`, there are set data for current envirnment.
	 * @param  string|NULL $environmentName Set configuration data for specific
	 *                                      environment name. If `NULL`, there are
	 *                                      set data for current environment.
	 * @return \MvcCore\Config
	 */
	public function SetData (array $data = [], $environmentName = NULL);

	/**
	 * Full path, where are configuration data stored.
	 * @return string
	 */
	public function GetFullPath ();

	/**
	 * Config file last changed UNIX timestamp.
	 * @return int
	 */
	public function GetLastChanged ();

	/**
	 * If `TRUE`, config contains system data.
	 * @return bool
	 */
	public function IsSystem ();

	/**
	 * Load config file and return `TRUE` for success or `FALSE` in failure.
	 * - Load all sections for all environment names into `$this->envData` collection.
	 * - Retype all raw string values into `float`, `int` or `boolean` types.
	 * - Retype collections into `\stdClass`, if there are no numeric keys.
	 * @return bool
	 */
	public function Read ();

	/**
	 * Dump configuration data (for all environments) into INI configuration
	 * syntax with environment specific sections and data.
	 * @return string
	 */
	public function Dump ();

	/**
	 * Get not defined property from `$this->currentData` array store,
	 * if there is nothing, return `NULL`.
	 * @param  string $key
	 * @return mixed
	 */
	public function __get ($key);

	/**
	 * Store not defined property inside `$this->currentData` array store.
	 * @param  string $key
	 * @param  mixed  $value
	 * @return mixed
	 */
	public function __set ($key, $value);

	/**
	 * Magic function triggered by: `isset($cfg->key);`.
	 * @param  string $key
	 * @return bool
	 */
	public function __isset ($key);

	/**
	 * Magic function triggered by: `unset($cfg->key);`.
	 * @param  string $key
	 * @return void
	 */
	public function __unset ($key);
}
