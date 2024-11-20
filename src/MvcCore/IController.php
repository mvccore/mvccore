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
 * Responsibility - controller lifecycle - data preparing, rendering, response completing.
 * - Controller lifecycle dispatching:
 *   - Handling setup methods after creation from application core dispatching.
 *   - Calling lifecycle methods (`\MvcCore\Controller::Dispatch();`):
 *   - `\MvcCore\Controller::Init();`
 *   - `\MvcCore\Controller::PreDispatch();`
 *   - Calling routed controller action.
 *   - `\MvcCore\Controller::Render();`
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
 *     but it's possible to use it for custom purposes.
 * - `Terminate()`
 *   - Called internally after lifecycle dispatching,
 *     but it's possible to use it for custom purposes.
 * - `Dispatch()`
 *   - Processing whole controller and sub-controllers lifecycle.
 * - `AssetAction()`
 *   - Handling internal MvcCore HTTP requests
 *     to get assets from packed application package.
 */
interface IController extends \MvcCore\Controller\IConstants {

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
	 * Try to determinate `\MvcCore\Controller` instance from `debug_bactrace()`,
	 * where was form created, if no form instance given into form constructor.
	 * If no previous controller instance founded, `NULL` is returned.
	 * @return \MvcCore\IController|NULL
	 */
	public static function GetCallerControllerInstance ();

	/**
	 * Redirect client browser to another place by `"Location: ..."`
	 * header and call `\MvcCore\Application::GetInstance()->Terminate();`.
	 * @param  string      $location
	 * @param  int         $code
	 * @param  string|NULL $reason   Any optional text header for reason why.
	 * @throws \MvcCore\Controller\TerminateException
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
	 * @param  string|NULL $actionName
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
	 * Get param value from `$_GET`, `$_POST` or `php://input`, filtered by
	 * "rule to keep defined characters only", defined in second argument (by `preg_replace()`).
	 * Place into second argument only char groups you want to keep.
	 * Shortcut for: `\MvcCore\Request::GetParam();`
	 * @param  string                               $name
	 * Parameter string name.
	 * @param  string|array{0:string,1:string}|bool $pregReplaceAllowedChars
	 * If `string` - list of regular expression characters to only keep,
	 * if `array` - `preg_replace()` pattern and reverse,
	 * if `FALSE`, raw value is returned.
	 * @param  mixed                                $ifNullValue
	 * Default value returned if given param name is null.
	 * @param  string|NULL                          $targetType
	 * Target type to retype param value or default if-null value. 
	 * If param is an array, every param item will be retyped into given target type.
	 * @return string|array<string>|int|array<int>|bool|array<bool>|array|mixed
	 */
	public function GetParam (
		$name = "",
		$pregReplaceAllowedChars = "a-zA-Z0-9_;, /\-\@\:",
		$ifNullValue = NULL,
		$targetType = NULL
	);

	/**
	 * Get current application singleton instance object as reference.
	 * @return \MvcCore\Application
	 */
	public function GetApplication ();

	/**
	 * Sets up `\MvcCore\Application` singleton object.
	 * This is INTERNAL, not TEMPLATE method, internally called in
	 * `\MvcCore\Application::CreateController()` before controller is dispatched.
	 * Usually call this as soon as possible after controller creation.
	 * @param  \MvcCore\Application $application
	 * @return \MvcCore\Controller
	 */
	public function SetApplication (\MvcCore\IApplication $application);

	/**
	 * Get environment object to detect and manage environment name.
	 * @return \MvcCore\Environment
	 */
	public function GetEnvironment();

	/**
	 * Get current application request object as reference.
	 * @return \MvcCore\Request
	 */
	public function GetRequest ();

	/**
	 * Get requested controller name - `"dashed-controller-name"`.
	 * @return string
	 */
	public function GetControllerName ();

	/**
	 * Set requested controller name - `"dashed-controller-name"`.
	 * @param  string $controllerName 
	 * @return \MvcCore\Controller
	 */
	public function SetControllerName ($controllerName);

