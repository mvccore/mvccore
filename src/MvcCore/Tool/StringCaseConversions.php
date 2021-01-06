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

namespace MvcCore\Tool;

trait StringConversions {

	/**
	 * Convert all strings `"from" => "to"`:
	 * - `"MyCustomValue"				=> "my-custom-value"`
	 * - `"MyWTFValue"					=> "my-w-t-f-value"`
	 * - `"MyWtfValue"					=> "my-wtf-value"`
	 * - `"MyCustom/Value/InsideFolder"	=> "my-custom/value/inside-folder"`
	 * @param string $pascalCase
	 * @return string
	 */
	public static function GetDashedFromPascalCase ($pascalCase = '') {
		/*
		// This commented version converts `MyWFTValue` to `my-wtf-value`, which 
		// is cool, but inputs `MyWFTValue` and `MyWtfValue` have the same 
		// inconsistent output `my-wtf-value`, which is wrong.
		$result = preg_replace_callback("#[A-Z]{2,}#", function ($match) {
			$str = $match[0];
			$length = strlen($str);
			$result = substr($str, 0, 1);
			if ($length > 2)
				$result .= strtolower(substr($str, 1, $length - 2));
			return $result . '-' . strtolower(substr($str, $length - 1));
		}, $pascalCase);
		return strtolower(preg_replace("#([a-zA-Z])([A-Z])#", "$1-$2", lcfirst($result)));
		*/
		return strtolower(preg_replace(
			"#([a-z])([A-Z])#", 
			"$1-$2", 
			lcfirst(preg_replace_callback(
				"#[A-Z]{2,}#", 
				function ($match) {
					$str = $match[0];
					return substr($str, 0, 1) . '-' . strtolower(implode('-', str_split(substr($str, 1))));
				}, $pascalCase)
			)
		));
	}

	/**
	 * Convert all string `"from" => "to"`:
	 * - `"my-custom-value"					=> "MyCustomValue"`
	 * - `"my-wtf-value"					=> "MyWtfValue"`
	 * - `"my-w-t-f-value"					=> "MyWTFValue"`
	 * - `"my-custom/value/inside-folder"	=> "MyCustom/Value/InsideFolder"`
	 * @param string $dashed
	 * @return string
	 */
	public static function GetPascalCaseFromDashed ($dashed = '') {
		$a = explode('/', $dashed);
		if (PHP_VERSION_ID < 50432) {
			foreach ($a as & $b) $b = ucfirst(str_replace('-', '', static::upperCaseWords($b, '-')));
		} else {
			foreach ($a as & $b) $b = ucfirst(str_replace('-', '', ucwords($b, '-')));
		}
		return ucfirst(implode('/', $a));
	}

	/**
	 * Convert all string `"from" => "to"`:
	 * - `"MyCutomValue"				=> "my_custom_value"`
	 * - `"MyWTFValue"					=> "my_w_t_f_value"`
	 * - `"MyWtfValue"					=> "my_wtf_value"`
	 * - `"MyCutom/Value/InsideFolder"	=> "my_custom/value/inside_folder"`
	 * @param string $pascalCase
	 * @return string
	 */
	public static function GetUnderscoredFromPascalCase ($pascalCase = '') {
		/*
		// This commented version converts `MyWFTValue` to `my_wtf_value`, which 
		// is cool, but inputs `MyWFTValue` and `MyWtfValue` have the same 
		// inconsistent output `my_wtf_value`, which is wrong.
		$result = preg_replace_callback("#[A-Z]{2,}#", function ($match) {
			$str = $match[0];
			$length = strlen($str);
			$result = substr($str, 0, 1);
			if ($length > 2) $result .= strtolower(substr($str, 1, $length - 2));
			return $result . '_' . strtolower(substr($str, $length - 1));
		}, $pascalCase);
		return strtolower(preg_replace("#([a-zA-Z])([A-Z])#", "$1_$2", lcfirst($result)));
		*/
		return strtolower(preg_replace(
			"#([a-z])([A-Z])#", 
			"$1_$2", 
			lcfirst(preg_replace_callback(
				"#[A-Z]{2,}#", 
				function ($match) {
					$str = $match[0];
					return substr($str, 0, 1) . '_' . strtolower(implode('_', str_split(substr($str, 1))));
				}, $pascalCase)
			)
		));
	}

	/**
	 * Convert all string `"from" => "to"`:
	 * - `"my_custom_value"					=> "MyCutomValue"`
	 * - `"my_wtf_value"					=> "MyWtfValue"`
	 * - `"my_w_t_f_value"					=> "MyWTFValue"`
	 * - `"my_custom/value/inside_folder"	=> "MyCutom/Value/InsideFolder"`
	 * @param string $underscored
	 * @return string
	 */
	public static function GetPascalCaseFromUnderscored ($underscored = '') {
		$a = explode('/', $underscored);
		if (PHP_VERSION_ID < 50432) {
			foreach ($a as & $b) $b = ucfirst(str_replace('_', '', static::upperCaseWords($b, '_')));
		} else {
			foreach ($a as & $b) $b = ucfirst(str_replace('_', '', ucwords($b, '_')));
		}
		return ucfirst(implode('/', $a));
	}
	
	/**
	 * PHP < 5.4.32 compatible method.
	 */
	protected static function upperCaseWords ($str, $delimiter) {
		$words = explode($delimiter, $str);
		foreach ($words as $index => $word) 
			$words[$index] = mb_strtoupper(mb_substr($word, 0, 1)) . mb_substr($word, 1);
		return implode($delimiter, $words);
	}
}
