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

namespace MvcCore\Controller;

trait PropsGettersSetters
{
	/**
	 * Path to all static files - css, js, images and fonts.
	 * @var string
	 */
	protected static $staticPath = '/static';

	/**
	 * Path to temporary directory with generated css and js files.
	 * @var string
	 */
	protected static $tmpPath = '/Var/Tmp';

	/**
	 * Reference to `\MvcCore\Application` singleton object.
	 * @var \MvcCore\Application|\MvcCore\IApplication
	 */
	protected $application;

	/**
	 * Request object - parsed URI, query params, app paths...
	 * @var \MvcCore\Request|\MvcCore\IRequest
	 */
	protected $request;

	/**
	 * Response object - storage for response headers and rendered body.
	 * @var \MvcCore\Response|\MvcCore\IResponse
	 */
	protected $response;

	/**
	 * Application router object - reference storage for application router to crate URL addresses.
	 * @var \MvcCore\Router|\MvcCore\IRouter
	 */
	protected $router;

	/**
	 * Requested controller name - `"dashed-controller-name"`.
	 * @var string
	 */
	protected $controllerName = '';

	/**
	 * Requested action name - `"dashed-action-name"`.
	 * @var string
	 */
	protected $actionName = '';

	/**
	 * Boolean about AJAX request.
	 * `TRUE` if request is requested from browser by `XmlHttpRequest` object
	 * with http header: `X-Requested-With: AnyJavascriptFrameworkName`, `FALSE` otherwise.
	 * @var boolean
	 */
	protected $ajax = FALSE;

	/**
	 * Class store object for view properties.
	 * Before `\MvcCore\Controller::PreDispatch();` is called
	 * in controller lifecycle, this property will be still `NULL`.
	 * @var \MvcCore\View|\MvcCore\IView
	 */
	protected $view = NULL;

	/**
	 * Layout name to render html wrapper around rendered action view.
	 * @var string
	 */
	protected $layout = 'layout';

	/**
	 * This property is to customize sub-controls template path. `NULL` by default.
	 * You need to set into this property any custom string as relative path to 
	 * your template file placed somewhere in `/App/Views/Scripts/`. 
	 * For example if you want to render template file placed in:
	 * `/App/Views/Scripts/something/completely/custom.phtml`, you need to set
	 * up this property to value `something/completely` and then there is 
	 * necessary to render your template only by calling controller rendering by:
	 * `$subcontrollerInstance->Render('custom');`
	 * @var string|NULL
	 */
	protected $viewScriptsPath = NULL;

	/**
	 * If `TRUE`, view object is automatically created in base controller
	 * `PreDispatch()` method and view is automatically rendered with wrapping
	 * layout view around after controller action is called. Default value is
	 * `TRUE` for all non-ajax requests.
	 * @var boolean
	 */
	protected $viewEnabled = TRUE;

	/**
	 * User model instance. Template property.
	 * @var \MvcCore\Model
	 */
	protected $user = NULL;

	/**
	 * If `TRUE`, start session automatically in `Init()` method.
	 * @var bool
	 */
	protected $autoStartSession = TRUE;

	/**
	 * If `TRUE`, automatically initialize properties with `@autoinit` tag.
	 * @var bool
	 */
	protected $autoInitProperties = TRUE;

	/**
	 * Controller lifecycle state:
	 * - 0 => Controller has been created.
	 * - 1 => Controller has been initialized.
	 * - 2 => Controller has been pre-dispatched.
	 * - 3 => controller has been action dispatched.
	 * - 4 => Controller has been rendered.
	 * - 5 => Controller has been redirected.
	 * @var int
	 */
	protected $dispatchState = 0;

	/**
	 * Parent controller instance if any.
	 * @var \MvcCore\Controller|\MvcCore\IController|NULL
	 */
	protected $parentController = NULL;

	/**
	 * Registered sub-controllers instances.
	 * @var \MvcCore\Controller[]|\MvcCore\IController[]
	 */
	protected $childControllers = [];

