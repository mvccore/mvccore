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

namespace MvcCore\Controller;

#[\Attribute(\Attribute::TARGET_CLASS)]
class AutoInit {

	const PHP_DOCS_TAG = '@autoInit';

	/**
	 * Define this attribute for any controller property 
	 * necessary to initialize in `\MvcCore\Controller::Init()`
	 * automatically. 
	 * Instance is created by automatically localized local 
	 * context factory method with name '[_]create' + upper cased property 
	 * name (or with factory method name optionally defined 
	 * as first argument). If no factory method like that is found, 
	 * instance is created by property type static method 
	 * `CreateInstance()` or by property type `__construct()` method 
	 * with no arguments.
	 * @param string|NULL $factoryMethodName
	 */
	public function __construct ($factoryMethodName = NULL) {
	}
}