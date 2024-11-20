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

/**
 * @mixin \MvcCore\Config
 */
trait PropsGettersSetters {

	/**
	 * Replace filter for environment names in INI sections.
	 * @var string
	 */
	protected static $environmentNamesFilter = "#[^,_a-zA-Z0-9]#";

	/**
	 * Replace filter for INI sections names.
	 * @var string
	 */
	protected static $sectionNamesFilter = "#[^_a-zA-Z0-9]#";

	/**
	 * Loaded configurations array cache.
	 * @var array<string,\MvcCore\Config>
	 */
	protected static $configsCache = [];

	/**
	 * Reference to singleton instance in `\MvcCore\Application::GetInstance();`.
	 * @var \MvcCore\Application
	 */
	protected static $app;

	/**
	 * All environments specfic data. Each key in this array is environment
	 * name. Empty key is record with common data for all environments. This
	 * collection is always used as reading semi-result content, not serialized.
	 * @var array<mixed,mixed>
	 */
	protected $envData = [];

	/**
	 * Configuration data for all read environments (merged with common).
	 * Each key is environment name, each record is specific environment
	 * data collection with common environment data.
	 * This collection is always used as pre-computed cached content,
	 * where is necessary to have all configurations for all requested
	 * environments. Because environment on the same machine could be
	 * changed only by client specific ip. This collection is serialized.
	 * @var array<string,array<mixed,mixed>>
	 */
	protected $mergedData = [];

	/**
	 * Current environment data merged with common environment data.
	 * This collection is always used for current request dispatching
	 * and this collection is not serialized.
	 * @var array<mixed,mixed>
	 */
	protected $currentData = [];

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
	 * Config type flag:
	 * - 0 - `\MvcCore\IConfig::TYPE_COMMON`		- Any common config.
	 * - 1 - `\MvcCore\IConfig::TYPE_ENVIRONMENT`	- Environment config.
	 * - 2 - `\MvcCore\IConfig::TYPE_SYSTEM`		- System config.
	 * @var int
	 */
	protected $type = self::TYPE_COMMON;


	/**
	 * @inheritDoc
	 * @return string
	 */
	public static function GetConfigSystemPath () {
		return static::$configSystemPath;
	}

	/**
	 * @inheritDoc
	 * @param  string $configSystemPath
	 * @return string
	 */
	public static function SetConfigSystemPath ($configSystemPath) {
		return static::$configSystemPath = $configSystemPath;
	}
	
	/**
	 * @inheritDoc
	 * @return string|NULL
	 */
	public static function GetConfigEnvironmentPath () {
		return static::$configEnvironmentPath;
	}

	/**
	 * @inheritDoc
	 * @param  string|NULL $configEnvironmentPath
	 * @return string|NULL
	 */
	public static function SetConfigEnvironmentPath ($configEnvironmentPath) {
		return static::$configEnvironmentPath = $configEnvironmentPath;
	}

	/**
	 * @inheritDoc
	 * @param  string          $appRootRelativePath
	 * @param  \MvcCore\Config $config
	 * @return \MvcCore\Config
	 */
	public static function SetConfigCache ($appRootRelativePath, \MvcCore\IConfig $config) {
		/** @var \MvcCore\Config $config */
		return static::$configsCache[$appRootRelativePath] = $config;
	}

	/**
	 * @inheritDoc
	 * @param  string|NULL $appRootRelativePath
	 * @return bool
	 */
	public static function ClearConfigCache ($appRootRelativePath = NULL) {
		if ($appRootRelativePath === NULL) {
			static::$configsCache = [];
		} else {
			unset(static::$configsCache[$appRootRelativePath]);
		}
		return TRUE;
	}

	/**
	 * @inheritDoc
	 * @return string
	 */
	public function GetFullPath () {
		return $this->fullPath;
	}

	/**
	 * @inheritDoc
	 * @return int
	 */
	public function GetLastChanged () {
		return $this->lastChanged;
	}

	/**
	 * @inheritDoc
	 * @return int
	 */
	public function GetType () {
		return $this->type;
	}
}
