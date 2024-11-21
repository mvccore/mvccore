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

namespace MvcCore\Response;

interface IHeaders {
	
	/**
	 * Return `FALSE` if no HTTP headers have already been sent or `TRUE` otherwise.
	 * @return bool
	 */
	public function IsSentHeaders ();

	/**
	 * Set multiple HTTP response headers as `key => value` array.
	 * All previous request object and PHP headers are removed 
	 * and given headers will be only headers for output.
	 * There is automatically set response encoding from value for
	 * `Content-Type` header, if contains any `charset=...`.
	 * There is automatically set response encoding from value for
	 * `Content-Encoding` header.
	 * Example: `$request->SetHeader(array('Content-Type' => 'text/plain; charset=utf-8'));`
	 * @param  array<string,string|int|array<string|int>> $headers
	 * @return \MvcCore\Response
	 */
	public function SetHeaders (array $headers = []);

	/**
	 * Add multiple HTTP response headers as `key => value` array.
	 * All given headers are automatically merged with previously setted headers.
	 * There is automatically set response encoding from value for
	 * `Content-Type` header, if contains any `charset=...`.
	 * There is automatically set response encoding from value for
	 * `Content-Encoding` header.
	 * Example: `$request->SetHeader(array('Content-Type' => 'text/plain; charset=utf-8'));`
	 * @param  array<string,string|int|array<string|int>> $headers
	 * @param  bool                                       $cleanAllPrevious
	 * @return \MvcCore\Response
	 */
	public function AddHeaders (array $headers = [], $cleanAllPrevious = FALSE);

	/**
	 * Set HTTP response header, all previous header values will be overwritten.
	 * There is automatically set response encoding from value for
	 * `Content-Type` header, if contains any `charset=...`.
	 * There is automatically set response encoding from value for
	 * `Content-Encoding` header.
	 * Example: `$request->SetHeader('Content-Type', 'text/plain; charset=utf-8');`
	 * @param  string                       $name
	 * @param  string|int|array<string|int> $value
	 * @return \MvcCore\Response
	 */
	public function SetHeader ($name, $value);

	/**
	 * Add HTTP response header, if there is any previous header value
	 * and header is allowed with multiple values, value is added.
	 * If header is ingle value header, value is overwritten.
	 * There is automatically set response encoding from value for
	 * `Content-Type` header, if contains any `charset=...`.
	 * There is automatically set response encoding from value for
	 * `Content-Encoding` header.
	 * Example: `$request->SetHeader('Content-Type', 'text/plain; charset=utf-8');`
	 * @param  string               $name
	 * @param  string|array<string> $value
	 * @return \MvcCore\Response
	 */
	public function AddHeader ($name, $value);

	/**
	 * Get HTTP response header by name. If header doesn't exists, null is returned.
	 * Example: `$response->GetHeader('Content-Type'); // returns 'text/plain; charset=utf-8'`
	 * Example: `$response->GetHeader('Set-Cookie');   // returns ['PHPSESSID=...; path=/; secure; HttpOnly'`]
	 * @param  string $name
	 * @return string|array<string>|NULL
	 */
	public function GetHeader ($name);

	/**
	 * Get if response has any HTTP response header by given `$name`.
	 * Example:
	 * ````
	 *   $response->HasHeader('Content-Type'); // returns TRUE if there is header 'Content-Type'
	 *   $response->HasHeader('content-type'); // returns FALSE if there is header 'Content-Type'
	 * ````
	 * @param  string $name
	 * @return bool
	 */
	public function HasHeader ($name);

	/**
	 * Remove HTTP response header by given `$name`.
	 * Return `TRUE` if header was there, `FALSE` otherwise.
	 * Example:
	 * ````
	 *   $response->RemoveHeader('Content-type');
	 *   $response->RemoveHeader('content-type');
	 * ````
	 * @param  string $name
	 * @return bool
	 */
	public function RemoveHeader ($name);

	/**
	 * Update HTTP headers list in response object from PHP function `headers_list();`.
	 * Use this function after somewhere in your code is used any internal PHP function
	 * for headers manipulation to update MvcCore response object headers list.
	 * @return \MvcCore\Response
	 */
	public function UpdateHeaders ();

	/**
	 * Set disabled headers, never sent except if there is
	 * rendered exception in development environment.
	 * @param  array<string> $disabledHeaders,...
	 * @return \MvcCore\Response
	 */
	public function SetDisabledHeaders ($disabledHeaders);

	/**
	 * Get disabled headers, never sent except if there is
	 * rendered exception in development environment.
	 * @return array<string>
	 */
	public function GetDisabledHeaders ();

}
