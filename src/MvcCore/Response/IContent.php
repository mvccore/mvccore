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

interface IContent {

	/**
	 * Set HTTP response body.
	 * @param  string $body
	 * @return \MvcCore\Response
	 */
	public function SetBody ($body);

	/**
	 * Prepend HTTP response body.
	 * @param  string $body
	 * @return \MvcCore\Response
	 */
	public function PrependBody ($body);

	/**
	 * Append HTTP response body.
	 * @param  string $body
	 * @return \MvcCore\Response
	 */
	public function AppendBody ($body);

	/**
	 * Get HTTP response body.
	 * @return ?string    
	 */
	public function & GetBody ();

	/**
	 * Consolidate all headers from PHP response
	 * by calling `headers_list()` into local headers list.
	 * @return \MvcCore\Response
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
	 * `TRUE` if headers and body has been sent.
	 * @return bool
	 */
	public function IsSent ();

	/**
	 * `TRUE` if headers has been sent.
	 * @return bool
	 */
	public function IsSentHeaders ();

	/**
	 * `TRUE` if body has been sent.
	 * @return bool
	 */
	public function IsSentBody ();

	/**
	 * Set boolean flag about sended response body content.
	 * This function is used internally to define start point of
	 * response content sending in continuous rendering without output buffer.
	 * @param  bool $bodySent 
	 * @return \MvcCore\Response
	 */
	public function SetBodySent ($bodySent = TRUE);

	/**
	 * Send all HTTP headers and send response body.
	 * @return \MvcCore\Response
	 */
	public function Send ();

	/**
	 * Send all HTTP headers.
	 * @return \MvcCore\Response
	 */
	public function SendHeaders ();

	/**
	 * Send response body.
	 * @return \MvcCore\Response
	 */
	public function SendBody ();

}
