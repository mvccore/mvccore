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

trait MagicMethods {
	
	/**
	 * @inheritDocs
	 * @param  string $rawName
	 * @param  array  $arguments
	 * @throws \InvalidArgumentException If `strtolower($rawName)` doesn't begin with `"get"` or with `"set"`.
	 * @return mixed|\MvcCore\Model
	 */
	public function __call ($rawName, $arguments = []) {
		/** @var $this \MvcCore\Model */
		$nameBegin = strtolower(substr($rawName, 0, 3));
		$name = substr($rawName, 3);
		if ($nameBegin == 'get') {
			$lcName = lcfirst($name);
			if (property_exists($this, $lcName)) return $this->{$lcName};
			if (property_exists($this, $name)) return $this->$name;
			throw new \InvalidArgumentException(
				"[".get_class()."] No property `{$lcName}` or `{$name}` defined."
			);
		} else if ($nameBegin == 'set') {
			if (property_exists($this, lcfirst($name)))
				$this->{lcfirst($name)} = isset($arguments[0]) ? $arguments[0] : NULL;
			if (property_exists($this, $name))
				$this->$name = isset($arguments[0]) ? $arguments[0] : NULL;
			return $this;
		} else {
			throw new \InvalidArgumentException(
				"[".get_class()."] No method `{$rawName}()` defined."
			);
		}
	}

	/**
	 * @inheritDocs
	 * @param  string $name
	 * @param  mixed  $value
	 * @throws \InvalidArgumentException If name is `initialValues` or any custom name in extended class.
	 * @return bool
	 */
	public function __set ($name, $value) {
		/** @var $this \MvcCore\Model */
		if (isset(static::$protectedProperties[$name]))
			throw new \InvalidArgumentException(
				"[".get_class()."] It's not possible to change strongly property: `{$name}`."
			);
		if (property_exists($this, lcfirst($name)))
			return $this->{lcfirst($name)} = $value;
		return $this->{$name} = $value;
	}

	/**
	 * @inheritDocs
	 * @param  string $name
	 * @throws \InvalidArgumentException If name is `initialValues` or any custom name in extended class.
	 * @return mixed
	 */
	public function __get ($name) {
		/** @var $this \MvcCore\Model */
		if (isset(static::$protectedProperties[$name]))
			throw new \InvalidArgumentException(
				"[".get_class()."] It's not possible to get strongly protected property: `{$name}`."
			);
		if (isset($this->{lcfirst($name)}))
			return $this->{lcfirst($name)};
		if (isset($this->{$name}))
			return $this->{$name};
		return NULL;
	}

	/**
	 * @inheritDocs
	 * @return \string[]
	 */
	public function __sleep () {
		/** @var $this \MvcCore\Model */
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
					!$protectedPropNames[$propName]
				);
				if (!$doNotSerialize)
					$__serializePropsNames[] = $rawPropName;
			}
		}
		return $__serializePropsNames;
	}

}