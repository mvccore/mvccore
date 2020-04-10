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
 * Responsibility - session data management - starting, writing and expirations.
 * - Safe start (only once)
 *   - By `\MvcCore\ISession::Start()`
 *	 - Called by `\MvcCore\Application::GetInstance()->SessionStart();`
 *		 - Called by `\MvcCore\Controller::Init();`.
 * - Session writing and closing at request end:
 *   - In `\MvcCore\ISession::Close()`
 *	 - Called over `register_shutdown_function()`
 *	   from `\MvcCore::Terminate();`
 * - Session namespaces management:
 *   - Variables expiration by seconds.
 *   - Variables expiration by request hoops.
 */
class Session implements \Iterator, \ArrayAccess, \Countable, ISession
{
	use \MvcCore\Session\Props;
	use \MvcCore\Session\Starting;
	use \MvcCore\Session\MetaData;
	use \MvcCore\Session\Closing;
	use \MvcCore\Session\NamespaceMethods;
	use \MvcCore\Session\MagicMethods;
}
