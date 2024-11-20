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

namespace MvcCore;

/**
 * Responsibility - reading/writing config file(s).
 * - Config file(s) reading:
 *   - Reading any `config.ini` file by relative path.
 *   - Parsing and typing INI data into `stdClass|array` by key types or typing
 *     INI values into `int|float|bool|string` for all other detected primitives.
 * - Config file(s) writing:
 *   - Dumping `stdClass`es and `array`s into INI syntax string with
 *     all other environment records.
 *   - Storing serialized config data in single process.
 */
interface	IConfig
extends		\MvcCore\Config\IConstants,
			\MvcCore\Config\IGettersSetters,
			\MvcCore\Config\IEnvironment,
			\MvcCore\Config\IReadWrite,
			\MvcCore\Config\IMagicMethods,
			\MvcCore\Config\ITypeRead,
			\MvcCore\Config\ITypeDump {

}
