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
	 * @param array  $args			  Values array with variables to pass into resource `__construct()` method.
	 * @param string $modelClassPath
	 * @param string $resourceClassPath
	 * @return \MvcCore\Model|\MvcCore\IModel
	 */
	public static function GetResource ($args = [], $modelClassName = '', $resourceClassPath = '\\Resource') {
		$result = NULL;
		if (!$modelClassName) 
			$modelClassName = version_compare(PHP_VERSION, '5.5', '>') ? static::class : get_called_class();
		// do not create resource instance in resource class (if current class name doesn't end with '_Resource' substring):
		if (strpos($modelClassName, '\\Resource') === FALSE) {
			$resourceClassName = $modelClassName . $resourceClassPath;
			// do not create resource instance if resource class doesn't exist:
			if (class_exists($resourceClassName)) {
				$result = call_user_func_array([$resourceClassName, 'GetInstance'], $args);
			}
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
		$this->resource = static::GetResource([], get_class($this));
	}
}
