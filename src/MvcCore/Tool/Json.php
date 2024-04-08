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

/**
 * @mixin \MvcCore\Tool
 */
trait Json {
	
	/**
	 * JSON detection regular expression.
	 * @var string|NULL
	 */
	protected static $jsonRegExp = NULL;

	/**
	 * @inheritDoc
	 * @param  mixed $data
	 * @param  int   $flags
	 * @param  int   $depth                     Set the maximum depth. 
	 *                                          Must be greater than zero, default: 512.
	 * @throws \RuntimeException|\JsonException JSON encoding error.
	 * @return string
	 */
	public static function JsonEncode ($data, $flags = 0, $depth = 512) {
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
			"[".get_called_class()."] ".static::getJsonLastErrorMessage($errorCode), $errorCode
		);
	}

	/**
	 * @inheritDoc
	 * @param  string|NULL $jsonStr
	 * @param  int         $flags
	 * @param  int         $depth               User specified recursion depth, default: 512.
	 * @throws \RuntimeException|\JsonException JSON decoding error.
	 * @return mixed
	 */
	public static function JsonDecode ($jsonStr, $flags = 0, $depth = 512) {
		if ($jsonStr === NULL) return NULL;
		$assoc = ($flags & JSON_OBJECT_AS_ARRAY) != 0;
		//var_dump(decbin($flags));
		$result = @json_decode($jsonStr, $assoc, $depth, $flags);
		$errorCode = json_last_error();
		if ($errorCode == JSON_ERROR_NONE)
			return $result;
		throw new \RuntimeException(
			"[".get_called_class()."] ".static::getJsonLastErrorMessage($errorCode), $errorCode
		);
	}
	
	/**
	 * @inheritDoc
	 * @see https://www.ietf.org/rfc/rfc4627.txt
	 * @see https://stackoverflow.com/a/6249375/7032987
	 * @param  string $jsonStr
	 * @return bool
	 */
	public static function IsJsonString ($jsonStr) {
		if (self::$jsonRegExp === NULL) {
			$regexString = '"([^"\\\\]*|\\\\["\\\\bfnrt\/]|\\\\u[0-9a-f]{4})*"';
			$regexNumber = "-?(?=[1-9]|0(?!\d))\d+(\.\d+)?([eE][+-]?\d+)?";
			$regexBoolean = "true|false|null";
			self::$jsonRegExp = implode('', [
				"#\A({$regexString}|{$regexNumber}|{$regexBoolean}|",					// string, number, boolean
				"\[(?:(?1)(?:,(?1))*)?\s*\]|",											// arrays
				"\{(?:\s*{$regexString}\s*:(?1)(?:,\s*{$regexString}\s*:(?1))*)?\s*\}",	// objects
				")\Z#is",
			]);
		}
		return (bool) preg_match(self::$jsonRegExp, (string) $jsonStr);
	}

	/**
	 * Return last JSON encode/decode error message, optionally by error code for PHP 5.4.
	 * @param  int $jsonErrorCode
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