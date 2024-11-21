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

namespace MvcCore\Request;

/**
 * @phpstan-type CollectionItem string|int|float|bool|NULL|array<string|int|float|bool|NULL>|mixed
 * @phpstan-type CollectionFilter string|array<string,string>|bool
 */
interface IGettersSetters {
	
	/**
	 * Get one of the global data collections stored 
	 * as protected properties inside request object.
	 * Example:
	 *  // to get global `$_GET` with raw values:
	 *  `$globalGet = $request->GetGlobalCollection('get');`
	 * @param  string $type
	 * @return array<int|string,mixed>
	 */
	public function & GetGlobalCollection ($type);

	/**
	 * Set directly all raw http headers without any conversion at once.
	 * Header name(s) as array keys should be in standard format like:
	 * `"Content-Type" | "Content-Length" | "X-Requested-With" ...`.
	 * @param  array<string,mixed> $headers
	 * @return \MvcCore\Request
	 */
	public function SetHeaders (array & $headers = []);

	/**
	 * Get directly all raw http headers at once (with/without conversion).
	 * If headers are not initialized, initialize headers by
	 * `getallheaders()` or from `$_SERVER['HTTP_...']`.
	 * Headers are returned as `key => value` array, headers keys are
	 * in standard format like: 
	 * `"Content-Type" | "Content-Length" | "X-Requested-With" ...`.
	 * @param  CollectionFilter $pregReplaceAllowedChars
	 * If String - list of regular expression characters to only keep, 
	 * if array - `preg_replace()` pattern and reverse, if `FALSE`, 
	 * raw value is returned.
	 * @return array<string,mixed>
	 */
	public function GetHeaders ($pregReplaceAllowedChars = ['#[\<\>\'"]#' => '']);

	/**
	 * Set directly raw http header value without any conversion.
	 * Header name should be in standard format like:
	 * `"Content-Type" | "Content-Length" | "X-Requested-With" ...`.
	 * @param  string          $name
	 * @param  string|string[] $value
	 * @return \MvcCore\Request
	 */
	public function SetHeader ($name = '', $value = '');

	/**
	 * Get http header value filtered by "rule to keep defined characters only",
	 * defined in second argument (by `preg_replace()`). Place into second argument
	 * only char groups you want to keep. Header has to be in format like:
	 * `"Content-Type" | "Content-Length" | "X-Requested-With" ...`.
	 * @param  string           $name
	 * Http header string name.
	 * @param  CollectionFilter $pregReplaceAllowedChars
	 * If String - list of regular expression characters to only keep, 
	 * if array - `preg_replace()` pattern and reverse, if `FALSE`, 
	 * raw value is returned.
	 * @param  CollectionItem   $ifNullValue
	 * Default value returned if given param name is null.
	 * @param  string           $targetType
	 * Target type to retype param value or default if-null value. 
	 * If param is an array, every param item will be retyped 
	 * into given target type.
	 * @throws \InvalidArgumentException
	 * `$name` must be a `$targetType`, not an `array`.
	 * @return CollectionItem
	 */
	public function GetHeader (
		$name = '',
		$pregReplaceAllowedChars = "a-zA-Z0-9_;, /\-\.\@\=\+\?\!",
		$ifNullValue = NULL,
		$targetType = NULL
	);

	/**
	 * Return if request has any http header by given name.
	 * @param  string $name Http header string name.
	 * @return bool
	 */
	public function HasHeader ($name = '');


	/**
	 * Set directly all raw parameters without any conversion at once.
	 * @param  array<string,mixed> $params
	 * Keys are param names, values are param values.
	 * @param  int                 $sourceType
	 * Param source collection flag(s). If param has defined 
	 * source type flag already, this given flag is used 
	 * to overwrite already defined flag.
	 * @return \MvcCore\Request
	 */
	public function SetParams (
		array & $params = [], 
		$sourceType = \MvcCore\IRequest::PARAM_TYPE_ANY
	);

