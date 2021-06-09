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
trait Parsers {

	/**
	 * Try to convert raw database value into first type in target types.
	 * @param  mixed     $rawValue
	 * @param  \string[] $typesString
	 * @return mixed Converted result.
	 */
	protected static function parseToTypes ($rawValue, $typesString) {
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
					) = static::parseToType($item, $arrayItemTypeString);
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
				) = static::parseToType($rawValue, $typeString);
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
	 * @param  mixed  $rawValue
	 * @param  string $typeStr
	 * @return array First item is conversion boolean success, second item is converted result.
	 */
	protected static function parseToType ($rawValue, $typeStr) {
		$conversionResult = FALSE;
		$typeStr = trim($typeStr, '\\');
		if ($typeStr == 'DateTime') {
			if (!($rawValue instanceof \DateTime)) {
				$dateTime = static::parseToDateTime($rawValue, '+Y-m-d H:i:s');
				if ($dateTime instanceof \DateTime) {
					$rawValue = $dateTime;
					$conversionResult = TRUE;
				}
			}
		} else if ($typeStr === 'bool' || $typeStr === 'boolean') {
			$rawValue = static::parseToBool($rawValue);
			$conversionResult = TRUE;
		} else {
			// int, float, string, array, object, null:
			if (settype($rawValue, $typeStr)) 
				$conversionResult = TRUE;
		}
		return [$conversionResult, $rawValue];
	}
	
	/**
	 * Convert int, float or string value into bool.
	 * @param  int|float|string|NULL $rawValue 
	 * @return bool
	 */
	protected static function parseToBool ($rawValue) {
		if (is_bool($rawValue)) {
			return $rawValue;
		} else if (is_string($rawValue)) {
			return mb_strtolower($rawValue) === 'true' || $rawValue === '1';
		} else {
			return (bool) $rawValue;
		}
	}

	/**
	 * Convert int, float or string value into \DateTime.
	 * @param  int|float|string|NULL $rawValue 
	 * @param  string                $formatArgs 
	 * @return \DateTime|bool
	 */
	protected static function parseToDateTime ($rawValue, $formatArgs) {
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
		$dotPos = strpos($dateTimeStr, '.');
		if ($dotPos !== FALSE) {
			$msDigitsCount = strlen($dateTimeStr) - $dotPos - 1;
			$formatArgs .= $msDigitsCount === 3 ? '.v' : '.u';
		}
		return \date_create_from_format($formatArgs, $dateTimeStr);
	}
}