	/**
	 * Get requested action name - `"dashed-action-name"`.
	 * @return string
	 */
	public function GetActionName ();

	/**
	 * Set requested action name - `"dashed-action-name"`.
	 * @param  string $actionName
	 * @return \MvcCore\Controller
	 */
	public function SetActionName ($actionName);

	/**
	 * Set environment object to detect and manage environment name.
	 * This is INTERNAL, not TEMPLATE method.
	 * @param  \MvcCore\Environment $environment
	 * @return \MvcCore\Controller
	 */
	public function SetEnvironment (\MvcCore\IEnvironment $environment);

	/**
	 * Sets up `\MvcCore\Request` object and other protected properties.
	 * This is INTERNAL, not TEMPLATE method, internally called in
	 * `\MvcCore\Application::CreateController();` before controller is dispatched.
	 * Usually call this as soon as possible after controller creation
	 * to set up following controller properties:
	 * - `\MvcCore\Controller::$request`
	 * - `\MvcCore\Controller::$controllerName`
	 * - `\MvcCore\Controller::$actionName`
	 * - `\MvcCore\Controller::$ajax`
	 * @param  \MvcCore\Request $request
	 * @return \MvcCore\Controller
	 */
	public function SetRequest (\MvcCore\IRequest $request);

	/**
	 * Get current application response object as reference.
	 * @return \MvcCore\Response
	 */
	public function GetResponse ();

	/**
	 * Sets up `\MvcCore\Response` object.
	 * This is INTERNAL, not TEMPLATE method, internally called in
	 * `\MvcCore\Application::CreateController()` before controller is dispatched.
	 * Usually call this as soon as possible after controller creation.
	 * @param  \MvcCore\Response $response
	 * @return \MvcCore\Controller
	 */
	public function SetResponse (\MvcCore\IResponse $response);

	/**
	 * Get current application router object as reference.
	 * @return \MvcCore\Router
	 */
	public function GetRouter ();

	/**
	 * Sets up `\MvcCore\Router` object.
	 * This is INTERNAL, not TEMPLATE method, internally called in
	 * `\MvcCore\Application::CreateController()` before controller is dispatched.
	 * Usually call this as soon as possible after controller creation.
	 * @param  \MvcCore\Router $router
	 * @return \MvcCore\Controller
	 */
	public function SetRouter (\MvcCore\IRouter$router);

	/**
	 * Get boolean about AJAX request.
	 * `TRUE` if request is requested from browser by `XmlHttpRequest` object
	 * with http header: `X-Requested-With: AnyJavascriptFrameworkName`, `FALSE` otherwise.
	 * @return boolean
	 */
	public function IsAjax ();
	
	/**
	 * Set boolean about AJAX request.
	 * `TRUE` if request is requested from browser by `XmlHttpRequest` object
	 * with http header: `X-Requested-With: AnyJavascriptFrameworkName`, `FALSE` otherwise.
	 * @param  boolean $ajax 
	 * @return \MvcCore\Controller
	 */
	public function SetIsAjax ($ajax);

	/**
	 * Get controller lifecycle state:
	 * - 0 => Controller has been created.
	 * - 1 => Controller has been initialized.
	 * - 2 => Controller has been pre-dispatched.
	 * - 3 => controller has been action executed.
	 * - 4 => Controller has been rendered.
	 * - 5 => Controller has been redirected.
	 * You can compare value with predefined constants:
	 * - `\MvcCore\IController::DISPATCH_STATE_CREATED`
	 * - `\MvcCore\IController::DISPATCH_STATE_INITIALIZED`
	 * - `\MvcCore\IController::DISPATCH_STATE_PRE_DISPATCHED`
	 * - `\MvcCore\IController::DISPATCH_STATE_ACTION_EXECUTED`
	 * - `\MvcCore\IController::DISPATCH_STATE_RENDERED`
	 * - `\MvcCore\IController::DISPATCH_STATE_TERMINATED`
	 * @return int
	 */
	public function GetDispatchState ();