	/**
	 * Get directly all raw parameters at once (with/without conversion).
	 * If any defined char groups in `$pregReplaceAllowedChars`, 
	 * there will be returned all params filtered by given rule 
	 * in `preg_replace()`.
	 * @param  CollectionFilter $pregReplaceAllowedChars
	 * If String - list of regular expression characters to only keep, 
	 * if array - `preg_replace()` pattern and reverse, if `FALSE`, 
	 * raw value is returned.
	 * @param  array<string>    $onlyKeys
	 * Array with keys to get only. If empty (by default), 
	 * all possible params are returned.
	 * @param  int              $sourceType
	 * Param source collection flag(s). If defined, there 
	 * are returned only params from given collection types.
	 * @return array<string,mixed>
	 */
	public function GetParams (
		$pregReplaceAllowedChars = ['#[\<\>\'"]#' => ''], 
		$onlyKeys = [], 
		$sourceType = \MvcCore\IRequest::PARAM_TYPE_ANY
	);

	/**
	 * Set directly raw parameter value without any conversion.
	 * @param  string                    $name
	 * Param raw name.
	 * @param  string|array<string>|NULL $value
	 * Param raw value.
	 * @param  int                       $sourceType
	 * Param source collection flag(s). If param has defined 
	 * source type flag already, this given flag is used 
	 * to overwrite already defined flag.
	 * @return \MvcCore\Request
	 */
	public function SetParam (
		$name, 
		$value = NULL, 
		$sourceType = \MvcCore\IRequest::PARAM_TYPE_ANY
	);

	/**
	 * Get param value from `$_GET`, `$_POST` or `php://input`, filtered by
	 * "rule to keep defined characters only", defined in second argument 
	 * (by `preg_replace()`). Place into second argument only char 
	 * groups you want to keep.
	 * @param  string           $name
	 * Parameter string name.
	 * @param  CollectionFilter $pregReplaceAllowedChars
	 * If String - list of regular expression characters to only keep, 
	 * if array - `preg_replace()` pattern and reverse, if `FALSE`, 
	 * raw value is returned.
	 * @param  CollectionItem   $ifNullValue
	 * Default value returned if given param name is null.
	 * @param  string           $targetType
	 * Target type to retype param value or default if-null value. 
	 * If param is an array, every param item will be retyped 
	 * into given target type.
	 * @param  int              $sourceType
	 * Param source collection flag(s). If defined, there is returned 
	 * only param from given collection type(s).
	 * @throws \InvalidArgumentException
	 * `$name` must be a `$targetType`, not an `array`.
	 * @return CollectionItem
	 */
	public function GetParam (
		$name,
		$pregReplaceAllowedChars = "a-zA-Z0-9_;, /\-\@\:",
		$ifNullValue = NULL,
		$targetType = NULL,
		$sourceType = \MvcCore\IRequest::PARAM_TYPE_ANY
	);

	/**
	 * Get param source type flag as integer:
	 * - `1` - `\MvcCore\IRequest::PARAM_TYPE_QUERY_STRING`
	 * - `2` - `\MvcCore\IRequest::PARAM_TYPE_URL_REWRITE`
	 * - `4` - `\MvcCore\IRequest::PARAM_TYPE_INPUT`
	 * @param  string $name 
	 * @return int
	 */
	public function GetParamSourceType ($name);

	/**
	 * Change existing param source type flag:
	 * - `1` - `\MvcCore\IRequest::PARAM_TYPE_QUERY_STRING`
	 * - `2` - `\MvcCore\IRequest::PARAM_TYPE_URL_REWRITE`
	 * - `4` - `\MvcCore\IRequest::PARAM_TYPE_INPUT`
	 * @param  string $name       Existing param name.
	 * @param  int    $sourceType Param source collection flag(s).
	 * @return \MvcCore\Request
	 */
	public function SetParamSourceType ($name, $sourceType = \MvcCore\IRequest::PARAM_TYPE_ANY);
	
