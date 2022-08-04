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
trait ReadWrite {

	/**
	 * @inheritDocs
	 * @param  array  $mergedData     Configuration data for all environments.
	 * @param  string $configFullPath Config absolute path.
	 * @return \MvcCore\Config
	 */
	public static function CreateInstance (array $mergedData = [], $configFullPath = NULL) {
		/** @var \MvcCore\Config $config */
		$config = new static();
		if ($mergedData)
			$config->mergedData = & $mergedData;
		if ($configFullPath)
			$config->fullPath = $configFullPath;
		return $config;
	}

	/**
	 * @inheritDocs
	 * @throws \RuntimeException
	 * @return \MvcCore\Config|NULL
	 */
	public static function GetSystem () {
		/** @var \MvcCore\Config $config */
		$app = self::$app ?: self::$app = \MvcCore\Application::GetInstance();
		$configClass = $app->GetConfigClass();
		$systemConfigPath = $configClass::GetSystemConfigPath();
		$systemConfigPath = str_replace('%appPath%', $app->GetAppDir(), $systemConfigPath);
		if (mb_strpos($systemConfigPath, '~/') === 0) {
			$appRoot = self::$appRoot ?: self::$appRoot = $app->GetRequest()->GetAppRoot();	
			$systemConfigPath = $appRoot . mb_substr($systemConfigPath, 1);
		}
		$toolClass = $app->GetToolClass();
		$configFullPath = $toolClass::RealPathVirtual($systemConfigPath);
		if (!array_key_exists($configFullPath, self::$configsCache)) {
			$config = $configClass::LoadConfig($configFullPath, $configClass, TRUE);
			if ($config) {
				$environment = $app->GetEnvironment();
				$doNotThrownError = func_num_args() > 0 ? func_get_arg(0) : FALSE;
				if ($environment->IsDetected()) {
					$configClass::SetUpEnvironmentData($config, $environment->GetName());
				} else if (!$doNotThrownError) {
					throw new \RuntimeException(
						"The configuration cannot be loaded until the environment is detected. ".
						"Please detect the environment first before loading configuration by: ".
						"`\MvcCore\Application::GetInstance()->GetEnvironment()->GetName()`."
					);
				}
			}
			self::$configsCache[$configFullPath] = $config;
		}
		return self::$configsCache[$configFullPath];
	}

	/**
	 * @inheritDocs
	 * @param  string $appRootRelativePath Any config relative path from application root dir like `'~/%appPath%/website.ini'`.
	 * @throws \RuntimeException
	 * @return \MvcCore\Config|NULL
	 */
	public static function GetConfig ($appRootRelativePath) {
		/** @var \MvcCore\Config $config */
		//$appRootRelativePath = ltrim($appRootRelativePath, '/');
		$app = self::$app ?: self::$app = \MvcCore\Application::GetInstance();
		$appRootRelativePath = str_replace('%appPath%', $app->GetAppDir(), $appRootRelativePath);
		if (mb_strpos($appRootRelativePath, '~/') === 0) {
			$appRoot = self::$appRoot ?: self::$appRoot = $app->GetRequest()->GetAppRoot();	
			$appRootRelativePath = $appRoot . mb_substr($appRootRelativePath, 1);
		}
		$toolClass = $app->GetToolClass();
		$configFullPath = $toolClass::RealPathVirtual($appRootRelativePath);
		$systemConfigClass = $app->GetConfigClass();
		$isSystem = $systemConfigClass::GetSystemConfigPath() === '/' . $appRootRelativePath;
		return static::GetConfigByFullPath(
			$configFullPath, $isSystem
		);
	}

