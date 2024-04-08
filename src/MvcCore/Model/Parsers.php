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
 * @phpstan-type RawValue int|float|string|bool|\DateTime|\DateTimeImmutable|array<mixed,mixed>|object
 * @phpstan-type ParserArgs array<int|string,mixed>|NULL
 */
trait Parsers {

	/**
	 * @inheritDoc
	 * @param  RawValue      $rawValue
	 * @param  array<string> $typesString
	 * @param  array<mixed>  $parserArgs
	 * This argument is used in extended model only.
	 * @return RawValue Converted result.
	 */
	public static function ParseToTypes ($rawValue, $typesString, $parserArgs = []) {
		$targetTypeValue = NULL;
		$value = $rawValue;
		/** @var string $typeString */
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
	 * @param  RawValue $rawValue
	 * @param  string   $typeStr
	 * @return array{0:bool,1:RawValue}
	 * First item is conversion boolean success, second item is converted result.
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
			$conversionResult = settype($rawValue, $typeStr);
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
	 * @param  int|float|string|bool $rawValue 
	 * @return bool
	 */
	protected static function parseToBool ($rawValue) {
		if (is_bool($rawValue)) {
			return $rawValue;
		} else if (is_string($rawValue)) {
			$rawValue = trim($rawValue);
			return (
				$rawValue === '0' || 
				$rawValue === '' || 
				$rawValue === '0.0' || 
				mb_strtolower($rawValue) === 'false'
			) ? FALSE : TRUE;
		} else {
			return (bool) $rawValue;
		}
	}

	/**
	 * Convert int, float or string value into \DateTime.
	 * @param  string           $typeStr
	 * @param  int|float|string $rawValue 
	 * @param  ParserArgs       $parserArgs 
	 * @return \DateTime|\DateTimeImmutable|bool
	 */
	protected static function parseToDateTime ($typeStr, $rawValue, $parserArgs = []) {
		$format = $parserArgs[0];
		$format = '!' . ltrim($format, '!'); // to reset all other values not included in format into zeros
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
		return @$typeStr::createFromFormat($format, $dateTimeStr);
	}

	/**
	 * Convert raw value into `array` or `\stdClass`.
	 * @param  string                           $typeStr
	 * @param  array<mixed,mixed>|object|string $rawValue 
	 * @param  ParserArgs                       $parserArgs 
	 * @return array<mixed,mixed>|object|NULL
	 */
	protected static function parseToArrayOrObject ($typeStr, $rawValue, $parserArgs = [0, 512]) {
		if (!is_string($rawValue)) {
			$value = $rawValue;
		} else {
			$toolClass = \MvcCore\Application::GetInstance()->GetToolClass();
			if (!$toolClass::IsJsonString($rawValue)) {
				if (is_array($rawValue) || is_object($rawValue)) {
					$value = $rawValue;
				} else if (is_string($rawValue)) {
					$separator = ',';
					$callable = NULL;
					if (is_array($parserArgs)) {
						$parserArgsCount = count($parserArgs);
						if ($parserArgsCount > 0 && is_string($parserArgs[0]))
							$separator = $parserArgs[0];
						if ($parserArgsCount > 1 && is_callable($parserArgs[1]))
							$callable = $parserArgs[1];
					}
					$value = explode($separator, $rawValue);
					if ($callable !== NULL)
						$value = array_map($callable, $value);
				} else {
					$value = [$rawValue];
				}
			} else {
				$value = NULL;
				$flags = isset($parserArgs[0]) && is_int($parserArgs[0]) 
					? $parserArgs[0] 
					: 0;
				$depth = isset($parserArgs[1]) && is_int($parserArgs[1]) 
					? $parserArgs[1] 
					: 512;
				try {
					$value = $toolClass::JsonDecode($rawValue, $flags, $depth);
					if ($typeStr === 'array' || $typeStr === 'object') {
						settype($value, $typeStr);
					} else {
						$value = new $typeStr($value);
					}
				} catch (\Throwable $e) {
					\MvcCore\Debug::Log($e);
				}
			}
		}
		return $value;
	}
}
