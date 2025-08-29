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

/**
 * @mixin \MvcCore\Environment
 */
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
	 * - `"alpha"`			- Pre-release testing environment.
	 * - `"beta"`			- Release testing environment.
	 * - `"gamma"`			- Release environment in debug mode or in any other special mode.
	 * - `"production"`		- Release environment.
	 * @var ?string    
	 */
	protected $name = NULL;

	/**
	 * Boolean values for all detected environments.
	 * @var array<string,bool>
	 */
	protected $values = [];


	/**
	 * @inheritDoc
	 * @return array<string>
	 */
	public static function GetAllNames () {
		return [
			\MvcCore\IEnvironment::DEVELOPMENT,
			\MvcCore\IEnvironment::ALPHA,
			\MvcCore\IEnvironment::BETA,
			\MvcCore\IEnvironment::GAMMA,
			\MvcCore\IEnvironment::PRODUCTION
		];
	}

	/**
	 * @inheritDoc
	 * @param  boolean $detectionBySystemConfig `TRUE` by default.
	 * @return boolean
	 */
	public static function SetDetectionBySystemConfig ($detectionBySystemConfig = TRUE) {
		return static::$detectionBySystemConfig = $detectionBySystemConfig;
	}

	/**
	 * @inheritDoc
	 * @return boolean
	 */
	public static function GetDetectionBySystemConfig () {
		return static::$detectionBySystemConfig;
	}

	/**
	 * @inheritDoc
	 * @return bool
	 */
	public function IsDevelopment () {
		if ($this->name === NULL) $this->GetName();
		return $this->values[\MvcCore\IEnvironment::DEVELOPMENT];
	}

	/**
	 * @inheritDoc
	 * @return bool
	 */
	public function IsAlpha () {
		if ($this->name === NULL) $this->GetName();
		return $this->values[\MvcCore\IEnvironment::ALPHA];
	}

	/**
	 * @inheritDoc
	 * @return bool
	 */
	public function IsBeta () {
		if ($this->name === NULL) $this->GetName();
		return $this->values[\MvcCore\IEnvironment::BETA];
	}

	/**
	 * @inheritDoc
	 * @return bool
	 */
	public function IsGamma () {
		if ($this->name === NULL) $this->GetName();
		return $this->values[\MvcCore\IEnvironment::GAMMA];
	}

	/**
	 * @inheritDoc
	 * @return bool
	 */
	public function IsProduction () {
		if ($this->name === NULL) $this->GetName();
		return $this->values[\MvcCore\IEnvironment::PRODUCTION];
	}

	/**
	 * @inheritDoc
	 * @return bool
	 */
	public function IsDetected () {
		return $this->name !== NULL;
	}

	/**
	 * @inheritDoc
	 * @param  string $name
	 * @return string
	 */
	public function SetName ($name = \MvcCore\IEnvironment::PRODUCTION) {
		$this->name = $name;
		foreach (static::GetAllNames() as $envName)
			$this->values[$envName] = FALSE;
		$this->values[$this->name] = TRUE;
		return $name;
	}

	/**
	 * @inheritDoc
	 * @return string
	 */
	public function GetName () {
		if ($this->name === NULL) {
			if (static::$detectionBySystemConfig) {
				$app = self::$app ?: (self::$app = \MvcCore\Application::GetInstance()); /** @phpstan-ignore-line */
				$configClass = $app->GetConfigClass();
				$envConfig = $configClass::GetConfigEnvironment(TRUE);
				if ($envConfig) {
					$envDetectionData = & $configClass::GetEnvironmentDetectionData($envConfig);
					$this->name = static::DetectBySystemConfig((array) $envDetectionData);
				} else {
					$sysConfig = $configClass::GetConfigSystem(TRUE);
					if ($sysConfig) {
						$envDetectionData = & $configClass::GetEnvironmentDetectionData($sysConfig);
						$this->name = static::DetectBySystemConfig((array) $envDetectionData);
						// Set up system config current environment data collection for first time:
						$configClass::SetUpEnvironmentData($sysConfig, $this->name);
					}
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