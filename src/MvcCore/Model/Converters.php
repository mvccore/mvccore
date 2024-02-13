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

namespace MvcCore\Model;

/**
 * @mixin \MvcCore\Model
 */
trait Converters {

	/**
	 * Convert `int`(s), `float`(s), `bool`(s), `string`(s), `array`(s), 
	 * `\DateTimeInterface`(s) or `\DateInterval`(s) or any other value(s)
	 * into proper database (`scalar`) value if necessary.
	 * @param  string                                                                                                                             $propName
	 * @param  bool|int|float|string|\DateTimeInterface|\DateInterval|\bool[]|\int[]|\float[]|\string[]|\DateTimeInterface[]|\DateInterval[]|NULL $value 
	 * @param  array                                                                                                                              $parserArgs 
	 * @return int|string|NULL
	 */
	protected static function convertToScalar ($propName, $value, $parserArgs = []) {
		if (is_int($value)) {
			return static::convertToScalarInt($propName, $value, $parserArgs);
		} else if (is_float($value)) {
			return static::convertToScalarFloat($propName, $value, $parserArgs);
		} else if (is_bool($value)) {
			return static::convertToScalarBool($propName, $value, $parserArgs);
		} else if (is_string($value)) {
			return static::convertToScalarString($propName, $value, $parserArgs);
		} else if (is_array($value) || $value instanceof \Traversable) { // `is_iterable()`
			return static::convertToScalarArray($propName, $value, $parserArgs);
		} else if ($value instanceof \DateTime || $value instanceof \DateTimeImmutable) { // PHP 5.4 compatible
			return static::convertToScalarDateTime($propName, $value, $parserArgs);
		} else if ($value instanceof \DateInterval) {
			return static::convertToScalarDateInterval($propName, $value, $parserArgs);
		} else {
			return static::convertToScalarOther($propName, $value, $parserArgs);
		}
	}
	
	/**
	 * Convert integer into database scalar value.
	 * @param  string $propName
	 * @param  int    $value 
	 * @param  array  $parserArgs 
	 * @return int
	 */
	protected static function convertToScalarInt ($propName, $value, $parserArgs = []) {
		return $value;
	}
	
	/**
	 * Convert float into database scalar value.
	 * @param  string $propName
	 * @param  float  $value 
	 * @param  array  $parserArgs 
	 * @return string
	 */
	protected static function convertToScalarFloat ($propName, $value, $parserArgs = []) {
		if (is_array($parserArgs) && count($parserArgs) > 0) 
			$value = call_user_func_array('round', array_merge([$value], $parserArgs));
		$valueStr = strtolower((string) $value);
		if (strpos($valueStr, 'e') === FALSE) 
			return $valueStr;
		$floatPrecision = self::$floatPrecision ?: (
			self::$floatPrecision = (@ini_get('precision') ? intval(ini_get('precision')) : 14)
		);
		list(, $fractStr) = explode('.', number_format($value, $floatPrecision, '.', ''));
		return number_format($value, strlen(rtrim($fractStr, '0')), '.', '');
	}
	
	/**
	 * Convert bool into database scalar value.
	 * @param  string $propName
	 * @param  bool   $value 
	 * @param  array  $parserArgs 
	 * @return int
	 */
	protected static function convertToScalarBool ($propName, $value, $parserArgs = []) {
		return $value ? 1 : 0 ;
	}
	
	/**
	 * Convert string into database scalar value.
	 * @param  string $propName
	 * @param  string $value 
	 * @param  array  $parserArgs 
	 * @return string
	 */
	protected static function convertToScalarString ($propName, $value, $parserArgs = []) {
		return $value;
	}
	
	/**
	 * Convert array into database scalar value.
	 * @param  string $propName
	 * @param  array  $value 
	 * @param  array  $parserArgs 
	 * @return string
	 */
	protected static function convertToScalarArray ($propName, $value, $parserArgs = []) {
		$items = [];
		foreach ($value as $key => $item) {
			$propSubName = implode('.', [$propName, $key]);
			$items[$key] = $item !== NULL
				? static::convertToScalar($propSubName, $item, $parserArgs)
				: NULL;
		}
		if (count($items) === 0) return '[]';
		$toolClass = \MvcCore\Application::GetInstance()->GetToolClass();
		return $toolClass::JsonEncode($items);
	}
	
	/**
	 * Convert `\DateTime` or `\DateTimeImmutable` into database scalar value.
	 * @param  string                       $propName
	 * @param  \DateTime|\DateTimeImmutable $value 
	 * @param  array                        $parserArgs 
	 * @return string
	 */
	protected static function convertToScalarDateTime ($propName, $value, $parserArgs = []) {
		$parserArgsCount = is_array($parserArgs) ? count($parserArgs) : 0;
		if ($parserArgsCount > 0)
			return $value->format($parserArgs[0]);
		return $value->format('Y-m-d H:i:s');
	}
	
