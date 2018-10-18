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
class Response implements IResponse
{
	use \MvcCore\Response\PropsGettersSetters;
	use \MvcCore\Response\Instancing;
	use \MvcCore\Response\Headers;
	use \MvcCore\Response\Cookies;
	use \MvcCore\Response\Content;
}
