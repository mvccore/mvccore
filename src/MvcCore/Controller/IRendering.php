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

namespace MvcCore\Controller;

interface IRendering {
	
	/**
	 * Rendering process alias for `\MvcCore\Controller::Render();`.
	 * @return string
	 */
	public function __toString ();

	/**
	 * - This method is called INTERNALLY in lifecycle dispatching process,
	 *   but you can use it sooner or in any different time for custom render purposes.
	 * - Render prepared controller/action view in path by default:
	 * `"/App/Views/Scripts/<ctrl-dashed-name>/<action-dashed-name>.phtml"`.
	 * - If controller has no other parent controller, render layout view around action view.
	 * - For top most parent controller - store rendered action and layout view in response object and return empty string.
	 * - For child controller - return rendered action view as string.
	 * @param  ?string     $controllerOrActionNameDashed
	 * @param  ?string     $actionNameDashed
	 * @return string
	 */
	public function Render ($controllerOrActionNameDashed = NULL, $actionNameDashed = NULL);

	/**
	 * Store rendered HTML output inside `\MvcCore\Controller::$response`
	 * to send into client browser later in `\MvcCore\Application::GetInstance()->Terminate();`.
	 * Set up `Content-Type` header to `text/html` or to `application/xhtml+xml` only if not set.
	 * @param  string $output
	 * @param  bool   $terminate
	 * @return void
	 */
	public function HtmlResponse ($output, $terminate = TRUE);

	/**
	 * Store rendered XML output inside `\MvcCore\Controller::$response`
	 * to send into client browser later in `\MvcCore\Application::GetInstance()->Terminate();`.
	 * Set up `Content-Type` header to `application/xml` only if not set.
	 * @param  string $output
	 * @param  bool   $terminate
	 * @return void
	 */
	public function XmlResponse ($output, $terminate = TRUE);

	/**
	 * Store rendered text output inside `\MvcCore\Controller::$response`
	 * to send into client browser later in `\MvcCore\Application::GetInstance()->Terminate();`.
	 * Set up `Content-Type` header to `text/plain`.
	 * @param  string $output
	 * @param  bool   $terminate
	 * @return void
	 */
	public function TextResponse ($output, $terminate = TRUE);

	/**
	 * Serialize any PHP value into `JSON string` and store
	 * it inside `\MvcCore\Controller::$response` to send it
	 * into client browser later in `\MvcCore\Application::GetInstance()->Terminate();`.
	 * Set up `Content-Type` header to `application/json`.
	 * 
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
	 * @param  mixed $data
	 * @param  bool  $terminate
	 * @param  int   $jsonEncodeFlags
	 * @throws \Exception JSON encoding error.
	 * @return void
	 */
	public function JsonResponse ($data, $terminate = TRUE, $jsonEncodeFlags = 0);

	/**
	 * Serialize any PHP value into `JSON string`, wrap around prepared public
	 * javascript function in target window sent as `$_GET` param under
	 * variable `$callbackParamName` (allowed chars: `a-zA-Z0-9\.\-_\$`) and
	 * store it inside `\MvcCore\Controller::$response` to send it
	 * into client browser later in `\MvcCore\Application::GetInstance()->Terminate();`.
	 * Set up `Content-Type` header to `application/javascript`.
	 * 
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
	 * @param  mixed  $data
	 * @param  string $callbackParamName
	 * @param  bool   $terminate
	 * @param  int    $jsonEncodeFlags
	 * @throws \Exception JSON encoding error.
	 * @return void
	 */
	public function JsonpResponse ($data, $callbackParamName = 'callback', $terminate = TRUE, $jsonEncodeFlags = 0);

	/**
	 * Render error controller and error action
	 * for any dispatch exception or error as
	 * rendered html response or as plain text response.
	 * @param  string|mixed $errorMessage
	 * @return void
	 */
	public function RenderError ($errorMessage);

	/**
	 * Render not found controller and not found action
	 * for any dispatch exception with code 404 as
	 * rendered html response or as plain text response.
	 * @param  string|mixed $errorMessage
	 * @return void
	 */
	public function RenderNotFound ($errorMessage);

	/**
	 * Complete view script path by given controller and action or only by given action rendering arguments.
	 * @param  string $controllerOrActionNameDashed
	 * @param  string $actionNameDashed
	 * @return string
	 */
	public function GetViewScriptPath ($controllerOrActionNameDashed = NULL, $actionNameDashed = NULL);

}