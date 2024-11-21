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

namespace MvcCore\View;

interface IMagicMethods {
	
	/**
	 * Try to call view helper.
	 * If view helper doesn't exist in global helpers store - create new helper instance.
	 * If helper already exists in global helpers store - do not create it again - use instance from the store.
	 * Then call it's public method named in the same way as helper and return result
	 * as it is, without any conversion. So then there could be called any other helper method if whole helper instance is returned.
	 * @param  string         $method    View helper method name in pascal case.
	 * @param  mixed          $arguments View helper method arguments.
	 * @throws \InvalidArgumentException If view doesn't exist in configured namespaces.
	 * @return string|mixed              View helper string result or any other view helper result type or view helper instance, always as `\MvcCore\Ext\Views\Helpers\AbstractHelper|\MvcCore\Ext\Views\Helpers\IHelper` instance.
	 */
	public function __call ($method, $arguments);
	
	/**
	 * Set any value into view context internal store.
	 * @param  string $name
	 * @param  mixed  $value
	 * @return void
	 */
	public function __set ($name, $value);

	/**
	 * Get any value by given name existing in local store. If there is no value
	 * in local store by given name, try to get result value into store by
	 * controller reflection class from controller instance property.
	 * @param  string $name
	 * @return mixed
	 */
	public function & __get ($name);

	/**
	 * Get `TRUE` if any value by given name exists in
	 * local view store or in local controller instance.
	 * @param  string $name
	 * @return bool
	 */
	public function __isset ($name);

	/**
	 * Unset any value from view context internal store.
	 * @param  string $name
	 * @return void
	 */
	public function __unset ($name);

}
