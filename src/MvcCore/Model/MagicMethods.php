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

/**
 * @mixin \MvcCore\Model
 */
trait MagicMethods {
	
	/**
	 * @inheritDoc
	 * @param  string $rawName
	 * @param  array  $arguments
	 * @throws \InvalidArgumentException If `strtolower($rawName)` doesn't begin with `"get"` or with `"set"`.
	 * @return mixed|\MvcCore\Model
	 */
	public function __call ($rawName, $arguments = []) {
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
	 * @inheritDoc
	 * @param  string $name
	 * @param  mixed  $value
	 * @throws \InvalidArgumentException If name is `initialValues` or any custom name in extended class.
	 * @return bool
	 */
	public function __set ($name, $value) {
		if (isset(static::$protectedProperties[$name]))
			throw new \InvalidArgumentException(
				"[".get_class()."] It's not possible to change strongly property: `{$name}`."
			);
		if (property_exists($this, lcfirst($name)))
			return $this->{lcfirst($name)} = $value;
		return $this->{$name} = $value;
	}

	/**
	 * @inheritDoc
	 * @param  string $name
	 * @throws \InvalidArgumentException If name is `initialValues` or any custom name in extended class.
	 * @return mixed
	 */
	public function __get ($name) {
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
	 * @inheritDoc
	 * @return \string[]
	 */
	public function __sleep () {
		$toolsClass = \MvcCore\Application::GetInstance()->GetToolClass();
		return $toolsClass::GetSleepPropNames($this, static::$protectedProperties);
	}
	
	/**
	 * @return void
	 */
	public function __clone () {
		$phpWithTypes = PHP_VERSION_ID >= 70400;
		$type = new \ReflectionClass($this);
		/** @var \ReflectionProperty[] $props */
		$props = $type->getProperties(
			\ReflectionProperty::IS_PRIVATE |	
			\ReflectionProperty::IS_PROTECTED |
			\ReflectionProperty::IS_PUBLIC
		);
		/** @var \ReflectionProperty $prop */
		foreach ($props as $prop) {
			if (
				$prop->isStatic() ||
				isset(static::$protectedProperties[$prop->name])
			) continue;
			$currentValue = NULL;
			$propertyName = $prop->getName();
			$propIsPrivate = $prop->isPrivate();
			if ($propIsPrivate) {
				$prop->setAccessible(TRUE);
				if ($phpWithTypes)
					if (!$prop->isInitialized($this))
						continue;
				$currentValue = $prop->getValue($this);
			} else if (isset($this->{$propertyName})) {
				$currentValue = $this->{$propertyName};
			}
			if (is_scalar($currentValue) || $currentValue === NULL) 
				continue;
			if (is_resource($currentValue)) {
				$clonedValue = $currentValue;
			} else if (is_array($currentValue)) {
				$clonedValue = [];
				foreach ($currentValue as $key => $value)
					$clonedValue[$key] = is_object($value)
						? clone $value
						: $value;
			} else {
				// objects and \Closures
				$clonedValue = clone $currentValue;
			}
			if ($propIsPrivate) {
				$prop->setValue($this, $clonedValue);
			} else {
				$this->{$propertyName} = $clonedValue;
			}
			if (isset($this->initialValues[$propertyName]))
				$this->initialValues[$propertyName] = $clonedValue;
		}
	}

	/**
	 * @inheritDoc
	 * @param  int $propsFlags
	 * @return array|mixed
	 */
	#[\ReturnTypeWillChange]
	public function jsonSerialize ($propsFlags = 0) {
		if ($propsFlags === 0) 
			$propsFlags = \MvcCore\IModel::PROPS_INHERIT | \MvcCore\IModel::PROPS_PROTECTED;
		$data = static::GetValues($propsFlags, TRUE);
		return array_filter($data, function ($val) {
			return !is_resource($val) && !($val instanceof \Closure);
		});
	}
}