	/**
	 * Set controller lifecycle state:
	 * - 0 => Controller has been created.
	 * - 1 => Controller has been initialized.
	 * - 2 => Controller has been pre-dispatched.
	 * - 3 => controller has been action executed.
	 * - 4 => Controller has been rendered.
	 * - 5 => Controller has been redirected.
	 * You can use predefined constants:
	 * - `\MvcCore\IController::DISPATCH_STATE_CREATED`
	 * - `\MvcCore\IController::DISPATCH_STATE_INITIALIZED`
	 * - `\MvcCore\IController::DISPATCH_STATE_PRE_DISPATCHED`
	 * - `\MvcCore\IController::DISPATCH_STATE_ACTION_EXECUTED`
	 * - `\MvcCore\IController::DISPATCH_STATE_RENDERED`
	 * - `\MvcCore\IController::DISPATCH_STATE_TERMINATED`
	 * @param  int $dispatchState
	 * @return \MvcCore\Controller
	 */
	public function SetDispatchState ($dispatchState);

	/**
	 * Get user model instance. Template method.
	 * @return \MvcCore\Model
	 */
	public function GetUser ();

	/**
	 * Set user model instance. Template method.
	 * @param \MvcCore\Model $user
	 * @return \MvcCore\Controller
	 */
	public function SetUser ($user);

	/**
	 * Return current controller view object if any.
	 * Before `\MvcCore\Controller::PreDispatch();` is called
	 * in controller lifecycle, this property will be still `NULL`.
	 * @return \MvcCore\View|NULL
	 */
	public function GetView ();

	/**
	 * Set current controller view object.
	 * @param  \MvcCore\View $view
	 * @return \MvcCore\Controller
	 */
	public function SetView (\MvcCore\IView $view);

	/**
	 * Get rendering mode switch to render views in two ways:
	 * `\MvcCore\IView::RENDER_WITH_OB_FROM_ACTION_TO_LAYOUT`:
	 *   - Render action view first into output buffer, then render layout view
	 *     wrapped around rendered action view string also into output buffer.
	 *     Then set up rendered content from output buffer into response object
	 *     and then send HTTP headers and content after all.
	 * `\MvcCore\IView::RENDER_WITHOUT_OB_CONTINUOUSLY`:
	 *   - Special rendering mode to continuously sent larger data to client.
	 *     Render layout view and render action view together inside it without
	 *     output buffering. There is not used reponse object body property for
	 *     this rendering mode. Http headers are sent before view rendering.
	 * @return int
	 */
	public function GetRenderMode ();

	/**
	 * Set rendering mode switch to render views in two ways:
	 * `\MvcCore\IView::RENDER_WITH_OB_FROM_ACTION_TO_LAYOUT`:
	 *   - Render action view first into output buffer, then render layout view
	 *     wrapped around rendered action view string also into output buffer.
	 *     Then set up rendered content from output buffer into response object
	 *     and then send HTTP headers and content after all.
	 * `\MvcCore\IView::RENDER_WITHOUT_OB_CONTINUOUSLY`:
	 *   - Special rendering mode to continuously sent larger data to client.
	 *     Render layout view and render action view together inside it without
	 *     output buffering. There is not used reponse object body property for
	 *     this rendering mode. Http headers are sent before view rendering.
	 * @param  int $renderMode
	 * @return \MvcCore\Controller
	 */
	public function SetRenderMode ($renderMode = \MvcCore\IView::RENDER_WITH_OB_FROM_ACTION_TO_LAYOUT);

	/**
	 * Get layout name to render html wrapper around rendered action view.
	 * Example: `"front" | "admin" | "account"...`.
	 * @return string|NULL
	 */
	public function GetLayout ();

	/**
	 * Set layout name to render html wrapper around rendered action view.
	 * Example: `"front" | "admin" | "account"...`.
	 * @param  string|NULL $layout
	 * @return \MvcCore\Controller
	 */
	public function SetLayout ($layout);

