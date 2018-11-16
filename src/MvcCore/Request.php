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
 *	 developer rules from params array, headers array and cookies array.
 */
class Request implements IRequest
{
	use \MvcCore\Request\Props;
	use \MvcCore\Request\GettersSetters;
	use \MvcCore\Request\Instancing;
	use \MvcCore\Request\InternalInits;
	use \MvcCore\Request\CollectionsMethods;
}