	/**
	 * All registered controllers instances.
	 * @var \MvcCore\Controller[]|\MvcCore\IController[]
	 */
	protected static $allControllers = [];

	/**
	 * All asset mime types possibly called through `\MvcCore\Controller::AssetAction();`.
	 * @var string
	 */
	private static $_assetsMimeTypes = [
		'js'	=> 'text/javascript',
		'css'	=> 'text/css',
		'ico'	=> 'image/x-icon',
		'gif'	=> 'image/gif',
		'png'	=> 'image/png',
		'jpg'	=> 'image/jpg',
		'jpeg'	=> 'image/jpeg',
		'bmp'	=> 'image/bmp',
		'svg'	=> 'image/svg+xml',
		'eot'	=> 'application/vnd.ms-fontobject',
		'ttf'	=> 'font/truetype',
		'otf'	=> 'font/opentype',
		'woff'	=> 'application/x-font-woff',
	];
	

	/**
	 * Get param value from `$_GET`, `$_POST` or `php://input`, filtered by
	 * "rule to keep defined characters only", defined in second argument (by `preg_replace()`).
	 * Place into second argument only char groups you want to keep.
	 * Shortcut for: `\MvcCore\Request::GetParam();`
	 * @param string $name Parameter string name.
	 * @param string|array|bool $pregReplaceAllowedChars If String - list of regular expression characters to only keep, if array - `preg_replace()` pattern and reverse, if `FALSE`, raw value is returned.
	 * @param mixed $ifNullValue Default value returned if given param name is null.
	 * @param string $targetType Target type to retype param value or default if-null value. If param is an array, every param item will be retyped into given target type.
	 * @return string|\string[]|int|\int[]|bool|\bool[]|array|mixed
	 */
	public function GetParam (
		$name = "",
		$pregReplaceAllowedChars = "a-zA-Z0-9_;, /\-\@\:",
		$ifNullValue = NULL,
		$targetType = NULL
	) {
		return $this->request->GetParam(
			$name, $pregReplaceAllowedChars, $ifNullValue, $targetType
		);
	}

	/**
	 * Get current application singleton instance object as reference.
	 * @return \MvcCore\Application
	 */
	public function GetApplication () {
		return $this->application;
	}

	/**
	 * Sets up `\MvcCore\Application` singleton object.
	 * This is INTERNAL, not TEMPLATE method, internally called in
	 * `\MvcCore\Application::DispatchControllerAction()` before controller is dispatched.
	 * Usually call this as soon as possible after controller creation.
	 * @param \MvcCore\Application $application
	 * @return \MvcCore\Controller|\MvcCore\Controller\PropsGettersSetters
	 */
	public function SetApplication (\MvcCore\IApplication $application) {
		$this->application = $application;
		return $this;
	}

	/**
	 * Get current application request object as reference.
	 * @return \MvcCore\Request
	 */
	public function GetRequest () {
		return $this->request;
	}

	/**
	 * Get requested controller name - `"dashed-controller-name"`.
	 * @return string
	 */
	public function GetControllerName () {
		return $this->controllerName;
	}

	/**
	 * Get requested action name - `"dashed-action-name"`.
	 * @return string
	 */
	public function GetActionName () {
		return $this->actionName;
	}

	/**
	 * Get current application response object as reference.
	 * @return \MvcCore\Response
	 */
	public function GetResponse () {
		return $this->response;
	}

	/**
	 * Sets up `\MvcCore\Response` object.
	 * This is INTERNAL, not TEMPLATE method, internally called in
	 * `\MvcCore::DispatchControllerAction()` before controller is dispatched.
	 * Usually call this as soon as possible after controller creation.
	 * @param \MvcCore\Response $response
	 * @return \MvcCore\Controller|\MvcCore\Controller\PropsGettersSetters
	 */
	public function SetResponse (\MvcCore\IResponse $response) {
		$this->response = $response;
		return $this;
	}

	/**
	 * Get current application router object as reference.
	 * @return \MvcCore\Router
	 */
	public function GetRouter () {
		return $this->router;
	}