	/**
	 * Get customized sub-controllers template path value. `NULL` by default.
	 * You need to set into this property any custom string as relative path to
	 * your template file placed somewhere in `/App/Views/Scripts/`.
	 * For example if you want to render template file placed in:
	 * `/App/Views/Scripts/something/completely/custom.phtml`, you need to set
	 * up this property to value `something/completely` and then there is
	 * necessary to render your template only by calling controller rendering by:
	 * `$subcontrollerInstance->Render('custom');`
	 * @return string|NULL
	 */
	public function GetViewScriptsPath ();

	/**
	 * Get customized sub-controllers template path value. `NULL` by default.
	 * You need to set into this property any custom string as relative path to
	 * your template file placed somewhere in `/App/Views/Scripts/`.
	 * For example if you want to render template file placed in:
	 * `/App/Views/Scripts/something/completely/custom.phtml`, you need to set
	 * up this property to value `something/completely` and then there is
	 * necessary to render your template only by calling controller rendering by:
	 * `$subcontrollerInstance->Render('custom');`
	 * @param  string|NULL $viewScriptsPath
	 * @return \MvcCore\Controller
	 */
	public function SetViewScriptsPath ($viewScriptsPath = NULL);

	/**
	 * Get `TRUE` if view is automatically created in base controller `PreDispatch()`
	 * method and if view is automatically rendered with wrapping layout view
	 * around after controller action is called. Or get `FALSE` if no view
	 * automatically rendered. Default value is `TRUE` for all non-ajax requests.
	 * @return bool
	 */
	public function GetViewEnabled ();

	/**
	 * Set `TRUE` if view object will be automatically created in base controller
	 * `PreDispatch()` method and if view will be automatically rendered with wrapping
	 * layout view around after controller action is called. Or set `FALSE`
	 * otherwise to not render any view. Default value is `TRUE` for all non-ajax requests.
	 * @param  bool $viewEnabled
	 * @return \MvcCore\Controller
	 */
	public function SetViewEnabled ($viewEnabled = TRUE);

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
	 * Get parent controller instance if any.
	 * Method for child controllers. This method returns
	 * `NULL` for top most parent controller instance.
	 * @return \MvcCore\Controller|NULL
	 */
	public function GetParentController ();

	/**
	 * Set parent controller instance
	 * or `NULL` for "top most parent" controller.
	 * Method for child controllers.
	 * @param  \MvcCore\Controller|NULL $parentController
	 * @return \MvcCore\Controller
	 */
	public function SetParentController (\MvcCore\IController $parentController = NULL);

	/**
	 * Get all child controllers array, indexed by
	 * sub-controller property string name or by
	 * custom string name or by custom numeric index.
	 * @return \MvcCore\Controller[]
	 */
	public function GetChildControllers ();

	/**
	 * Set all child controllers array, indexed by
	 * sub-controller property string name or by
	 * custom string name or by custom numeric index.
	 * This method is dangerous, because it replace all
	 * previous child controllers with given child controllers.
	 * If you want only to add child controller, use method:
	 * \MvcCore\Controller::AddChildController();` instead.
	 * @param  \MvcCore\Controller[] $childControllers
	 * @return \MvcCore\Controller
	 */
	public function SetChildControllers (array $childControllers = []);

	/**
	 * Get child controller at specific index.
	 * Sub-controller index should be string by parent controller
	 * property name or custom string name or numeric index.
	 * @param  string|int $index
	 * @return \MvcCore\Controller
	 */
	public function GetChildController ($index = NULL);

	/**
	 * Get (optionally cached) config INI file as `stdClass` or `array`,
	 * placed relatively from application document root.
	 * @param  string $appRootRelativePath Any config relative path from application root dir like `'~/App/website.ini'`.
	 * @return \MvcCore\Config|NULL
	 */
	public function GetConfig ($appRootRelativePath);

	/**
	 * Get (optionally cached) system config INI file as `stdClass` or `array`,
	 * placed by default in: `"/App/config.ini"`.
	 * @return \MvcCore\Config|NULL
	 */
	public function GetConfigSystem ();

