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

trait ReadingWriting
{
	/**
	 * This is INTERNAL method.
	 * Return always new instance of statically called class, no singleton.
	 * Always called from `\MvcCore\Config::GetSystem()` before system config is readed.
	 * This is place where to customize any config creation process,
	 * before it's created by MvcCore framework.
	 * @return \MvcCore\Config
	 */
	public static function & CreateInstance () {
		$instance = new static();
		return $instance;
	}

	/**
	 * Get cached singleton system config INI file as `stdClass`es and `array`s,
	 * placed by default in: `"/App/config.ini"`.
	 * @return \MvcCore\Config|bool
	 */
	public static function & GetSystem () {
		if (self::$app === NULL) 
			self::$app = & \MvcCore\Application::GetInstance();
		$app = & self::$app;
		$systemConfigClass = $app->GetConfigClass();
		$appRootRelativePath = $systemConfigClass::$SystemConfigPath;
		if (!isset(self::$configsCache[$appRootRelativePath])) 
			self::$configsCache[$appRootRelativePath] = & self::getConfigInstance($appRootRelativePath, TRUE);
		return self::$configsCache[$appRootRelativePath];
	}

	/**
	 * Get cached config INI file as `stdClass`es and `array`s,
	 * placed relatively from application document root.
	 * @param string $appRootRelativePath Any config relative path like `'/%appPath%/website.ini'`.
	 * @return \MvcCore\Config|bool
	 */
	public static function & GetConfig ($appRootRelativePath) {
		if (!isset(self::$configsCache[$appRootRelativePath])) {
			if (self::$app === NULL) 
				self::$app = & \MvcCore\Application::GetInstance();
			$app = & self::$app;
			$systemConfigClass = $app->GetConfigClass();
			$system = $systemConfigClass::$SystemConfigPath === $appRootRelativePath;
			self::$configsCache[$appRootRelativePath] = & self::getConfigInstance($appRootRelativePath, $system);
		}
		return self::$configsCache[$appRootRelativePath];
	}

	/**
	 * Try to load and parse config file by app root relative path.
	 * If config contains system data, try to detect environment.
	 * @param string $appRootRelativePath 
	 * @param bool $systemConfig 
	 * @return \MvcCore\Config|bool
	 */
	protected static function & getConfigInstance ($appRootRelativePath, $systemConfig = FALSE) {
		if (self::$app === NULL) 
			self::$app = & \MvcCore\Application::GetInstance();
		$app = & self::$app;
		if (self::$appRoot === NULL) 
			self::$appRoot = $app->GetRequest()->GetAppRoot();
		$appRoot = & self::$appRoot;
		$fullPath = $appRoot . str_replace(
			'%appPath%', $app->GetAppDir(), $appRootRelativePath
		);
		if (!file_exists($fullPath)) {
			$result = FALSE;
		} else {
			$systemConfigClass = $app->GetConfigClass();
			$result = $systemConfigClass::CreateInstance();
			$result->system = $systemConfig;
			$result->fullPath = $fullPath;
			if (!$result->read(FALSE)) 
				$result = FALSE;
		}
		return $result;
	}

	/**
	 * Encode all data into string and store it in `$this->fullPath` property.
	 * @return bool
	 */
	public function & Save () {
		return $this->write();
	}

	/**
	 * Get not defined property from `$this->data` array store, if there is nothing, return `NULL`.
	 * @param string $key 
	 * @return mixed
	 */
	public function __get ($key) {
		if (array_key_exists($key, $this->data))
			return $this->data[$key];
		return NULL;
	}

	/**
	 * Store not defined property inside `$this->data` array store.
	 * @param string $key 
	 * @return void
	 */
	public function __set ($key, $value) {
		$this->data[$key] = $value;
	}
}