	/**
	 * Sets up `\MvcCore\Router` object.
	 * This is INTERNAL, not TEMPLATE method, internally called in
	 * `\MvcCore::DispatchControllerAction()` before controller is dispatched.
	 * Usually call this as soon as possible after controller creation.
	 * @param \MvcCore\Router $router
	 * @return \MvcCore\Controller|\MvcCore\Controller\PropsGettersSetters
	 */
	public function SetRouter (\MvcCore\IRouter $router) {
		$this->router = $router;
		return $this;
	}

	/**
	 * Boolean about AJAX request.
	 * `TRUE` if request is requested from browser by `XmlHttpRequest` object
	 * with http header: `X-Requested-With: AnyJavascriptFrameworkName`, `FALSE` otherwise.
	 * @return boolean
	 */
	public function IsAjax () {
		return $this->ajax;
	}

	/**
	 * Get user model instance.
	 * @return \MvcCore\Model|\MvcCore\IModel
	 */
	public function GetUser () {
		return $this->user;
	}

	/**
	 * Set user model instance.
	 * @param \MvcCore\Model|\MvcCore\IModel $user
	 * @return \MvcCore\Controller|\MvcCore\Controller\PropsGettersSetters
	 */
	public function SetUser ($user) {
		$this->user = $user;
		return $this;
	}

	/**
	 * Return current controller view object if any.
	 * Before `\MvcCore\Controller::PreDispatch();` is called
	 * in controller lifecycle, this property will be still `NULL`.
	 * @return \MvcCore\View|NULL
	 */
	public function GetView () {
		return $this->view;
	}

	/**
	 * Set current controller view object.
	 * @param \MvcCore\View $view
	 * @return \MvcCore\Controller|\MvcCore\Controller\PropsGettersSetters
	 */
	public function SetView (\MvcCore\IView $view) {
		$this->view = $view;
		return $this;
	}

	/**
	 * Get layout name to render html wrapper around rendered action view.
	 * Example: `"front" | "admin" | "account"...`.
	 * @return string
	 */
	public function GetLayout () {
		return $this->layout;
	}

	/**
	 * Set layout name to render html wrapper around rendered action view.
	 * Example: `"front" | "admin" | "account"...`.
	 * @param string $layout
	 * @return \MvcCore\Controller|\MvcCore\Controller\PropsGettersSetters
	 */
	public function SetLayout ($layout = '') {
		$this->layout = $layout;
		return $this;
	}

	/**
	 * Get customized sub-controls template path value. `NULL` by default.
	 * You need to set into this property any custom string as relative path to 
	 * your template file placed somewhere in `/App/Views/Scripts/`. 
	 * For example if you want to render template file placed in:
	 * `/App/Views/Scripts/something/completely/custom.phtml`, you need to set
	 * up this property to value `something/completely` and then there is 
	 * necessary to render your template only by calling controller rendering by:
	 * `$subcontrollerInstance->Render('custom');`
	 * @return string|NULL
	 */
	public function GetViewScriptsPath () {
		return $this->viewScriptsPath;
	}

	/**
	 * Get customized sub-controls template path value. `NULL` by default.
	 * You need to set into this property any custom string as relative path to 
	 * your template file placed somewhere in `/App/Views/Scripts/`. 
	 * For example if you want to render template file placed in:
	 * `/App/Views/Scripts/something/completely/custom.phtml`, you need to set
	 * up this property to value `something/completely` and then there is 
	 * necessary to render your template only by calling controller rendering by:
	 * `$subcontrollerInstance->Render('custom');`
	 * @param string|NULL $viewScriptsPath
	 * @return \MvcCore\Controller|\MvcCore\Controller\PropsGettersSetters
	 */
	public function SetViewScriptsPath ($viewScriptsPath = NULL) {
		$this->viewScriptsPath = $viewScriptsPath;
		return $this;
	}

	/**
	 * Get `TRUE` if view is automatically created in base controller `PreDispatch()` 
	 * method and if view is automatically rendered with wrapping layout view 
	 * around after controller action is called. Or get `FALSE` if no view 
	 * automatically rendered. Default value is `TRUE` for all non-ajax requests.
	 * @return bool
	 */
	public function GetViewEnabled () {
		return $this->viewEnabled;
	}