	/**
	 * Return small assets content with proper headers
	 * in single file application mode and immediately exit.
	 * @throws \Exception If file path is not allowed (500) or file not found (404).
	 * @return void
	 */
	public function AssetAction ();
	
	/**
	 * Rendering process alias for `\MvcCore\Controller::Render();`.
	 * @return string
	 */
	public function __toString ();

	/**
	 * @inheritDoc
	 * @param  string            $msg
	 * Flash message text to display in next request(s).
	 * @param  int|list<int>     $options
	 * Could be defined as integer or as array with integer 
	 * keys and values. Use flags like 
	 * `\MvcCore\IController::FLASH_MESSAGE_*`.
	 * @param  array<int,string> $replacements
	 * Array with integer (`{0},{1},{2}...`) or 
	 * named (`{two},{two},{three}...`) replacements.
	 * @return \MvcCore\Controller Returns current controller context.
	 */
	public function FlashMessageAdd ($msg, $options = \MvcCore\IController::FLASH_MESSAGE_TYPE_DEFAULT, array $replacements = []);

	/**
	 * Get flash messages from previous request 
	 * to render it and clean flash messages records.
	 * @return array<string,\stdClass>
	 */
	public function FlashMessagesGetClean ();

	/**
	 * - This method is called INTERNALLY in lifecycle dispatching process,
	 *   but you can use it sooner or in any different time for custom render purposes.
	 * - Render prepared controller/action view in path by default:
	 * `"/App/Views/Scripts/<ctrl-dashed-name>/<action-dashed-name>.phtml"`.
	 * - If controller has no other parent controller, render layout view around action view.
	 * - For top most parent controller - store rendered action and layout view in response object and return empty string.
	 * - For child controller - return rendered action view as string.
	 * @param  string|NULL $controllerOrActionNameDashed
	 * @param  string|NULL $actionNameDashed
	 * @return string
	 */
	public function Render ($controllerOrActionNameDashed = NULL, $actionNameDashed = NULL);

	/**
	 * Store rendered HTML output inside `\MvcCore\Controller::$response`
	 * to send into client browser later in `\MvcCore\Application::GetInstance()->Terminate();`.
	 * Set up `Content-Type` header to `text/html` or to `application/xhtml+xml` only if not set.
	 * @param  string $output
	 * @param  bool   $terminate
	 * @return void
	 */
	public function HtmlResponse ($output, $terminate = TRUE);

	/**
	 * Store rendered XML output inside `\MvcCore\Controller::$response`
	 * to send into client browser later in `\MvcCore\Application::GetInstance()->Terminate();`.
	 * Set up `Content-Type` header to `application/xml` only if not set.
	 * @param  string $output
	 * @param  bool   $terminate
	 * @return void
	 */
	public function XmlResponse ($output, $terminate = TRUE);

	/**
	 * Store rendered text output inside `\MvcCore\Controller::$response`
	 * to send into client browser later in `\MvcCore\Application::GetInstance()->Terminate();`.
	 * Set up `Content-Type` header to `text/plain`.
	 * @param  string $output
	 * @param  bool   $terminate
	 * @return void
	 */
	public function TextResponse ($output, $terminate = TRUE);

