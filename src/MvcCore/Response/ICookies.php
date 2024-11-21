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

interface ICookies {
	
	/**
	 * Send a cookie.
	 * @param  string $name      Cookie name. Assuming the name is `cookiename`, this value is retrieved through `$_COOKIE['cookiename']`.
	 * @param  string $value     The value of the cookie. This value is stored on the clients computer; do not store sensitive information.
	 * @param  int    $lifetime  Life time in seconds to expire. 0 means "until the browser is closed".
	 * @param  string $path      The path on the server in which the cookie will be available on. If set to '/', the cookie will be available within the entire domain.
	 * @param  string $domain    If not set, value is completed by `\MvcCore\Application::GetInstance()->GetRequest()->GetHostName();` .
	 * @param  bool   $secure    If not set, value is completed by `\MvcCore\Application::GetInstance()->GetRequest()->IsSecure();`.
	 * @param  bool   $httpOnly  HTTP only cookie, `TRUE` by default.
	 * @param  string $sameSite  HTTP cookie `SameSite` attribute. Default value is `Lax`.
	 * @throws \RuntimeException If HTTP headers have been sent.
	 * @return bool              True if cookie has been set.
	 */
	public function SetCookie (
		$name, $value,
		$lifetime = 0, $path = '/',
		$domain = NULL, $secure = NULL, 
		$httpOnly = TRUE, $sameSite = \MvcCore\Response::COOKIE_SAMESITE_LAX
	);

	/**
	 * Delete cookie - set value to empty string and set expiration to past time.
	 * @param  string $name      Cookie name. Assuming the name is `cookiename`, this value is retrieved through `$_COOKIE['cookiename']`.
	 * @param  string $path      The path on the server in which the cookie will be available on. If set to '/', the cookie will be available within the entire domain.
	 * @param  string $domain    If not set, value is completed by `\MvcCore\Application::GetInstance()->GetRequest()->GetHostName();` .
	 * @param  bool   $secure    If not set, value is completed by `\MvcCore\Application::GetInstance()->GetRequest()->IsSecure();`.
	 * @throws \RuntimeException If HTTP headers have been sent.
	 * @return bool              True if cookie has been set.
	 */
	public function DeleteCookie ($name, $path = '/', $domain = NULL, $secure = NULL);

}
