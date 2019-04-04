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

trait Instancing
{
	/**
	 * Automatically initialize `$this-config`, `$this->db` and `$this->resource` properties
	 * if local protected property `$this->autoInit` is still `TRUE` (`TRUE` as default in `\MvcCore\Model`).
	 * @param string|int|NULL $connectionName Optional. If not set, there is used value from `static::$connectionName`.
	 * @return void
	 */
	public function __construct ($connectionName = NULL) {
		if ($this->autoInit) $this->Init($connectionName);
	}

	/**
	 * Returns (or creates and holds) instance from local store.
	 * @param mixed $args,... unlimited OPTIONAL variables to pass into model `__construct()` method.
	 * @return \MvcCore\Model|\MvcCore\IModel
	 */
	public static function & GetInstance () {
		// get `"ClassName"` string from this call: `ClassName::GetInstance();`
		$staticClassName = version_compare(PHP_VERSION, '5.5', '>') ? static::class : get_called_class();
		$args = func_get_args();
		$instanceIndex = str_replace('\\', '_', $staticClassName) . '#' . serialize($args);
		if (!isset(self::$instances[$instanceIndex])) {
			$reflectionClass = new \ReflectionClass($staticClassName);
			$instance = $reflectionClass->newInstanceArgs($args);
			self::$instances[$instanceIndex] = $instance;
		}
		return self::$instances[$instanceIndex];
	}

	/**
	 * Returns (or creates if necessary) model resource instance.
	 * @param array|NULL	$args				Values array with variables to pass into resource `__construct()` method.
	 * @param string		$resourceClassPath	Automatically initialized with string replaced with `%SELF%` by `static::class` (or by `get_called_class()`).
	 * @return \MvcCore\Model|\MvcCore\IModel
	 */
	public static function & GetResource ($args = [], $resourceClassPath = '%SELF%s\Resource') {
		$result = NULL;
		$staticClassPath = version_compare(PHP_VERSION, '5.5', '>') ? static::class : get_called_class();
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
			$result = call_user_func_array([$resourceClassName, 'GetInstance'], $args ?: []);
		} else {
			throw new \InvalidArgumentException("Class `$resourceClassName` doesn't exist.");
		}
		return $result;
	}

	/**
	 * Initialize `$this->config`, `$this->db` and `$this->resource` properties.
	 * If no `$connectionName` specified by first argument, return connection
	 * config by connection name defined first in `static::$connectionName`
	 * and if there is nothing, return connection config by connection name
	 * defined in `\MvcCore\Model::$connectionName`.
	 * @param string|int|NULL $connectionName Optional. If not set, there is used value from `static::$connectionName`.
	 * @return void
	 */
	public function Init ($connectionName = NULL) {
		if ($connectionName === NULL) $connectionName = static::$connectionName;
		if ($connectionName === NULL) $connectionName = self::$connectionName;
		$this->db = static::GetDb($connectionName);
		$this->config = static::GetConfig($connectionName);
		$this->resource = static::GetResource();
	}
}
