<?php

/**
 * MvcCore
 *
 * This source file is subject to the BSD 3 License
 * For the full copyright and license information, please view 
 * the LICENSE.md file that are distributed with this source code.
 *
 * @copyright	Copyright (c) 2016 Tom Flídr (https://github.com/mvccore/mvccore)
 * @license		https://mvccore.github.io/docs/mvccore/3.0.0/LICENCE.md
 */

class MvcCore_Tool
{
    /**
     * Convert all string from 'MyCutomValue' to 'my-custom-value'
     * @param string $pascalCase 
     * @return string
     */
    public static function GetDashedFromPascalCase ($pascalCase = '') {
		return strtolower(preg_replace("#([A-Z])#", "-$1", lcfirst($pascalCase)));
	}
    /**
	 * Convert all string from 'my-custom-value' to 'MyCutomValue'
	 * @param string $dashed 
	 * @return string
	 */
	public static function GetPascalCaseFromDashed ($dashed = '') {
		return ucfirst(str_replace('-', '', ucwords($dashed, '-')));
	}
    /**
	 * Convert all string from 'MyCutomValue' to 'my_custom_value'
	 * @param string $pascalCase
	 * @return string
	 */
    public static function GetUnderscoredFromPascalCase ($pascalCase = '') {
		return strtolower(preg_replace("#([A-Z])#", "_$1", lcfirst($pascalCase)));
	}

    /**
	 * Convert all string from 'my_custom_value' to 'MyCutomValue'
	 * @param string $underscored
	 * @return string
	 */
	public static function GetPascalCaseFromUnderscored ($underscored = '') {
		return ucfirst(str_replace('_', '', ucwords($underscored, '_')));
	}
	/**
	 * Safely decode json string
	 * Result has always key 'success' with boolean and key 'data' with decoded json data.
	 * @param string $jsonStr 
	 * @return object
	 */
	public static function DecodeJson (& $jsonStr) {
		$result = (object) array(
			'success'	=> TRUE,
			'data'		=> null,
		);
		$jsonData = @json_decode($jsonStr);
		if (json_last_error() == JSON_ERROR_NONE) {
			$result->data = $jsonData;
		} else {
			$result->success = FALSE;
		}
		return $result;
	}
}
