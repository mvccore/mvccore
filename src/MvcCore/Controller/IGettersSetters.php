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

interface IGettersSetters {
	
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
	 * Set environment object to detect and manage environment name.
	 * This is INTERNAL, not TEMPLATE method.
	 * @param  \MvcCore\Environment $environment
	 * @return \MvcCore\Controller
	 */
	public function SetEnvironment (\MvcCore\IEnvironment $environment);
	
	/**
	 * Get current application request object as reference.
	 * @return \MvcCore\Request
	 */
	public function GetRequest ();
	
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

}