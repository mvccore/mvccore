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
	 * System config relative path from app root.
	 * This value could be changed to any value at the very application start.
	 * @var string
	 */
	public static $SystemConfigPath = '/%appPath%/config.ini';

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
	 * System config object placed by default in: `"/App/config.ini"`.
	 * @var \stdClass|array|boolean
	 */
	protected static $systemConfig = NULL;

	/**
	 * Loaded configs array cache.
	 * @var array
	 */
	protected static $configsCache = [];

	/**
	 * Ini file values to convert into booleans.
	 * @var mixed
	 */
	protected static $booleanValues = [
		'yes'	=> TRUE,
		'no'	=> FALSE,
		'true'	=> TRUE,
		'false'	=> FALSE,
	];

	/**
	 * Reference to singleton instance in `\MvcCore\Application::GetInstance();`.
	 * @var \MvcCore\Application
	 */
	private static $_app;

	/**
	 * Reference to `\MvcCore\Application::GetInstance()->GetRequest()->GetAppRoot();`.
	 * @var string
	 */
	private static $_appRoot;

	/**
	 * Temporary variable used when ini file is parsed and loaded
	 * to store complete result to return.
	 * @var array|\stdClass|bool
	 */
	protected $result = FALSE;

	/**
	 * Temporary variable used when ini file is parsed and loaded,
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
	public static function IsDevelopment ($autoloadSystemConfig = FALSE) {
		return static::GetEnvironment($autoloadSystemConfig) === static::ENVIRONMENT_DEVELOPMENT;
	}

	/**
	 * Return `TRUE` if environment is `"beta"`.
	 * @param bool $autoloadSystemConfig If `TRUE`, environment will be detected by loaded system config.
	 * @return bool
	 */
	public static function IsBeta ($autoloadSystemConfig = FALSE) {
		return static::GetEnvironment($autoloadSystemConfig) === static::ENVIRONMENT_BETA;
	}

	/**
	 * Return `TRUE` if environment is `"alpha"`.
	 * @param bool $autoloadSystemConfig If `TRUE`, environment will be detected by loaded system config.
	 * @return bool
	 */
	public static function IsAlpha ($autoloadSystemConfig = FALSE) {
		return static::GetEnvironment($autoloadSystemConfig) === static::ENVIRONMENT_ALPHA;
	}

	/**
	 * Return `TRUE` if environment is `"production"`.
	 * @param bool $autoloadSystemConfig If `TRUE`, environment will be detected by loaded system config.
	 * @return bool
	 */
	public static function IsProduction ($autoloadSystemConfig = FALSE) {
		return static::GetEnvironment($autoloadSystemConfig) === static::ENVIRONMENT_PRODUCTION;
	}
}
