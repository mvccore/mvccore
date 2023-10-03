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
 * @inheritDoc
 */
class Config implements \Iterator, \ArrayAccess, \Countable, IConfig {
	use \MvcCore\Config\PropsGettersSetters;
	use \MvcCore\Config\ReadWrite;
	use \MvcCore\Config\MagicMethods;
	use \MvcCore\Config\Environment;
	use \MvcCore\Config\IniProps;
	use \MvcCore\Config\IniRead;
	use \MvcCore\Config\IniDump;
}
