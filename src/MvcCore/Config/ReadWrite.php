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
	public static function GetConfigSystem () {
		/** @var \MvcCore\Config $config */
		$app = self::$app ?: self::$app = \MvcCore\Application::GetInstance();
		$configClass = $app->GetConfigClass();
		$configFullPath = $configClass::GetConfigFullPath(
			$configClass::GetConfigSystemPath(), FALSE
		);
		if (!array_key_exists($configFullPath, self::$configsCache)) {
			$config = $configClass::LoadConfig(
				$configFullPath, $configClass, $configClass::TYPE_SYSTEM
			);
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
	 * @throws \RuntimeException
	 * @return \MvcCore\Config|NULL
	 */
	public static function GetConfigEnvironment () {
		/** @var \MvcCore\Config $config */
		$app = self::$app ?: self::$app = \MvcCore\Application::GetInstance();
		$configClass = $app->GetConfigClass();
		$envConfigPath = $configClass::GetConfigEnvironmentPath();
		if ($envConfigPath === NULL) return NULL;
		$configFullPath = $configClass::GetConfigFullPath($envConfigPath, FALSE);
		if (!array_key_exists($configFullPath, self::$configsCache)) {
			$config = $configClass::LoadConfig(
				$configFullPath, $configClass, $configClass::TYPE_ENVIRONMENT
			);
			if ($config === NULL) {
				$environment = $app->GetEnvironment();
				$doNotThrownError = func_num_args() > 0 ? func_get_arg(0) : FALSE;
				if (!$environment->IsDetected() && !$doNotThrownError) {
					throw new \RuntimeException(
						"Environment configuration not found in path: `{$configFullPath}`."
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
		$app = self::$app ?: self::$app = \MvcCore\Application::GetInstance();
		$configClass = $app->GetConfigClass();
		$configFullPath = $configClass::GetConfigFullPath($appRootRelativePath, FALSE);
		$app = self::$app ?: self::$app = \MvcCore\Application::GetInstance();
		$systemConfigClass = $app->GetConfigClass();
		$configType = $configClass::TYPE_COMMON;
		if ($appRootRelativePath === $systemConfigClass::GetConfigEnvironmentPath()) {
			$configType = $configClass::TYPE_ENVIRONMENT;
		} else if ($appRootRelativePath === $systemConfigClass::GetConfigSystemPath()) {
			$configType = $configClass::TYPE_SYSTEM;
		}
		return $configClass::GetConfigByFullPath($configFullPath, $configType);
	}
	
	/**
	 * @inheritDocs
	 * @param  string $vendorAppRootRelativePath Any config relative path from application root dir like `'~/%appPath%/website.ini'`.
	 * @throws \RuntimeException
	 * @return \MvcCore\Config|NULL
	 */
	public static function GetConfigVendor ($vendorAppRootRelativePath) {
		/** @var \MvcCore\Config $config */
		$app = self::$app ?: self::$app = \MvcCore\Application::GetInstance();
		$configClass = $app->GetConfigClass();
		$configFullPath = $configClass::GetConfigFullPath($vendorAppRootRelativePath, TRUE);
		return $configClass::GetConfigByFullPath($configFullPath, $configClass::TYPE_VENDOR);
	}

	/**
	 * @inheritDocs
	 * @param  string $configFullPath Full path to config file.
	 * @param  int    $configType
	 * @throws \RuntimeException
	 * @return \MvcCore\Config|NULL
	 */
	public static function GetConfigByFullPath ($configFullPath, $configType = \MvcCore\IConfig::TYPE_COMMON) {
		if (!array_key_exists($configFullPath, self::$configsCache)) {
			$app = self::$app ?: self::$app = \MvcCore\Application::GetInstance();
			$systemConfigClass = $app->GetConfigClass();
			$config = $systemConfigClass::LoadConfig(
				$configFullPath, $systemConfigClass, $configType
			);
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
				15000	// Maximum milliseconds time to consider lock file as operative or as old after some died process.
			);
		} catch (\Throwable $e) {
			throw $e;
		}
		return TRUE;
	}

	/**
	 * @inheritDocs
	 * @internal
	 * @param  string $configFullPath
	 * @param  string $systemConfigClass
	 * @param  int    $configType
	 * @return \MvcCore\Config|bool
	 */
	public static function LoadConfig ($configFullPath, $systemConfigClass, $configType = \MvcCore\IConfig::TYPE_COMMON) {
		/** @var \MvcCore\Config $config */
		$config = $systemConfigClass::CreateInstance([], $configFullPath);
		if (!file_exists($configFullPath)) {
			$config = NULL;
		} else {
			$config->type = $configType;
			if ($config->Read()) {
				$config->mergedData = [];
				$config->currentData = [];
			} else {
				$config = NULL;
			}
		}
		return $config;
	}

	/**
	 * @inheritDocs
	 * @internal
	 * @param  string $configPath   Relative from app root.
	 * @param  bool   $vendorConfig `FALSE` by default.
	 * @throws \RuntimeException
	 * @return string
	 */
	public static function GetConfigFullPath ($configPath, $vendorConfig = FALSE) {
		$app = self::$app ?: self::$app = \MvcCore\Application::GetInstance();
		$configPath = str_replace('%appPath%', $app->GetAppDir(), $configPath);
		if (mb_strpos($configPath, '~/') === 0) {
			if ($vendorConfig) {
				if (!$app->GetVendorAppDispatch()) throw new \RuntimeException(
					"The vendor configuration file cannot be loaded, ".
					"because dispatched main controller is not from any vendor package."
				);
				$vendorPackageRoot = $app->GetVendorAppRoot();
				$configPath = $vendorPackageRoot . mb_substr($configPath, 1);
			} else {
				$appRoot = self::$appRoot ?: self::$appRoot = $app->GetRequest()->GetAppRoot();	
				$configPath = $appRoot . mb_substr($configPath, 1);
			}
		}
		$toolClass = $app->GetToolClass();
		$configFullPath = $toolClass::RealPathVirtual($configPath);
		return $configFullPath;
	}
}