	/**
	 * Serialize any PHP value into `JSON string` and store
	 * it inside `\MvcCore\Controller::$response` to send it
	 * into client browser later in `\MvcCore\Application::GetInstance()->Terminate();`.
	 * Set up `Content-Type` header to `application/json`.
	 * 
	 * JSON encoding flags used by default:
	 *  - `JSON_HEX_TAG`:
	 *     All < and > are converted to \u003C and \u003E. Available as of PHP 5.3.0.
	 *  - `JSON_HEX_AMP`:
	 *    All & are converted to \u0026. Available as of PHP 5.3.0.
	 *  - `JSON_HEX_APOS`:
	 *    All ' are converted to \u0027. Available as of PHP 5.3.0.
	 *  - `JSON_HEX_QUOT`:
	 *    All " are converted to \u0022. Available as of PHP 5.3.0.
	 *  - `JSON_UNESCAPED_SLASHES`:
	 *    Don't escape /. Available as of PHP 5.4.0.
	 *  - `JSON_PRESERVE_ZERO_FRACTION`:
	 *    Ensures that float values are always encoded as a float value. Available as of PHP 5.6.6.
	 * Possible JSON encoding flags to add:
	 *  - `JSON_PRETTY_PRINT`:
	 *    Encode JSON into pretty print syntax, Available as of PHP 5.4.0.
	 *  - `JSON_NUMERIC_CHECK`:
	 *    Encodes numeric strings as numbers (be carefull for phone numbers). Available as of PHP 5.3.3.
	 *  - `JSON_UNESCAPED_UNICODE`:
	 *    Encode multibyte Unicode characters literally (default is to escape as \uXXXX). Available as of PHP 5.4.0.
	 *  - `JSON_UNESCAPED_LINE_TERMINATORS`:
	 *    The line terminators are kept unescaped when JSON_UNESCAPED_UNICODE
	 *    is supplied. It uses the same behaviour as it was before PHP 7.1
	 *    without this constant. Available as of PHP 7.1.0.	The following
	 *    constants can be combined to form options for json_decode()
	 *    and json_encode().
	 *  - `JSON_INVALID_UTF8_IGNORE`:
	 *    Ignore invalid UTF-8 characters. Available as of PHP 7.2.0.
	 *  - `JSON_INVALID_UTF8_SUBSTITUTE`:
	 *    Convert invalid UTF-8 characters to \0xfffd (Unicode Character
	 *    'REPLACEMENT CHARACTER') Available as of PHP 7.2.0.
	 *  - `JSON_THROW_ON_ERROR`:
	 *    Throws JsonException if an error occurs instead of setting the global
	 *    error state that is retrieved with json_last_error() and
	 *    json_last_error_msg(). JSON_PARTIAL_OUTPUT_ON_ERROR takes precedence
	 *    over JSON_THROW_ON_ERROR. Available as of PHP 7.3.0.
	 * @param  mixed $data
	 * @param  bool  $terminate
	 * @param  int   $jsonEncodeFlags
	 * @throws \Exception JSON encoding error.
	 * @return void
	 */
	public function JsonResponse ($data, $terminate = TRUE, $jsonEncodeFlags = 0);