	/**
	 * @inheritDocs
	 * @param  string $vendorAppRootRelativePath Any config relative path from application root dir like `'~/%appPath%/website.ini'`.
	 * @throws \RuntimeException
	 * @return \MvcCore\Config|NULL
	 */
	public static function GetVendorConfig ($vendorAppRootRelativePath) {
		/** @var \MvcCore\Config $config */
		$vendorAppRootRelativePath = ltrim($vendorAppRootRelativePath, '/');
		$app = self::$app ?: self::$app = \MvcCore\Application::GetInstance();
		$vendorAppRootRelativePath = str_replace('%appPath%', $app->GetAppDir(), $vendorAppRootRelativePath);
		if (mb_strpos($vendorAppRootRelativePath, '~/') === 0) {
			if (!$app->GetVendorAppDispatch()) throw new \RuntimeException(
				"The vendor configuration file cannot be loaded, ".
				"because dispatched main controller is not from any vendor package."
			);
			$vendorPackageRoot = $app->GetVendorAppRoot();
			$vendorAppRootRelativePath = $vendorPackageRoot . mb_substr($vendorAppRootRelativePath, 1);
		}
		$toolClass = $app->GetToolClass();
		$configFullPath = $toolClass::RealPathVirtual($vendorAppRootRelativePath);
		return static::GetConfigByFullPath($configFullPath, FALSE);
	}

	/**
	 * @inheritDocs
	 * @param  string $configFullPath Full path to config file.
	 * @param  bool   $isSystem       `TRUE` for system config, false otherwise.
	 * @throws \RuntimeException
	 * @return \MvcCore\Config|NULL
	 */
	public static function GetConfigByFullPath ($configFullPath, $isSystem = FALSE) {
		if (!array_key_exists($configFullPath, self::$configsCache)) {
			$app = self::$app ?: self::$app = \MvcCore\Application::GetInstance();
			$systemConfigClass = $app->GetConfigClass();
			$config = $systemConfigClass::LoadConfig($configFullPath, $systemConfigClass, $isSystem);
			if ($config) {
				$environment = $app->GetEnvironment();
				$doNotThrownError = func_num_args() > 2 ? func_get_arg(2) : FALSE;
				if ($environment->IsDetected()) {
					$systemConfigClass::SetUpEnvironmentData($config, $environment->GetName());
				} else if (!$doNotThrownError) {
					throw new \RuntimeException(
						"The configuration cannot be loaded until the environment is detected. ".
						"Please detect the environment first before loading configuration by: ".
						"`\MvcCore\Application::GetInstance()->GetEnvironment()->GetName()`."
					);
				}
			}
			self::$configsCache[$configFullPath] = $config;
		}
		return self::$configsCache[$configFullPath];
	}

	/**
	 * @inheritDocs
	 * @throws \Exception Configuration data was not possible to dump or write.
	 * @return bool
	 */
	public function Save () {
		$rawContent = $this->Dump();
		if ($rawContent === NULL)
			throw new \Exception('Configuration data was not possible to dump.');
		$app = self::$app ?: self::$app = \MvcCore\Application::GetInstance();
		$toolClass = $app->GetToolClass();
		try {
			$toolClass::AtomicWrite(
				$this->fullPath,
				$rawContent,
				'w',	// Open for writing only; place pointer at the beginning and truncate to zero length. If file doesn't exist, create it.
				10,		// Milliseconds to wait before next lock file existence is checked in `while()` cycle.
				5000,	// Maximum milliseconds time to wait before thrown an exception about not possible write.
				30000	// Maximum milliseconds time to consider lock file as operative or as old after some died process.
			);
		} catch (\Throwable $e) {
			throw $e;
		}
		return TRUE;
	}

	/**
	 * @inheritDocs
	 * @param  string $configFullPath
	 * @param  string $systemConfigClass
	 * @param  bool   $isSystemConfig
	 * @return \MvcCore\Config|bool
	 */
	public static function LoadConfig ($configFullPath, $systemConfigClass, $isSystemConfig = FALSE) {
		/** @var \MvcCore\Config $config */
		$config = $systemConfigClass::CreateInstance([], $configFullPath);
		if (!file_exists($configFullPath)) {
			$config = NULL;
		} else {
			$config->system = $isSystemConfig;
			if ($config->Read()) {
				$config->mergedData = [];
				$config->currentData = [];
			} else {
				$config = NULL;
			}
		}
		return $config;
	}
}
