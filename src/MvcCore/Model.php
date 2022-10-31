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
 * @inheritDocs
 */
#[\AllowDynamicProperties]
class Model implements \MvcCore\IModel, \JsonSerializable {
	use \MvcCore\Model\Props;
	use \MvcCore\Model\Config;
	use \MvcCore\Model\Connection;
	use \MvcCore\Model\Resources;
	use \MvcCore\Model\MetaData;
	use \MvcCore\Model\Converters;
	use \MvcCore\Model\Parsers;
	use \MvcCore\Model\DataMethods;
	use \MvcCore\Model\Comparers;
	use \MvcCore\Model\MagicMethods;
}