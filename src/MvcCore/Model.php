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
 * Responsibility - static members for connections and by configuration,
 *				  instances members for active record pattern.
 * - Reading `db` section from system `config.ini` file.
 * - Database `\PDO` connecting by config settings and index.
 * - Instance loaded variables initializing.
 * - Instance initialized values reading.
 * - Virtual calls/sets and gets handling.
 */
class Model implements IModel
{
	use \MvcCore\Model\Props;
	use \MvcCore\Model\Instancing;
	use \MvcCore\Model\DbConnection;
	use \MvcCore\Model\DataMethods;
}
