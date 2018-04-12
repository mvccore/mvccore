<?php

/**
 * MvcCore
 *
 * This source file is subject to the BSD 3 License
 * For the full copyright and license information, please view
 * the LICENSE.md file that are distributed with this source code.
 *
 * @copyright	Copyright (c) 2016 Tom Flídr (https://github.com/mvccore/mvccore)
 * @license		https://mvccore.github.io/docs/mvccore/4.0.0/LICENCE.md
 */

namespace MvcCore;

//include_once(__DIR__ . '/Interfaces/ITool.php');

/**
 * Responsibility - static helpers for core classes inheritance, string conversions and JSON.
 * - Static translation functions (supports containing folder or file path):
 *   - `"dashed-case"		=> "PascalCase"`
 *   - `"PascalCase"		=> "dashed-case"`
 *   - `"unserscore_case"	=> "PascalCase"`
 *   - `"PascalCase"		=> "unserscore_case"`
 * - Static functions to safely encode/decode JSON.
 * - Static functions to get client/server IPs.
 * - Static function to check core classes inheritance.
 */
class Tool implements Interfaces\ITool
{
    /**
	 * Convert all strings `"from" => "to"`:
	 * - `"MyCustomValue"				=> "my-custom-value"`
	 * - `"MyCustom/Value/InsideFolder"	=> "my-custom/value/inside-folder"`
	 * @param string $pascalCase
	 * @return string
	 */
    public static function GetDashedFromPascalCase ($pascalCase = '') {
		return strtolower(preg_replace("#([a-z])([A-Z])#", "$1-$2", lcfirst($pascalCase)));
	}

	/**
	 * Convert all string `"from" => "to"`:
	 * - `"my-custom-value"					=> "MyCustomValue"`
	 * - `"my-custom/value/inside-folder"	=> "MyCustom/Value/InsideFolder"`
	 * @param string $dashed
	 * @return string
	 */
	public static function GetPascalCaseFromDashed ($dashed = '') {
		$a = explode('/', $dashed);
		foreach ($a as & $b) $b = ucfirst(str_replace('-', '', ucwords($b, '-')));
		return implode('/', $a);
	}

	/**
	 * Convert all string `"from" => "to"`:
	 * - `"MyCutomValue"				=> "my_custom_value"`
	 * - `"MyCutom/Value/InsideFolder"	=> "my_custom/value/inside_folder"`
	 * @param string $pascalCase
	 * @return string
	 */
    public static function GetUnderscoredFromPascalCase ($pascalCase = '') {
		return strtolower(preg_replace("#([a-z])([A-Z])#", "$1_$2", lcfirst($pascalCase)));
	}

	/**
	 * Convert all string `"from" => "to"`:
	 * - `"my_custom_value"					=> "MyCutomValue"`
	 * - `"my_custom/value/inside_folder"	=> "MyCutom/Value/InsideFolder"`
	 * @param string $underscored
	 * @return string
	 */
	public static function GetPascalCaseFromUnderscored ($underscored = '') {
		$a = explode('/', $underscored);
		foreach ($a as & $b) $b = ucfirst(str_replace('_', '', ucwords($b, '_')));
		return implode('/', $a);
	}

	/**
	 * Safely encode json string from php value.
	 * @param mixed $data
	 * @throws \Exception
	 * @return string
	 */
	public static function EncodeJson (& $data) {
		$flags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP |
			(defined('JSON_UNESCAPED_SLASHES') ? JSON_UNESCAPED_SLASHES : 0) |
			(defined('JSON_UNESCAPED_UNICODE') ? JSON_UNESCAPED_UNICODE : 0) |
			(defined('JSON_PRESERVE_ZERO_FRACTION') ? JSON_PRESERVE_ZERO_FRACTION : 0);
		$json = json_encode($data, $flags);
		if ($errorCode = json_last_error()) {
			throw new \RuntimeException("[".__CLASS__."] ".json_last_error_msg(), $errorCode);
		}
		if (PHP_VERSION_ID < 70100) {
			$json = strtr($json, array(
				"\xe2\x80\xa8" => '\u2028',
				"\xe2\x80\xa9" => '\u2029',
			));
		}
		return $json;
	}

	/**
	 * Safely decode json string into php `stdClass/array`.
	 * Result has always keys:
	 * - `"success"`	- decoding boolean success
	 * - `"data"`		- decoded json data as stdClass/array
	 * - `"errorData"`	- array with possible decoding error message and error code
	 * @param string $jsonStr
	 * @return object
	 */
	public static function DecodeJson (& $jsonStr) {
		$result = (object) array(
			'success'	=> TRUE,
			'data'		=> null,
			'errorData'	=> array(),
		);
		$jsonData = @json_decode($jsonStr);
		$errorCode = json_last_error();
		if ($errorCode == JSON_ERROR_NONE) {
			$result->data = $jsonData;
		} else {
			$result->success = FALSE;
			$result->errorData = array(json_last_error_msg(), $errorCode);
		}
		return $result;
	}

	/**
	 * Get server IP from `$_SERVER` global variable.
	 * @return string
	 */
	public static function GetServerIp () {
		return isset($_SERVER['SERVER_ADDR'])
			? $_SERVER['SERVER_ADDR']
			: isset($_SERVER['LOCAL_ADDR'])
				? $_SERVER['LOCAL_ADDR']
				: '';
	}

	/**
	 * Get client IP from `$_SERVER` global variable.
	 * @return string
	 */
	public static function GetClientIp () {
		return isset($_SERVER['HTTP_X_CLIENT_IP'])
			? $_SERVER['HTTP_X_CLIENT_IP']
			: isset($_SERVER['REMOTE_ADDR'])
				? $_SERVER['REMOTE_ADDR']
				: '';
	}

	/**
	 * Check if given class implements given interface, else throw an exception.
	 * @param string $testClassName
	 * @param string $interfaceName
	 * @throws \Exception
	 * @return boolean
	 */
	public static function CheckClassInterface ($testClassName, $interfaceName) {
		$interfaces = class_implements($testClassName, TRUE); // load the class by autoload
		if (isset($interfaces[$interfaceName])) return TRUE;
		throw new \InvalidArgumentException(
			"[".__CLASS__."] Class '$testClassName' doesn't implement interface '$interfaceName'."
		);
	}
}
