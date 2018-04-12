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
 * Responsibility - completing all information for response - headers (cookies) and content.
 * - HTTP response wrapper carrying response headers and response body.
 * - PHP `setcookie` function wrapper to complete default values such domain or http only etc.
 * - Sending response at application terminate process by `\MvcCore\Interfaces\IResponse::Send();`.
 * - Completing MvcCore performance header at response end.
 */
interface IResponse
{
	const OK = 200;
	const MOVED_PERMANENTLY = 301;
	const SEE_OTHER = 303;
	const NOT_FOUND = 404;
	const INTERNAL_SERVER_ERROR = 500;

	/**
	 * No singleton, get everytime new instance of configured HTTP response
	 * class in `\MvcCore\Application::GetInstance()->GetResponseClass();`.
	 * @param int		$code
	 * @param array		$headers
	 * @param string	$body
	 * @return \MvcCore\Interfaces\IResponse
	 */
	public static function GetInstance (
		$code = \MvcCore\Interfaces\IResponse::OK,
		$headers = array(),
		$body = ''
	);

	/**
	 * Set HTTP response code.
	 * @param int $code
	 * @return \MvcCore\Interfaces\IResponse
	 */
	public function & SetCode ($code);

	/**
	 * Set HTTP response header.
	 * @param string $name
	 * @param string $value
	 * @return \MvcCore\Interfaces\IResponse
	 */
	public function & SetHeader ($name, $value);

	/**
	 * Set multiple HTTP response headers as `key => value` array.
	 * @param array $headers
	 * @return \MvcCore\Interfaces\IResponse
	 */
	public function & SetHeaders ($headers = array());

	/**
	 * Set HTTP response body.
	 * @param string $body
	 * @return \MvcCore\Interfaces\IResponse
	 */
	public function & SetBody ($body);

	/**
	 * Prepend HTTP response body.
	 * @param string $body
	 * @return \MvcCore\Interfaces\IResponse
	 */
	public function PrependBody ($body);

	/**
	 * Append HTTP response body.
	 * @param string $body
	 * @return \MvcCore\Interfaces\IResponse
	 */
	public function AppendBody ($body);

	/**
	 * Consolidate headers array from PHP response headers array by calling `headers_list()`.
	 * @return void
	 */
	public function UpdateHeaders ();

	/**
	 * Return if response has any redirect `"Location: ..."` header inside.
	 * @return bool
	 */
	public function IsRedirect ();

	/**
	 * Return if response has any html/xhtml header inside.
	 * @return bool
	 */
	public function IsHtmlOutput ();

	/**
	 * `TRUE` if headers or body has been sent.
	 * @return bool
	 */
	public function IsSent ();

	/**
	 * Send all http headers and send response body.
	 * @return void
	 */
	public function Send ();

	/**
	 * Send a cookie.
	 * @param string $name        Cookie name. Assuming the name is `cookiename`, this value is retrieved through `$_COOKIE['cookiename']`.
	 * @param string $value       The value of the cookie. This value is stored on the clients computer; do not store sensitive information.
	 * @param int    $lifetime    Life time in seconds to expire. 0 means "until the browser is closed".
	 * @param string $path        The path on the server in which the cookie will be available on. If set to '/', the cookie will be available within the entire domain.
	 * @param string $domain      If not set, value is completed by `\MvcCore\Application::GetInstance()->GetRequest()->GetServerName();` .
	 * @param bool   $secure      If not set, value is completed by `\MvcCore\Application::GetInstance()->GetRequest()->IsSecure();`.
	 * @param bool   $httpOnly    HTTP only cookie, `TRUE` by default.
	 * @throws \RuntimeException  If HTTP headers have been sent.
	 * @return bool               True if cookie has been set.
	 */
	public function SetCookie (
		$name, $value,
		$lifetime = 0, $path = '/',
		$domain = NULL, $secure = NULL, $httpOnly = TRUE
	);

	/**
	 * Delete cookie - set value to empty string and
	 * set expiration to "until the browser is closed".
	 * @param string $name        Cookie name. Assuming the name is `cookiename`, this value is retrieved through `$_COOKIE['cookiename']`.
	 * @param string $path        The path on the server in which the cookie will be available on. If set to '/', the cookie will be available within the entire domain.
	 * @param string $domain      If not set, value is completed by `\MvcCore\Application::GetInstance()->GetRequest()->GetServerName();` .
	 * @param bool   $secure      If not set, value is completed by `\MvcCore\Application::GetInstance()->GetRequest()->IsSecure();`.
	 * @throws \RuntimeException  If HTTP headers have been sent.
	 * @return bool               True if cookie has been set.
	 */
	public function DeleteCookie ($name, $path = '/', $domain = NULL, $secure = NULL);
}
