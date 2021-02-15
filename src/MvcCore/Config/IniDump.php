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

trait IniDump {

	/**
	 * @inheritDocs
	 * @return string
	 */
	public function Dump () {
		/** @var $this \MvcCore\Config */
		$environmentNames = array_keys($this->mergedData);
		// Split merged data into environment specific and common environment collections:
		static::dumpSplitData($this, $environmentNames);
		// Dump data into INI syntax with environment sections:
		$result = static::dumpRenderEnvData($this, $environmentNames);
		$this->envData = []; // frees memory
		return $result;
	}

	/**
	 * Split data into environment specific collections from `$config->mergedData`.
	 * @param  \MvcCore\Config $config           Config instance.
	 * @param  array           $environmentNames All detected environment names in merged configuration data.
	 * @return void
	 */
	protected static function dumpSplitData (\MvcCore\IConfig $config, array & $environmentNames) {
		$mergedDataArr = static::dumpCastToArrayRecursive($config->mergedData);
		$commonEnvDataKey = static::$commonEnvironmentDataKey;
		if (count($environmentNames) == 1) {
			$singleEnvName = $environmentNames[0];
			$config->envData[$commonEnvDataKey] = $mergedDataArr[$singleEnvName];
			return;
		}
		// Split merged data into environment-specific
		// collections and into common data collection:
		$currentMerged = new \stdClass;
		$currentSeparated = new \stdClass;
		foreach ($environmentNames as $envName) {
			$config->envData[$envName] = [];
			$currentMerged->{$envName} = & $mergedDataArr[$envName];
			$currentSeparated->{$envName} = & $config->envData[$envName];
		}
		$config->envData[$commonEnvDataKey] = [];
		$currentSeparated->{$commonEnvDataKey} = & $config->envData[$commonEnvDataKey];
		static::dumpSplitDataRecursive(
			$currentMerged, $currentSeparated, $environmentNames
		);
	}

	/**
	 * Cast any `\stdClass` in configuration structure into array recursively.
	 * @param  \stdClass|array $obj
	 * @return array
	 */
	protected static function dumpCastToArrayRecursive ($obj) {
		$array = $obj instanceof \stdClass
			? (array) $obj
			: $obj;
		foreach ($array as $key => $value)
			if (is_array($value) || $value instanceof \stdClass)
				$array[$key] = static::dumpCastToArrayRecursive($value);
		return $array;
	}

	/**
	 * Split data into nevironment specific collections from `$config->mergedData` recursively.
	 * @param  \stdClass $currentMerged    Collection with all detected environments and it's recursive level for merged collection.
	 * @param  \stdClass $currentSeparated Collection with all detected environments and it's recursive level for splited collections.
	 * @param  array     $allEnvNames      All detected environments in configuration.
	 * @return void
	 */
	protected static function dumpSplitDataRecursive (\stdClass $currentMerged, \stdClass $currentSeparated, array & $allEnvNames) {
		$commonEnvDataKey = static::$commonEnvironmentDataKey;
		// get common level keys (keys existing in all environments)
		// and specific level keys (keys existing only in some environments).
		list($allCommonKeys, $allSpecificKeys) = static::dumpSplitDataKeys(
			$currentMerged, $allEnvNames
		);
		// comparing and separating values under common level keys:
		foreach ($allCommonKeys as $commonKey) {
			$compareValues = [];
			$childrenMerged = new \stdClass;
			$childrenSeparated = new \stdClass;
			foreach ($allEnvNames as $envName) {
				$compareValue = & $currentMerged->{$envName}[$commonKey];
				$compareValues[] = & $compareValue;
				$childrenMerged->{$envName} = & $compareValue;
			}
			// Compare values in current level:
			list (
				$commonValue,
				$valuesAreEqual,
				$valuesAreScalars
			) = static::dumpSplitDataCompareValues(
				$compareValues, $allEnvNames
			);
			if ($valuesAreEqual) {
				// All environments values are equal - assign it into common values collection:
				$commonEnvDataCollection = & $currentSeparated->{$commonEnvDataKey};
				$commonEnvDataCollection[$commonKey] = $commonValue;
			} else if ($valuesAreScalars) {
				// All environments values are NOT equal - assign it into specific environments collections:
				foreach ($allEnvNames as $envName) {
					$separatedCollection = & $currentSeparated->{$envName};
					$separatedCollection[$commonKey] = $childrenMerged->{$envName};
				}
			} else {
				// Values are collections - move collections into sublevel and process recursion:
				foreach ($allEnvNames as $envName) {
					$separatedCollection = & $currentSeparated->{$envName};
					$separatedCollection[$commonKey] = [];
					$childrenSeparated->{$envName} = & $separatedCollection[$commonKey];
				}
				$commonCollection = & $currentSeparated->{$commonEnvDataKey};
				$commonCollection[$commonKey] = [];
				$childrenSeparated->{$commonEnvDataKey} = & $commonCollection[$commonKey];
				static::dumpSplitDataRecursive(
					$childrenMerged, $childrenSeparated, $allEnvNames
				);
				// If and env. collection or common collection is empty - unset it:
				foreach ($allEnvNames as $envName) {
					$separatedCollection = & $currentSeparated->{$envName};
					if (count($separatedCollection[$commonKey]) === 0)
						unset($separatedCollection[$commonKey]);
				}
				if (count($commonCollection[$commonKey]) === 0)
					unset($commonCollection[$commonKey]);
			}
		}
		// assign specific level keys:
		foreach ($allSpecificKeys as $envName => $specificKeys) {
			$separatedCollection = & $currentSeparated->{$envName};
			$mergedCollection = & $currentMerged->{$envName};
			foreach ($specificKeys as $specificKey)
				$separatedCollection[$specificKey] = $mergedCollection[$specificKey];
		}
	}

