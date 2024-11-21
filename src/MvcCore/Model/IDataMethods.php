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

interface IDataMethods {
	
	/**
	 * Collect all model class properties values into array.
	 * Result keys could be converted by any conversion flag.
	 * @param  int  $propsFlags    All properties flags are available except flags: 
	 *                             - `\MvcCore\IModel::PROPS_INITIAL_VALUES`,
	 *                             - `\MvcCore\IModel::PROPS_CONVERT_CASE_INSENSITIVE`,
	 *                             - `\MvcCore\IModel::PROPS_NAMES_BY_*`.
	 * @param  bool $getNullValues If `TRUE`, include also values with `NULL`s, 
	 *                             `FALSE` by default.
	 * @throws \InvalidArgumentException
	 * @return array<string,mixed>
	 */
	public function GetValues ($propsFlags = 0, $getNullValues = FALSE);

	/**
	 * Set up given `$data` items into `$this` instance context
	 * as typed properties by PHP types (or by PhpDocs comments in PHP < 7.4) 
	 * as properties with the same names as `$data` array keys or converted
	 * by properties flags. Case sensitivelly by default.
	 * Any `$data` items, which are not declared in `$this` context are 
	 * initialized by  `__set()` method.
	 * @param  array<string,mixed> $data
	 * Raw data from database (row) or from form fields.
	 * @param  int                 $propsFlags
	 * All properties flags are available.
	 * @throws \InvalidArgumentException
	 * @return \MvcCore\Model Current `$this` context.
	 */
	public function SetValues ($data = [], $propsFlags = 0);

	/**
	 * Get touched properties from `$this` context.
	 * Touched properties are properties with different value than value under 
	 * property name key in `$this->initialValues` (initial array is optionally 
	 * completed in `SetValues()` method). Result keys could be converted by any 
	 * conversion flag.
	 * @param  int $propsFlags
	 * All properties flags are available except flags: 
	 * - `\MvcCore\IModel::PROPS_INITIAL_VALUES`,
	 * - `\MvcCore\IModel::PROPS_CONVERT_CASE_INSENSITIVE`.
	 * @throws \InvalidArgumentException
	 * @return array<string,mixed>
	 */
	public function GetTouched ($propsFlags = 0);

	/**
	 * Return original initial values completed in model creation.
	 * @param  int $propsFlags
	 * All properties flags are available except flags: 
	 * - `\MvcCore\IModel::PROPS_INITIAL_VALUES`,
	 * - `\MvcCore\IModel::PROPS_CONVERT_CASE_INSENSITIVE`,
	 * - `\MvcCore\IModel::PROPS_SET_DEFINED_ONLY`.
	 * @return array<string,mixed>
	 */
	public function GetInitialValues ($propsFlags = 0);

}