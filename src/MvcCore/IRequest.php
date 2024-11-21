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
 * Responsibility - request description - URL and params inputs parsing and cleaning.
 * - Linear request URL parsing from referenced `$_SERVER` global variable
 *   (as constructor argument) into local properties, describing URL sections.
 * - Params reading from referenced `$_GET` and `$_POST` global variables
 *   (as constructor arguments) or reading data from direct PHP
 *   input `"php://input"` (as encoded JSON data or as query string).
 * - Headers cleaning and reading by `getallheaders()` or from referenced `$_SERVER['HTTP_...']`.
 * - Cookies cleaning and reading from referenced `$_COOKIE['...']`.
 * - Uploaded files by wrapped referenced `$_FILES` global array.
 * - Primitive values cleaning or array recursive cleaning by called
 *   developer rules from params array, headers array and cookies array.
 */
interface	IRequest
extends		\MvcCore\Request\IConstants,
			\MvcCore\Request\IGettersSetters,
			\MvcCore\Request\ICollectionsMethods,
			\MvcCore\Request\IInstancing,
			\MvcCore\Request\IInternalInits {
}
