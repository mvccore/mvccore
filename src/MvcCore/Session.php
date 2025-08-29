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
 * @implements \Iterator<int|string, mixed>
 * @implements \ArrayAccess<int|string, mixed>
 */
class		Session
implements	\Iterator,
			\ArrayAccess,
			\Countable,
			ISession {
	use \MvcCore\Session\Props;
	use \MvcCore\Session\Starting;
	use \MvcCore\Session\MetaData;
	use \MvcCore\Session\Security;
	use \MvcCore\Session\Closing;
	use \MvcCore\Session\NamespaceMethods;
	use \MvcCore\Session\MagicMethods;
}
