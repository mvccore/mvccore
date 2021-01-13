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
 * @inheritDocs
 */
class Model implements \MvcCore\IModel {
	use \MvcCore\Model\Props;
	use \MvcCore\Model\Config;
	use \MvcCore\Model\Connection;
	use \MvcCore\Model\Resource;
	use \MvcCore\Model\MetaData;
	use \MvcCore\Model\Converters;
	use \MvcCore\Model\Parsers;
	use \MvcCore\Model\DataMethods;
	use \MvcCore\Model\Comparers;
	use \MvcCore\Model\MagicMethods;
}
