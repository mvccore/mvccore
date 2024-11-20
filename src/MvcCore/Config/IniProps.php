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

namespace MvcCore\Config;

/**
 * @mixin \MvcCore\Config
 */
trait IniProps {

	/**
	 * System config relative path from app root.
	 * This value could be changed to any value at the very application start.
	 * @var string
	 */
	protected static $configSystemPath = '~/App/config.ini';
	
	/**
	 * Environment config relative path from app root.
	 * This value could be changed to any value at the very application start.
	 * Example: `'~/App/env.ini'`
	 * @var string|NULL
	 */
	protected static $configEnvironmentPath = NULL;

	/**
	 * INI special values to type into `bool` or `NULL`.
	 * @var array<string,bool|NULL>
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
}
