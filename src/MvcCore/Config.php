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

namespace MvcCore;

/**
 * Responsibility - reading config file(s), detecting environment in system config.
 * - Config file(s) reading:
 *   - Reading any `config.ini` file by relative path.
 *   - Parsing and typing ini data into `stdClass|array` by key types or typing
 *	 ini values into `int|float|bool|string` for all other detected primitives.
 * - Environment management:
 *   - Simple environment name detection by comparing server and client ip.
 *   - Environment name detection by config records about computer name or ip.
 */
class Config implements IConfig
{
	use \MvcCore\Config\PropsGettersSetters;
	use \MvcCore\Config\Reading;
	use \MvcCore\Config\Environment;
	use \MvcCore\Config\LoadingIniData;
	use \MvcCore\Config\Helpers;
}
