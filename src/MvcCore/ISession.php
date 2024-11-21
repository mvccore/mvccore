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
 * Responsibility - session data management - starting, writing and expirations.
 * - Safe start (only once)
 *   - By `\MvcCore\Session::Start()`
 *     - Called by `\MvcCore\Application::GetInstance()->SessionStart();`
 *       - Called by `\MvcCore\Controller::Init();`.
 * - Session writing and closing at request end:
 *   - In `\MvcCore\Session::Close()`
 *   - Called over `register_shutdown_function()`
 *     from `\MvcCore::Terminate();`
 * - Session namespaces management:
 *   - Variables expiration by seconds.
 *   - Variables expiration by request hoops.
 */
interface	ISession
extends		\MvcCore\Session\IConstants,
			\MvcCore\Session\IStarting,
			\MvcCore\Session\IMetaData,
			\MvcCore\Session\INamespaceMethods,
			\MvcCore\Session\IMetaData,
			\MvcCore\Session\IClosing {
}
