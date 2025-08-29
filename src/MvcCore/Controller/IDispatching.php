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

namespace MvcCore\Controller;

interface IDispatching {
	
	/**
	 * Return always new instance of statically called class, no singleton.
	 * Always called from `\MvcCore\Application::CreateController()` before controller is dispatched,
	 * or always called in `\MvcCore\Controller::autoInitMembers();` in base controller initialization.
	 * This is place where to customize any controller creation process,
	 * before it's created by MvcCore framework to dispatch it.
	 * @return \MvcCore\Controller
	 */
	public static function CreateInstance ();

	/**
	 * Remove controller from dispatching process.
	 * @param  \MvcCore\IController $controller
	 * @return void
	 */
	public static function RemoveController (\MvcCore\IController $controller);

	/**
	 * Remove all controllers from dispatching process.
	 * @return void
	 */
	public static function RemoveAllControllers ();

	/**
	 * Try to determinate `\MvcCore\Controller` class name from `debug_bactrace()`,
	 * where was controller subclass created, if no controller instance given 
	 * into subclass constructor.
	 * If no previous controller instance found, `NULL` is returned.
	 * @return ?string    
	 */
	public static function GetCallerControllerClass ();

	/**
	 * Try to determinate `\MvcCore\Controller` instance from `debug_bactrace()`,
	 * where was controller subclass created, if no controller instance given 
	 * into subclass constructor.
	 * If no previous controller instance founded, `NULL` is returned.
	 * @return ?\MvcCore\Controller
	 */
	public static function GetCallerControllerInstance ();
	
	/**
	 * Redirect client browser to another place by `"Location: ..."`
	 * header and call `\MvcCore\Application::GetInstance()->Terminate();`.
	 * @param  string      $location
	 * @param  int         $code
	 * @param  ?string     $reason   Any optional text header for reason why.
	 * @throws \MvcCore\Application\TerminateException
	 * @return void
	 */
	public static function Redirect ($location, $code = \MvcCore\IResponse::SEE_OTHER, $reason = NULL);
	
	/**
	 * Dispatching controller life cycle by given action.
	 * This is INTERNAL, not TEMPLATE method, internally
	 * called in `\MvcCore\Application::DispatchExec();`.
	 * Call this immediately after calling controller methods:
	 * ```
	 * (new \MvcCore\Controller)
	 *    ->SetApplication($application)
	 *    ->SetEnvironment($environment)
	 *    ->SetRequest($request)
	 *    ->SetResponse($response)
	 *    ->SetRouter($router);
	 * ```
	 * This function automatically complete (through controller life cycle)
	 * protected `\MvcCore\Response` object with response headers and content,
	 * which you can send to client browser by method
	 * `$controller->Terminate()` or which you can store
	 * anywhere in cache to use it later etc.
	 * @internal
	 * @param  ?string     $actionName
	 * Optional, PHP code action name, it has to be in PascalCase 
	 * without any suffix (`Init` or `Action'). This value is used 
	 * later to call your desired functions in controller with this changes:
	 * - `$controller->{$actionName . 'Init'}()`,
	 * - `$controller->{$actionName . 'Action'}()`.
	 * @return bool
	 * Return `FALSE` if application has been already terminated.
	 */
	public function Dispatch ($actionName = NULL);

	/**
	 * Dispatch controller previous states before given target state if necessary.
	 * If controller state is equal or above given point, `FALSE` is returned.
	 * If controller state is under given point, controller is dispatched
	 * to the point and `TRUE` is returned.
	 * @internal
	 * @param  int $state 
	 * Dispatch state, that is required to be completed. Possible values are:
	 * - `\MvcCore\IController::DISPATCH_STATE_CREATED`,
	 * - `\MvcCore\IController::DISPATCH_STATE_INITIALIZED`,
	 * - `\MvcCore\IController::DISPATCH_STATE_ACTION_INITIALIZED`,
	 * - `\MvcCore\IController::DISPATCH_STATE_PRE_DISPATCHED`,
	 * - `\MvcCore\IController::DISPATCH_STATE_ACTION_EXECUTED`,
	 * - `\MvcCore\IController::DISPATCH_STATE_RENDERED`,
	 * - `\MvcCore\IController::DISPATCH_STATE_TERMINATED`.
	 * @return bool
	 */
	public function DispatchStateCheck ($state);
	
	/**
	 * TEMPLATE method. Call `parent::Init();` at the method very beginning.
	 * Application controllers initialization.
	 * This is best time to initialize language, locale, session etc.
	 * There is also called auto initialization processing - instance creation
	 * on each controller class member implementing `\MvcCore\IController`
	 * and marked in doc comments as `@autoInit`.
	 * then there is of course called `\MvcCore\Controller::Init();` method on each
	 * automatically created sub-controller.
	 * @return void
	 */
	public function Init ();

	/**
	 * TEMPLATE method. Call `parent::PreDispatch();` at the method very beginning.
	 * Application pre render common action - always used in application controllers.
	 * This is best time to define any common properties or common view properties,
	 * which are the same for multiple actions in controller etc.
	 * There is also called `\MvcCore\Controller::PreDispatch();` method on each sub-controller.
	 * @return void
	 */
	public function PreDispatch ();
	
	/**
	 * - Register child controller to process dispatching on it later.
	 * - This method is always called INTERNALLY, but you can use it for custom purposes.
	 * - This method automatically assigns into child controller(s) properties from parent:
	 *   - `\MvcCore\Controller::$_parentController`
	 *   - `\MvcCore\Controller::$request`
	 *   - `\MvcCore\Controller::$response`
	 *   - `\MvcCore\Controller::$router`
	 *   - `\MvcCore\Controller::$layout`
	 *   - `\MvcCore\Controller::$viewEnabled`
	 *   - `\MvcCore\Controller::$user`
	 * @param  \MvcCore\Controller $controller
	 * @param  string|int          $index
	 * @return \MvcCore\Controller
	 */
	public function AddChildController (\MvcCore\IController $controller, $index = NULL);
	
	/**
	 * Alias for `\MvcCore\Session::GetNamespace($name);`
	 * but called with configured session core class name.
	 * @param  mixed $name
	 * @return \MvcCore\Session
	 */
	public function GetSessionNamespace ($name = \MvcCore\ISession::DEFAULT_NAMESPACE_NAME);
	
	/**
	 * Terminates request by throwing terminate exception.
	 * - Send headers if possible.
	 * - Echo response body.
	 * - Write session.
	 * This method is always called INTERNALLY after controller
	 * lifecycle has been dispatched. But you can use it any
	 * time sooner for custom purposes.
	 * @throws \MvcCore\Application\TerminateException
	 * @return void
	 */
	public function Terminate ();
	
	/**
	 * Return small assets content with proper headers
	 * in single file application mode and immediately exit.
	 * @throws \Exception If file path is not allowed (500) or file not found (404).
	 * @return void
	 */
	public function AssetAction ();
	
}