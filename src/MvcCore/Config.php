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
 * Responsibility - reading/writing config file(s), 
 *					detecting environment in system config.
 * - Config file(s) reading:
 *   - Reading any `config.ini` file by relative path.
 *   - Parsing and typing INI data into `stdClass|array` by key types or typing
 *	   INI values into `int|float|bool|string` for all other detected primitives.
 * - Config file(s) writing:
 *   - Dumping `stdClass`es and `array`s into INI syntax string with 
 *     all other environment records.
 *   - Storing serialized config data in single process.
 * - Environment management and detection by:
 *   - comparing server and client IP, by value or regular expression.
 *   - comparing server hostname or IP, by value or regular expression.
 *   - checking system environment variable existence, value or by regular exp.
 */
class Config extends \ArrayObject implements IConfig
{
	use \MvcCore\Config\PropsGettersSetters;
	use \MvcCore\Config\ReadWrite;
	use \MvcCore\Config\Environment;
	use \MvcCore\Config\MagicMethods;
	use \MvcCore\Config\IniProps;
	use \MvcCore\Config\IniRead;
	use \MvcCore\Config\IniDump;
}
