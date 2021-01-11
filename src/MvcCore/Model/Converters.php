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

trait Converters {

	/**
	 * Try to convert raw database value into first type in target types.
	 * @param mixed $rawValue
	 * @param \string[] $typesString
	 * @return mixed Converted result.
	 */
	protected static function convertToTypes ($rawValue, $typesString) {
		$targetTypeValue = NULL;
		$value = $rawValue;
		foreach ($typesString as $typeString) {
			if (substr($typeString, -2, 2) === '[]') {
				if (!is_array($value)) {
					$value = trim(strval($rawValue));
					$value = $value === '' ? [] : explode(',', $value);
				}
				$arrayItemTypeString = substr($typeString, 0, strlen($typeString) - 2);
				$targetTypeValue = [];
				$conversionResult = TRUE;
				foreach ($value as $key => $item) {
					list(
						$conversionResultLocal, $targetTypeValueLocal
					) = static::convertToType($item, $arrayItemTypeString);
					if ($conversionResultLocal) {
						$targetTypeValue[$key] = $targetTypeValueLocal;
					} else {
						$conversionResult = FALSE;
						break;
					}
				}
			} else {
				list(
					$conversionResult, $targetTypeValue
				) = static::convertToType($rawValue, $typeString);
			}
			if ($conversionResult) {
				$value = $targetTypeValue;
				break;
			}
		}
		return $value;
	}

	/**
	 * Try to convert database value into target type.
	 * @param mixed $rawValue
	 * @param string $typeStr
	 * @return array First item is conversion boolean success, second item is converted result.
	 */
	protected static function convertToType ($rawValue, $typeStr) {
		$conversionResult = FALSE;
		$typeStr = trim($typeStr, '\\');
		if ($typeStr == 'DateTime' && !($rawValue instanceof \DateTime)) {
			$dateTime = static::convertToDateTime($rawValue, 'Y-m-d H:i:s');
			if ($dateTime instanceof \DateTime) {
				$rawValue = $dateTime;
				$conversionResult = TRUE;
			}
		} else {
			// bool, int, float, string, array, object, null:
			if (settype($rawValue, $typeStr)) 
				$conversionResult = TRUE;
		}
		return [$conversionResult, $rawValue];
	}

	/**
	 * Convert int, float or string value into \DateTime.
	 * @param int|float|string|NULL $rawValue 
	 * @param string $formatArgs 
	 * @return \DateTime|bool
	 */
	protected static function convertToDateTime ($rawValue, $formatArgs) {
		if (is_numeric($rawValue)) {
			$rawValueStr = str_replace(['+','-','.'], '', (string) $rawValue);
			$secData = mb_substr($rawValueStr, 0, 10);
			$dateTimeStr = date($formatArgs, intval($secData));
			if (strlen($rawValueStr) > 10)
				$dateTimeStr .= '.' . mb_substr($rawValueStr, 10);
		} else {
			$dateTimeStr = (string) $rawValue;
			if (strpos($dateTimeStr, '-') === FALSE) {
				$formatArgs = substr($formatArgs, 6);
			} else if (strpos($dateTimeStr, ':') === FALSE) {
				$formatArgs = substr($formatArgs, 0, 5);
			}
		}
		if (strpos($dateTimeStr, '.') !== FALSE) 
			$formatArgs .= '.u';
		return \date_create_from_format($formatArgs, $dateTimeStr);
	}

