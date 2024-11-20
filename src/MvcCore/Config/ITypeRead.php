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

namespace MvcCore\Config;

interface ITypeRead {
	
	/**
	 * Load config file and return `TRUE` for success or `FALSE` in failure.
	 * - Load all sections for all environment names into `$this->envData` collection.
	 * - Retype all raw string values into `float`, `int` or `boolean` types.
	 * - Retype collections into `\stdClass`, if there are no numeric keys.
	 * @return bool
	 */
	public function Read ();

}