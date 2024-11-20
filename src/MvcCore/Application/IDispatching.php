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

namespace MvcCore\Application;

interface IDispatching {

	/***********************************************************************************
	 *                   `\MvcCore\Application` - Normal Dispatching                   *
	 ***********************************************************************************/

	/**
	 * Dispatch http request/response.
	 * - 1. Complete and init:
	 *     - Complete describing environment object `\MvcCore\Request`.
	 *     - Complete describing request object `\MvcCore\Request`.
	 *     - Complete response storage object `\MvcCore\Response`.
	 *     - Init debugging and logging by `\MvcCore\Debug::Init();`.
	 * - 2. (Process pre-route handlers queue.)
	 * - 3. Route request by your router or with `\MvcCore\Router::Route()` by default.
	 * - 4. (Process post-route handlers queue.)
	 * - 5. Create and set up controller instance.
	 * - 6. (Process pre-dispatch handlers queue.)
	 * - 7. Dispatch controller life-cycle.
	 *     - Call `\MvcCore\Controller::Init()` and `\MvcCore\Controller::PreDispatch()`.
	 *     - Call routed action method.
	 *     - Call `\MvcCore\Controller::Render()` to render all views.
	 * - 6. Terminate request:
	 *     - (Process post-dispatch handlers queue.)
	 *     - Write session in `register_shutdown_function()` handler.
	 *     - Send response headers if possible and echo response body.
	 * @return \MvcCore\Application
	 */
	public function Dispatch ();

	/**
	 * Initialize environment if necessary,
	 * Initialize requst object if necessary,
	 * initialize response object if necessary
	 * and call debug class static `Init()` method.
	 * @throws \Throwable
	 * @return void
	 */
	public function DispatchInit ();

	/**
	 * Return `NULL` for successfully executed request from start to end.
	 * Return `TRUE` for redirected or stopped request by any other way.
	 * Return `FALSE` if request is already terminated.
	 * @throws \Throwable
	 * @return bool|NULL
	 */
	public function DispatchExec ();

	/**
	 * Route request by router obtained by default by calling:
	 * `\MvcCore\Router::GetInstance();`.
	 * Store requested route inside configured
	 * router class to get it later by calling:
	 * `\MvcCore\Router::GetCurrentRoute();`
	 * @throws \LogicException|\InvalidArgumentException
	 * @return bool
	 */
	public function RouteRequest ();

	/**
	 * Process pre-route, pre-request or post-dispatch
	 * handlers queue by queue index. Call every handler in queue
	 * in try catch mode to catch any exceptions to call:
	 * `\MvcCore\Application::DispatchException($e);`.
	 * @param  CustomHandlerRecord[] $handlers
	 * @throws \Throwable
	 * @return bool
	 */
	public function ProcessCustomHandlers (& $handlers = []);

	/**
	 * Resolve controller class name and create controller instance.
	 * If controller class from current route exists - create controller 
	 * instance by the class. If controller class doesn't exist but
	 * if view file for routed controller exists - create controller 
	 * instance by core controller (`\MvcCore\Controller` by default).
	 * @throws \Exception No route for request (404), 
	 *                    controller class `...` doesn't exist. (404),
	 *                    syntax error in controller or view (500) or
	 *                    error by controller instancing (500) or
	 *                    controller class `...` has not method `...` or 
	 *                    view doesn't exist: `...` (404).
	 * @return bool
	 */
	public function SetUpController ();

	/**
	 * Create controller instance by given full class name.
	 * Verify if controller instance has method by
	 * current route or if at least view exists by full path.
	 * @param  string   $ctrlClassFullName
	 * @param  string   $actionNamePc
	 * @param  string   $viewScriptFullPath
	 * @throws \Exception Controller class `...` has not method `...` or 
	 *                    view doesn't exist: `...` (404).
	 * @return bool
	 */
	public function CreateController (
		$ctrlClassFullName, $actionNamePc, $viewScriptFullPath
	);
	
	/**
	 * Starts a session, standardly called from `\MvcCore\Controller::Init();`.
	 * But is should be called anytime sooner, for example in any pre request handler
	 * to redesign request before MVC dispatching or anywhere else.
	 * @return void
	 */
	public function SessionStart ();

	/**
	 * Generates url:
	 * - By `"Controller:Action"` name and params array
	 *   (for routes configuration when routes array has keys with `"Controller:Action"` strings
	 *   and routes has not controller name and action name defined inside).
	 * - By route name and params array
	 *   (route name is key in routes configuration array, should be any string
	 *   but routes must have information about controller name and action name inside).
	 * Result address (url string) should have two forms:
	 * - Nice rewritten URL by routes configuration
	 *   (for apps with URL rewrite support (Apache `.htaccess` or IIS URL rewrite module)
	 *   and when first param is key in routes configuration array).
	 * - For all other cases is URL form like: `"index.php?controller=ctrlName&amp;action=actionName"`
	 *   (when first param is not founded in routes configuration array).
	 * @param  string               $controllerActionOrRouteName
	 * Should be `"Controller:Action"` combination or just any route name as custom specific string.
	 * @param  array<string, mixed> $params
	 * Optional, array with params, key is param name, value is param value.
	 * @return string
	 */
	public function Url ($controllerActionOrRouteName = 'Index:Index', $params = []);

	/**
	 * Terminate request.
	 * The only place in application where is called `echo '....'` without output buffering.
	 * - Process post-dispatch handlers queue.
	 * - Write session through registered handler into `register_shutdown_function()`.
	 * - Send HTTP headers (if still possible).
	 * - Echo response body.
	 * This method is always called INTERNALLY after controller
	 * life-cycle has been dispatched. But you can use it any
	 * time sooner for custom purposes.
	 * @return \MvcCore\Application
	 */
	public function Terminate ();


	/***********************************************************************************
	 *               `\MvcCore\Application` - Request Error Dispatching                *
	 ***********************************************************************************/

	/**
	 * Dispatch caught exception:
	 * - If request is processing PHP package packing to determinate current script dependencies:
	 *   - Do not log or render nothing.
	 * - If request is production mode:
	 *   - Print exception in browser.
	 * - If request is not in development mode:
	 *   - Log error and try to render error page by configured controller and error action:,
	 *     `\App\Controllers\Index::Error();` by default.
	 * @param  \Exception|string $exceptionOrMessage
	 * @param  int|NULL          $code
	 * @return bool
	 */
	public function DispatchException ($exceptionOrMessage, $code = NULL);

	/**
	 * Render error by configured default controller and error action,
	 * `\App\Controllers\Index::Error();` by default.
	 * If there is no controller/action like that or any other exception happens,
	 * it's processed very simple plain text response with 500 http code.
	 * @param  \Exception $e
	 * @return bool
	 */
	public function RenderError ($e);

	/**
	 * Render error by configured default controller and not found error action,
	 * `\App\Controllers\Index::NotFound();` by default.
	 * If there is no controller/action like that or any other exception happens,
	 * it's processed very simple plain text response with 404 http code.
	 * @param  string|NULL $exceptionMessage
	 * @return bool
	 */
	public function RenderNotFound ($exceptionMessage);

	/**
	 * Prepare very simple response with internal server error (500)
	 * as plain text response into `\MvcCore\Application::$response`.
	 * @param  string $text
	 * @return bool
	 */
	public function RenderError500PlainText ($text);

	/**
	 * Prepare very simple response with not found error (404)
	 * as plain text response into `\MvcCore\Application::$response`.
	 * @param  string $text
	 * @return bool
	 */
	public function RenderError404PlainText ($text);

}
