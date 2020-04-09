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

namespace MvcCore\Config;

trait IniProps
{
	/**
	 * System config relative path from app root.
	 * This value could be changed to any value at the very application start.
	 * @var string
	 */
	protected static $systemConfigPath = '/%appPath%/config.ini';
	//protected static $systemConfigPath = '/%appPath%/config_object.ini';
	//protected static $systemConfigPath = '/%appPath%/config_array.ini';
	//protected static $systemConfigPath = '/%appPath%/config_simple.ini';

	/**
	 * INI special values to type into `bool` or `NULL`.
	 * @var array
	 */
	protected static $specialValues = [
		'true'	=> TRUE,
		'on'	=> TRUE,
		'yes'	=> TRUE,
		'false'	=> FALSE,
		'off'	=> FALSE,
		'no'	=> FALSE,
		'none'	=> FALSE,
		'null'	=> NULL,
	];

	/**
	 * INI scanner mode. For old PHP versions, lower than `5.6.1`
	 * is automatically set to `1`, for higher, where is possible to
	 * get INI data automatically type, is set to `2`.
	 * @var int
	 */
	private $_iniScannerMode = 0;
}
