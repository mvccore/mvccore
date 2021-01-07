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

trait Resource {

	/**
	 * Returns staticly cached model resource instance.
	 * @param array|NULL	$args				Values array with variables to pass into resource `__construct()` method.
	 * @param string		$resourceClassPath	Automatically initialized with string replaced with `%SELF%` by `static::class` (or by `get_called_class()`).
	 * @return \MvcCore\Model|\MvcCore\IModel
	 */
	public static function GetResource ($args = [], $resourceClassPath = '%SELF%s\Resource') {
		return static::_getResource($args, $resourceClassPath);
	}

	/**
	 * Returns (or creates if necessary) model resource instance.
	 * @param array|NULL	$args				Values array with variables to pass into resource `__construct()` method.
	 * @param string		$resourceClassPath	Automatically initialized with string replaced with `%SELF%` by `static::class` (or by `get_called_class()`).
	 * @return \MvcCore\Model|\MvcCore\IModel
	 */
	private static function _getResource ($args, $resourceClassPath) {
		static $__resource = [];
		
		$staticClassPath = get_called_class();

		if (isset($__resources[$staticClassPath])) 
			return $__resource[$staticClassPath];

		$resource = NULL;
		$namespaceSeparator = strpos($staticClassPath, '\\') === FALSE ? '_' : '\\';
		$staticClassPathExpl = explode($namespaceSeparator, $staticClassPath);
		$resourceClassPathExpl = explode($namespaceSeparator, $resourceClassPath);
		$resourceClassPathArr = [];
		foreach ($resourceClassPathExpl as $key => $resourceClassPathItem) {
			$selfMatched = mb_strpos($resourceClassPathItem, '%SELF%') !== FALSE;
			if ($selfMatched) {
				$resourceClassPathItem = str_replace('%SELF%', $staticClassPath, $resourceClassPathItem);
				$resourceClassPathItemExpl = explode($namespaceSeparator, $resourceClassPathItem);
				$resourceClassPathArr = array_merge($resourceClassPathArr, $resourceClassPathItemExpl);
			} else if ($resourceClassPathItem === '.') {
				if ($key === 0) {
					unset($staticClassPathExpl[count($staticClassPathExpl) - 1]);
					$resourceClassPathArr = array_merge([], $staticClassPathExpl);
				}
				continue;
			} else if ($resourceClassPathItem === '..') {
				if ($key === 0) {
					unset($staticClassPathExpl[count($staticClassPathExpl) - 1]);
					$resourceClassPathArr = array_merge([], $staticClassPathExpl);
				}
				unset($resourceClassPathArr[count($resourceClassPathArr) - 1]);
			} else {
				$resourceClassPathArr[] = $resourceClassPathItem;
			}
		}
		$resourceClassName = implode($namespaceSeparator, $resourceClassPathArr);

		// Do not create resource instance if resource class doesn't exist:
		if (class_exists($resourceClassName)) {
			$reflectionClass = new \ReflectionClass($resourceClassName);
			$resource = $reflectionClass->newInstanceArgs($args);
		} else {
			throw new \InvalidArgumentException("Class `{$resourceClassName}` doesn't exist.");
		}

		$__resources[$staticClassPath] = $resource;

		return $resource;
	}
}