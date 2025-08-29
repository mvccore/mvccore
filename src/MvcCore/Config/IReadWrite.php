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

namespace MvcCore\Config;

interface IReadWrite {

	/**
	 * This is INTERNAL method.
	 * Return always new instance of statically called class, no singleton.
	 * Always called from `\MvcCore\Config::GetConfigSystem()` before system config is read.
	 * This is place where to customize any config creation process,
	 * before it's created by MvcCore framework.
	 * @param  array<string,array<mixed,mixed>> $mergedData
	 * Configuration data for all environments.
	 * @param  ?string                          $configFullPath
	 * Config absolute path.
	 * @return \MvcCore\Config
	 */
	public static function CreateInstance (array $mergedData = [], $configFullPath = NULL);

	/**
	 * Get (optionally cached) system config INI file as `stdClass` or `array`,
	 * placed by default in: `"/App/config.ini"`.
	 * @throws \RuntimeException
	 * @return ?\MvcCore\Config
	 */
	public static function GetConfigSystem ();

	/**
	 * Get (optionally cached) environment config INI file 
	 * as `stdClass` or `array`, placed by default in: `"/App/env.ini"`.
	 * To use separated environment config, you need to fill it's path first by: 
	 * `\MvcCore\Config::SetConfigEnvironmentPath('~/App/env.ini');`
	 * @throws \RuntimeException
	 * @return ?\MvcCore\Config
	 */
	public static function GetConfigEnvironment ();

	/**
	 * Get (optionally cached) config INI file as `stdClass` or `array`,
	 * placed relatively from application document root.
	 * @param  string $appRootRelativePath Any config relative path from application root dir like `'~/App/website.ini'`.
	 * @throws \RuntimeException
	 * @return ?\MvcCore\Config
	 */
	public static function GetConfig ($appRootRelativePath);

	/**
	 * Get (optionally cached) config INI file as `stdClass` or `array`,
	 * placed relatively from current vendor package root directory.
	 * Example: `'~/App/website.ini'`
	 * @param  string $vendorAppRootRelativePath
	 * @throws \RuntimeException
	 * @return ?\MvcCore\Config
	 */
	public static function GetConfigVendor ($vendorAppRootRelativePath);

	/**
	 * Get (optionally cached) config INI file 
	 * as `stdClass` or `array` by full path.
	 * Config type flag (advanced use):
	 * - 0 - `\MvcCore\IConfig::TYPE_COMMON`		- Any common config.
	 * - 1 - `\MvcCore\IConfig::TYPE_ENVIRONMENT`	- Environment config.
	 * - 2 - `\MvcCore\IConfig::TYPE_SYSTEM`		- System config.
	 * @param  string $configFullPath Full path to config file.
	 * @param  int    $configType
	 * @throws \RuntimeException
	 * @return ?\MvcCore\Config
	 */
	public static function GetConfigByFullPath ($configFullPath, $configType = \MvcCore\IConfig::TYPE_COMMON);
	
	/**
	 * Get absolute path for config path definition.
	 * This method is always called by config class internally.
	 * Example: `\MvcCore\Config::GetAbsoluteConfigPath('~/App/myConfig.ini');
	 * @internal
	 * @param  string $configPath   Relative from app root.
	 * @param  bool   $vendorConfig `FALSE` by default.
	 * @throws \RuntimeException
	 * @return string
	 */
	public static function GetConfigFullPath ($configPath, $vendorConfig = FALSE);

	/**
	 * Try to load and parse config file by absolute path.
	 * Config type flag (advanced use):
	 * - 0 - `\MvcCore\IConfig::TYPE_COMMON`		- Any common config.
	 * - 1 - `\MvcCore\IConfig::TYPE_ENVIRONMENT`	- Environment config.
	 * - 2 - `\MvcCore\IConfig::TYPE_SYSTEM`		- System config.
	 * @internal
	 * @param  string $configFullPath
	 * @param  string $systemConfigClass
	 * @param  int    $configType
	 * @return ?\MvcCore\Config
	 */
	public static function LoadConfig ($configFullPath, $systemConfigClass, $configType = \MvcCore\IConfig::TYPE_COMMON);
	
	/**
	 * Encode all data into string and store it in `\MvcCore\Config::$fullPath`.
	 * @throws \Exception Configuration data was not possible to dump or write.
	 * @return bool
	 */
	public function Save ();
	
}