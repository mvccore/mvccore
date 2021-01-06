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

trait Json {

	/**
	 * Safely encode json string from php value.
	 * @param mixed $data
	 * @param int   $flags
	 * JSON encoding flags used by default:
	 *  - `JSON_HEX_TAG`:
	 *     All < and > are converted to \u003C and \u003E. Available as of PHP 5.3.0.
	 *  - `JSON_HEX_AMP`:
	 *    All & are converted to \u0026. Available as of PHP 5.3.0.
	 *  - `JSON_HEX_APOS`:
	 *    All ' are converted to \u0027. Available as of PHP 5.3.0.
	 *  - `JSON_HEX_QUOT`:
	 *    All " are converted to \u0022. Available as of PHP 5.3.0.
	 *  - `JSON_UNESCAPED_SLASHES`:
	 *    Don't escape /. Available as of PHP 5.4.0.
	 *  - `JSON_PRESERVE_ZERO_FRACTION`:
	 *    Ensures that float values are always encoded as a float value. Available as of PHP 5.6.6.
	 * Possible JSON encoding flags to add:
	 *  - `JSON_PRETTY_PRINT`:
	 *    Encode JSON into pretty print syntax, Available as of PHP 5.4.0.
	 *  - `JSON_NUMERIC_CHECK`:
	 *    Encodes numeric strings as numbers (be carefull for phone numbers). Available as of PHP 5.3.3.
	 *  - `JSON_UNESCAPED_UNICODE`:
	 *    Encode multibyte Unicode characters literally (default is to escape as \uXXXX). Available as of PHP 5.4.0.
	 *  - `JSON_UNESCAPED_LINE_TERMINATORS`:
	 *    The line terminators are kept unescaped when JSON_UNESCAPED_UNICODE
	 *    is supplied. It uses the same behaviour as it was before PHP 7.1
	 *    without this constant. Available as of PHP 7.1.0.	The following
	 *    constants can be combined to form options for json_decode()
	 *    and json_encode().
	 *  - `JSON_INVALID_UTF8_IGNORE`:
	 *    Ignore invalid UTF-8 characters. Available as of PHP 7.2.0.
	 *  - `JSON_INVALID_UTF8_SUBSTITUTE`:
	 *    Convert invalid UTF-8 characters to \0xfffd (Unicode Character
	 *    'REPLACEMENT CHARACTER') Available as of PHP 7.2.0.
	 *  - `JSON_THROW_ON_ERROR`:
	 *    Throws JsonException if an error occurs instead of setting the global
	 *    error state that is retrieved with json_last_error() and
	 *    json_last_error_msg(). JSON_PARTIAL_OUTPUT_ON_ERROR takes precedence
	 *    over JSON_THROW_ON_ERROR. Available as of PHP 7.3.0.
	 * @param int    $depth Set the maximum depth. Must be greater than zero, default: 512.
	 * @throws \RuntimeException|\JsonException JSON encoding error.
	 * @return string
	 */
	public static function EncodeJson ($data, $flags = 0, $depth = 512) {
		if (!defined('JSON_PRESERVE_ZERO_FRACTION'))
			define('JSON_PRESERVE_ZERO_FRACTION', 1024);
		$flags |= (
			JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT |
			JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION
		);
		//var_dump(decbin($flags));
		if (\PHP_VERSION_ID >= 50500) {
			$result = @json_encode($data, $flags, $depth);
		} else {
			$result = @json_encode($data, $flags);
		}
		$errorCode = json_last_error();
		if ($errorCode == JSON_ERROR_NONE) {
			if (PHP_VERSION_ID < 70100)
				$result = strtr($result, [
					"\xe2\x80\xa8" => '\u2028',
					"\xe2\x80\xa9" => '\u2029',
				]);
			return $result;
		}
		throw new \RuntimeException(
			"[".get_class()."] ".static::getJsonLastErrorMessage($errorCode), $errorCode
		);
	}

	/**
	 * Safely decode json string into php `stdClass/array`.
	 * @param string $jsonStr
	 * @param int    $flags
	 * - `JSON_BIGINT_AS_STRING`:
	 *    Decodes large integers as their original string value. Available as of PHP 5.4.0.
	 * - `JSON_OBJECT_AS_ARRAY`:
	 *   Decodes JSON objects as PHP array. This option can be added automatically by calling json_decode() with
	 *   the second parameter equal to TRUE. Available as of PHP 5.4.0.
	 * - `JSON_INVALID_UTF8_IGNORE`:
	 *   Ignore invalid UTF-8 characters. Available as of PHP 7.2.0.
	 *  - `JSON_INVALID_UTF8_SUBSTITUTE`:
	 *    Convert invalid UTF-8 characters to \0xfffd (Unicode Character
	 *    'REPLACEMENT CHARACTER') Available as of PHP 7.2.0.
	 *  - `JSON_THROW_ON_ERROR`:
	 *    Throws JsonException if an error occurs instead of setting the global
	 *    error state that is retrieved with json_last_error() and
	 *    json_last_error_msg(). JSON_PARTIAL_OUTPUT_ON_ERROR takes precedence
	 *    over JSON_THROW_ON_ERROR. Available as of PHP 7.3.0.
	 * @param int    $depth User specified recursion depth, default: 512.
	 * @throws \RuntimeException|\JsonException JSON decoding error.
	 * @return object
	 */
	public static function DecodeJson ($jsonStr, $flags = 0, $depth = 512) {
		$assoc = ($flags & JSON_OBJECT_AS_ARRAY) != 0;
		//var_dump(decbin($flags));
		$result = @json_decode($jsonStr, $assoc, $depth, $flags);
		$errorCode = json_last_error();
		if ($errorCode == JSON_ERROR_NONE)
			return $result;
		throw new \RuntimeException(
			"[".get_class()."] ".static::getJsonLastErrorMessage($errorCode), $errorCode
		);
	}
	
	/**
	 * Recognize if given string is JSON or not without JSON parsing.
	 * @see https://www.ietf.org/rfc/rfc4627.txt
	 * @param string $jsonStr
	 * @return bool
	 */
	public static function IsJsonString ($jsonStr) {
		return !preg_match(
			'#[^,:{}\[\]0-9.\\-+Eaeflnr-u \n\r\t]#',
			preg_replace(
				'#"(\.|[^\\"])*"#',
				'',
				(string) $jsonStr
			)
		);
	}

	/**
	 * Return last JSON encode/decode error message, optionally by error code for PHP 5.4.
	 * @param int $jsonErrorCode
	 * @return string
	 */
	protected static function getJsonLastErrorMessage ($jsonErrorCode) {
		if (function_exists('json_last_error_msg')) {
			return json_last_error_msg();
		} else {
			// errors before PHP 5.5:
			static $__jsonErrorMessages = array(
				JSON_ERROR_DEPTH			=> 'The maximum stack depth has been exceeded.',
				JSON_ERROR_STATE_MISMATCH	=> 'Occurs with underflow or with the modes mismatch.',
				JSON_ERROR_CTRL_CHAR		=> 'Control character error, possibly incorrectly encoded.',
				JSON_ERROR_SYNTAX			=> 'Syntax error.',
				JSON_ERROR_UTF8				=> 'Malformed UTF-8 characters, possibly incorrectly encoded.'
			);
			return $__jsonErrorMessages[$jsonErrorCode];
		}
	}
}