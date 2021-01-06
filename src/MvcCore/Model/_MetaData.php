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

trait _MetaData {
	
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
				// TODO: tady chybí záskávání dalších atributů
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