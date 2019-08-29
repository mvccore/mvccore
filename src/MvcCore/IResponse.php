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

namespace MvcCore;

/**
 * Responsibility - completing all information for response - headers (cookies) and content.
 * - HTTP response wrapper carrying response headers and response body.
 * - PHP `setcookie` function wrapper to complete default values such domain or http only etc.
 * - Sending response at application terminate process by `\MvcCore\IResponse::Send();`.
 * - Completing MvcCore performance header at response end.
 */
interface IResponse
{
	/**
	 * HTTP response code 200 for OK response;
	 */
	const OK = 200;

	/**
	 * HTTP response code 301 for moved permanently redirection;
	 */
	const MOVED_PERMANENTLY = 301;

	/**
	 * HTTP response code 303 for see other redirection;
	 */
	const SEE_OTHER = 303;

	/**
	 * HTTP response code 404 for not found error;
	 */
	const NOT_FOUND = 404;

	/**
	 * HTTP response code 500 for internal server error;
	 */
	const INTERNAL_SERVER_ERROR = 500;

	/**
	 * MvcCore internal header always sent in every response.
	 */
	const HEADER_X_MVCCORE_CPU_RAM = 'X-MvcCore-Cpu-Ram';

	/**
	 * No singleton, get every time new instance of configured HTTP response
	 * class in `\MvcCore\Application::GetInstance()->GetResponseClass();`.
	 * @param int|NULL	$code
	 * @param array		$headers
	 * @param string	$body
	 * @return \MvcCore\IResponse
	 */
	public static function CreateInstance (
		$code = NULL,
		$headers = [],
		$body = ''
	);


	/**
	 * Get response protocol HTTP version by `$_SERVER['SERVER_PROTOCOL']`, 
	 * `HTTP/1.1` by default.
	 * @return string
	 */
	public function GetHttpVersion ();

	/**
	 * Set response protocol HTTP version - `HTTP/1.1 | HTTP/2.0`...
	 * @param string $httpVersion
	 * @return \MvcCore\IResponse
	 */
	public function & SetHttpVersion ($httpVersion);

	/**
	 * Set HTTP response code.
	 * @param int $code
	 * @param string|NULL $codeMessage
	 * @return \MvcCore\IResponse
	 */
	public function & SetCode ($code, $codeMessage = NULL);

	/**
	 * Get HTTP response code.
	 * @return int `200 | 301 | 404`
	 */
	public function GetCode ();

	/**
	 * Set multiple HTTP response headers as `key => value` array.
	 * All given headers are automatically merged with previously setted headers.
	 * If you change second argument to true, all previous request object and PHP
	 * headers are removed and given headers will be only headers for output.
	 * There is automatically set response encoding from value for
	 * `Content-Type` header, if contains any `charset=...`.
	 * There is automatically set response encoding from value for
	 * `Content-Encoding` header.
	 * Example: `$request->SetHeader(array('Content-Type' => 'text/plain; charset=utf-8'));`
	 * @param array $headers
	 * @param bool $cleanAllPrevious `FALSE` by default. If `TRUE`, all previous headers
	 *								 set by PHP `header()` or by this object will be removed.
	 * @return \MvcCore\IResponse
	 */
	public function & SetHeaders (array $headers = []);

	/**
	 * Set HTTP response header.
	 * There is automatically set response encoding from value for
	 * `Content-Type` header, if contains any `charset=...`.
	 * There is automatically set response encoding from value for
	 * `Content-Encoding` header.
	 * Example: `$request->SetHeader('Content-Type', 'text/plain; charset=utf-8');`
	 * @param string $name
	 * @param string $value
	 * @return \MvcCore\IResponse
	 */
	public function & SetHeader ($name, $value);

	/**
	 * Get HTTP response header by name.
	 * Example: `$request->GetHeader('Content-Type'); // returns 'text/plain; charset=utf-8'`
	 * @param string $name
	 * @return string|NULL
	 */
	public function GetHeader ($name);

	/**
	 * Get if response has any HTTP response header by given `$name`.
	 * Example:
	 *	`$request->GetHeader('Content-Type'); // returns TRUE if there is header 'Content-Type'
	 *	`$request->GetHeader('content-type'); // returns FALSE if there is header 'Content-Type'
	 * @param string $name
	 * @return bool
	 */
	public function HasHeader ($name);

