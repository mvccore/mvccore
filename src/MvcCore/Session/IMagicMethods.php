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

namespace MvcCore\Session;

interface IMagicMethods {
	
	/**
	 * Magic function triggered by: `$value = \MvcCore\Session->key;`.
	 * @param  string $key
	 * @return mixed
	 */
	public function __get ($key);

	/**
	 * Magic function triggered by: `\MvcCore\Session->key = "value";`.
	 * @param  string $key
	 * @param  mixed  $value
	 * @return void
	 */
	public function __set ($key, $value);

	/**
	 * Magic function triggered by: `isset(\MvcCore\Session->key);`.
	 * @param  string $key
	 * @return bool
	 */
	public function __isset ($key);

	/**
	 * Magic function triggered by: `unset(\MvcCore\Session->key);`.
	 * @param  string $key
	 * @return void
	 */
	public function __unset ($key);

}
