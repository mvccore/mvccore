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

interface IResources {
	
	/**
	 * Returns (or creates if necessary) model resource instance.
	 * Common resource instance is stored all the time in static store
	 * under key from resource full class name and constructor arguments.
	 * @param  array<int,mixed>|NULL $args
	 * Values array with variables to pass into resource `__construct()` method.
	 * If `NULL`, recource class will be created without `__construct()` method call.
	 * @param  string                $classPath
	 * Relative namespace path to resource class. It could contains `.` or `..`
	 * to traverse over namespaces (directories) and it could contains `{self}` 
	 * keyword, which is automatically replaced with current class name.
	 * @throws \InvalidArgumentException Class `{$resourceClassName}` doesn't exist.
	 * @return \MvcCore\IModel
	 */
	public static function GetCommonResource ($args = NULL, $classPath = '{self}s\CommonResource');

	/**
	 * Returns (or creates if doesn`t exist) model resource instance.
	 * Resource instance is stored in protected instance property `resource`.
	 * @param  array<int,mixed>|NULL $args
	 * Values array with variables to pass into resource `__construct()` method.
	 * If `NULL`, recource class will be created without `__construct()` method call.
	 * @param  string                $classPath
	 * Relative namespace path to resource class. It could contains `.` or `..`
	 * to traverse over namespaces (directories) and it could contains `{self}` 
	 * keyword, which is automatically replaced with current class name.
	 * @throws \InvalidArgumentException Class `{$resourceClassName}` doesn't exist.
	 * @return \MvcCore\IModel
	 */
	public function GetResource ($args = NULL, $classPath = '{self}s\Resource');

}