	/**
	 * Set `TRUE` if view object will be automatically created in base controller
	 * `PreDispatch()` method and if view will be automatically rendered with wrapping
	 * layout view around after controller action is called. Or set `FALSE` 
	 * otherwise to not render any view. Default value is `TRUE` for all non-ajax requests.
	 * @return \MvcCore\Controller|\MvcCore\Controller\PropsGettersSetters
	 */
	public function SetViewEnabled ($viewEnabled = TRUE) {
		$this->viewEnabled = $viewEnabled;
		return $this;
	}

	/**
	 * Get parent controller instance if any.
	 * Method for child controllers. This method returns
	 * `NULL` for top most parent controller instance.
	 * @return \MvcCore\Controller|NULL
	 */
	public function GetParentController () {
		return $this->parentController;
	}

	/**
	 * Set parent controller instance
	 * or `NULL` for "top most parent" controller.
	 * Method for child controllers.
	 * @param \MvcCore\Controller|\MvcCore\IController|NULL $parentController
	 * @return \MvcCore\Controller|\MvcCore\Controller\PropsGettersSetters
	 */
	public function SetParentController (\MvcCore\IController $parentController = NULL) {
		$this->parentController = $parentController;
		return $this;
	}

	/**
	 * Get all child controllers array, indexed by
	 * sub-controller property string name or by
	 * custom string name or by custom numeric index.
	 * @return \MvcCore\Controller[]
	 */
	public function GetChildControllers () {
		return $this->childControllers;
	}

	/**
	 * Set all child controllers array, indexed by
	 * sub-controller property string name or by
	 * custom string name or by custom numeric index.
	 * This method is dangerous, because it replace all
	 * previous child controllers with given child controllers.
	 * If you want only to add child controller, use method:
	 * \MvcCore\Controller::AddChildController();` instead.
	 * @param \MvcCore\Controller[]|\MvcCore\IController[] $childControllers
	 * @return \MvcCore\Controller|\MvcCore\Controller\PropsGettersSetters
	 */
	public function SetChildControllers (array $childControllers = []) {
		$this->childControllers = $childControllers;
		return $this;
	}

	/**
	 * Get child controller at specific index.
	 * Sub-controller index should be string by parent controller
	 * property name or custom string name or numeric index.
	 * @param string|int $index
	 * @return \MvcCore\Controller
	 */
	public function GetChildController ($index = NULL) {
		return $this->childControllers[$index];
	}

	/**
	 * Generates url:
	 * - By `"Controller:Action"` name and params array
	 *   (for routes configuration when routes array has keys with `"Controller:Action"` strings
	 *   and routes has not controller name and action name defined inside).
	 * - By route name and params array
	 *	 (route name is key in routes configuration array, should be any string
	 *	 but routes must have information about controller name and action name inside).
	 * Result address (url string) should have two forms:
	 * - Nice rewritten URL by routes configuration
	 *   (for apps with URL rewrite support (Apache `.htaccess` or IIS URL rewrite module)
	 *   and when first param is key in routes configuration array).
	 * - For all other cases is URL form like: `"index.php?controller=ctrlName&amp;action=actionName"`
	 *	 (when first param is not founded in routes configuration array).
	 * @param string $controllerActionOrRouteName	Should be `"Controller:Action"` combination or just any route name as custom specific string.
	 * @param array  $params						Optional, array with params, key is param name, value is param value.
	 * @return string
	 */
	public function Url ($controllerActionOrRouteName = 'Index:Index', array $params = []) {
		return $this->router->Url($controllerActionOrRouteName, $params);
	}

	/**
	 * Return asset path or single file mode URL for small assets
	 * handled by internal controller action `"Controller:Asset"`.
	 * @param string $path
	 * @return string
	 */
	public function AssetUrl ($path = '') {
		return $this->router->Url('Controller:Asset', ['path' => $path]);
	}
}
