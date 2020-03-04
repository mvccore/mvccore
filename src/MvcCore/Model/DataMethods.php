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
	 * @param bool $getNullValues			 If `TRUE`, include also values with `NULL`s, default - `FALSE`.
	 * @param bool $includeInheritProperties If `TRUE`, include fields from current and all parent classes, if `FALSE`, include fields only from current model class, default - `TRUE`.
	 * @param bool $publicOnly			     If `TRUE`, include only public instance fields, if `FALSE`, include all instance fields, default - `TRUE`.
	 * @return array
	 */
	public function GetValues ($getNullValues = FALSE, $includeInheritProperties = TRUE, $publicOnly = TRUE) {
		/** @var $this \MvcCore\Model */
		$data = [];
		$properties = $publicOnly
			? static::__getPropsNamesAll()
			: static::__getPropsNamesPublic();
		$phpWithTypes = PHP_VERSION_ID >= 70400;
		/** @var $prop \ReflectionProperty */
		foreach ($properties as $propertyName => $propData) {
			list($ownedByCurrent, $accessMod, $types, $prop) = $propData;
			if (!$includeInheritProperties && !$ownedByCurrent)
				continue;
			$propValue = NULL;
			if ($accessMod == 4) { // private
				//$prop->setAccessible(TRUE);
				if ($phpWithTypes) {
					if ($prop->isInitialized($this))
						$propValue = $this->getValue($this, $propertyName);
				} else if (isset($this->{$propertyName})) {
					$propValue = $this->getValue($this, $propertyName);
				}
			} else {
				$propValue = $this->{$propertyName};
			}
			if (!$getNullValues && $propValue === NULL)
				continue;
			$data[$propertyName] = $propValue;
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
		/** @var $this \MvcCore\Model */
		$propsData = static::__getPropsData();
		$caseSensitiveKeysMap = ','.implode(',', array_keys($propsData)).',';
		$keyConversionsMethods = $keysConversionFlags !== NULL
			? static::getKeyConversionMethods($keysConversionFlags)
			: [];
		$toolsClass = \MvcCore\Application::GetInstance()->GetToolClass();
		foreach ($data as $dbKey => $value) {
			$propertyName = $dbKey;
			foreach ($keyConversionsMethods as $keyConversionsMethod)
				$propertyName = static::$keyConversionsMethod(
					$propertyName, $toolsClass, $caseSensitiveKeysMap
				);
			$isNotNull = $value !== NULL;
			$accessMod = 1;
			if ($isNotNull && isset($propsData[$propertyName])) {
				/**
				 * @var $accessMod \int
				 * @var $typeStrings \string[]
				 * @var $prop \ReflectionProperty
				 */
				list(, $accessMod, $typeStrings, $prop) = $propsData[$propertyName];
				$targetTypeValue = NULL;
				foreach ($typeStrings as $typeString) {
					if (substr($typeString, -2, 2) === '[]') {
						if (!is_array($value)) {
							$value = trim(strval($value));
							$value = $value === '' ? [] : explode(',', $value);
						}
						$arrayItemTypeString = substr($typeString, 0, strlen($typeString) - 2);
						$targetTypeValue = [];
						$conversionResult = TRUE;
						foreach ($value as $key => $item) {
							list($conversionResultLocal, $targetTypeValueLocal) = static::convertToType($item, $arrayItemTypeString);
							if ($conversionResultLocal) {
								$targetTypeValue[$key] = $targetTypeValueLocal;
							} else {
								$conversionResult = FALSE;
								break;
							}
						}
					} else {
						list($conversionResult, $targetTypeValue) = static::convertToType($value, $typeString);
					}
					if ($conversionResult) {
						$value = $targetTypeValue;
						break;
					}
				}
			}
			if ($accessMod == 4) { // private
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
		/** @var $this \MvcCore\Model */
		$touchedValues = [];
		$properties = $publicOnly
			? static::__getPropsNamesAll()
			: static::__getPropsNamesPublic();
		$phpWithTypes = PHP_VERSION_ID >= 70400;
		/** @var $prop \ReflectionProperty */
		foreach ($properties as $propertyName => $propData) {
			list($ownedByCurrent, $accessMod, $types, $prop) = $propData;
			if (!$includeInheritProperties && !$ownedByCurrent)
				continue;
			$initialValue = NULL;
			$currentValue = NULL;
			if (array_key_exists($propertyName, $this->initialValues))
				$initialValue = & $this->initialValues[$propertyName];
			$currentValue = NULL;
			if ($accessMod == 4) { // private
				//$prop->setAccessible(TRUE);
				if ($phpWithTypes) {
					if ($prop->isInitialized($this))
						$currentValue = $this->getValue($this, $propertyName);
				} else {
					$currentValue = $this->getValue($this, $propertyName);
				}
			} else if (isset($this->{$propertyName})) {
				$currentValue = & $this->{$propertyName};
			}
			if ($initialValue !== $currentValue)
				$touchedValues[$propertyName] = & $this->{$propertyName};
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
		/** @var $this \MvcCore\Model */
		$props = array_keys((array) $this);
		foreach (static::$protectedProperties as $propName => $serialize)
			if ($serialize)
				unset($props[$propName]);
		return $props;
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
	 * Return cached array about properties in current class to not create reflection object every time.
	 * Every key in array is property name, every value in array is array with following values:
	 * - `bool` - for property defined in current class
	 * - `int` - property access modified  (`1` public, `2` protected and `4` private)
	 * - `string[]` - property types from code or from doc comments
	 * - `\ReflectionProperty|NULL` - reflection property object
	 * @return array
	 */
	private static function __getPropsData () {
		/** @var $this \MvcCore\Model */
		static $__propsData = NULL;
		if ($__propsData == NULL) {
			$calledClassFullName = get_called_class();
			$props = (new \ReflectionClass($calledClassFullName))->getProperties(
				\ReflectionProperty::IS_PUBLIC |
				\ReflectionProperty::IS_PROTECTED |
				\ReflectionProperty::IS_PRIVATE
			);
			$__propsData = [];
			$phpWithTypes = PHP_VERSION_ID >= 70400;
			foreach ($props as $prop){
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
				$accessMod = $prop->getModifiers();
				$prop->setAccessible(TRUE);
				$__propsData[$propName] = [
					$prop->class == $calledClassFullName,
					$accessMod, // 1 public, 2 protected, 4 private
					$types,
					$accessMod == 4 || $phpWithTypes ? $prop : NULL
				];
			}
		}
		return $__propsData;
	}

	/**
	 * Return array with keys representing all instance properties names
	 * and boolean values representing if property is defined in current class or not.
	 * @return array
	 */
	private static function __getPropsNamesAll () {
		/** @var $this \MvcCore\Model */
		static $__allPropsNames = NULL;
		if ($__allPropsNames == NULL) {
			$propsData = static::__getPropsData();
			$__allPropsNames = [];
			foreach ($propsData as $propName => $propData)
				$__allPropsNames[$propName] = $propData[0];
		}
		return $__allPropsNames;
	}

	/**
	 * Return array with keys representing public only instance properties names
	 * and boolean values representing if property is defined in current class or not.
	 * @return array
	 */
	private static function __getPropsNamesPublic () {
		/** @var $this \MvcCore\Model */
		static $__publicPropsNames = NULL;
		if ($__publicPropsNames == NULL) {
			$propsData = static::__getPropsData();
			$__publicPropsNames = [];
			foreach ($propsData as $propName => $propData) {
				list($ownedByCurrent, $accessMod) = $propData;
				if ($accessMod == 1)
					$__publicPropsNames[$propName] = $ownedByCurrent;
			}
		}
		return $__publicPropsNames;
	}
}
