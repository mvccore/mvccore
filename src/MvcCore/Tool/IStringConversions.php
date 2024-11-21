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

namespace MvcCore\Tool;

interface IStringConversions {
	
	/**
	 * Convert all strings `"from" => "to"`:
	 * - `"MyCustomValue"               => "my-custom-value"`
	 * - `"MyWTFValue"                  => "my-w-t-f-value"`
	 * - `"MyWtfValue"                  => "my-wtf-value"`
	 * - `"MyCustom/Value/InsideFolder" => "my-custom/value/inside-folder"`
	 * @param  string $pascalCase
	 * @return string
	 */
	public static function GetDashedFromPascalCase ($pascalCase);

	/**
	 * Convert all string `"from" => "to"`:
	 * - `"my-custom-value"               => "MyCustomValue"`
	 * - `"my-wtf-value"                  => "MyWtfValue"`
	 * - `"my-w-t-f-value"                => "MyWTFValue"`
	 * - `"my-custom/value/inside-folder" => "MyCustom/Value/InsideFolder"`
	 * @param  string $dashed
	 * @return string
	 */
	public static function GetPascalCaseFromDashed ($dashed);

	/**
	 * Convert all string `"from" => "to"`:
	 * - `"MyCutomValue"               => "my_custom_value"`
	 * - `"MyWTFValue"                 => "my_w_t_f_value"`
	 * - `"MyWtfValue"                 => "my_wtf_value"`
	 * - `"MyCutom/Value/InsideFolder" => "my_custom/value/inside_folder"`
	 * @param  string $pascalCase
	 * @return string
	 */
	public static function GetUnderscoredFromPascalCase ($pascalCase);

	/**
	 * Convert all string `"from" => "to"`:
	 * - `"my_custom_value"               => "MyCutomValue"`
	 * - `"my_wtf_value"                  => "MyWtfValue"`
	 * - `"my_w_t_f_value"                => "MyWTFValue"`
	 * - `"my_custom/value/inside_folder" => "MyCutom/Value/InsideFolder"`
	 * @param  string $underscored
	 * @return string
	 */
	public static function GetPascalCaseFromUnderscored ($underscored);

}