	/**
	 * Return common keys and environment specific keys for all nevironment values levels.
	 * @param  \stdClass $currentMerged    Recursive level of values from `$config->mergedData`.
	 * @param  \string[] $environmentNames Environment names found in configuration.
	 * @return array [all common environment keys, environment specific keys]
	 */
	protected static function dumpSplitDataKeys (\stdClass $currentMerged, array & $environmentNames) {
		$allEnvKeys = [];
		$allSeparatedKeys = [];
		foreach ($environmentNames as $envName) {
			if (isset($currentMerged->{$envName})) {
				$envKeys = array_keys($currentMerged->{$envName});
				$allSeparatedKeys[$envName] = $envKeys;
				$allEnvKeys = array_unique($allEnvKeys + $envKeys);
			} else {
				$allSeparatedKeys[$envName] = [];
			}
		}
		// buggy with very simple arrays:
		//$intersectArgs += array_values($allSeparatedKeys);
		$intersectArgs = [$allEnvKeys];
		foreach ($allSeparatedKeys as $allSeparatedKeysItem)
			$intersectArgs[] = $allSeparatedKeysItem;
		$allCommonKeys = call_user_func_array('array_intersect', $intersectArgs);
		$allSpecificKeys = [];
		foreach ($environmentNames as $envName)
			$allSpecificKeys[$envName] = array_values(
				array_diff($allSeparatedKeys[$envName], $allCommonKeys)
			);
		return [$allCommonKeys, $allSpecificKeys];
	}

	/**
	 * Compare given values for all environments
	 * and return specific info about comparison.
	 * @param  \mixed[]  $compareValues
	 * @param  \string[] $environmentNames Environment names found in configuration.
	 * @return array     [common env. value, boolean about if all env. values are the same, boolean about if values are scalar]
	 */
	protected static function dumpSplitDataCompareValues (& $compareValues, & $environmentNames) {
		$baseValue = $compareValues[0];
		$valuesAreEqual = TRUE;
		$valuesAreScalars = is_scalar($baseValue);
		$i = 1;
		$l = count($environmentNames);
		while ($i < $l) {
			$compareValue = & $compareValues[$i++];
			if (!$valuesAreScalars && is_scalar($compareValue))
				$valuesAreScalars = TRUE;
			if ($baseValue !== $compareValue) {
				$valuesAreEqual = FALSE;
				break;
			}
		}
		return [$baseValue, $valuesAreEqual, $valuesAreScalars];
	}

