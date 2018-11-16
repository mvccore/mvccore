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
 * Responsibility - controller lifecycle - data preparing, rendering, response completing.
 * - Controller lifecycle dispatching:
 *   - Handling setup methods after creation from application core dispatching.
 *   - Calling lifecycle methods (`\MvcCore\Controller::Dispatch();`):
 *	 - `\MvcCore\Controller::Init();`
 *	 - `\MvcCore\Controller::PreDispatch();`
 *	 - Calling routed controller action.
 *	 - `\MvcCore\Controller::Render();`
 * - Rendering or no-rendering customization.
 * - HTTP responses and redirects managing and customization.
 * - Basic error responses rendering.
 * - Customization for request termination to write
 *   and close session, sending response etc.
 *
 * Template methods (necessary to call parent at method begin):
 * - `Init()`
 *   - Called after controller is created.
 *   - Session start.
 *   - Auto initialization for sub controllers.
 *   - All internal variables initialized, except `\MvcCore\Controller::$view`.
 * - `PreDispatch()`
 *   - Called after `Init()`, before every controller action.
 *   - `\MvcCore\Controller::$view` property initialization.
 * - `Render()`
 *   - Called after dispatching action has been called.
 *   - `Controller:Action` view rendering responsibility and response competition.
 *
 * Important methods:
 * - `Url()` - proxy method to build URL by configured routes.
 * - `GetParam()` - proxy method to read and clean request param values.
 * - `AddChildController()` - method to register child controller (navigations, etc.)
 *
 * Internal methods and actions:
 * - `Render()`
 *   - Called internally in lifecycle dispatching,
 *	 but it's possible to use it for custom purposes.
 * - `Terminate()`
 *   - Called internally after lifecycle dispatching,
 *	 but it's possible to use it for custom purposes.
 * - `Dispatch()`
 *   - Processing whole controller and sub-controllers lifecycle.
 * - `AssetAction()`
 *   - Handling internal MvcCore HTTP requests
 *	 to get assets from packed application package.
 */
class Controller implements IController
{
	use \MvcCore\Controller\PropsGettersSetters;
	use \MvcCore\Controller\Dispatching;
	use \MvcCore\Controller\Rendering;
}
