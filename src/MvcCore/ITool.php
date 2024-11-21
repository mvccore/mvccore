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
 * Responsibility - static helpers for core classes.
 * - Static functions for string case conversions.
 * - Static functions to safely encode/decode JSON.
 * - Static functions to get client/server IPs.
 * - Static functions to get system temp directory.
 * - Static functions to safely invoke dangerous calls.
 * - Static functions to write into file by one process only.
 * - Static functions to check core classes inheritance.
 * - Static functions to cache and read attributes (or PhpDocs tags).
 * @extends \MvcCore\Tool\IReflection<object>
 */
interface	ITool
extends		\MvcCore\Tool\IStringConversions,
			\MvcCore\Tool\IJson,
			\MvcCore\Tool\IHelpers,
			\MvcCore\Tool\IReflection {
}
