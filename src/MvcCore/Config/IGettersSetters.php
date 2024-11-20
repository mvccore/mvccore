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

interface IGettersSetters {

	/**
	 * Get system config relative path from app root.
	 * @return string
	 */
	public static function GetConfigSystemPath ();

	/**
	 * Set system config relative path from app root.
	 * This value could be changed to any value at the very application start.
	 * Example: `\MvcCore\Config::SetConfigSystemPath('~/App/config.ini');`
	 * @param  string $configSystemPath
	 * @return string
	 */
	public static function SetConfigSystemPath ($configSystemPath);
	
	/**
	 * Get environment config relative path from app root.
	 * @return string
	 */
	public static function GetConfigEnvironmentPath ();

	/**
	 * Set environment config relative path from app root.
	 * This value could be changed to any value at the very application start.
	 * Example: `\MvcCore\Config::SetConfigEnvironmentPath('~/App/env.ini');`
	 * @param  string|NULL $configEnvironmentPath
	 * @return string|NULL
	 */
	public static function SetConfigEnvironmentPath ($configEnvironmentPath);

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
	 * Get config type flag:
	 * - 0 - `\MvcCore\IConfig::TYPE_COMMON`		- Any common config.
	 * - 1 - `\MvcCore\IConfig::TYPE_ENVIRONMENT`	- Environment config.
	 * - 2 - `\MvcCore\IConfig::TYPE_SYSTEM`		- System config.
	 * @return int
	 */
	public function GetType ();

}