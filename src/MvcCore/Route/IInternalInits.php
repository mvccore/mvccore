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

namespace MvcCore\Route;

interface IInternalInits {
	
	/**
	 * Initialize all possible protected values (`match`, `reverse` etc...). This 
	 * method is not recommended to use in production mode, it's designed mostly 
	 * for development purposes, to see what could be inside route object.
	 * @return \MvcCore\Route
	 */
	public function InitAll ();

	/**
	 * Collect all properties names to serialize them by `serialize()` method.
	 * Collect all instance properties declared as private, protected and public
	 * and if there is not configured in `static::$protectedProperties` anything
	 * under property name, return those properties in result array.
	 * @return array<string>
	 */
	public function __sleep ();

	/**
	 * Assign router instance to local property `$this->router;`.
	 * @return void
	 */
	public function __wakeup ();

}
