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

namespace MvcCore\Interfaces;

//include_once('IRequest.php');
//include_once('IResponse.php');
//include_once('IModel.php');
//include_once('IView.php');
//include_once('ISession.php');

/**
 * Responsibility - controller lifecycle - data preparing, rendering, response completing.
 * - Controller lifecycle dispatching:
 *   - Handling setup methods after creation from application core dispatching.
 *   - Calling lifecycle methods (`\MvcCore\Controller::Dispatch();`):
 *     - `\MvcCore\Controller::Init();`
 *     - `\MvcCore\Controller::PreDispatch();`
 *     - Calling routed controller action.
 *     - `\MvcCore\Controller::Render();`
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
 *   - `Controller:Action` view rendering responsibility and response completition.
 *
 * Important methods:
 * - `Url()` - proxy method to build url by configured routes.
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
 *   - Processing whole controller and subcontrollers lifecycle.
 * - `AssetAction()`
 *   - Handling internal MvcCore HTTP requests
 *     to get assets from packed application package.
 */
interface IController
{
	/**
	 * Return always new instance of staticly called class, no singleton.
	 * Always called from `\MvcCore::DispatchControllerAction()` before controller is dispatched,
	 * or always called in `\MvcCore\Controller::autoInitMembers();` in base controller initialization.
	 * This is place where to customize any controller creation process,
	 * before it's created by MvcCore framework to dispatch it.
	 * @return \MvcCore\Interfaces\IController
	 */
	public static function GetInstance ();

	/**
	 * Dispatching controller life cycle by given action.
	 * This is INTERNAL, not TEMPLATE method, internally
	 * called in `\MvcCore::DispatchControllerAction();`.
	 * Call this imediatelly after calling controller methods:
	 * - `\MvcCore\Controller::__construct()`
	 * - `\MvcCore\Controller::SetApplication($application)`
	 * - `\MvcCore\Controller::SetRequest($request)`
	 * - `\MvcCore\Controller::SetResponse($response)`
	 * - `\MvcCore\Controller::SetRouter($router)`
	 * This function automaticly complete (throught controller lifecycle)
	 * protected `\MvcCore\Response` object with response headers and content,
	 * which you can send to client browser by method
	 * `\MvcCore\Controller::Terminate()` or which you can store
	 * anywhere in cache to use it later etc.
	 * @param string $actionName PHP code action name in PascalCase.
	 *							 This value is used to call your desired function
	 *							 in controller without any change.
	 * @return void
	 */
	public function Dispatch ($actionName = "IndexAction");

	/**
	 * TEMPLATE method. Call `parent::Init();` at the method very beginning.
	 * Application controllers initialization.
	 * This is best time to initialize language, locale, session etc.
	 * There is also called auto initialization processing - instance creation
	 * on each controller class member imlementing `\MvcCore\Interfaces\IController`
	 * and marked in doc comments as `@autoinit`.
	 * then there is of course called `\MvcCore\Controller::Init();` method on each
	 * automaticly created subcontroller.
	 * @return void
	 */
	public function Init ();

	/**
	 * TEMPLATE method. Call `parent::PreDispatch();` at the method very beginning.
	 * Application pre render common action - always used in application controllers.
	 * This is best time to define any common properties or common view properties,
	 * which are the same for multiple actions in controller etc.
	 * There is also called `\MvcCore\Controller::PreDispatch();` method on each subcontroller.
	 * @return void
	 */
	public function PreDispatch ();

	/**
	 * Get param value from `$_GET` or `$_POST` or `php://input`,
	 * filtered by characters defined in second argument throught `preg_replace()`.
	 * Place into second argument only char groups you want to keep.
	 * @param string $name
	 * @param string $pregReplaceAllowedChars
	 * @return string
	 */
	public function GetParam ($name = "", $pregReplaceAllowedChars = "a-zA-Z0-9_/\-\.\@");

	/**
	 * Get current application singleton instance object as reference.
	 * @return \MvcCore\Application
	 */
	public function & GetApplication ();

	/**
	 * Sets up `\MvcCore\Application` singleton object.
	 * This is INTERNAL, not TEMPLATE method, internally called in
	 * `\MvcCore::DispatchControllerAction()` before controller is dispatched.
	 * Usually call this as soon as possible after controller creation.
	 * @param \MvcCore\Application $application
	 * @return \MvcCore\Controller
	 */
	public function & SetApplication (\MvcCore\Interfaces\IApplication & $application);

	/**
	 * Get current application request object as reference.
	 * @return \MvcCore\Interfaces\IRequest
	 */
	public function & GetRequest ();

