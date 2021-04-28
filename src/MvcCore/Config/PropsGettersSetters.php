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
	 * All environments specfic data. Each key in this array is environment
	 * name. Empty key is record with common data for all environments. This
	 * collection is always used as reading semi-result content, not serialized.
	 * @var array
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
	 * @var array
	 */
	protected $mergedData = [];

	/**
	 * Current environment data merged with common environment data.
	 * This collection is always used for current request dispatching
	 * and this collection is not serialized.
	 * @var array
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
	 * If `TRUE`, config contains system data.
	 * @var bool
	 */
	protected $system = FALSE;


	/**
	 * @inheritDocs
	 * @return string
	 */
	public static function GetSystemConfigPath () {
		return static::$systemConfigPath;
	}

	/**
	 * @inheritDocs
	 * @param  string $systemConfigPath
	 * @return string
	 */
	public static function SetSystemConfigPath ($systemConfigPath) {
		return static::$systemConfigPath = $systemConfigPath;
	}

	/**
	 * @inheritDocs
	 * @param  string          $appRootRelativePath
	 * @param  \MvcCore\Config $config
	 * @return \MvcCore\Config
	 */
	public static function SetConfigCache ($appRootRelativePath, \MvcCore\IConfig $config) {
		/** @var \MvcCore\Config $config */
		return static::$configsCache[$appRootRelativePath] = $config;
	}

	/**
	 * @inheritDocs
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
	 * @inheritDocs
	 * @return string
	 */
	public function GetFullPath () {
		return $this->fullPath;
	}

	/**
	 * @inheritDocs
	 * @return int
	 */
	public function GetLastChanged () {
		return $this->lastChanged;
	}

	/**
	 * @inheritDocs
	 * @return bool
	 */
	public function IsSystem () {
		return $this->system;
	}
}
