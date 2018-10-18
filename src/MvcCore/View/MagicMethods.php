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

namespace MvcCore\View;

trait MagicMethods
{
	/**
	 * Set any value into view context internal store.
	 * @param string $name
	 * @param mixed $value
	 * @return bool
	 */
	public function __set ($name, $value) {
		return $this->__protected['store'][$name] = & $value;
	}

	/**
	 * Get any value by given name existing in local store. If there is no value
	 * in local store by given name, try to get result value into store by
	 * controller reflection class from controller instance property.
	 * @param string $name
	 * @return mixed
	 */
	public function __get ($name) {
		$store = & $this->__protected['store'];
		// if property is in view store - return it
		if (array_key_exists($name, $store))
			return $store[$name];
		// if property is not in view store - try to get it from controller and set it into local view store
		if ($controllerType = $this->getReflectionClass('controller')) {
			if ($controllerType->hasProperty($name)) {
				/** @var $property \ReflectionProperty */
				$property = $controllerType->getProperty($name);
				if (!$property->isStatic()) {
					if (!$property->isPublic()) $property->setAccessible (TRUE); // protected or private
					$value = $property->getValue($this->controller);
					$store[$name] = & $value;
					return $value;
				}
			}
		}
		// return null, if property is not in local store an even not in controller
		return NULL;
	}

	/**
	 * Get `TRUE` if any value by given name exists in
	 * local view store or in local controller instance.
	 * @param string $name
	 * @return bool
	 */
	public function __isset ($name) {
		$store = & $this->__protected['store'];
		// if property is in view store - return it
		if (array_key_exists($name, $store)) return TRUE;
		// if property is not in view store - try to get it from controller and set it into local view store
		if ($controllerType = $this->getReflectionClass('controller')) {
			if ($controllerType->hasProperty($name)) {
				/** @var $property \ReflectionProperty */
				$property = $controllerType->getProperty($name);
				if (!$property->isStatic()) {
					if (!$property->isPublic()) $property->setAccessible (TRUE); // protected or private
					$value = $property->getValue($this->controller);
					$store[$name] = & $value;
					return TRUE;
				}
			}
		}
		// property is not in local store and even in controller instance, return `FALSE`
		return FALSE;
	}

	/**
	 * Unset any value from view context internal store.
	 * @param string $name
	 * @return void
	 */
	public function __unset ($name) {
		$store = & $this->__protected['store'];
		if (isset($store[$name]))
			unset($store[$name]);
	}

	/**
	 * Get cached reflection class instance about given name from current `$this` context.
	 * If given name doesn't exists in local context, return `NULL`.
	 * @param string $currentContextObjectName Local context property name to get reflection class about.
	 * @return \ReflectionClass|NULL
	 */
	protected function & getReflectionClass ($currentContextObjectName) {
		$privates = & $this->__protected;

		$reflectionTypes = & $privates['reflectionTypes'];
		if (isset($reflectionTypes[$currentContextObjectName])) {
			return $reflectionTypes[$currentContextObjectName];
		}
		// prevent inifinite loop
		if ($privates['reflectionName'] === $currentContextObjectName) {
			$privates['reflectionName'] = NULL;
			return NULL;
		}
		$privates['reflectionName'] = $currentContextObjectName;
		$currentContextObject = $this->{$currentContextObjectName};
		if ($currentContextObject !== NULL) {
			$reflectionType = new \ReflectionClass($currentContextObject);
			$reflectionTypes[$currentContextObjectName] = & $reflectionType;
			return $reflectionType;
		}
		$privates['reflectionName'] = NULL;
		return NULL;
	}
}