	/**
	 * Render splitted data from environment specific collections (`$config->envData`)
	 * into INI syntax with environment specific sections, optionally grouped.
	 * @param  \MvcCore\Config $config           Config instance.
	 * @param  array           $environmentNames All detected environment names in merged configuration data.
	 * @return string
	 */
	protected static function dumpRenderEnvData (\MvcCore\IConfig $config, array & $environmentNames) {
		$commonEnvDataKey = static::$commonEnvironmentDataKey;
		$commonEnvData = & $config->envData[$commonEnvDataKey];
		$renderSections = static::dumpDetectSections($config, $environmentNames);
		if ($renderSections) {
			$environmentSpecificSections = FALSE;
			$renderedSections = [];
			$allEnvSectionsNames = [];
			foreach ($environmentNames as $envName) {
				if (isset($config->mergedData[$envName])) {
					$envKeys = array_keys($config->mergedData[$envName]);
					$allEnvSectionsNames = array_unique($allEnvSectionsNames + $envKeys);
				}
			}
			foreach ($allEnvSectionsNames as $sectionName) {
				if (array_key_exists($sectionName, $commonEnvData)) {
					$sectionCommonData = $commonEnvData[$sectionName];
					$renderedSection = ['['.$sectionName.']'];
					static::dumpRenderRecursive(
						$renderedSection, $sectionCommonData, 1
					);
				} else {
					$renderedSection = [];
				}
				// try to get the same section also from another environments,
				// compare it's values if they are the same and render it:
				$envSectionsData = static::dumpGroupEnvSectionData(
					$config, $sectionName, $environmentNames
				);
				if ($envSectionsData) {
					$environmentSpecificSections = TRUE;
					foreach ($envSectionsData as $envNames => $groupedEnvSectionData) {
						$renderedSection[] = (count($renderedSection) > 0 ? PHP_EOL : '') 
							. '['.$envNames . ' > '.$sectionName.']';
						static::dumpRenderRecursive(
							$renderedSection, $groupedEnvSectionData, 1
						);
					}
				}
				$renderedSections[] = implode(PHP_EOL, $renderedSection);
			}
			$sectionsGlue = $environmentSpecificSections
				? PHP_EOL . PHP_EOL . PHP_EOL
				: PHP_EOL . PHP_EOL;
			$result = implode($sectionsGlue, $renderedSections);
		} else {
			$rawIniData = [];
			static::dumpRenderRecursive(
				$rawIniData, $commonEnvData, 0
			);
			$result = implode(PHP_EOL, $rawIniData);
		}
		return $result;
	}

	/**
	 * Detect if protected collection `$config->envData` needs sections.
	 * @param  \MvcCore\Config $config           Config instance.
	 * @param  array           $environmentNames All detected environment names in merged configuration data.
	 * @return bool
	 */
	protected static function dumpDetectSections (\MvcCore\IConfig $config, array & $environmentNames) {
		$result = NULL;
		// Check if there are any environment specific data:
		foreach ($environmentNames as $envName) {
			if (
				array_key_exists($envName, $config->envData) &&
				count($config->envData[$envName]) > 0
			) {
				// Some environment specific section found:
				$result = TRUE;
				break;
			}
		}
		if (!$result) {
			$twoLevelsDetected = FALSE;
			// Check if there is better to render sections,
			// because there are a lot data levels (but no numeric arrays in second level):
			foreach ($config->envData as $envName => $envRootData) {
				//$break1 = FALSE;
				foreach ($envRootData as $rootKey => $secondLevel) {
					if (is_numeric($rootKey)) {
						$result = FALSE;
						break 2; // section name can NOT be numeric:
					}
					if (is_array($secondLevel)) {
						$twoLevelsDetected = TRUE;
						foreach ($secondLevel as $secondLevelKey => $secondLevelValue) {
							if (is_numeric($secondLevelKey)) {
								$result = FALSE;
								break 3; // first key under section can NOT be numeric
							}
						}
					}
				}
			}
			if ($result === NULL && $twoLevelsDetected)
				$result = TRUE;
		}
		return $result;
	}

