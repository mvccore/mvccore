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

namespace MvcCore\Environment;

trait PropsGettersSetters {

	/**
	 * If `TRUE` (by default), environment will be detected by loaded system config.
	 * @var boolean
	 */
	protected static $detectionBySystemConfig = TRUE;

	/**
	 * Reference to singleton instance in `\MvcCore\Application::GetInstance();`.
	 * @var \MvcCore\Application
	 */
	protected static $app;

	/**
	 * Environment name. Usual values:
	 * - `"dev"`			- Development environment.
	 * - `"beta"`			- Common team testing environment.
	 * - `"alpha"`			- Release testing environment.
	 * - `"production"`		- Release environment.
	 * @var string|NULL
	 */
	protected $name = NULL;

	/**
	 * Boolean values for all detected environments.
	 * @var array
	 */
	protected $values = [];


	/**
	 * @inheritDocs
	 * @return \string[]
	 */
	public static function GetAllNames () {
		return [
			\MvcCore\IEnvironment::DEVELOPMENT,
			\MvcCore\IEnvironment::ALPHA,
			\MvcCore\IEnvironment::BETA,
			\MvcCore\IEnvironment::PRODUCTION
		];
	}

	/**
	 * @inheritDocs
	 * @param boolean $detectionBySystemConfig `TRUE` by default.
	 * @return boolean
	 */
	public static function SetDetectionBySystemConfig ($detectionBySystemConfig = TRUE) {
		return static::$detectionBySystemConfig = $detectionBySystemConfig;
	}

	/**
	 * @inheritDocs
	 * @return boolean
	 */
	public static function GetDetectionBySystemConfig () {
		return static::$detectionBySystemConfig;
	}

	/**
	 * @inheritDocs
	 * @return bool
	 */
	public function IsDevelopment () {
		/** @var $this \MvcCore\Environment */
		if ($this->name === NULL) $this->GetName();
		return $this->values[\MvcCore\IEnvironment::DEVELOPMENT];
	}

	/**
	 * @inheritDocs
	 * @return bool
	 */
	public function IsBeta () {
		/** @var $this \MvcCore\Environment */
		if ($this->name === NULL) $this->GetName();
		return $this->values[\MvcCore\IEnvironment::BETA];
	}

	/**
	 * @inheritDocs
	 * @return bool
	 */
	public function IsAlpha () {
		/** @var $this \MvcCore\Environment */
		if ($this->name === NULL) $this->GetName();
		return $this->values[\MvcCore\IEnvironment::ALPHA];
	}

	/**
	 * @inheritDocs
	 * @return bool
	 */
	public function IsProduction () {
		/** @var $this \MvcCore\Environment */
		if ($this->name === NULL) $this->GetName();
		return $this->values[\MvcCore\IEnvironment::PRODUCTION];
	}

	/**
	 * @inheritDocs
	 * @return bool
	 */
	public function IsDetected () {
		/** @var $this \MvcCore\Environment */
		return $this->name !== NULL;
	}

	/**
	 * @inheritDocs
	 * @param string $name
	 * @return string
	 */
	public function SetName ($name = \MvcCore\IEnvironment::PRODUCTION) {
		/** @var $this \MvcCore\Environment */
		$this->name = $name;
		foreach (static::GetAllNames() as $envName)
			$this->values[$envName] = FALSE;
		$this->values[$this->name] = TRUE;
		return $name;
	}

	/**
	 * @inheritDocs
	 * @return string
	 */
	public function GetName () {
		/** @var $this \MvcCore\Environment */
		if ($this->name === NULL) {
			if (static::$detectionBySystemConfig) {
				$app = self::$app ?: (self::$app = \MvcCore\Application::GetInstance());
				$configClass = $app->GetConfigClass();
				$sysConfig = $configClass::GetSystem(TRUE);
				if ($sysConfig) {
					$envDetectionData = & $configClass::GetEnvironmentDetectionData($sysConfig);
					$this->name = static::DetectBySystemConfig((array) $envDetectionData);
					// Set up system config current environment data collection for first time:
					$configClass::SetUpEnvironmentData($sysConfig, $this->name);
				}
				// if not recognized by system config, recognize only
				// by simplest way - by server and client IP:
				if ($this->name === NULL)
					$this->name = static::DetectByIps();
			} else {
				$this->name = static::DetectByIps();
			}
			foreach (static::GetAllNames() as $envName)
				$this->values[$envName] = FALSE;
			$this->values[$this->name] = TRUE;
		}
		return $this->name;
	}
}