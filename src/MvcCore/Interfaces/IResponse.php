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
 * - HTTP response wrapper carrying response headers and response body.
 * - Sending response at application terminate process by `\MvcCore\Interfaces\IResponse::Send();` method.
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
	 * Get everytime calling this function new instance of HTTP response.
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
	 * Set HTTP response body.
	 * @param string $body
	 * @return \MvcCore\Interfaces\IResponse
	 */
	public function & SetBody ($body);

	/**
	 * Append HTTP response body.
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
	 * Consolidate headers array from php response headers array.
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
	 * Send all http headers and send response body.
	 * @return void
	 */
	public function Send ();
}
