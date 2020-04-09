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
	 * Always called from `\MvcCore\Config::GetSystem()` before system config is read.
	 * This is place where to customize any config creation process,
	 * before it's created by MvcCore framework.
	 * @param array $data Configuration raw data.
	 * @param string $appRootRelativePath Relative config path from app root.
	 * @return \MvcCore\Config|\MvcCore\IConfig
	 */
	public static function CreateInstance (array $data = [], $appRootRelativePath = NULL) {
		/** @var $config \MvcCore\Config */
		$config = new static();
		if ($data) $config->data = & $data;
		if ($appRootRelativePath) {
			$app = self::$app ?: self::$app = \MvcCore\Application::GetInstance();
			$appRoot = self::$appRoot ?: self::$appRoot = $app->GetRequest()->GetAppRoot();
			$config->fullPath = $appRoot . '/' . str_replace(
				'%appPath%', $app->GetAppDir(), ltrim($appRootRelativePath, '/')
			);
		}
		return $config;
	}

	/**
	 * Get (optionally cached) system config INI file as `stdClass` or `array`,
	 * placed by default in: `"/App/config.ini"`.
	 * @return \MvcCore\Config|\MvcCore\IConfig|NULL
	 */
	public static function GetSystem () {
		/** @var $config \MvcCore\Config */
		$app = self::$app ?: self::$app = \MvcCore\Application::GetInstance();
		$systemConfigClass = $app->GetConfigClass();
		$appRootRelativePath = $systemConfigClass::GetSystemConfigPath();
		if (!array_key_exists($appRootRelativePath, self::$configsCache)) {
			$config = self::getConfigInstance(
				$appRootRelativePath, TRUE
			);
			if ($config) {
				$environment = $app->GetEnvironment();
				if ($environment->IsDetected())
					static::SetUpEnvironmentData($config, $environment->GetName());
			}
			self::$configsCache[$appRootRelativePath] = $config;
		}
		return self::$configsCache[$appRootRelativePath];
	}

	/**
	 * Get (optionally cached) config INI file as `stdClass` or `array`,
	 * placed relatively from application document root.
	 * @param string $appRootRelativePath Any config relative path like `'/%appPath%/website.ini'`.
	 * @return \MvcCore\Config|\MvcCore\IConfig|NULL
	 */
	public static function GetConfig ($appRootRelativePath) {
		/** @var $config \MvcCore\Config */
		if (!array_key_exists($appRootRelativePath, self::$configsCache)) {
			$app = self::$app ?: self::$app = \MvcCore\Application::GetInstance();
			$systemConfigClass = $app->GetConfigClass();
			$isSystem = $systemConfigClass::GetSystemConfigPath() === '/' . ltrim($appRootRelativePath, '/');
			$config = self::getConfigInstance(
				$appRootRelativePath, $isSystem
			);
			if ($config) {
				$environment = $app->GetEnvironment();
				if ($environment->IsDetected())
					static::SetUpEnvironmentData($config, $environment->GetName());
			}
			self::$configsCache[$appRootRelativePath] = $config;
		}
		return self::$configsCache[$appRootRelativePath];
	}

	/**
	 * Encode all data into string and store it in `\MvcCore\Config::$fullPath`.
	 * @throws \Exception Configuration data was not possible to dump or write.
	 * @return bool
	 */
	public function Save () {
		$rawContent = $this->Dump();
		if ($rawContent === FALSE)
			throw new \Exception('Configuration data was not possible to dump.');
		$app = self::$app ?: self::$app = \MvcCore\Application::GetInstance();
		$toolClass = $app->GetToolClass();
		try {
			$toolClass::SingleProcessWrite(
				$this->fullPath,
				$rawContent,
				'w',	// Open for writing only; place pointer at the beginning and truncate to zero length. If file doesn't exist, create it.
				10,		// Milliseconds to wait before next lock file existence is checked in `while()` cycle.
				5000,	// Maximum milliseconds time to wait before thrown an exception about not possible write.
				30000	// Maximum milliseconds time to consider lock file as operative or as old after some died process.
			);
		} catch (\Exception $ex) {
			throw $ex;
		}
		return TRUE;
	}

	/**
	 * Try to load and parse config file by app root relative path.
	 * If config contains system data, try to detect environment.
	 * @param string $appRootRelativePath
	 * @param bool $systemConfig
	 * @return \MvcCore\Config|\MvcCore\IConfig|bool
	 */
	protected static function getConfigInstance ($appRootRelativePath, $systemConfig = FALSE) {
		/** @var $config \MvcCore\Config */
		$app = self::$app ?: self::$app = \MvcCore\Application::GetInstance();
		$appRoot = self::$appRoot ?: self::$appRoot = $app->GetRequest()->GetAppRoot();
		$fullPath = $appRoot . '/' . str_replace(
			'%appPath%', $app->GetAppDir(), ltrim($appRootRelativePath, '/')
		);
		if (!file_exists($fullPath)) {
			$config = NULL;
		} else {
			$systemConfigClass = $app->GetConfigClass();
			$config = $systemConfigClass::CreateInstance();
			$config->system = $systemConfig;
			if (!$config->read($fullPath))
				$config = NULL;
		}
		return $config;
	}
}
