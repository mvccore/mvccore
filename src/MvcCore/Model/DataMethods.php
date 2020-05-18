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
	 * @param bool $includeInheritProperties If `TRUE`, include fields from current and all parent classes, if `FALSE`, include fields only from current model class, default - `TRUE`.
	 * @param bool $publicOnly			     If `TRUE`, include only public instance fields, if `FALSE`, include all instance fields, default - `TRUE`.
	 * @param bool $getNullValues			 If `TRUE`, include also values with `NULL`s, default - `FALSE`.
	 * @return array
	 */
	public function GetValues ($includeInheritProperties = TRUE, $publicOnly = TRUE, $getNullValues = FALSE) {
		/**
		 * @var $this \MvcCore\Model
		 * @var $prop \ReflectionProperty
		 */
		$data = [];
		if ($publicOnly) {
			$properties = static::__getPropsDataPublic();
			foreach ($properties as $propertyName => $ownedByCurrent) {
				if (!$includeInheritProperties && !$ownedByCurrent)
					continue;
				$propValue = NULL;
				if (
					/**
					 * If property is public, it's ok to ask only by
					 * `isset($this->{$propertyName})`, because then it works
					 * in the same way like: `$prop->isInitialized($this)`
					 * for older PHP versions and also for PHP >= 7.4 and typed
					 * properties.
					 */
					isset($this->{$propertyName})
				) $propValue = $this->{$propertyName};
				if (!$getNullValues && $propValue === NULL)
					continue;
				$data[$propertyName] = $propValue;
			}
		} else {
			$properties = static::__getPropsDataAll();
			$phpWithTypes = PHP_VERSION_ID >= 70400;
			foreach ($properties as $propertyName => $propData) {
				list($ownedByCurrent, /*$types*/, $prop, $isPrivate) = $propData;
				if (!$includeInheritProperties && !$ownedByCurrent)
					continue;
				$propValue = NULL;
				if ($isPrivate) {
					/**
					 * If property is private, there is only way to get
					 * it's value by reflection rpoperty object. But for
					 * PHP >= 7.4 and typed properties, there is necessary
					 * to ask `$prop->isInitialized($this)` first before
					 * calling `$prop->getValue($this);`.
					 */
					if ($phpWithTypes) {
						if ($prop->isInitialized($this))
							$propValue = $prop->getValue($this);
					} else {
						$propValue = $prop->getValue($this);
					}
				} else if (
					/**
					 * If property is not private, it's ok to ask only by
					 * `isset($this->{$propertyName})`, because then it works
					 * in the same way like: `$prop->isInitialized($this)`
					 * for older PHP versions and also for PHP >= 7.4 and typed
					 * properties.
					 */
					isset($this->{$propertyName})
				) {
					$propValue = $this->{$propertyName};
				}
				if (!$getNullValues && $propValue === NULL)
					continue;
				$data[$propertyName] = $propValue;
			}
		}
		return $data;
	}

	/**
	 * Set up given `$data` items into `$this` instance context
	 * as typed properties by PHP doc comments, as properties
	 * with the same names as `$data` array keys. Case sensitively by default.
	 * Do not set any `$data` items, which are not declared in `$this` context.
	 * @param array   $data						Collection with data to set up
	 * @param int	  $keysConversionFlags		`\MvcCore\IModel::KEYS_CONVERSION_*` flags to process array keys conversion before set up into properties.
	 * @param bool    $completeInitialValues    Complete protected array `initialValues` to be able to compare them by calling method `GetTouched()` anytime later.
	 * @return \MvcCore\Model|\MvcCore\IModel
	 */
	public function SetUp ($data = [], $keysConversionFlags = NULL, $completeInitialValues = TRUE) {
		/**
		 * @var $this \MvcCore\Model
		 * @var $typeStrings \string[]
		 * @var $prop \ReflectionProperty
		 */
		$propsData = static::__getPropsDataAll();
		$caseSensitiveKeysMap = ','.implode(',', array_keys($propsData)).',';
		$keyConversionsMethods = $keysConversionFlags !== NULL
			? static::getKeyConversionMethods($keysConversionFlags)
			: [];
		$toolsClass = \MvcCore\Application::GetInstance()->GetToolClass();
		foreach ($data as $dbKey => $value) {
			$propertyName = $dbKey;
			foreach ($keyConversionsMethods as $keyConversionsMethod)
				$propertyName = static::{$keyConversionsMethod}(
					$propertyName, $toolsClass, $caseSensitiveKeysMap
				);
			$isNotNull = $value !== NULL;
			$isPrivate = NULL;
			if ($isNotNull && isset($propsData[$propertyName])) {
				list(/*$ownedByCurrent*/, $types, $prop, $isPrivate) = $propsData[$propertyName];
				$targetTypeValue = NULL;
				foreach ($types as $typeString) {
					if (substr($typeString, -2, 2) === '[]') {
						if (!is_array($value)) {
							$value = trim(strval($value));
							$value = $value === '' ? [] : explode(',', $value);
						}
						$arrayItemTypeString = substr($typeString, 0, strlen($typeString) - 2);
						$targetTypeValue = [];
						$conversionResult = TRUE;
						foreach ($value as $key => $item) {
							list(
								$conversionResultLocal, $targetTypeValueLocal
							) = static::convertToType($item, $arrayItemTypeString);
							if ($conversionResultLocal) {
								$targetTypeValue[$key] = $targetTypeValueLocal;
							} else {
								$conversionResult = FALSE;
								break;
							}
						}
					} else {
						list(
							$conversionResult, $targetTypeValue
						) = static::convertToType($value, $typeString);
					}
					if ($conversionResult) {
						$value = $targetTypeValue;
						break;
					}
				}
			}
			if ($isPrivate) {
				//$prop->setAccessible(TRUE);
				$prop->setValue($this, $value);
			} else {
				$this->{$propertyName} = $value;
			}
			if ($completeInitialValues)
				$this->initialValues[$propertyName] = $value;
		}
		return $this;
	}

	/**
	 * Get touched properties from initial moment called by `SetUp()` method.
	 * Get everything, what is different to `$this->initialValues` array.
	 * @param bool $includeInheritProperties If `TRUE`, include fields from current and all parent classes, if `FALSE`, include fields only from current model class, default - `TRUE`.
	 * @param bool $publicOnly			     If `TRUE`, include only public instance fields, if `FALSE`, include all instance fields, default - `TRUE`.
	 * @return array Keys are class properties names, values are changed values.
	 */
	public function GetTouched ($includeInheritProperties = TRUE, $publicOnly = FALSE) {
		/**
		 * @var $this \MvcCore\Model
		 * @var $prop \ReflectionProperty
		 */
		$touchedValues = [];
		if ($publicOnly) {
			$properties = static::__getPropsDataPublic();
			foreach ($properties as $propertyName => $ownedByCurrent) {
				if (!$includeInheritProperties && !$ownedByCurrent)
					continue;
				$initialValue = NULL;
				$currentValue = NULL;
				if (array_key_exists($propertyName, $this->initialValues))
					$initialValue = $this->initialValues[$propertyName];
				$currentValue = NULL;
				if (
					/**
					 * If property is public, it's ok to ask only by
					 * `isset($this->{$propertyName})`, because then it works
					 * in the same way like: `$prop->isInitialized($this)`
					 * for older PHP versions and also for PHP >= 7.4 and typed
					 * properties.
					 */
					isset($this->{$propertyName})
				) $currentValue = $this->{$propertyName};
				if ($initialValue !== $currentValue)
					$touchedValues[$propertyName] = $currentValue;
			}
		} else {
			$properties = static::__getPropsDataAll();
			$phpWithTypes = PHP_VERSION_ID >= 70400;
			foreach ($properties as $propertyName => $propData) {
				list($ownedByCurrent, /*$types*/, $prop, $isPrivate) = $propData;
				if (!$includeInheritProperties && !$ownedByCurrent)
					continue;
				$initialValue = NULL;
				$currentValue = NULL;
				if (array_key_exists($propertyName, $this->initialValues))
					$initialValue = $this->initialValues[$propertyName];
				$currentValue = NULL;
				if ($isPrivate) {
					/**
					 * If property is private, there is only way to get
					 * it's value by reflection rpoperty object. But for
					 * PHP >= 7.4 and typed properties, there is necessary
					 * to ask `$prop->isInitialized($this)` first before
					 * calling `$prop->getValue($this);`.
					 */
					if ($phpWithTypes) {
						if ($prop->isInitialized($this))
							$currentValue = $prop->getValue($this);
					} else {
						$currentValue = $prop->getValue($this);
					}
				} else if (
					/**
					 * If property is not private, it's ok to ask only by
					 * `isset($this->{$propertyName})`, because then it works
					 * in the same way like: `$prop->isInitialized($this)`
					 * for older PHP versions and also for PHP >= 7.4 and typed
					 * properties.
					 */
					isset($this->{$propertyName})
				) {
					$currentValue = $this->{$propertyName};
				}
				if ($initialValue !== $currentValue)
					$touchedValues[$propertyName] = $currentValue;
			}
		}
		return $touchedValues;
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

	/**
	 * Return cached array about properties in current class to not create
	 * reflection object every time. Every key in array is property name,
	 * every value in array is array with following values:
	 * - `0` => `bool`						`TRUE` for property defined in current
	 *										class instance, `FALSE` for parent
	 *										properties.
	 * - `1` => `string[]`					Property types from code or from doc
	 *										comments or empty array.
	 * - `2` => `\ReflectionProperty|NULL`	Reflection property object for private
	 *										properties.
	 * - `3` => `boolean`					`TRUE` for private properties.
	 * @return array
	 */
	private static function __getPropsDataAll () {
		/** @var $this \MvcCore\Model */
		static $__propsDataAll = NULL;
		if ($__propsDataAll == NULL) {
			$calledClassFullName = get_called_class();
			$props = (new \ReflectionClass($calledClassFullName))
				->getProperties(
					\ReflectionProperty::IS_PUBLIC |
					\ReflectionProperty::IS_PROTECTED |
					\ReflectionProperty::IS_PRIVATE
				);
			$phpWithTypes = PHP_VERSION_ID >= 70400;
			$__propsDataAll = [];
			/** @var $prop \ReflectionProperty */
			foreach ($props as $prop) {
				if ($prop->isStatic()) continue;
				$propName = $prop->getName();
				if (isset(static::$protectedProperties[$propName])) continue;
				$types = [];
				if ($phpWithTypes && $prop->hasType()) {
					$refType = $prop->getType();
					if ($refType !== NULL)
						$types = [$refType->getName()];
				} else {
					preg_match('/@var\s+([^\s]+)/', $prop->getDocComment(), $matches);
					if ($matches) {
						list(, $rawType) = $matches;
						$types = explode('|', $rawType);
					}
				}
				$isPrivate = $prop->isPrivate();
				if ($isPrivate) $prop->setAccessible(TRUE);
				$__propsDataAll[$propName] = [
					$prop->class == $calledClassFullName,	// $ownedByCurrent	boolean
					$types,									// $types			string[]
					$isPrivate ? $prop : NULL,				// $prop			\ReflectionProperty|NULL
					$isPrivate								// $isPrivate		boolean
				];
			}
		}
		return $__propsDataAll;
	}

	/**
	 * Return cached array about public only properties in current class
	 * to not create reflection object every time. Every key in array
	 * is property name, every value in array is boolean about if property
	 * is defined in current class instance or not.
	 * @return array
	 */
	private static function __getPropsDataPublic () {
		/** @var $this \MvcCore\Model */
		static $__propsDataPublic = NULL;
		if ($__propsDataPublic == NULL) {
			$calledClassFullName = get_called_class();
			$props = (new \ReflectionClass($calledClassFullName))
				->getProperties(\ReflectionProperty::IS_PUBLIC);
			$__propsDataPublic = [];
			/** @var $prop \ReflectionProperty */
			foreach ($props as $prop) {
				if ($prop->isStatic()) continue;
				$propName = $prop->getName();
				if (isset(static::$protectedProperties[$propName])) continue;
				$__propsDataPublic[$propName] = $prop->class == $calledClassFullName;
			}
		}
		return $__propsDataPublic;
	}

}
