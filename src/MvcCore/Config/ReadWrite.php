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

trait ReadWrite
{
	/**
	 * This is INTERNAL method.
	 * Return always new instance of statically called class, no singleton.
	 * Always called from `\MvcCore\Config::GetSystem()` before system config is readed.
	 * This is place where to customize any config creation process,
	 * before it's created by MvcCore framework.
	 * @param array $data Configuration raw data.
	 * @param string $appRootRelativePath Relative config path from app root.
	 * @return \MvcCore\Config
	 */
	public static function & CreateInstance (array $data = [], $appRootRelativePath = NULL) {
		$instance = new static();
		if ($data) $instance->data = & $data;
		if ($appRootRelativePath) {
			$app = self::$app ?: self::$app = & \MvcCore\Application::GetInstance();
			$appRoot = self::$appRoot ?: self::$appRoot = $app->GetRequest()->GetAppRoot();
			$instance->fullPath = $appRoot . '/' . str_replace(
				'%appPath%', $app->GetAppDir(), ltrim($appRootRelativePath, '/')
			);
		}
		return $instance;
	}

	/**
	 * Get cached singleton system config INI file as `stdClass`es and `array`s,
	 * placed by default in: `"/App/config.ini"`.
	 * @return \MvcCore\Config|bool
	 */
	public static function & GetSystem () {
		$app = self::$app ?: self::$app = & \MvcCore\Application::GetInstance();
		$systemConfigClass = $app->GetConfigClass();
		$appRootRelativePath = $systemConfigClass::GetSystemConfigPath();
		if (!isset(self::$configsCache[$appRootRelativePath])) 
			self::$configsCache[$appRootRelativePath] = & self::getConfigInstance(
				$appRootRelativePath, TRUE
			);
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
			$app = self::$app ?: self::$app = & \MvcCore\Application::GetInstance();
			$systemConfigClass = $app->GetConfigClass();
			$system = $systemConfigClass::GetSystemConfigPath() === '/' . ltrim($appRootRelativePath, '/');
			self::$configsCache[$appRootRelativePath] = & self::getConfigInstance(
				$appRootRelativePath, $system
			);
		}
		return self::$configsCache[$appRootRelativePath];
	}

	/**
	 * Encode all data into string and store it in `$this->fullPath` property.
	 * @return bool
	 */
	public function & Save () {
		$rawContent = $this->Dump();
		if ($rawContent === FALSE) return FALSE;
		$app = self::$app ?: self::$app = & \MvcCore\Application::GetInstance();
		$toolClass = $app->GetToolClass();
		$tempFullPath = tempnam($toolClass::GetTmpDir(), 'mvccore_config');
		file_put_contents($tempFullPath, $rawContent);
		$canRename = TRUE;
		clearstatcache(TRUE, $this->fullPath);
		if (file_exists($this->fullPath)) {
			$canRename = unlink($this->fullPath);
			clearstatcache(TRUE, $this->fullPath);
		}
		$success = FALSE;
		if ($canRename)
			$success = @rename($tempFullPath, $this->fullPath);
		if (!$success) {
			unlink($tempFullPath);
			clearstatcache(TRUE, $this->fullPath);
		}
		return $success;
	}

	/**
	 * Try to load and parse config file by app root relative path.
	 * If config contains system data, try to detect environment.
	 * @param string $appRootRelativePath 
	 * @param bool $systemConfig 
	 * @return \MvcCore\Config|bool
	 */
	protected static function & getConfigInstance ($appRootRelativePath, $systemConfig = FALSE) {
		$app = self::$app ?: self::$app = & \MvcCore\Application::GetInstance();
		$appRoot = self::$appRoot ?: self::$appRoot = $app->GetRequest()->GetAppRoot();
		$fullPath = $appRoot . '/' . str_replace(
			'%appPath%', $app->GetAppDir(), ltrim($appRootRelativePath, '/')
		);
		if (!file_exists($fullPath)) {
			$result = FALSE;
		} else {
			$systemConfigClass = $app->GetConfigClass();
			$result = $systemConfigClass::CreateInstance();
			if (!$result->Read($fullPath, $systemConfig)) 
				$result = FALSE;
		}
		return $result;
	}
}