	/**
	 * Sets up `\MvcCore\Request` object and other protected properties.
	 * This is INTERNAL, not TEMPLATE method, internally called in
	 * `\MvcCore::DispatchControllerAction();` before controller is dispatched.
	 * Usually call this as soon as possible after controller creation
	 * to set up following controller properties:
	 * - `\MvcCore\Controller::$request`
	 * - `\MvcCore\Controller::$response`
	 * - `\MvcCore\Controller::$router`
	 * - `\MvcCore\Controller::$controllerName`
	 * - `\MvcCore\Controller::$actionName`
	 * - `\MvcCore\Controller::$ajax`
	 * @param \MvcCore\Interfaces\IRequest $request
	 * @return \MvcCore\Interfaces\IController
	 */
	public function & SetRequest (\MvcCore\Interfaces\IRequest & $request);

	/**
	 * Get current application response object as reference.
	 * @return \MvcCore\Interfaces\IResponse
	 */
	public function & GetResponse ();

	/**
	 * Sets up `\MvcCore\Response` object.
	 * This is INTERNAL, not TEMPLATE method, internally called in
	 * `\MvcCore::DispatchControllerAction()` before controller is dispatched.
	 * Usually call this as soon as possible after controller creation.
	 * @param \MvcCore\Interfaces\IResponse $response
	 * @return \MvcCore\Interfaces\IController
	 */
	public function & SetResponse (\MvcCore\Interfaces\IResponse & $response);

	/**
	 * Get current application router object as reference.
	 * @return \MvcCore\Interfaces\IRouter
	 */
	public function & GetRouter ();

	/**
	 * Sets up `\MvcCore\Router` object.
	 * This is INTERNAL, not TEMPLATE method, internally called in
	 * `\MvcCore::DispatchControllerAction()` before controller is dispatched.
	 * Usually call this as soon as possible after controller creation.
	 * @param \MvcCore\Interfaces\IRouter $router
	 * @return \MvcCore\Interfaces\IController
	 */
	public function & SetRouter (\MvcCore\Interfaces\IRouter & $router);

	/**
	 * Boolean about AJAX request.
	 * `TRUE` if request is requested from browser by `XmlHttpRequest` object
	 * with http header: `X-Requested-With: AnyJavascriptFrameworkName`, `FALSE` otherwise.
	 * @return boolean
	 */
	public function IsAjax ();

	/**
	 * Boolean about disabled or enabled rendering wrapper layout view around at last.
	 * @return bool
	 */
	public function IsViewEnabled ();

	/**
	 * Get user model instance. Template method.
	 * @return \MvcCore\Interfaces\IModel
	 */
	public function & GetUser ();

	/**
	 * Set user model instance. Template method.
	 * @param \MvcCore\Interfaces\IModel $user
	 * @return \MvcCore\Controller
	 */
	public function & SetUser (& $user);

	/**
	 * Return current controller view object if any.
	 * Before `\MvcCore\Controller::PreDispatch();` is called
	 * in controller lifecycle, this property will be still `NULL`.
	 * @return \MvcCore\Interfaces\IView|NULL
	 */
	public function & GetView ();

	/**
	 * Set current controller view object.
	 * @param \MvcCore\Interfaces\IView $view
	 * @return \MvcCore\Interfaces\IController
	 */
	public function & SetView (\MvcCore\Interfaces\IView & $view);

	/**
	 * Get layout name to render html wrapper around rendered action view.
	 * Example: `"front" | "admin" | "account"...`.
	 * @return string
	 */
	public function GetLayout ();

	/**
	 * Set layout name to render html wrapper around rendered action view.
	 * Example: `"front" | "admin" | "account"...`.
	 * @param string $layout
	 * @return \MvcCore\Interfaces\IController
	 */
	public function & SetLayout ($layout = '');

	/**
	 * Disable layout view rendering (rendering html wrapper around rendered action view).
	 * This method is always called internally before
	 * `\MvcCore\Controller::Init();` for all AJAX requests.
	 * @return void
	 */
	public function DisableView ();

	/**
	 * - Register child controller to process dispatching on it later.
	 * - This method is always called INTERNALLY, but you can use it for custom purposes.
	 * - This method automaticly assigns into child controller(s) properties from parent:
	 *   - `\Mvccore\Controller::$_parentController`
	 *   - `\Mvccore\Controller::$request`
	 *   - `\Mvccore\Controller::$response`
	 *   - `\MvcCore\Controller::$router`
	 *   - `\Mvccore\Controller::$layout`
	 *   - `\Mvccore\Controller::$viewEnabled`
	 *   - `\Mvccore\Controller::$user`
	 * @param \MvcCore\Interfaces\IController &$controller
	 * @param string|int $index
	 * @return \MvcCore\Interfaces\IController
	 */
	public function AddChildController (\MvcCore\Interfaces\IController & $controller, $index = NULL);

	/**
	 * Get parent controller instance if any.
	 * Method for child controllers. This method returns
	 * `NULL` for top most parent controller instance.
	 * @return \MvcCore\Interfaces\IController|NULL
	 */
	public function GetParentController ();

	/**
	 * Get all child controllers array, indexed by
	 * subcontroller property string name or by
	 * custom string name or by custom numeric index.
	 * @return \MvcCore\Interfaces\IController[]
	 */
	public function GetChildControllers ();

