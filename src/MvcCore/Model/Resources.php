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
trait Resources {
	
	/**
	 * @inheritDoc
	 * @param  array<int,mixed>|NULL $args
	 * Values array with variables to pass into resource `__construct()` method.
	 * If `NULL`, recource class will be created without `__construct()` method call.
	 * @param  string                $classPath
	 * Relative namespace path to resource class. It could contains `.` or `..`
	 * to traverse over namespaces (directories) and it could contains `{self}` 
	 * keyword, which is automatically replaced with current class name.
	 * @throws \InvalidArgumentException Class `{$resourceClassName}` doesn't exist.
	 * @return \MvcCore\Model
	 */
	public static function GetCommonResource ($args = NULL, $classPath = '{self}s\CommonResource') {
		static $__commonResources = [];
		$staticClassPath = get_called_class();
		$serializeFn = function_exists('igbinary_serialize') ? 'igbinary_serialize' : 'serialize';
		$cacheKey = implode('|', [$staticClassPath, call_user_func($serializeFn, $args)]);
		if (isset($__commonResources[$cacheKey])) 
			return $__commonResources[$cacheKey];
		return $__commonResources[$cacheKey] = static::findAndCreateResource($args, $classPath);
	}
	
	/**
	 * @inheritDoc
	 * @param  array<int,mixed>|NULL $args
	 * Values array with variables to pass into resource `__construct()` method.
	 * If `NULL`, recource class will be created without `__construct()` method call.
	 * @param  string                $classPath
	 * Relative namespace path to resource class. It could contains `.` or `..`
	 * to traverse over namespaces (directories) and it could contains `{self}` 
	 * keyword, which is automatically replaced with current class name.
	 * @throws \InvalidArgumentException Class `{$resourceClassName}` doesn't exist.
	 * @return \MvcCore\Model
	 */
	public function GetResource ($args = NULL, $classPath = '{self}s\Resource') {
		if ($this->resource !== NULL)
			return $this->resource;
		return $this->resource = static::findAndCreateResource($args, $classPath);
	}
	
	/**
	 * Localize resource class, create and return it. If class is not localized, thrown an exception.
	 * @param  array<int,mixed>|NULL $args
	 * Values array with variables to pass into resource `__construct()` method.
	 * If `NULL`, recource class will be created without `__construct()` method call.
	 * @param  string                $classPath
	 * Relative namespace path to resource class. It could contains `.` or `..`
	 * to traverse over namespaces (directories) and it could contains `{self}` 
	 * keyword, which is automatically replaced with current class name.
	 * @throws \InvalidArgumentException Class `{$resourceClassName}` doesn't exist.
	 * @return \MvcCore\Model
	 */
	protected static function findAndCreateResource ($args = NULL, $classPath = '{self}s\Resource') {
		$resource = NULL;
		$staticClassPath = get_called_class();
		$namespaceSeparator = strpos($staticClassPath, '\\') === FALSE ? '_' : '\\';
		$staticClassPathExpl = explode($namespaceSeparator, $staticClassPath);
		$resourceClassPathExpl = explode($namespaceSeparator, $classPath);
		$resourceClassPathArr = [];
		foreach ($resourceClassPathExpl as $key => $resourceClassPathItem) {
			$selfMatched = mb_strpos($resourceClassPathItem, '{self}') !== FALSE;
			if ($selfMatched) {
				$resourceClassPathItem = str_replace('{self}', $staticClassPath, $resourceClassPathItem);
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
			$resource = $args === NULL
				? $reflectionClass->newInstanceWithoutConstructor()
				: $reflectionClass->newInstanceArgs($args);
			return $resource;
		} else {
			throw new \InvalidArgumentException("Class `{$resourceClassName}` doesn't exist.");
		}
	}
}