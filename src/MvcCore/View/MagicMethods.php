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

/**
 * @mixin \MvcCore\View
 */
trait MagicMethods {

	/**
	 * @inheritDocs
	 * @param  string $name
	 * @param  mixed $value
	 * @return mixed
	 */
	public function __set ($name, $value) {
		return $this->__protected['store'][$name] = & $value;
	}

	/**
	 * @inheritDocs
	 * @param  string $name
	 * @return mixed
	 */
	public function & __get ($name) {
		$store = & $this->__protected['store'];
		// if property is in view store - return it
		if (array_key_exists($name, $store))
			return $store[$name];
		// if property is not in view store - try to get it from controller and set it into local view store
		if ($controllerType = $this->getReflectionClass('controller')) {
			if ($controllerType->hasProperty($name)) {
				/** @var \ReflectionProperty $property */
				$property = $controllerType->getProperty($name);
				if (!$property->isStatic()) {
					if (!$property->isPublic())
						$property->setAccessible(TRUE); // protected or private
					$value = NULL;
					if (PHP_VERSION_ID >= 70400 && $property->hasType()) {
						if ($property->isInitialized($this->controller))
							$value = $property->getValue($this->controller);
					} else {
						$value = $property->getValue($this->controller);
					}
					$store[$name] = & $value;
					return $value;
				}
			}
		}
		// return null, if property is not in local store an even not in controller
		$null = NULL;
		return $null;
	}

	/**
	 * @inheritDocs
	 * @param  string $name
	 * @return bool
	 */
	public function __isset ($name) {
		$store = & $this->__protected['store'];
		// if property is in view store - return it
		if (array_key_exists($name, $store)) 
			return $store[$name] !== NULL;
		// if property is not in view store - try to get it from controller and set it into local view store
		if ($controllerType = $this->getReflectionClass('controller')) {
			if ($controllerType->hasProperty($name)) {
				/** @var \ReflectionProperty $property */
				$property = $controllerType->getProperty($name);
				if (!$property->isStatic()) {
					if (!$property->isPublic())
						$property->setAccessible(TRUE); // protected or private
					$value = NULL;
					if (PHP_VERSION_ID >= 70400 && $property->hasType()) {
						if ($property->isInitialized($this->controller))
							$value = $property->getValue($this->controller);
					} else {
						$value = $property->getValue($this->controller);
					}
					$store[$name] = & $value;
					return $value !== NULL;
				}
			}
		}
		// property is not in local store and even in controller instance, return `FALSE`
		return FALSE;
	}

	/**
	 * @inheritDocs
	 * @param  string $name
	 * @return void
	 */
	public function __unset ($name) {
		$store = & $this->__protected['store'];
		if (array_key_exists($name, $store)) 
			unset($store[$name]);
	}

	/**
	 * Get cached reflection class instance about given name from current `$this` context.
	 * If given name doesn't exists in local context, return `NULL`.
	 * @param  string $currentContextObjectName Local context property name to get reflection class about.
	 * @return \ReflectionClass|NULL
	 */
	protected function getReflectionClass ($currentContextObjectName) {
		$privates = & $this->__protected;

		$reflectionTypes = & $privates['reflectionTypes'];
		if (isset($reflectionTypes[$currentContextObjectName])) {
			return $reflectionTypes[$currentContextObjectName];
		}
		// prevent infinite loop
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