	/**
	 * Dump recursive with dot syntax any PHP object/array data into INI syntax.
	 * @param  \string[] $rawData
	 * @param  array     $data
	 * @param  int       $level
	 * @param  string    $levelKey
	 * @param  boolean   $sequentialKeys
	 * @return void
	 */
	protected static function dumpRenderRecursive (& $rawData, & $data, $level, $levelKey = '', $sequentialKeys = FALSE) {
		if (is_object($data) || is_array($data)) {
			$sequentialKeys = array_keys($data) === range(0, count($data) - 1);
			$levelKeyHasParent = mb_strlen($levelKey) > 0;
			if ($sequentialKeys) {
				if ($levelKeyHasParent)
					$levelKey .= '[';
				foreach ($data as $key => $value)
					static::dumpRenderRecursive(
						$rawData, $value, $level + 1, $levelKey, TRUE
					);
			} else {
				if ($levelKeyHasParent)
					$levelKey .= '.';
				foreach ($data as $key => $value)
					static::dumpRenderRecursive(
						$rawData, $value, $level + 1, $levelKey . $key, FALSE
					);
			}
		} else {
			$nextRow = $levelKey;
			if ($sequentialKeys)
				$nextRow .= ']';
			$nextRow .= ' = ' . static::dumpRenderScalar($data);
			$rawData[] = $nextRow;
		}
	}

	/**
	 * Dump any scalar value into INI syntax by special local static
	 * configuration array.
	 * @param  mixed $value
	 * @return string
	 */
	protected static function dumpRenderScalar ($value) {
		if (is_numeric($value)) {
			return (string) $value;
		} else if (is_bool($value)) {
			return $value ? 'true' : 'false';
		} else if ($value === NULL) {
			return 'null';
		} else {
			static $specialChars = [
				'=', '/', '.', '#', '&', '!', '?', '-', '@', "'", '"', '*', '^',
				'[', ']', '(', ')', '{', '}', '<', '>', '\n', '\r',
			];
			$valueStr = (string) $value;
			$specialCharCaught = FALSE;
			foreach ($specialChars as $specialChar) {
				if (mb_strpos($valueStr, $specialChar)) {
					$specialCharCaught = TRUE;
					break;
				}
			}
			if ($specialCharCaught) {
				return '"' . addcslashes($valueStr, '"') . '"';
			} else {
				return $valueStr;
			}
		}
	}

	/**
	 * Try to found the same configuration records accross all environment specific data collections.
	 * @param  \MvcCore\Config $config
	 * @param  string          $sectionName
	 * @param  \string[]       $environmentNames
	 * @return array
	 */
	protected static function dumpGroupEnvSectionData (\MvcCore\IConfig $config, $sectionName, $environmentNames) {
		$result = [];
		$sectionValues = [];
		foreach ($environmentNames as $envName) {
			if (array_key_exists($envName, $config->envData)) {
				$envRootData = & $config->envData[$envName];
				if (array_key_exists($sectionName, $envRootData))
					$sectionValues[$envName] = & $envRootData[$sectionName];
			}
		}
		if (!$sectionValues)
			return $result;
		$processedEnvNames = array_combine(
			array_keys($sectionValues),
			array_fill(0, count($sectionValues), FALSE)
		);
		foreach ($processedEnvNames as $envName => $processed) {
			$theSameKeys = [];
			if ($processedEnvNames[$envName]) continue;
			$envValue = & $sectionValues[$envName];
			foreach ($processedEnvNames as $nextEnvName => $nextEnvProcessed) {
				if ($nextEnvProcessed || $envName == $nextEnvName) continue;
				$nextEnvValue = $sectionValues[$nextEnvName];
				if ($envValue === $nextEnvValue) {
					$theSameKeys[] = $nextEnvName;
					$processedEnvNames[$nextEnvName] = TRUE;
				}
			}
			if ($theSameKeys) {
				array_unshift($theSameKeys, $envName);
				$resultKey = implode(',', $theSameKeys);
				$result[$resultKey] = & $envValue;
			} else {
				$result[$envName] = & $envValue;
			}
			$processedEnvNames[$envName] = TRUE;
		}
		return $result;
	}
}
