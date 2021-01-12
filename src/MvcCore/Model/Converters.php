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
