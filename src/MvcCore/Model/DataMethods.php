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

trait DataMethods
{
	/**
	 * Collect all model class public and inherit field values into array.
	 * @param boolean $getNullValues			If `TRUE`, include also values with `NULL`s, by default - `FALSE`.
	 * @param boolean $includeInheritProperties If `TRUE`, include only fields from current model class and from parent classes.
	 * @param boolean $publicOnly			   If `TRUE`, include only public model fields.
	 * @return array
	 */
	public function GetValues ($getNullValues = FALSE, $includeInheritProperties = TRUE, $publicOnly = TRUE) {
		$data = [];
		$modelClassName = get_class($this);
		$classReflector = new \ReflectionClass($modelClassName);
		$properties = $publicOnly ? $classReflector->getProperties(\ReflectionProperty::IS_PUBLIC) : $classReflector->getProperties();
		foreach ($properties as $property) {
			if (!$includeInheritProperties && $property->class != $modelClassName) continue;
			$propertyName = $property->name;
			if (isset(static::$protectedProperties[$propertyName])) continue;
			if (!$getNullValues && $this->$propertyName === NULL) continue;
			$data[$propertyName] = $this->$propertyName;
		}
		return $data;
	}

	/**
	 * Set up given `$data` items into `$this` instance context
	 * as typed properties by PHP doc comments, as properties
	 * with the same names as `$data` array keys. Case sensitively by default.
	 * Do not set any `$data` items, which are not declared in `$this` context.
	 * @param array   $data					 Collection with data to set up
	 * @param boolean $keysInsensitive			If `TRUE`, set up properties from `$data` with case insensitively.
	 * @param boolean $includeInheritProperties If `TRUE`, include only fields from current model class and from parent classes.
	 * @param boolean $publicOnly			   If `TRUE`, include only public model fields.
	 * @return \MvcCore\Model|\MvcCore\IModel
	 */
	public function & SetUp ($data = [], $keysInsensitive = FALSE, $includeInheritProperties = TRUE, $publicOnly = TRUE) {
		/** @var $this \MvcCore\Model */
		$modelClassName = get_class($this);
		$classReflector = new \ReflectionClass($modelClassName);
		$properties = $publicOnly
			? $classReflector->getProperties(\ReflectionProperty::IS_PUBLIC)
			: $classReflector->getProperties();
		$dataKeys = $keysInsensitive ? ','.implode(',', array_keys($data)).',' : '' ;
		foreach ($properties as $property) {
			if (!$includeInheritProperties && $property->class != $modelClassName) continue;
			$propertyName = $property->name;
			if (isset($data[$propertyName])) {
				$value = $data[$propertyName];
			} else if ($keysInsensitive) {
				// try to search with not case sensitively same property name
				$dataKeyPos = stripos($dataKeys, ','.$propertyName.',');
				if ($dataKeyPos === FALSE) continue;
				$dataKey = substr($dataKeys, $dataKeyPos + 1, strlen($propertyName));
				$value = $data[$dataKey];
			} else {
				continue;
			}
			if ($value !== NULL && preg_match('/@var\s+([^\s]+)/', $property->getDocComment(), $matches)) {
				list(, $rawType) = $matches;
				$types = explode('|', $rawType);
				foreach ($types as $type) if (settype($value, $type)) break;
			}
			$this->$propertyName = $value;
		}
		return $this;
	}

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
		$nameBegin = strtolower(substr($rawName, 0, 3));
		$name = substr($rawName, 3);
		if ($nameBegin == 'get' && isset($this->$name)) {
			return $this->$name;
		} else if ($nameBegin == 'set') {
			$this->$name = isset($arguments[0]) ? $arguments[0] : NULL;
			return $this;
		} else {
			$selfClass = version_compare(PHP_VERSION, '5.5', '>') ? self::class : __CLASS__;
			throw new \InvalidArgumentException('['.$selfClass."] No property with name '$name' defined.");
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
		if (isset(static::$protectedProperties[$name])) {
			$selfClass = version_compare(PHP_VERSION, '5.5', '>') ? self::class : __CLASS__;
			throw new \InvalidArgumentException(
				'['.$selfClass."] It's not possible to change property: '$name' originally declared in class ".__CLASS__.'.'
			);
		}
		return $this->$name = $value;
	}

	/**
	 * Get any custom property, not necessary to previously defined,
	 * if property is not defined, NULL is returned.
	 * @param string $name
	 * @throws \InvalidArgumentException If name is `"autoInit" || "db" || "config" || "resource"`
	 * @return mixed
	 */
	public function __get ($name) {
		if (isset(static::$protectedProperties[$name])) {
			$selfClass = version_compare(PHP_VERSION, '5.5', '>') ? self::class : __CLASS__;
			throw new \InvalidArgumentException(
				'['.$selfClass."] It's not possible to get property: '$name' originally declared in this class."
			);
		}
		return (isset($this->$name)) ? $this->$name : null;
	}
}
