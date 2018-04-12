<?php

/**
 * MvcCore
 *
 * This source file is subject to the BSD 3 License
 * For the full copyright and license information, please view
 * the LICENSE.md file that are distributed with this source code.
 *
 * @copyright	Copyright (c) 2016 Tom Flídr (https://github.com/mvccore/mvccore)
 * @license		https://mvccore.github.io/docs/mvccore/4.0.0/LICENCE.md
 */

namespace MvcCore\Interfaces;

require_once('Config.php');

/**
 * Responsibility - static members for connections and by configuration,
 *                  instances members for active record pattern.
 * - Reading `db` section from system `config.ini` file.
 * - Database `\PDO` connecting by config settings and index.
 * - Instance loaded variables initializing.
 * - Instance initialized values reading.
 * - Virtual calls/sets and gets handling.
 */
interface IModel
{
	/**
	 * Collect all model class public and inherit field values into array.
	 * @param boolean $getNullValues			If `TRUE`, include also values with `NULL`s, by default - `FALSE`.
	 * @param boolean $includeInheritProperties If `TRUE`, include only fields from current model class and from parent classes.
	 * @param boolean $publicOnly               If `TRUE`, include only public model fields.
	 * @return array
	 */
	public function GetValues (
		$getNullValues = FALSE,
		$includeInheritProperties = TRUE,
		$publicOnly = TRUE
	);

	/**
	 * Set up given `$data` items into `$this` instance context
	 * as typed properties by PHP doc comments, as properties
	 * with the same names as `$data` array keys. Case sesitively by default.
	 * Do not set any `$data` items, which are not declared in `$this` context.
	 * @param array   $data                     Collection with data to set up
	 * @param boolean $keysInsensitive			If `TRUE`, set up properties from `$data` with case insensivity.
	 * @param boolean $includeInheritProperties If `TRUE`, include only fields from current model class and from parent classes.
	 * @param boolean $publicOnly               If `TRUE`, include only public model fields.
	 * @return \MvcCore\Interfaces\IModel
	 */
	public function & SetUp (
		$data = array(),
		$keysInsensitive = FALSE,
		$includeInheritProperties = TRUE,
		$publicOnly = TRUE
	);

	/**
	 * Returns (or creates and holds) instance from local store.
	 * @param mixed $arg,... unlimited OPTIONAL variables to pass into model `__construct()` method.
	 * @return \MvcCore\Interfaces\IModel
	 */
	public static function GetInstance (/* $arg1, $arg2, $arg, ... */);

	/**
	 * Returns (or creates if necessary) model resource instance.
	 * @param array  $args              Values array with variables to pass into resource `__construct()` method.
	 * @param string $modelClassPath
	 * @param string $resourceClassPath
	 * @return \MvcCore\Interfaces\IModel
	 */
	public static function GetResource (
		$args = array(),
		$modelClassName = '',
		$resourceClassPath = '\Resource'
	);

	/**
	 * Creates an instance and inits cfg, db and resource properties.
	 * @param int $connectionIndex
	 * @return void
	 */
	public function Init ($connectionIndex = -1);

	/**
	 * Returns database connection by connection index (cached by local store)
	 * or create new connection of no connection cached.
	 * @param int $connectionIndex
	 * @return \PDO
	 */
	public static function GetDb ($connectionIndex = -1);

	/**
	 * Returns database config by connection index as `\stdClass` (cached by local store).
	 * @param int $connectionIndex
	 * @return object
	 */
	public static function GetCfg ($connectionIndex = -1);

	/**
	 * Sets any custom property `"PropertyName"` by `\MvcCore\Interfaces\IModel::SetPropertyName("value")`,
	 * which is not necessary to define previously or gets previously defined
	 * property `"PropertyName"` by `\MvcCore\Interfaces\IModel::GetPropertyName();`.
	 * Throws exception if no property defined by get call
	 * or if virtual call begins with anything different from `Set` or `Get`.
	 * This method returns custom value for get and `\MvcCore\Interfaces\IModel` instance for set.
	 * @param string $rawName
	 * @param array  $arguments
	 * @throws \Exception
	 * @return mixed|\MvcCore\Interfaces\IModel
	 */
	public function __call ($rawName, $arguments = array());

	/**
	 * Set any custom property, not necessary to previously defined.
	 * @param string $name
	 * @param bool   $value
	 */
	public function __set ($name, $value);

	/**
	 * Get any custom property, not necessary to previously defined,
	 * if property is not defined, NULL is returned.
	 * @param string $name
	 * @return mixed
	 */
	public function __get ($name);
}
