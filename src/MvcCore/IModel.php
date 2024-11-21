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
 * Responsibility - static methods for connections, configuration
 *                  and for active record properties manipulation.
 * - Database `\PDO` connecting by config settings.
 * - Reading `db` section configuration(s) from system `config.ini` file.
 * - Resource class with SQL queries localization, instancing and caching.
 * - Data methods for manipulating properties based on active record pattern.
 * - Meta data about properties parsing and caching.
 * - Magic methods handling.
 */
interface	IModel
extends		\MvcCore\Model\IConstants,
			\MvcCore\Model\IComparers,
			\MvcCore\Model\IConfig,
			\MvcCore\Model\IDataMethods,
			\MvcCore\Model\IMagicMethods,
			\MvcCore\Model\IMetaData,
			\MvcCore\Model\IParsers,
			\MvcCore\Model\IResources {
}