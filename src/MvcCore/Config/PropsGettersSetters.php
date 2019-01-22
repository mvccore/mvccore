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
	 * Loaded configurations array cache.
	 * @var array
	 */
	protected static $configsCache = [];

	/**
	 * Name of system config root section with environments recognition configuration.
	 * @var string
	 */
	protected static $environmentsSectionName = 'environments';

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
	 * Temporary variable used when INI file is parsed and loaded
	 * to store complete result to return.
	 * @var array
	 */
	protected $data = [];

	/**
	 * Full path, where are configuration data stored.
	 * @var string|NULL
	 */
	protected $fullPath = NULL;

	/**
	 * Config file last changed UNIX timestamp.
	 * @var int|NULL
	 */
	protected $lastChanged = 0;

	/**
	 * If `TRUE`, config contains system data.
	 * @var bool
	 */
	protected $system = FALSE;

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
	 * Get system config relative path from app root.
	 * @return string
	 */
	public static function GetSystemConfigPath () {
		return static::$systemConfigPath;
	}
	
	/**
	 * Set system config relative path from app root.
	 * @param string $systemConfigPath
	 * @return string
	 */
	public static function SetSystemConfigPath ($systemConfigPath) {
		return static::$systemConfigPath = $systemConfigPath;
	}

	/**
	 * Get internal array store as reference.
	 * @return array
	 */
	public function & GetData () {
		return $this->data;
	}

	/**
	 * Full path, where are configuration data stored.
	 * @return string
	 */
	public function GetFullPath () {
		return $this->fullPath;
	}

	/**
	 * Config file last changed UNIX timestamp.
	 * @return int
	 */
	public function GetLastChanged () {
		return $this->lastChanged;
	}

	/**
	 * If `TRUE`, config contains system data.
	 * @return bool
	 */
	public function IsSystem () {
		return $this->system;
	}
}
