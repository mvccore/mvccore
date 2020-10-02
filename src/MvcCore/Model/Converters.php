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

namespace MvcCore\Model;

trait Converters
{
	/**
	 * Try to convert database value into target type.
	 * @param mixed $rawValue
	 * @param string $typeStr
	 * @return array First item is conversion boolean success, second item is converted result.
	 */
	protected static function convertToType ($rawValue, $typeStr) {
		$conversionResult = FALSE;
		$typeStr = trim($typeStr, '\\');
		if ($typeStr == 'DateTime') {
			$dateTimeFormat = 'Y-m-d H:i:s';
			if (is_numeric($rawValue)) {
				$rawValueStr = str_replace(['+','-','.'], '', strval($rawValue));
				$secData = mb_substr($rawValueStr, 0, 10);
				$dateTimeStr = date($dateTimeFormat, intval($secData));
				if (strlen($rawValueStr) > 10)
					$dateTimeStr .= '.' . mb_substr($rawValueStr, 10);
			} else {
				$dateTimeStr = strval($rawValue);
				if (strpos($dateTimeStr, '-') === FALSE) {
					$dateTimeFormat = substr($dateTimeFormat, 6);
				} else if (strpos($dateTimeStr, ':') === FALSE) {
					$dateTimeFormat = substr($dateTimeFormat, 0, 5);
				}
			}
			if (strpos($dateTimeStr, '.') !== FALSE) 
				$dateTimeFormat .= '.u';
			$dateTime = date_create_from_format($dateTimeFormat, $dateTimeStr);
			if ($dateTime !== FALSE) {
				$rawValue = $dateTime;
				$conversionResult = TRUE;
			}
		} else {
			if (settype($rawValue, $typeStr)) $conversionResult = TRUE;
		}
		return [$conversionResult, $rawValue];
	}

	/**
	 * Return protected static key conversion methods
	 * array by given conversion flag.
	 * @param int $keysConversionFlags
	 * @return \string[]
	 */
	protected static function getKeyConversionMethods ($keysConversionFlags = \MvcCore\IModel::KEYS_CONVERSION_CASE_SENSITIVE) {
		$flagsAndConversionMethods = [
			$keysConversionFlags & \MvcCore\IModel::KEYS_CONVERSION_UNDERSCORES_TO_PASCALCASE   => 'keyConversionUnderscoresToPascalcase',
			$keysConversionFlags & \MvcCore\IModel::KEYS_CONVERSION_UNDERSCORES_TO_CAMELCASE    => 'keyConversionUnderscoresToCamelcase',
			$keysConversionFlags & \MvcCore\IModel::KEYS_CONVERSION_PASCALCASE_TO_UNDERSCORES   => 'keyConversionPascalcaseToUnderscores',
			$keysConversionFlags & \MvcCore\IModel::KEYS_CONVERSION_PASCALCASE_TO_CAMELCASE     => 'keyConversionPascalcaseToCamelcase',
			$keysConversionFlags & \MvcCore\IModel::KEYS_CONVERSION_CAMELCASE_TO_UNDERSCORES    => 'keyConversionCamelcaseToUnderscores',
			$keysConversionFlags & \MvcCore\IModel::KEYS_CONVERSION_CAMELCASE_TO_PASCALCASE     => 'keyConversionCamelcaseToPascalcase',
			/*$keysConversionFlags & \MvcCore\IModel::KEYS_CONVERSION_CASE_SENSITIVE			=> NULL,*/
			$keysConversionFlags & \MvcCore\IModel::KEYS_CONVERSION_CASE_INSENSITIVE            => 'keyConversionCaseInsensitive',
		];
		unset($flagsAndConversionMethods[0]);
		return $flagsAndConversionMethods;
	}

	/**
	 * Return key proper case sensitive value by given case sensitive map.
	 * @param string $key
	 * @param string $toolsClass
	 * @param string $csKeysMap
	 * @return string
	 */
	protected static function keyConversionCaseInsensitive ($key, $toolsClass, $csKeysMap) {
		$keyPos = stripos($csKeysMap, ','.$key.',');
		if ($keyPos === FALSE) return $key;
		return substr($csKeysMap, $keyPos + 1, strlen($key));
	}

	/**
	 * Return key proper case sensitive value by given case sensitive map.
	 * @param string $key
	 * @param string $toolsClass
	 * @param string $csKeysMap
	 * @return string
	 */
	protected static function keyConversionUnderscoresToPascalcase ($key, $toolsClass, $csKeysMap) {
		return $toolsClass::GetPascalCaseFromUnderscored($key);
	}

	/**
	 * Return camel case key from underscore case key.
	 * @param string $key
	 * @param string $toolsClass
	 * @param string $csKeysMap
	 * @return string
	 */
	protected static function keyConversionUnderscoresToCamelcase ($key, $toolsClass, $csKeysMap) {
		return lcfirst($toolsClass::GetPascalCaseFromUnderscored($key));
	}

	/**
	 * Return underscore case key from pascal case key.
	 * @param string $key
	 * @param string $toolsClass
	 * @param string $csKeysMap
	 * @return string
	 */
	protected static function keyConversionPascalcaseToUnderscores ($key, $toolsClass, $csKeysMap) {
		return $toolsClass::GetUnderscoredFromPascalCase($key);
	}

	/**
	 * Return camel case key from pascal case key.
	 * @param string $key
	 * @param string $toolsClass
	 * @param string $csKeysMap
	 * @return string
	 */
	protected static function keyConversionPascalcaseToCamelcase ($key, $toolsClass, $csKeysMap) {
		return lcfirst($key);
	}

	/**
	 * Return underscore case key from camel case key.
	 * @param string $key
	 * @param string $toolsClass
	 * @param string $csKeysMap
	 * @return string
	 */
	protected static function keyConversionCamelcaseToUnderscores ($key, $toolsClass, $csKeysMap) {
		return $toolsClass::GetUnderscoredFromPascalCase(lcfirst($key));
	}

	/**
	 * Return pascal case key from camel case key.
	 * @param string $key
	 * @param string $toolsClass
	 * @param string $csKeysMap
	 * @return string
	 */
	protected static function keyConversionCamelcaseToPascalcase ($key, $toolsClass, $csKeysMap) {
		return ucfirst($key);
	}
}
