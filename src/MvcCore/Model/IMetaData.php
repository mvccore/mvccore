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

interface IMetaData {
	
	/**
	 * Return cached array of arrays about properties in current class to not create
	 * and parse reflection objects every time. Be carefull, meta data are in lowest 
	 * level as it could be - only in array types, to serialize/unserialize them 
	 * into/from cache as fast as possible instead of serializing PHP objects. 
	 * 
	 * Every key in array is property name, every value is array with metadata:
	 * - `0`	`boolean`	`TRUE` for private property.
	 * - `1'	`boolean`	`TRUE` to allow `NULL` values.
	 * - `2`	`string[]`	Property types from code or from doc comments or empty array.
	 * 
	 * Possible reading flags:
	 *  - `\MvcCore\IModel::PROPS_INHERIT`
	 *  - `\MvcCore\IModel::PROPS_PRIVATE`
	 *  - `\MvcCore\IModel::PROPS_PROTECTED`
	 *  - `\MvcCore\IModel::PROPS_PUBLIC`
	 * @param  int        $propsFlags
	 * @param  array<int> $additionalMaps Compatible format for extension `mvccore/ext-model-db`.
	 * @return array<string,array{0:bool,1:bool,2:array<string>}>
	 */
	public static function GetMetaData ($propsFlags = 0, $additionalMaps = []);

}