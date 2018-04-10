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

namespace MvcCore\Interfaces;

/**
 * Responsibilities:
 * - Static translation functions (supports containing folder or file path):
 *   - `"dashed-case"		=> "PascalCase"`
 *   - `"PascalCase"		=> "dashed-case"`
 *   - `"unserscore_case"	=> "PascalCase"`
 *   - `"PascalCase"		=> "unserscore_case"`
 * - Static functions to safely encode/decode JSON.
 * - Static functions to get client/server IPs.
 * - Static function to check core classes inheritance.
 */
interface ITool
{
    /**
	 * Convert all strings `"from" => "to"`:
	 * - `"MyCustomValue"				=> "my-custom-value"`
	 * - `"MyCustom/Value/InsideFolder"	=> "my-custom/value/inside-folder"`
     * @param string $pascalCase
     * @return string
     */
    public static function GetDashedFromPascalCase ($pascalCase = '');

    /**
	 * Convert all string `"from" => "to"`:
	 * - `"my-custom-value"					=> "MyCustomValue"`
	 * - `"my-custom/value/inside-folder"	=> "MyCustom/Value/InsideFolder"`
	 * @param string $dashed
	 * @return string
	 */
	public static function GetPascalCaseFromDashed ($dashed = '');

    /**
	 * Convert all string `"from" => "to"`:
	 * - `"MyCutomValue"				=> "my_custom_value"`
	 * - `"MyCutom/Value/InsideFolder"	=> "my_custom/value/inside_folder"`
	 * @param string $pascalCase
	 * @return string
	 */
    public static function GetUnderscoredFromPascalCase ($pascalCase = '');

    /**
	 * Convert all string `"from" => "to"`:
	 * - `"my_custom_value"					=> "MyCutomValue"`
	 * - `"my_custom/value/inside_folder"	=> "MyCutom/Value/InsideFolder"`
	 * @param string $underscored
	 * @return string
	 */
	public static function GetPascalCaseFromUnderscored ($underscored = '');

	/**
	 * Safely encode json string from php value.
	 * @param mixed $data
	 * @throws \Exception
	 * @return string
	 */
	public static function EncodeJson ($data);

	/**
	 * Safely decode json string into php `stdClass/array`.
	 * Result has always keys:
	 * - `"success"`	- decoding boolean success
	 * - `"data"`		- decoded json data as stdClass/array
	 * - `"errorData"`	- array with possible decoding error message and error code
	 * @param string $jsonStr
	 * @return object
	 */
	public static function DecodeJson ($jsonStr);

	/**
	 * Get server IP from `$_SERVER` global variable.
	 * @return string
	 */
	public static function GetServerIp ();

	/**
	 * Get client IP from `$_SERVER` global variable.
	 * @return string
	 */
	public static function GetClientIp ();

	/**
	 * Check if given class implements given interface, else throw an exception.
	 * @param string $testClassName
	 * @param string $interfaceName
	 * @throws \Exception
	 * @return boolean
	 */
	public static function CheckClassInterface ($testClassName, $interfaceName);
}