	/**
	 * Get child controller at specific index.
	 * Subcontroller index should be string by parent controller
	 * property name or custom string name or numeric index.
	 * @param string|int $index
	 * @return \MvcCore\Interfaces\IController
	 */
	public function GetChildController ($index = NULL);

	/**
	 * Return small assets content with proper headers
	 * in single file application mode and immediately exit.
	 * @throws \Exception If file path is not allowed (500) or file not found (404).
	 * @return void
	 */
	public function AssetAction ();

	/**
	 * - This method is called INTERNALLY in lifecycle dispatching process,
	 *   but you can use it sooner or in any different time for custom render purposes.
	 * - Render prepared controller/action view in path by default:
	 * `"/App/Views/Scripts/<ctrl-dashed-name>/<action-dashed-name>.phtml"`.
	 * - If controller has no other parent controller, render layout view aroud action view.
	 * - For top most parent controller - store rendered action and layout view in response object and return empty string.
	 * - For child controller - return rendered action view as string.
	 * @param string $controllerDashedName
	 * @param string $actionDashedName
	 * @return string
	 */
	public function Render ($controllerDashedName = '', $actionDashedName = '');

	/**
	 * Store rendered HTML output inside `\MvcCore\Controller::$response`
	 * to send into client browser later in `MvcCore::Terminate();`.
	 * @param string $output
	 * @param bool $terminate
	 * @return void
	 */
	public function HtmlResponse ($output = "", $terminate = TRUE);

	/**
	 * Store rendered XML output inside `\MvcCore\Controller::$response`
	 * to send into client browser later in `MvcCore::Terminate();`.
	 * @param string $output
	 * @param bool $terminate
	 * @return void
	 */
	public function XmlResponse ($output = "", $terminate = TRUE);

	/**
	 * Serialize any PHP value into `JSON string` and store
	 * it inside `\MvcCore\Controller::$response` to send it
	 * into client browser later in `MvcCore::Terminate();`.
	 * @param mixed $data
	 * @param bool  $terminate
	 * @return void
	 */
	public function JsonResponse ($data = NULL, $terminate = TRUE);

	/**
	 * Generates url:
	 * - By `"Controller:Action"` name and params array
	 *   (for routes configuration when routes array has keys with `"Controller:Action"` strings
	 *   and routes has not controller name and action name defined inside).
	 * - By route name and params array
	 *	 (route name is key in routes configuration array, should be any string
	 *	 but routes must have information about controller name and action name inside).
	 * Result address (url string) should have two forms:
	 * - Nice rewrited url by routes configuration
	 *   (for apps with URL rewrite support (Apache `.htaccess` or IIS URL rewrite module)
	 *   and when first param is key in routes configuration array).
	 * - For all other cases is url form like: `"index.php?controller=ctrlName&amp;action=actionName"`
	 *	 (when first param is not founded in routes configuration array).
	 * @param string $controllerActionOrRouteName	Should be `"Controller:Action"` combination or just any route name as custom specific string.
	 * @param array  $params						Optional, array with params, key is param name, value is param value.
	 * @return string
	 */
	public function Url ($controllerActionOrRouteName = 'Index:Index', $params = array());

	/**
	 * Return asset path or single file mode url for small assets
	 * handled by internal controller action `"Controller:Asset"`.
	 * @param string $path
	 * @return string
	 */
	public function AssetUrl ($path = '');

	/**
	 * Alias for `\MvcCore\Session::GetNamespace($name);`
	 * but called with configured session core class name.
	 * @param mixed $name
	 * @return \MvcCore\Interfaces\ISession
	 */
	public function GetSessionNamespace ($name = \MvcCore\Interfaces\ISession::DEFAULT_NAMESPACE_NAME);

		/**
	 * Render error controller and error action
	 * for any dispatch exception or error as
	 * rendered html response or as plain text response.
	 * @param string $exceptionMessage
	 * @return void
	 */
	public function RenderError ($exceptionMessage = '');

	/**
	 * Render not found controller and not found action
	 * for any dispatch exception with code 404 as
	 * rendered html response or as plain text response.
	 * @return void
	 */
	public function RenderNotFound ();

	/**
	 * Terminate request.
	 * - Send headers if possible.
	 * - Echo response body.
	 * - Write session.
	 * This method is always called INTERNALLY after controller
	 * lifecycle has been dispatched. But you can use it any
	 * time sooner for custom purposses.
	 * This method is only shortcut for: `\MvcCore\Application::GetInstance()->Terminate();`.
	 * @return void
	 */
	public function Terminate ();

	/**
	 * Redirect client browser to another place by `"Location: ..."`
	 * header and call `\MvcCore\Application::GetInstance()->Terminate();`.
	 * @param string $location
	 * @param int    $code
	 * @return void
	 */
	public static function Redirect ($location = '', $code = \MvcCore\Interfaces\IResponse::SEE_OTHER);
}
