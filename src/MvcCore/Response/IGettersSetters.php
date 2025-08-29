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

interface IGettersSetters {

	/**
	 * Set cookie name(s) for session id for secure or/and for non-secure connection.
	 * @param  ?string $secureConnCookieName 
	 * @param  ?string $nonSecureConnCookieName
	 * @return array{0:?string,1:?string}
	 */
	public static function SetSessionIdCookieNames ($secureConnCookieName, $nonSecureConnCookieName);

	/**
	 * Get CSRF protection cookie name. `__MCP` by default.
	 * @return string
	 */
	public static function GetCsrfProtectionCookieName ();
	
	/**
	 * Set CSRF protection cookie name. `__MCP` by default.
	 * @param  string $csrfProtectionCookieName 
	 * @return string
	 */
	public static function SetCsrfProtectionCookieName ($csrfProtectionCookieName);

	/**
	 * Get HTTP response headers names, which are allowed to use multiple times.
	 * @return array<string>
	 */
	public static function GetMultiplyHeaders ();
	
	/**
	 * Set HTTP response headers names, which are allowed to use multiple times.
	 * @param  array<string> $multiplyHeaders 
	 * @return array<string>
	 */
	public static function SetMultiplyHeaders ($multiplyHeaders);
	
	/**
	 * Get cookie name for session id by secured or by non secured request.
	 * @return string
	 */
	public function GetSessionIdCookieName ();

	/**
	 * Get response protocol HTTP version by `$_SERVER['SERVER_PROTOCOL']`,
	 * `HTTP/1.1` by default.
	 * @return string
	 */
	public function GetHttpVersion ();

	/**
	 * Set response protocol HTTP version - `HTTP/1.1 | HTTP/2.0`...
	 * @param  string $httpVersion
	 * @return \MvcCore\Response
	 */
	public function SetHttpVersion ($httpVersion);

	/**
	 * Set HTTP response code.
	 * @param  ?int        $code
	 * @param  ?string $codeMessage
	 * @return \MvcCore\Response
	 */
	public function SetCode ($code, $codeMessage = NULL);

	/**
	 * Get HTTP response code.
	 * @return ?int     `200 | 301 | 404`
	 */
	public function GetCode ();
	
	/**
	 * Set HTTP response content encoding.
	 * Example: `$response->SetEncoding('utf-8');`
	 * @param  string $encoding
	 * @return \MvcCore\Response
	 */
	public function SetEncoding ($encoding = 'utf-8');

	/**
	 * Get HTTP response content encoding.
	 * Example: `$response->GetEncoding(); // returns 'utf-8'`
	 * @return ?string
	 */
	public function GetEncoding ();

	/**
	 * Return `TRUE` if response defines redirect `Location` http header.
	 * @return bool
	 */
	public function IsRedirect ();

	/**
	 * Return `TRUE` of HTTP headers and any response body content has been send.
	 * @return bool
	 */
	public function IsSent ();

}
