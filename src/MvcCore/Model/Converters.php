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
	 * Convert `bool`(s), `array`(s), `\DateTimeInterface` or `\DateInterval` 
	 * value(s) into proper database (`scalar`) value if necessary.
	 * @param bool|int|float|string|\DateTimeInterface|\DateInterval|\bool[]|\int[]|\float[]|\string[]||\DateTimeInterface[]|\\DateInterval[]|NULL $value 
	 * @param array $formatArgs 
	 * @return int|float|string|NULL
	 */
	protected static function convertToScalar ($value, $formatArgs = []) {
		if (is_bool($value)) {
			return $value ? 1 : 0 ;
		} else if (is_iterable($value)) {
			$items = [];
			foreach ($value as $item)
				if ($item !== NULL)
					$items[] = static::convertToScalar($item, $formatArgs);
			if (count($items) === 0) return NULL;
			return implode(',', $items);
		} else if ($value instanceof \DateTimeInterface) {
			$formatArgsCount = count($formatArgs);
			if ($formatArgsCount > 0) {
				$formatMask = $formatArgs[0];
				if (mb_substr($formatMask, 0, 1) === '+')
					$formatMask = mb_substr($formatMask, 1);
				if ($formatArgsCount > 2) {
					$targetType = $formatArgs[2];
					if ($targetType === 'int') {
						$formatMask = 'U';
					} else if ($targetType === 'float') {
						$formatMask = 'U.u';
					}
				}
				return $value->format($formatMask);
			}
			return $value->format('Y-m-d H:i:s');
		} else if ($value instanceof \DateInterval) {
			$formatArgsCount = count($formatArgs);
			if ($formatArgsCount > 0) {
				$formatMask = $formatArgs[0];
				if ($formatArgsCount > 2) {
					$targetType = $formatArgs[2];
					if ($targetType === 'int') {
						return intval(round(
							static::convertIntervalToFloat($value)
						));
					} else if ($targetType === 'float') {
						return static::convertIntervalToFloat($value);
					}
				}
				return $value->format($formatMask);
			}
			return static::convertIntervalToFloat($value);
		} else {
			return $value;
		}
	}

	/**
	 * Convert date interval to total microseconds float.
	 * @param \DateInterval $interval 
	 * @return float
	 */
	protected static function convertIntervalToFloat ($interval) {
		$result = floatval(
			($interval->days * 86400) + 
			($interval->h * 3600) + 
			($interval->i * 60) + 
			($interval->s)
		);
		if (PHP_VERSION_ID >= 70100)
			$result += $interval->f;
		return $result;
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
			$keysConversionFlags & static::PROPS_CONVERT_UNDERSCORES_TO_PASCALCASE	=> 'convertPropUnderscoresToPascalcase',
			$keysConversionFlags & static::PROPS_CONVERT_UNDERSCORES_TO_CAMELCASE	=> 'convertPropUnderscoresToCamelcase',
			$keysConversionFlags & static::PROPS_CONVERT_PASCALCASE_TO_UNDERSCORES	=> 'convertPropPascalcaseToUnderscores',
			$keysConversionFlags & static::PROPS_CONVERT_PASCALCASE_TO_CAMELCASE	=> 'convertPropPascalcaseToCamelcase',
			$keysConversionFlags & static::PROPS_CONVERT_CAMELCASE_TO_UNDERSCORES	=> 'convertPropCamelcaseToUnderscores',
			$keysConversionFlags & static::PROPS_CONVERT_CAMELCASE_TO_PASCALCASE	=> 'convertPropCamelcaseToPascalcase',
			$keysConversionFlags & static::PROPS_CONVERT_CASE_INSENSITIVE			=> 'convertPropCaseInsensitive',
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
	protected static function convertPropCaseInsensitive ($key, $toolsClass, $csKeysMap) {
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
	protected static function convertPropUnderscoresToPascalcase ($key, $toolsClass, $csKeysMap) {
		return $toolsClass::GetPascalCaseFromUnderscored($key);
	}

	/**
	 * Return camel case key from underscore case key.
	 * @param string $key
	 * @param string $toolsClass
	 * @param string $csKeysMap
	 * @return string
	 */
	protected static function convertPropUnderscoresToCamelcase ($key, $toolsClass, $csKeysMap) {
		return lcfirst($toolsClass::GetPascalCaseFromUnderscored($key));
	}

	/**
	 * Return underscore case key from pascal case key.
	 * @param string $key
	 * @param string $toolsClass
	 * @param string $csKeysMap
	 * @return string
	 */
	protected static function convertPropPascalcaseToUnderscores ($key, $toolsClass, $csKeysMap) {
		return $toolsClass::GetUnderscoredFromPascalCase($key);
	}

	/**
	 * Return camel case key from pascal case key.
	 * @param string $key
	 * @param string $toolsClass
	 * @param string $csKeysMap
	 * @return string
	 */
	protected static function convertPropPascalcaseToCamelcase ($key, $toolsClass, $csKeysMap) {
		return lcfirst($key);
	}

	/**
	 * Return underscore case key from camel case key.
	 * @param string $key
	 * @param string $toolsClass
	 * @param string $csKeysMap
	 * @return string
	 */
	protected static function convertPropCamelcaseToUnderscores ($key, $toolsClass, $csKeysMap) {
		return $toolsClass::GetUnderscoredFromPascalCase(lcfirst($key));
	}

	/**
	 * Return pascal case key from camel case key.
	 * @param string $key
	 * @param string $toolsClass
	 * @param string $csKeysMap
	 * @return string
	 */
	protected static function convertPropCamelcaseToPascalcase ($key, $toolsClass, $csKeysMap) {
		return ucfirst($key);
	}
}
