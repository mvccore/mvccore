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
 * Responsibility - static helpers for core classes inheritance, string conversions and JSON.
 * - Static translation functions (supports containing folder or file path):
 *   - `"dashed-case"		=> "PascalCase"`
 *   - `"PascalCase"		=> "dashed-case"`
 *   - `"unserscore_case"	=> "PascalCase"`
 *   - `"PascalCase"		=> "unserscore_case"`
 * - Static functions to safely encode/decode JSON.
 * - Static functions to get client/server IPs.
 * - Static functions to get system temp directory.
 * - Static functions to safely invoke dangerous call.
 * - Static functions to write into file by one process only.
 * - Static functions to check core classes inheritance.
 */
class Tool implements ITool {
	use \MvcCore\Tool\Json;
	use \MvcCore\Tool\Helpers;
	use \MvcCore\Tool\Reflection;
	use \MvcCore\Tool\StringConversions;
}
