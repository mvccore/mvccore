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

namespace MvcCore\Model;

trait MagicMethods {
	
	/**
	 * Sets any custom property `"PropertyName"` by `\MvcCore\IModel::SetPropertyName("value")`,
	 * which is not necessary to define previously or gets previously defined
	 * property `"PropertyName"` by `\MvcCore\IModel::GetPropertyName();`.
	 * Throws exception if no property defined by get call
	 * or if virtual call begins with anything different from `Set` or `Get`.
	 * This method returns custom value for get and `\MvcCore\IModel` instance for set.
	 * @param string $rawName
	 * @param array  $arguments
	 * @throws \InvalidArgumentException If `strtolower($rawName)` doesn't begin with `"get"` or with `"set"`.
	 * @return mixed|\MvcCore\Model|\MvcCore\IModel
	 */
	public function __call ($rawName, $arguments = []) {
		/** @var $this \MvcCore\Model */
		$nameBegin = strtolower(substr($rawName, 0, 3));
		$name = substr($rawName, 3);
		if ($nameBegin == 'get') {
			if (property_exists($this, lcfirst($name))) return $this->{lcfirst($name)};
			if (property_exists($this, $name)) return $this->$name;
			return NULL;
		} else if ($nameBegin == 'set') {
			if (property_exists($this, lcfirst($name)))
				$this->{lcfirst($name)} = isset($arguments[0]) ? $arguments[0] : NULL;
			if (property_exists($this, $name))
				$this->$name = isset($arguments[0]) ? $arguments[0] : NULL;
			return $this;
		} else {
			throw new \InvalidArgumentException("[".get_class()."] No method `{$rawName}()` defined.");
		}
	}

	/**
	 * Set any custom property, not necessary to previously defined.
	 * @param string $name
	 * @param mixed  $value
	 * @throws \InvalidArgumentException If name is `"autoInit" || "db" || "config" || "resource"`
	 * @return bool
	 */
	public function __set ($name, $value) {
		/** @var $this \MvcCore\Model */
		if (isset(static::$protectedProperties[$name]))
			throw new \InvalidArgumentException(
				"[".get_class()."] It's not possible to change property: `{$name}` originally declared in this class."
			);
		if (property_exists($this, lcfirst($name)))
			return $this->{lcfirst($name)} = $value;
		return $this->{$name} = $value;
	}

	/**
	 * Get any custom property, not necessary to previously defined,
	 * if property is not defined, NULL is returned.
	 * @param string $name
	 * @throws \InvalidArgumentException If name is `"autoInit" || "db" || "config" || "resource"`
	 * @return mixed
	 */
	public function __get ($name) {
		/** @var $this \MvcCore\Model */
		if (isset(static::$protectedProperties[$name]))
			throw new \InvalidArgumentException(
				"[".get_class()."] It's not possible to get property: `{$name}` originally declared in this class."
			);
		if (isset($this->{lcfirst($name)}))
			return $this->{lcfirst($name)};
		if (isset($this->{$name}))
			return $this->{$name};
		return NULL;
	}

	/**
	 * Collect all properties names to serialize them by `serialize()` method.
	 * Collect all instance properties declared as private, protected and public
	 * and if there is configured in `static::$protectedProperties` anything as
	 * `TRUE` (under key by property name), also return those properties in
	 * result array.
	 * @return \string[]
	 */
	public function __sleep () {
		static $__serializePropsNames = NULL;
		if ($__serializePropsNames == NULL) {
			$rawPropNames = array_keys(
				$this instanceof \ArrayObject
					? $this
					: (array) $this
			);
			$__serializePropsNames = [];
			// for private and protected properties:
			$protectedPropNames = & static::$protectedProperties;
			foreach ($rawPropNames as $rawPropName) {
				$pos = strrpos($rawPropName, "\0");
				$propName = $pos === FALSE
					? $rawPropName
					: substr($rawPropName, $pos + 1);
				$doNotSerialize = (
					isset($protectedPropNames[$propName]) &&
					$protectedPropNames[$propName]
				);
				if (!$doNotSerialize)
					$__serializePropsNames[] = $rawPropName;
			}
		}
		return $__serializePropsNames;
	}

	/**
	 * Run `$this->Init()` method if there is `$this->autoInit` property defined
	 * and if the property is `TRUE`.
	 * @return void
	 */
	public function __wakeup () {
		/** @var $this \MvcCore\Model */
		if (property_exists($this, 'autoInit') && $this->autoInit)
			$this->Init();
	}

}