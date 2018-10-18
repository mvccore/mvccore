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

trait Reading
{
	/**
	 * Get cached singleton system config ini file as `stdClass`es and `array`s,
	 * placed by default in: `"/App/config.ini"`.
	 * @return \stdClass|array|boolean
	 */
	public static function & GetSystem () {
		if (self::$systemConfig === NULL) {
			$app = & \MvcCore\Application::GetInstance();
			$systemConfigClass = $app->GetConfigClass();
			$instance = $systemConfigClass::CreateInstance();
			self::$systemConfig = $instance->load(str_replace(
				'%appPath%',
				$app->GetAppDir(),
				$systemConfigClass::$SystemConfigPath
			), TRUE);
		}
		return self::$systemConfig;
	}

	/**
	 * Get cached config ini file as `stdClass`es and `array`s,
	 * placed relatively from application document root.
	 * @param string $appRootRelativePath Any config relative path like `'/%appPath%/website.ini'`.
	 * @return \stdClass|array|boolean
	 */
	public static function & GetConfig ($appRootRelativePath) {
		if (!isset(self::$configsCache[$appRootRelativePath])) {
			$app = & \MvcCore\Application::GetInstance();
			$systemConfigClass = $app->GetConfigClass();
			$instance = $systemConfigClass::CreateInstance();
			self::$configsCache[$appRootRelativePath] = $instance->load(str_replace(
				'%appPath%',
				$app->GetAppDir(),
				$appRootRelativePath
			), FALSE);
		}
		return self::$configsCache[$appRootRelativePath];
	}

	/**
	 * This is INTERNAL method.
	 * Return always new instance of staticly called class, no singleton.
	 * Always called from `\MvcCore\Config::GetSystem()` before system config is loaded.
	 * This is place where to customize any config creation process,
	 * before it's created by MvcCore framework.
	 * @return \MvcCore\Config
	 */
	public static function & CreateInstance () {
		$instance = new static();
		return $instance;
	}
}
