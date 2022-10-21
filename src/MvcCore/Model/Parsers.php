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
		$typeStr = trim($typeStr, '\\?');
		if (static::parseIsTypeString($typeStr)) {
			// string:
			$rawValue = (string) $rawValue;
			$conversionResult = TRUE;
		} else if (static::parseIsTypeNumeric($typeStr)) {
			// int or float:
			if (settype($rawValue, $typeStr)) 
				$conversionResult = TRUE;
		} else if (static::parseIsTypeBoolean($typeStr)) {
			// bool:
			$rawValue = static::parseToBool($rawValue);
			$conversionResult = TRUE;
		} else if (static::parseIsTypeDateTime($typeStr)) {
			// \DateTime, \DateTimeImmutable or it's extended class:
			if (!($rawValue instanceof \DateTime || $rawValue instanceof \DateTimeImmutable)) {
				$dateTime = static::parseToDateTime($typeStr, $rawValue, ['+Y-m-d H:i:s']);
				if ($dateTime !== FALSE) {
					$rawValue = $dateTime;
					$conversionResult = TRUE;
				}
			}
		} else {
			// array or object:
			$rawValue = static::parseToArrayOrObject($typeStr, $rawValue);
			$conversionResult = TRUE;
		}
		return [$conversionResult, $rawValue];
	}
	
	/**
	 * Get boolean `TRUE` if given full class name is `string`.
	 * @param  string $typeStr 
	 * @return bool
	 */
	protected static function parseIsTypeString ($typeStr) {
		return isset(static::$parserTypes['string'][$typeStr]);
	}
	
	/**
	 * Get boolean `TRUE` if given full class name is 
	 * `int`, `integer`, `long`, `float` or `real`.
	 * @param  string $typeStr 
	 * @return bool
	 */
	protected static function parseIsTypeNumeric ($typeStr) {
		return isset(static::$parserTypes['numeric'][$typeStr]);
	}
	
	/**
	 * Get boolean `TRUE` if given full class name is `bool` or `boolean`.
	 * @param  string $typeStr 
	 * @return bool
	 */
	protected static function parseIsTypeBoolean ($typeStr) {
		return isset(static::$parserTypes['boolean'][$typeStr]);
	}

	/**
	 * Get boolean `TRUE` if given full class name is class 
	 * or subclass of `DateTime` or `DateTimeImmutable`.
	 * @param  string $typeStr 
	 * @return bool
	 */
	protected static function parseIsTypeDateTime ($typeStr) {
		list ($dateTimeStr, $dateTimeImmutable) = static::$parserTypes['dates'];
		return (
			is_a($typeStr, $dateTimeStr, TRUE) || 
			is_subclass_of($typeStr, $dateTimeStr, TRUE) || 
			is_a($typeStr, $dateTimeImmutable, TRUE) || 
			is_subclass_of($typeStr, $dateTimeImmutable, TRUE)
		);
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
	 * @param  string                $typeStr
	 * @param  int|float|string|NULL $rawValue 
	 * @param  array                 $parserArgs 
	 * @return \DateTime|\DateTimeImmutable|bool
	 */
	protected static function parseToDateTime ($typeStr, $rawValue, $parserArgs = []) {
		$format = $parserArgs[0];
		if (is_numeric($rawValue)) {
			$rawValueStr = str_replace(['+','-','.'], '', (string) $rawValue);
			$secData = mb_substr($rawValueStr, 0, 10);
			$dateTimeStr = date($format, intval($secData));
			if (strlen($rawValueStr) > 10)
				$dateTimeStr .= '.' . mb_substr($rawValueStr, 10);
		} else {
			$dateTimeStr = (string) $rawValue;
			if (strpos($dateTimeStr, '-') === FALSE) {
				$format = substr($format, 6);
			} else if (strpos($dateTimeStr, ':') === FALSE) {
				$format = substr($format, 0, 5);
			}
		}
		$dotPos = strpos($dateTimeStr, '.');
		if ($dotPos !== FALSE) {
			$msDigitsCount = strlen($dateTimeStr) - $dotPos - 1;
			$format .= $msDigitsCount === 3 ? '.v' : '.u';
		}
		return $typeStr::createFromFormat($format, $dateTimeStr);
	}

	/**
	 * Convert raw value into `array` or `\stdClass`.
	 * @param  string                $typeStr
	 * @param  int|float|string|NULL $rawValue 
	 * @param  array                 $parserArgs 
	 * @return \DateTime|\DateTimeImmutable|bool
	 */
	protected static function parseToArrayOrObject ($typeStr, $rawValue, $parserArgs = [0, 512]) {
		$toolClass = \MvcCore\Application::GetInstance()->GetToolClass();
		$flags = isset($parserArgs[0]) && is_int($parserArgs[0]) 
			? $parserArgs[0] 
			: 0;
		$depth = isset($parserArgs[1]) && is_int($parserArgs[1]) 
			? $parserArgs[1] 
			: 512;
		return $toolClass::JsonDecode($rawValue, $flags, $depth);
	}
}
