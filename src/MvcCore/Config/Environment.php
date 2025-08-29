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
trait Environment {

	/**
	 * Name of system config root section with environments recognition configuration.
	 * @var string
	 */
	protected static $systemEnvironmentsSectionName = 'environments';

	/**
	 * Key value for configuration data common for all environments.
	 * @var string
	 */
	protected static $commonEnvironmentDataKey = '';

	/**
	 * @inheritDoc
	 * @param  \MvcCore\Config $config
	 * @return array<mixed,mixed>|\stdClass
	 */
	public static function & GetEnvironmentDetectionData (\MvcCore\IConfig $config) {
		$envConfData = [];
		if (
			($config->type & \MvcCore\IConfig::TYPE_ENVIRONMENT) == 0 &&
			($config->type & \MvcCore\IConfig::TYPE_SYSTEM) == 0
		)
			return $envConfData;
		$commonEnvDataKey = static::$commonEnvironmentDataKey;
		$someEnvironmentData = [];
		if (count($config->mergedData) > 0) {
			// Config is probably loaded from cache:
			$firstEnvironmentName = key($config->mergedData);
			$someEnvironmentData = & $config->mergedData[$firstEnvironmentName];
		} else if (isset($config->envData[$commonEnvDataKey])) {
			// Config is read and loaded from HDD:
			$someEnvironmentData = & $config->envData[$commonEnvDataKey];
		}
		if ($someEnvironmentData) {
			if (($config->GetType() & \MvcCore\IConfig::TYPE_ENVIRONMENT) != 0) {
				$envConfData = & $someEnvironmentData;
			} else {
				$sysEnvSectionName = static::$systemEnvironmentsSectionName;
				if (is_object($someEnvironmentData)) {
					if (isset($someEnvironmentData->{$sysEnvSectionName}))
						$envConfData = & $someEnvironmentData->{$sysEnvSectionName};
				} else /*if (is_array($someEnvironmentData))*/ {
					if (isset($someEnvironmentData[$sysEnvSectionName]))
						$envConfData = & $someEnvironmentData[$sysEnvSectionName];
				}
			}
		}
		return $envConfData;
	}

	/**
	 * @inheritDoc
	 * @param  \MvcCore\Config $config
	 * @param  string          $environmentName
	 * @return void
	 */
	public static function SetUpEnvironmentData (\MvcCore\IConfig $config, $environmentName) {
		// Serialized into cache is always only `$config->mergedData` collection.
		if (array_key_exists($environmentName, $config->mergedData)) {
			// 1. If there are data in `$config->mergedData` (config from cache), complete
			// `$config->currentData` collection from `$config->mergedData[$environmentName]`.
			$config->currentData = $config->mergedData[$environmentName];
		} else {
			// 2. If there are not data in `$config->mergedData` (loaded config), complete
			// `$config->currentData` collection from `$config->envData[$environmentName]`.
			static::mergeEnvironmentData(
				$config, $config->currentData, $environmentName
			);
			$config->mergedData[$environmentName] = & $config->currentData;
		}
		$config->envData = []; // frees memory.
	}

	/**
	 * @inheritDoc
	 * @param  ?string     $environmentName
	 * Return configuration data only for specific
	 * environment name. If `NULL`, there are
	 * returned data for current environment.
	 * @return array<mixed,mixed>
	 */
	public function & GetData ($environmentName = NULL) {
		$result = [];
		if ($environmentName === NULL) {
			if ($this->currentData) {
				// most often usage:
				$result = & $this->currentData;
			} else {
				$app = self::$app ?: (self::$app = \MvcCore\Application::GetInstance()); // @phpstan-ignore-line
				$currentEnvName = $app->GetEnvironment()->GetName();
				if ($currentEnvName && array_key_exists($currentEnvName, $this->mergedData))
					$result = & $this->mergedData[$currentEnvName];
			}
		} else if (array_key_exists($environmentName, $this->mergedData)) {
			$result = & $this->mergedData[$environmentName];
		} else {
			static::mergeEnvironmentData(
				$this, $this->mergedData[$environmentName], $environmentName
			);
			$result = & $this->mergedData[$environmentName];
		}
		return $result;
	}