	/**
	 * Return protected static conversion method by given conversion flag
	 * to convert database column name into property name or back.
	 * @param int $keysConversionFlags
	 * @throws \InvalidArgumentException
	 * @return string|NULL
	 */
	protected static function getKeyConversionMethod ($keysConversionFlags = 0) {
		$flagsAndConversionMethods = [
			0																		=> NULL,
			$keysConversionFlags & static::PROPS_CONVERT_UNDERSCORES_TO_PASCALCASE	=> 'propsConvertUnderscoresToPascalcase',
			$keysConversionFlags & static::PROPS_CONVERT_UNDERSCORES_TO_CAMELCASE	=> 'propsConvertUnderscoresToCamelcase',
			$keysConversionFlags & static::PROPS_CONVERT_PASCALCASE_TO_UNDERSCORES	=> 'propsConvertPascalcaseToUnderscores',
			$keysConversionFlags & static::PROPS_CONVERT_PASCALCASE_TO_CAMELCASE	=> 'propsConvertPascalcaseToCamelcase',
			$keysConversionFlags & static::PROPS_CONVERT_CAMELCASE_TO_UNDERSCORES	=> 'propsConvertCamelcaseToUnderscores',
			$keysConversionFlags & static::PROPS_CONVERT_CAMELCASE_TO_PASCALCASE	=> 'propsConvertCamelcaseToPascalcase',
			$keysConversionFlags & static::PROPS_CONVERT_CASE_INSENSITIVE			=> 'propsConvertCaseInsensitive',
		];
		unset($flagsAndConversionMethods[0]);
		$count = count($flagsAndConversionMethods);
		if ($count === 0) {
			return NULL;
		} else if ($count === 1) {
			return current($flagsAndConversionMethods);
		} else {
			$nextFreeFlag = static::PROPS_CONVERT_CASE_INSENSITIVE * 2;
			throw new \InvalidArgumentException(
				"Database column name to property conversion (or back) could NOT ".
				"be defined by multiple conversion flags. Use single conversion ".
				"flag or any custom flag (equal o higher than {$nextFreeFlag}) ".
				"to custom conversion method instead."
			);
		}
	}

	/**
	 * Return key proper case sensitive value by given case sensitive map.
	 * @param string $key
	 * @param string $toolsClass
	 * @param string $csKeysMap
	 * @return string
	 */
	protected static function propsConvertCaseInsensitive ($key, $toolsClass, $csKeysMap) {
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
	protected static function propsConvertUnderscoresToPascalcase ($key, $toolsClass, $csKeysMap) {
		return $toolsClass::GetPascalCaseFromUnderscored($key);
	}

	/**
	 * Return camel case key from underscore case key.
	 * @param string $key
	 * @param string $toolsClass
	 * @param string $csKeysMap
	 * @return string
	 */
	protected static function propsConvertUnderscoresToCamelcase ($key, $toolsClass, $csKeysMap) {
		return lcfirst($toolsClass::GetPascalCaseFromUnderscored($key));
	}

	/**
	 * Return underscore case key from pascal case key.
	 * @param string $key
	 * @param string $toolsClass
	 * @param string $csKeysMap
	 * @return string
	 */
	protected static function propsConvertPascalcaseToUnderscores ($key, $toolsClass, $csKeysMap) {
		return $toolsClass::GetUnderscoredFromPascalCase($key);
	}

	/**
	 * Return camel case key from pascal case key.
	 * @param string $key
	 * @param string $toolsClass
	 * @param string $csKeysMap
	 * @return string
	 */
	protected static function propsConvertPascalcaseToCamelcase ($key, $toolsClass, $csKeysMap) {
		return lcfirst($key);
	}

	/**
	 * Return underscore case key from camel case key.
	 * @param string $key
	 * @param string $toolsClass
	 * @param string $csKeysMap
	 * @return string
	 */
	protected static function propsConvertCamelcaseToUnderscores ($key, $toolsClass, $csKeysMap) {
		return $toolsClass::GetUnderscoredFromPascalCase(lcfirst($key));
	}

	/**
	 * Return pascal case key from camel case key.
	 * @param string $key
	 * @param string $toolsClass
	 * @param string $csKeysMap
	 * @return string
	 */
	protected static function propsConvertCamelcaseToPascalcase ($key, $toolsClass, $csKeysMap) {
		return ucfirst($key);
	}
}
