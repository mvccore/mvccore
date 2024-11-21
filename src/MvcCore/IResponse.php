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

namespace MvcCore;

/**
 * Responsibility - completing all information for response - headers (cookies) and content.
 * - HTTP response wrapper carrying response headers and response body.
 * - PHP `setcookie` function wrapper to complete default values such domain or http only etc.
 * - Sending response at application terminate process by `\MvcCore\IResponse::Send();`.
 * - Completing MvcCore performance header at response end.
 */
interface	IResponse
extends		\MvcCore\Response\IConstants,
			\MvcCore\Response\IInstancing,
			\MvcCore\Response\IHeaders,
			\MvcCore\Response\ICookies,
			\MvcCore\Response\IGettersSetters,
			\MvcCore\Response\IContent {
}