	/**
	 * Set HTTP response content encoding.
	 * Example: `$response->SetEncoding('utf-8');`
	 * @param string $encoding
	 * @return \MvcCore\IResponse
	 */
	public function & SetEncoding ($encoding = 'utf-8');

	/**
	 * Get HTTP response content encoding.
	 * Example: `$response->GetEncoding(); // returns 'utf-8'`
	 * @return string|NULL
	 */
	public function GetEncoding ();

	/**
	 * Set HTTP response body.
	 * @param string $body
	 * @return \MvcCore\IResponse
	 */
	public function & SetBody ($body);

	/**
	 * Prepend HTTP response body.
	 * @param string $body
	 * @return \MvcCore\IResponse
	 */
	public function & PrependBody ($body);

	/**
	 * Append HTTP response body.
	 * @param string $body
	 * @return \MvcCore\IResponse
	 */
	public function & AppendBody ($body);

	/**
	 * Get HTTP response body.
	 * @return string|NULL
	 */
	public function & GetBody ();

	/**
	 * Consolidate all headers from PHP response
	 * by calling `headers_list()` into local headers list.
	 * @return void
	 */
	public function UpdateHeaders ();

	/**
	 * Return if response has any redirect `"Location: ..."` header inside.
	 * @return bool
	 */
	public function IsRedirect ();

	/**
	 * Returns if response has any `text/html` or `application/xhtml+xml`
	 * substring in `Content-Type` header.
	 * @return bool
	 */
	public function IsHtmlOutput ();

	/**
	 * Returns if response has any `xml` substring in `Content-Type` header.
	 * @return bool
	 */
	public function IsXmlOutput ();

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
	 * @param string $name		Cookie name. Assuming the name is `cookiename`, this value is retrieved through `$_COOKIE['cookiename']`.
	 * @param string $value	   The value of the cookie. This value is stored on the clients computer; do not store sensitive information.
	 * @param int	$lifetime	Life time in seconds to expire. 0 means "until the browser is closed".
	 * @param string $path		The path on the server in which the cookie will be available on. If set to '/', the cookie will be available within the entire domain.
	 * @param string $domain	  If not set, value is completed by `\MvcCore\Application::GetInstance()->GetRequest()->GetHostName();` .
	 * @param bool   $secure	  If not set, value is completed by `\MvcCore\Application::GetInstance()->GetRequest()->IsSecure();`.
	 * @param bool   $httpOnly	HTTP only cookie, `TRUE` by default.
	 * @throws \RuntimeException  If HTTP headers have been sent.
	 * @return bool			   True if cookie has been set.
	 */
	public function SetCookie (
		$name, $value,
		$lifetime = 0, $path = '/',
		$domain = NULL, $secure = NULL, $httpOnly = TRUE
	);

	/**
	 * Delete cookie - set value to empty string and
	 * set expiration to "until the browser is closed".
	 * @param string $name		Cookie name. Assuming the name is `cookiename`, this value is retrieved through `$_COOKIE['cookiename']`.
	 * @param string $path		The path on the server in which the cookie will be available on. If set to '/', the cookie will be available within the entire domain.
	 * @param string $domain	  If not set, value is completed by `\MvcCore\Application::GetInstance()->GetRequest()->GetHostName();` .
	 * @param bool   $secure	  If not set, value is completed by `\MvcCore\Application::GetInstance()->GetRequest()->IsSecure();`.
	 * @throws \RuntimeException  If HTTP headers have been sent.
	 * @return bool			   True if cookie has been set.
	 */
	public function DeleteCookie ($name, $path = '/', $domain = NULL, $secure = NULL);

	/**
	 * Set disabled headers, never sent except if there is 
	 * rendered exception in development environment.
	 * @param \string[] $disabledHeaders,...
	 * @return \MvcCore\IResponse
	 */
	public function & SetDisabledHeaders ($disabledHeaders);
	
	/**
	 * Get disabled headers, never sent except if there is 
	 * rendered exception in development environment.
	 * @return \string[]
	 */
	public function GetDisabledHeaders ();
}