	/**
	 * @inheritDoc
	 * @param  array<mixed,mixed> $data
	 * Data to set into configuration store(s). If second
	 * param is `NULL`, there are set data for current envirnment.
	 * @param  ?string            $environmentName
	 * Set configuration data for specific
	 * environment name. If `NULL`, there are
	 * set data for current environment.
	 * @return \MvcCore\Config
	 */
	public function SetData (array $data = [], $environmentName = NULL) {
		$app = self::$app ?: (self::$app = \MvcCore\Application::GetInstance()); // @phpstan-ignore-line
		$currentEnvName = $app->GetEnvironment()->GetName();
		if ($environmentName === NULL) {
			$this->currentData = & $data;
			$this->mergedData[$currentEnvName] = & $data;
		} else {
			if ($environmentName === $currentEnvName)
				$this->currentData = & $data;
			$this->mergedData[$environmentName] = & $data;
		}
		return $this;
	}

	/**
	 * Merge data from `$config->envData` array (-> common end env. specific records)
	 * into given result collection.
	 * @param  \MvcCore\IConfig   $config
	 * @param  array<mixed,mixed> $resultCollection
	 * @param  string             $environmentName
	 * @return void
	 */
	protected static function mergeEnvironmentData (\MvcCore\IConfig $config, & $resultCollection, $environmentName) {
		$commonEnvDataKey = static::$commonEnvironmentDataKey;
		$envCommonData = [];
		$envSpecificData = [];
		if (isset($config->envData[$commonEnvDataKey]))
			$envCommonData = & $config->envData[$commonEnvDataKey];
		if (isset($config->envData[$environmentName]))
			$envSpecificData = & $config->envData[$environmentName];
		$envCommonDataEmpty = count((array) $envCommonData) === 0;
		$envSpecificDataEmpty = count((array) $envSpecificData) === 0;
		if ($envCommonDataEmpty) {
			$resultCollection = $envSpecificData;
		} else if ($envSpecificDataEmpty) {
			$resultCollection = $envCommonData;
		} else {
			$commonDataType = gettype($envCommonData);
			$specificDataType = gettype($envSpecificData);
			if ($commonDataType != $specificDataType)
				settype($envSpecificData, $commonDataType);
			$resultCollection = static::mergeRecursive(
				$envCommonData, $envSpecificData
			);
		}
	}

	/**
	 * Recursively merge two `\stdClass|array` objects and returns a resulting object.
	 * @param  \stdClass|array<mixed,mixed> $commonEnvData   The base object.
	 * @param  \stdClass|array<mixed,mixed> $specificEnvData The merge object.
	 * @return \stdClass|array<mixed,mixed> The merged object
	 */
	protected static function mergeRecursive ($commonEnvData, $specificEnvData) {
		$commonEnvDataClone = function_exists('igbinary_serialize') // clone
			? igbinary_unserialize(igbinary_serialize($commonEnvData))
			: unserialize(serialize($commonEnvData));
		self::_mergeArraysOrObjectsRecursive($commonEnvDataClone, $specificEnvData);
		return $commonEnvDataClone;
	}

	/**
	 * Recursively merge two `\stdClass|array` objects and returns a resulting object.
	 * First object will be changed.
	 * @param  \stdClass|array<mixed,mixed> $commonEnvData   The base object.
	 * @param  \stdClass|array<mixed,mixed> $specificEnvData The merge object.
	 * @return void
	 */
	private static function _mergeArraysOrObjectsRecursive (& $commonEnvData, & $specificEnvData) {
		if (is_object($specificEnvData)) {
			$specificEnvKeys = array_keys(get_object_vars($specificEnvData));
			foreach ($specificEnvKeys as $key) {
				$commonEnvValue = & $commonEnvData->{$key};
				$specificEnvValue = & $specificEnvData->{$key};
				if (!is_scalar($specificEnvValue) && $specificEnvValue !== NULL) {
					if (!isset($commonEnvData->{$key})) {
						$commonEnvData->{$key} = $specificEnvValue;
					} else {
						self::_mergeArraysOrObjectsRecursive(
							$commonEnvValue, $specificEnvValue
						);
					}
				} else {
					$commonEnvValue = $specificEnvValue;
				}
			}
		} else if (is_array($specificEnvData)) {
			$specificEnvKeys = array_keys($specificEnvData);
			foreach ($specificEnvKeys as $key) {
				$commonEnvValue = & $commonEnvData[$key];
				$specificEnvValue = & $specificEnvData[$key];
				if (!is_scalar($specificEnvValue) && $specificEnvValue !== NULL) {
					if ($commonEnvValue === NULL) {
						$commonEnvData[$key] = $specificEnvValue;
					} else {
						self::_mergeArraysOrObjectsRecursive(
							$commonEnvValue, $specificEnvValue
						);
					}
				} else {
					$commonEnvValue = $specificEnvValue;
				}
			}
		}
	}
}
