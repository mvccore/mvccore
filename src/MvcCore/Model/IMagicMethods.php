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

namespace MvcCore\Model;

interface IMagicMethods {
	
	/**
	 * Sets any custom property `"PropertyName"` by `\MvcCore\Model::SetPropertyName("value")`,
	 * which is not necessary to define previously or gets previously defined
	 * property `"PropertyName"` by `\MvcCore\Model::GetPropertyName();`.
	 * Throws exception if no property defined by get call
	 * or if virtual call begins with anything different from `Set` or `Get`.
	 * This method returns custom value for get and `\MvcCore\Model` instance for set.
	 * @param  string            $rawName
	 * @param  array<int,mixed>  $arguments
	 * @throws \InvalidArgumentException If `strtolower($rawName)` doesn't begin with `"get"` or with `"set"`.
	 * @return mixed|\MvcCore\Model
	 */
	public function __call ($rawName, $arguments = []);

	/**
	 * Set any custom property, not necessary to previously defined.
	 * @param  string $name
	 * @param  mixed  $value
	 * @throws \InvalidArgumentException If name is `initialValues` or any custom name in extended class.
	 * @return void
	 */
	public function __set ($name, $value);

	/**
	 * Get any custom property, not necessary to previously defined,
	 * if property is not defined, NULL is returned.
	 * @param  string $name
	 * @throws \InvalidArgumentException If name is `initialValues` or any custom name in extended class.
	 * @return mixed
	 */
	public function __get ($name);

	/**
	 * Collect all properties names to serialize them by `serialize()` method.
	 * Collect all instance properties declared as private, protected and public
	 * and if there is configured in `static::$protectedProperties` anything as
	 * `TRUE` (under key by property name), also return those properties in
	 * result array.
	 * @return array<string>
	 */
	public function __sleep ();

	/**
	 * Returns data which can be serialized by `json_encode()`, 
	 * which is a value of any type other than a resource.
	 * Possible reading flags:
	 *  - `\MvcCore\IModel::PROPS_INHERIT`	- default
	 *  - `\MvcCore\IModel::PROPS_PRIVATE`
	 *  - `\MvcCore\IModel::PROPS_PROTECTED`- default
	 *  - `\MvcCore\IModel::PROPS_PUBLIC`
	 * @param  int $propsFlags Flags used by default:
	 * `\MvcCore\IModel::PROPS_PROTECTED | \MvcCore\IModel::PROPS_INHERIT`
	 * @return mixed
	 */
	public function jsonSerialize ($propsFlags = 0);

}