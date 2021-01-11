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

interface IConstants {

	/**
	 * Set up instance instance properties initial values.
	 * @var int
	 */
	const PROPS_INITIAL_VALUES						= 1;


	/**
	 * Set up instance inherit properties.
	 * @var int
	 */
	const PROPS_INHERIT								= 2;


	/**
	 * Set up instance private properties.
	 * @var int
	 */
	const PROPS_PRIVATE								= 4;
	
	/**
	 * Set up instance protected properties.
	 * @var int
	 */
	const PROPS_PROTECTED							= 8;
	
	/**
	 * Set up instance public properties.
	 * @var int
	 */
	const PROPS_PUBLIC								= 16;


	/**
	 * Get result array with keys by properties names from code
	 * (this flag is not used in core, you have to install any 
	 * extension by `composer require mvccore/ext-database-*`).
	 * @var int
	 */
	const PROPS_NAMES_BY_CODE						= 32;

	/**
	 * Get result array with keys by columns names from database
	 * (this flag is not used in core, you have to install any 
	 * extension by `composer require mvccore/ext-database-*`).
	 * @var int
	 */
	const PROPS_NAMES_BY_DATABASE					= 64;


	/**
	 * Pass throught values with array keys conversion from underscored case
	 * into pascal case.
	 * @var int
	 */
	const PROPS_CONVERT_UNDERSCORES_TO_PASCALCASE	= 128;

	/**
	 * Pass throught values with array keys conversion from underscored case
	 * into camel case.
	 * @var int
	 */
	const PROPS_CONVERT_UNDERSCORES_TO_CAMELCASE	= 256;

	/**
	 * Pass throught values with array keys conversion from pascal case
	 * into underscored case.
	 * @var int
	 */
	const PROPS_CONVERT_PASCALCASE_TO_UNDERSCORES	= 512;

	/**
	 * Pass throught values with array keys conversion from pascal case
	 * into camel case.
	 * @var int
	 */
	const PROPS_CONVERT_PASCALCASE_TO_CAMELCASE		= 1024;

	/**
	 * Pass throught values with array keys conversion from camel case
	 * into underscored case.
	 * @var int
	 */
	const PROPS_CONVERT_CAMELCASE_TO_UNDERSCORES	= 2048;

	/**
	 * Pass throught values with array keys conversion from camel case
	 * into pascal case.
	 * @var int
	 */
	const PROPS_CONVERT_CAMELCASE_TO_PASCALCASE		= 4096;
	

	/**
	 * Pass throught values with array keys case insensitive.
	 * @var int
	 */
	const PROPS_CONVERT_CASE_INSENSITIVE			= 8192;
}