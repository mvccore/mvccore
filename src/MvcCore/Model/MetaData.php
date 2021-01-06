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

trait MetaData {
	
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
	 * - `3` => `boolean`					`TRUE` for private property.
	 * - `4` => `boolean`					`TRUE` for protected property.
	 * - `5` => `boolean`					`TRUE` for public property.
	 * Available reading flags:
	 *  - `\MvcCore\IModel::PROPS_INHERIT`
	 *  - `\MvcCore\IModel::PROPS_PRIVATE`
	 *  - `\MvcCore\IModel::PROPS_PROTECTED`
	 *  - `\MvcCore\IModel::PROPS_PUBLIC`
	 * @return array
	 */
	private static function __getPropsMetaData ($readingFlags = 0) {
		/** @var $this \MvcCore\Model */
		static $__propsMetaData = [];

		if (isset($__propsMetaData[$readingFlags])) 
			return $__propsMetaData[$readingFlags];
		
		$result = [];

		$inclInherit	= ($readingFlags & \MvcCore\IModel::PROPS_INHERIT) != 0;
		$accessModFlags = 0;
		if (($readingFlags & \MvcCore\IModel::PROPS_PRIVATE) != 0) 
			$accessModFlags |= \ReflectionProperty::IS_PRIVATE;
		if (($readingFlags & \MvcCore\IModel::PROPS_PROTECTED) != 0) 
			$accessModFlags |= \ReflectionProperty::IS_PROTECTED;
		if (($readingFlags & \MvcCore\IModel::PROPS_PUBLIC) != 0) 
			$accessModFlags |= \ReflectionProperty::IS_PUBLIC;

		$calledClassFullName = get_called_class();
		/** @var $props \ReflectionProperty[] */
		$props = (new \ReflectionClass($calledClassFullName))
			->getProperties($accessModFlags);

		$phpWithTypes = PHP_VERSION_ID >= 70400;

		/** @var $prop \ReflectionProperty */
		foreach ($props as $prop) {
			if ($prop->isStatic()) continue;
			
			$ownedByCurrent = $prop->class === $calledClassFullName;
			if (!$inclInherit && !$ownedByCurrent) continue;
			
			$propName = $prop->getName();
			if (isset(static::$protectedProperties[$propName])) continue;
			
			$types = [];
			if ($phpWithTypes && $prop->hasType()) {
				$refType = $prop->getType();
				if ($refType !== NULL)
					$types = [$refType->getName()];
			}
			if (!$types) {
				preg_match('/@var\s+([^\s]+)/', $prop->getDocComment(), $matches);
				if ($matches) 
					$types = explode('|', $matches[1]);
			}
			
			$isPrivate = $prop->isPublic();
			if ($isPrivate) $prop->setAccessible(TRUE);

			$result[$propName] = [
				$ownedByCurrent,				// $ownedByCurrent	boolean
				$types,							// $types			string[]
				$isPrivate ? $prop : NULL,		// $prop			\ReflectionProperty|NULL
				$isPrivate,						// $isPrivate		boolean
				$prop->isProtected(),			// $isProtected		boolean
				$prop->isPublic(),				// $isPublic		boolean
			];
		}
		
		$__propsMetaData[$readingFlags] = $result;

		return $result;
	}
}