	/**
	 * Get `TRUE` if any non `NULL` param value exists 
	 * in `$_GET`, `$_POST`, `php://input` or in any other source.
	 * @param  string $name
	 * Parameter string name.
	 * @param  int    $sourceType
	 * Param source collection flag(s). If defined, there is 
	 * returned `TRUE` only for param in given collection type(s).
	 * @return bool
	 */
	public function HasParam (
		$name, 
		$sourceType = \MvcCore\IRequest::PARAM_TYPE_ANY
	);
	
	/**
	 * Remove parameter by name.
	 * @param  string $name
	 * @return \MvcCore\Request
	 */
	public function RemoveParam ($name);


	/**
	 * Set directly whole raw global `$_FILES` without any conversion at once.
	 * @param  array<string,array<string,mixed>> $files
	 * @return \MvcCore\Request
	 */
	public function SetFiles (array & $files = []);

	/**
	 * Return reference to configured global `$_FILES`
	 * or reference to any other testing array representing it.
	 * @return array<string,array<string,mixed>>
	 */
	public function GetFiles ();

	/**
	 * Set file item into global `$_FILES` without any conversion at once.
	 * @param  string              $file Uploaded file string name.
	 * @param  array<string,mixed> $data
	 * @return \MvcCore\Request
	 */
	public function SetFile ($file = '', $data = []);

	/**
	 * Return item by file name from referenced global `$_FILES`
	 * or reference to any other testing array item representing it.
	 * @param  string $file Uploaded file string name.
	 * @return array<string,mixed>
	 */
	public function GetFile ($file = '');

	/**
	 * Return if any item by file name exists or not in referenced global `$_FILES`.
	 * @param  string $file Uploaded file string name.
	 * @return bool
	 */
	public function HasFile ($file = '');


	/**
	 * Set directly whole raw global `$_COOKIE` without any conversion at once.
	 * @param  array<string,string> $cookies
	 * @return \MvcCore\Request
	 */
	public function SetCookies (array & $cookies = []);

	/**
	 * Get directly all raw global `$_COOKIE`s at once (with/without conversion).
	 * Cookies are returned as `key => value` array.
	 * @param  CollectionFilter $pregReplaceAllowedChars
	 * If String - list of regular expression characters to only keep, 
	 * if array - `preg_replace()` pattern and reverse, if `FALSE`, 
	 * raw value is returned.
	 * @param  array<string>    $onlyKeys
	 * Array with keys to get only. If empty (by default), 
	 * all possible cookies are returned.
	 * @return array<string,string>
	 */
	public function GetCookies ($pregReplaceAllowedChars = ['#[\<\>\'"]#' => ''], $onlyKeys = []);

	/**
	 * Set raw request cookie into referenced global `$_COOKIE` without any conversion.
	 * @param  string          $name
	 * @param  string|string[] $value
	 * @return \MvcCore\Request
	 */
	public function SetCookie ($name = '', $value = '');

	/**
	 * Get request cookie value from referenced global `$_COOKIE` variable,
	 * filtered by characters defined in second argument through `preg_replace()`.
	 * Place into second argument only char groups you want to keep.
	 * @param  string           $name
	 * Cookie string name.
	 * @param  CollectionFilter $pregReplaceAllowedChars
	 * If String - list of regular expression characters to only keep, 
	 * if array - `preg_replace()` pattern and reverse, if `FALSE`, 
	 * raw value is returned.
	 * @param  CollectionItem   $ifNullValue
	 * Default value returned if given param name is null.
	 * @param  string           $targetType
	 * Target type to retype param value or default if-null value. 
	 * If param is an array, every param item will be retyped 
	 * into given target type.
	 * @throws \InvalidArgumentException
	 * `$name` must be a `$targetType`, not an `array`.
	 * @return CollectionItem
	 */
	public function GetCookie (
		$name = '',
		$pregReplaceAllowedChars = "a-zA-Z0-9_;, /\-\.\@\=\+\?\!",
		$ifNullValue = NULL,
		$targetType = NULL
	);

	/**
	 * Return if any item by cookie name exists or not in referenced global `$_COOKIE`.
	 * @param  string $name Cookie string name.
	 * @return bool
	 */
	public function HasCookie ($name = '');

}