	/**
	 * Serialize any PHP value into `JSON string`, wrap around prepared public
	 * javascript function in target window sent as `$_GET` param under
	 * variable `$callbackParamName` (allowed chars: `a-zA-Z0-9\.\-_\$`) and
	 * store it inside `\MvcCore\Controller::$response` to send it
	 * into client browser later in `\MvcCore\Application::GetInstance()->Terminate();`.
	 * Set up `Content-Type` header to `application/javascript`.
	 * 
	 * JSON encoding flags used by default:
	 *  - `JSON_HEX_TAG`:
	 *     All < and > are converted to \u003C and \u003E. Available as of PHP 5.3.0.
	 *  - `JSON_HEX_AMP`:
	 *    All & are converted to \u0026. Available as of PHP 5.3.0.
	 *  - `JSON_HEX_APOS`:
	 *    All ' are converted to \u0027. Available as of PHP 5.3.0.
	 *  - `JSON_HEX_QUOT`:
	 *    All " are converted to \u0022. Available as of PHP 5.3.0.
	 *  - `JSON_UNESCAPED_SLASHES`:
	 *    Don't escape /. Available as of PHP 5.4.0.
	 *  - `JSON_PRESERVE_ZERO_FRACTION`:
	 *    Ensures that float values are always encoded as a float value. Available as of PHP 5.6.6.
	 * Possible JSON encoding flags to add:
	 *  - `JSON_PRETTY_PRINT`:
	 *    Encode JSON into pretty print syntax, Available as of PHP 5.4.0.
	 *  - `JSON_NUMERIC_CHECK`:
	 *    Encodes numeric strings as numbers (be carefull for phone numbers). Available as of PHP 5.3.3.
	 *  - `JSON_UNESCAPED_UNICODE`:
	 *    Encode multibyte Unicode characters literally (default is to escape as \uXXXX). Available as of PHP 5.4.0.
	 *  - `JSON_UNESCAPED_LINE_TERMINATORS`:
	 *    The line terminators are kept unescaped when JSON_UNESCAPED_UNICODE
	 *    is supplied. It uses the same behaviour as it was before PHP 7.1
	 *    without this constant. Available as of PHP 7.1.0.	The following
	 *    constants can be combined to form options for json_decode()
	 *    and json_encode().
	 *  - `JSON_INVALID_UTF8_IGNORE`:
	 *    Ignore invalid UTF-8 characters. Available as of PHP 7.2.0.
	 *  - `JSON_INVALID_UTF8_SUBSTITUTE`:
	 *    Convert invalid UTF-8 characters to \0xfffd (Unicode Character
	 *    'REPLACEMENT CHARACTER') Available as of PHP 7.2.0.
	 *  - `JSON_THROW_ON_ERROR`:
	 *    Throws JsonException if an error occurs instead of setting the global
	 *    error state that is retrieved with json_last_error() and
	 *    json_last_error_msg(). JSON_PARTIAL_OUTPUT_ON_ERROR takes precedence
	 *    over JSON_THROW_ON_ERROR. Available as of PHP 7.3.0.
	 * @param  mixed  $data
	 * @param  string $callbackParamName
	 * @param  bool   $terminate
	 * @param  int    $jsonEncodeFlags
	 * @throws \Exception JSON encoding error.
	 * @return void
	 */
	public function JsonpResponse ($data, $callbackParamName = 'callback', $terminate = TRUE, $jsonEncodeFlags = 0);

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
	 * @param  string              $controllerActionOrRouteName
	 * Should be `"Controller:Action"` combination or just any route name as custom specific string.
	 * @param  array<string,mixed> $params
	 * Optional, array with params, key is param name, value is param value.
	 * @return string
	 */
	public function Url ($controllerActionOrRouteName = 'Index:Index', array $params = []);

	/**
	 * Return asset path or single file mode URL for small assets
	 * handled by internal controller action `"Controller:Asset"`.
	 * @param  string $path
	 * @return string
	 */
	public function AssetUrl ($path);

	/**
	 * Alias for `\MvcCore\Session::GetNamespace($name);`
	 * but called with configured session core class name.
	 * @param  mixed $name
	 * @return \MvcCore\Session
	 */
	public function GetSessionNamespace ($name = \MvcCore\ISession::DEFAULT_NAMESPACE_NAME);

	/**
	 * Render error controller and error action
	 * for any dispatch exception or error as
	 * rendered html response or as plain text response.
	 * @param  string $errorMessage
	 * @return void
	 */
	public function RenderError ($errorMessage);

	/**
	 * Render not found controller and not found action
	 * for any dispatch exception with code 404 as
	 * rendered html response or as plain text response.
	 * @param  string $errorMessage
	 * @return void
	 */
	public function RenderNotFound ($errorMessage);

	/**
	 * Complete view script path by given controller and action or only by given action rendering arguments.
	 * @param  string $controllerOrActionNameDashed
	 * @param  string $actionNameDashed
	 * @return string
	 */
	public function GetViewScriptPath ($controllerOrActionNameDashed = NULL, $actionNameDashed = NULL);

	/**
	 * Terminates request by throwing terminate exception.
	 * - Send headers if possible.
	 * - Echo response body.
	 * - Write session.
	 * This method is always called INTERNALLY after controller
	 * lifecycle has been dispatched. But you can use it any
	 * time sooner for custom purposes.
	 * @throws \MvcCore\Controller\TerminateException
	 * @return void
	 */
	public function Terminate ();
}