	/**
	 * Convert `\DateInterval` into database scalar value.
	 * @param  string        $propName
	 * @param  \DateInterval $value 
	 * @param  array         $parserArgs 
	 * @return string|int|float
	 */
	protected static function convertToScalarDateInterval ($propName, $value, $parserArgs = []) {
		$parserArgsCount = count($parserArgs);
		if ($parserArgsCount > 0) {
			$formatMask = $parserArgs[0];
			if ($parserArgsCount === 1) {
				return $value->format($formatMask);
			} else {
				$floatResult = static::convertIntervalToFloat($propName, $value);
				$targetType = $parserArgs[1];
				if ($targetType === 'int') {
					return intval(round($floatResult));
				} else /*if ($targetType === 'float')*/ {
					return $floatResult;
				}
			}
		}
		return static::convertIntervalToFloat($propName, $value);
	}
	
	/**
	 * Convert any other value type into database scalar value.
	 * @param  string $propName
	 * @param  mixed  $value 
	 * @param  array  $parserArgs 
	 * @return mixed
	 */
	protected static function convertToScalarOther ($propName, $value, $parserArgs = []) {
		return $value;
	}

	/**
	 * Convert `\DateInterval` into database float value.
	 * @param  string        $propName
	 * @param  \DateInterval $interval 
	 * @return float
	 */
	protected static function convertIntervalToFloat ($propName, $interval) {
		$result = floatval(
			($interval->h * 3600) + 
			($interval->i * 60) + 
			($interval->s)
		);
		if ($interval->days !== FALSE) {
			// $interval->days -> total number of full days between the start and end dates.
			$result += floatval(
				$interval->days * 86400
			);
		} else {
			$result += (floatval(
				$interval->y * 365 * 86400
			) + floatval(
				$interval->m * (365/12) * 86400
			));
		}
		// $interval->f -> number of microseconds, as a fraction of a second.
		if (PHP_VERSION_ID >= 70100)
			$result += $interval->f;
		if ($interval->invert) 
			$result *= -1;
		return $result;
	}

	/**
	 * Return protected static conversion method by given conversion flag
	 * to convert database column name into property name or back.
	 * @param  int $keysConversionFlags
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
	 * @param  string $key
	 * @param  string $toolsClass
	 * @param  string $csKeysMap
	 * @return string
	 */
	protected static function convertPropCaseInsensitive ($key, $toolsClass, $csKeysMap) {
		$keyPos = stripos($csKeysMap, ','.$key.',');
		if ($keyPos === FALSE) return $key;
		return substr($csKeysMap, $keyPos + 1, strlen($key));
	}

	/**
	 * Return key proper case sensitive value by given case sensitive map.
	 * @param  string $key
	 * @param  string $toolsClass
	 * @param  string $csKeysMap
	 * @return string
	 */
	protected static function convertPropUnderscoresToPascalcase ($key, $toolsClass, $csKeysMap) {
		return $toolsClass::GetPascalCaseFromUnderscored($key);
	}

	/**
	 * Return camel case key from underscore case key.
	 * @param  string $key
	 * @param  string $toolsClass
	 * @param  string $csKeysMap
	 * @return string
	 */
	protected static function convertPropUnderscoresToCamelcase ($key, $toolsClass, $csKeysMap) {
		return lcfirst($toolsClass::GetPascalCaseFromUnderscored($key));
	}

	/**
	 * Return underscore case key from pascal case key.
	 * @param  string $key
	 * @param  string $toolsClass
	 * @param  string $csKeysMap
	 * @return string
	 */
	protected static function convertPropPascalcaseToUnderscores ($key, $toolsClass, $csKeysMap) {
		return $toolsClass::GetUnderscoredFromPascalCase($key);
	}

	/**
	 * Return camel case key from pascal case key.
	 * @param  string $key
	 * @param  string $toolsClass
	 * @param  string $csKeysMap
	 * @return string
	 */
	protected static function convertPropPascalcaseToCamelcase ($key, $toolsClass, $csKeysMap) {
		return lcfirst($key);
	}

	/**
	 * Return underscore case key from camel case key.
	 * @param  string $key
	 * @param  string $toolsClass
	 * @param  string $csKeysMap
	 * @return string
	 */
	protected static function convertPropCamelcaseToUnderscores ($key, $toolsClass, $csKeysMap) {
		return $toolsClass::GetUnderscoredFromPascalCase(lcfirst($key));
	}

	/**
	 * Return pascal case key from camel case key.
	 * @param  string $key
	 * @param  string $toolsClass
	 * @param  string $csKeysMap
	 * @return string
	 */
	protected static function convertPropCamelcaseToPascalcase ($key, $toolsClass, $csKeysMap) {
		return ucfirst($key);
	}
}
