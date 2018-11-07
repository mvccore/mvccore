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

namespace MvcCore\Config;

trait PropsGettersSetters
{
	/**
	 * Environment name. Usual values:
	 * - `"development"`
	 * - `"beta"`
	 * - `"alpha"`
	 * - `"production"`
	 * @var string|NULL
	 */
	protected static $environment = NULL;

	/**
	 * Loaded configurations array cache.
	 * @var array
	 */
	protected static $configsCache = [];

	/**
	 * Reference to singleton instance in `\MvcCore\Application::GetInstance();`.
	 * @var \MvcCore\Application
	 */
	protected static $app;

	/**
	 * Reference to `\MvcCore\Application::GetInstance()->GetRequest()->GetAppRoot();`.
	 * @var string
	 */
	protected static $appRoot;

	/**
	 * Full path, where are configuration data stored.
	 * @var string|NULL
	 */
	protected $fullPath = NULL;

	/**
	 * If `TRUE`, config contains system data.
	 * @var bool
	 */
	protected $system = FALSE;

	/**
	 * Temporary variable used when INI file is parsed and loaded
	 * to store complete result to return.
	 * @var array
	 */
	protected $data = [];

	/**
	 * Temporary variable used when INI file is parsed and loaded,
	 * to store information about final retyping. Keys are addresses
	 * into result level to be retyped or not, values are arrays.
	 * First index in values is boolean to define if result level will
	 * be retyped into `\stdClass` or not, second index in values is reference
	 * link to object retyped at the end or not.
	 * @var array
	 */
	protected $objectTypes = [];


	/**
	 * Return `TRUE` if environment is `"development"`.
	 * @param bool $autoloadSystemConfig If `TRUE`, environment will be detected by loaded system config.
	 * @return bool
	 */
	public static function IsDevelopment ($autoloadSystemConfig = TRUE) {
		return static::GetEnvironment($autoloadSystemConfig) === static::ENVIRONMENT_DEVELOPMENT;
	}

	/**
	 * Return `TRUE` if environment is `"beta"`.
	 * @param bool $autoloadSystemConfig If `TRUE`, environment will be detected by loaded system config.
	 * @return bool
	 */
	public static function IsBeta ($autoloadSystemConfig = TRUE) {
		return static::GetEnvironment($autoloadSystemConfig) === static::ENVIRONMENT_BETA;
	}

	/**
	 * Return `TRUE` if environment is `"alpha"`.
	 * @param bool $autoloadSystemConfig If `TRUE`, environment will be detected by loaded system config.
	 * @return bool
	 */
	public static function IsAlpha ($autoloadSystemConfig = TRUE) {
		return static::GetEnvironment($autoloadSystemConfig) === static::ENVIRONMENT_ALPHA;
	}

	/**
	 * Return `TRUE` if environment is `"production"`.
	 * @param bool $autoloadSystemConfig If `TRUE`, environment will be detected by loaded system config.
	 * @return bool
	 */
	public static function IsProduction ($autoloadSystemConfig = TRUE) {
		return static::GetEnvironment($autoloadSystemConfig) === static::ENVIRONMENT_PRODUCTION;
	}

	/**
	 * Get system config relative path from app root.
	 * @return string
	 */
	public static function GetSystemConfigPath () {
		return static::$systemConfigPath;
	}
	
	/**
	 * Set system config relative path from app root.
	 * @param string $systemConfigPath
	 * @return void
	 */
	public static function SetSystemConfigPath ($systemConfigPath) {
		static::$systemConfigPath = $systemConfigPath;
	}
}
