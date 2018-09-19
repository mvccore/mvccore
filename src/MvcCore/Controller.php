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

//include_once(__DIR__.'/Interfaces/IController.php');
//include_once(__DIR__.'/Interfaces/ISession.php');
//include_once(__DIR__.'/Interfaces/IResponse.php');
//include_once('Application.php');
//include_once('Tool.php');
//include_once('View.php');
//include_once('Request.php');
//include_once('Response.php');
//include_once('Router.php');
//include_once('Request.php');

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
 *	 but it's possible to use it for custom purposes.
 * - `Terminate()`
 *   - Called internally after lifecycle dispatching,
 *	 but it's possible to use it for custom purposes.
 * - `Dispatch()`
 *   - Processing whole controller and subcontrollers lifecycle.
 * - `AssetAction()`
 *   - Handling internal MvcCore HTTP requests
 *	 to get assets from packed application package.
 */
class Controller implements Interfaces\IController
{
	/**
	 * Path to all static files - css, js, imgs and fonts.
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
	 * @var \MvcCore\Application|\MvcCore\Interfaces\IApplication
	 */
	protected $application;

	/**
	 * Request object - parsed uri, query params, app paths...
	 * @var \MvcCore\Request|\MvcCore\Interfaces\IRequest
	 */
	protected $request;

	/**
	 * Response object - storrage for response headers and rendered body.
	 * @var \MvcCore\Response|\MvcCore\Interfaces\IResponse
	 */
	protected $response;

	/**
	 * Application router object - reference storrage for application router to crate url addresses.
	 * @var \MvcCore\Router|\MvcCore\Interfaces\IRouter
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
	 * @var \MvcCore\View|\MvcCore\Interfaces\IView
	 */
	protected $view = NULL;

	/**
	 * Layout name to render html wrapper around rendered action view.
	 * @var string
	 */
	protected $layout = 'layout';

	/**
	 * This property is to customize subcontrols template path. `NULL` by default.
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
	 * If `TRUE`, view object is automaticly created in base controler
	 * `PreDispatch()` method and view is automaticly rendered with wrapping
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
	 * @var \MvcCore\Controller|\MvcCore\Interfaces\IController|NULL
	 */
	protected $parentController = NULL;

	/**
	 * Registered sub-controllers instances.
	 * @var \MvcCore\Controller[]|\MvcCore\Interfaces\IController[]
	 */
	protected $childControllers = [];

	/**
	 * All registered controllers instances.
	 * @var \MvcCore\Controller[]|\MvcCore\Interfaces\IController[]
	 */
	protected static $allControllers = [];

	/**
	 * All asset mime types possibly called throught `\MvcCore\Controller::AssetAction();`.
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
	 * Return always new instance of staticly called class, no singleton.
	 * Always called from `\MvcCore\Application::DispatchControllerAction()` before controller is dispatched,
	 * or always called in `\MvcCore\Controller::autoInitMembers();` in base controller initialization.
	 * This is place where to customize any controller creation process,
	 * before it's created by MvcCore framework to dispatch it.
	 * @return \MvcCore\Controller
	 */
	public static function & CreateInstance () {
		$instance = new static();
		self::$allControllers[spl_object_hash($instance)] = & $instance;
		return $instance;
	}

	/**
	 * Try to determinate `\MvcCore\Controller` instance from `debug_bactrace()`,
	 * where was form created, if no form instance given into form constructor.
	 * If no previous controller instance founded, `NULL` is returned.
	 * @return \MvcCore\Controller|\MvcCore\Interfaces\IController|NULL
	 */
	public static function & GetCallerControllerInstance () {
		$result = NULL;
		$backtraceItems = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT);
		if (count($backtraceItems) < 3) return $result;
		$calledClass = get_called_class();
		foreach ($backtraceItems as $backtraceItem) {
			if (!isset($backtraceItem['object']) || !$backtraceItem['object']) continue;
			$object = & $backtraceItem['object'];
			$class = & $backtraceItem['class'];
			if (
				$object instanceof \MvcCore\Interfaces\IController &&
				$class !== $calledClass
			) {
				$result = & $object;
				break;
			}
		}
		return $result;
	}

	/**
	 * Redirect client browser to another place by `"Location: ..."`
	 * header and call `\MvcCore\Application::GetInstance()->Terminate();`.
	 * @param string $location
	 * @param int	$code
	 * @return void
	 */
	public static function Redirect ($location = '', $code = \MvcCore\Interfaces\IResponse::SEE_OTHER) {
		$app = & \MvcCore\Application::GetInstance();
		$app->GetResponse()
			->SetCode($code)
			//->SetHeader('Refresh', '0;url='.$location);
			->SetHeader('Location', $location);
		foreach (self::$allControllers as & $controller)
			$controller->dispatchState = 5;
		$app->Terminate();
	}

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
	public function Dispatch ($actionName = "IndexAction") {
		// \MvcCore\Debug::Timer('dispatch');
		$this->Init();
		if ($this->dispatchState == 5) return; // terminated or redirected
		if ($this->dispatchState < 1) $this->dispatchState = 1;// for cases somebody forget to call parent init
		// \MvcCore\Debug::Timer('dispatch');
		$this->PreDispatch();
		if ($this->dispatchState == 5) return; // terminated or redirected
		if ($this->dispatchState < 2) $this->dispatchState = 2;// for cases somebody forget to call parent predispatch
		// \MvcCore\Debug::Timer('dispatch');
		if (method_exists($this, $actionName)) $this->$actionName();
		if ($this->dispatchState == 5) return; // terminated or redirected
		if ($this->dispatchState < 3) $this->dispatchState = 3;
		// \MvcCore\Debug::Timer('dispatch');
		$this->Render(
			$this->controllerName,	// dashed ctrl name
			$this->actionName		// dashed action name
		);
		// \MvcCore\Debug::Timer('dispatch');
	}

	/**
	 * Application controllers initialization.
	 * This is best time to initialize language, locale, session etc.
	 * There is also called auto initialization processing - instance creation
	 * on each controller class member imlementing `\MvcCore\Interfaces\IController`
	 * and marked in doc comments as `@autoinit`.
	 * then there is of course called `\MvcCore\Controller::Init();` method on each
	 * automaticly created subcontroller.
	 * @return void
	 */
	public function Init () {
		if ($this->dispatchState > 0) return;
		self::$allControllers[spl_object_hash($this)] = & $this;
		if ($this->parentController === NULL) {
			$this->application->SessionStart();
			$responseContentType = $this->ajax ? 'text/javascript' : 'text/html';
			$this->response->SetHeader('Content-Type', $responseContentType);
		}
		$this->autoInitProperties();
		foreach ($this->childControllers as $controller) {
			$controller->Init();
			if ($controller->dispatchState == 5) break;
		}
		if ($this->dispatchState === 0) 
			$this->dispatchState = 1;
	}

	/**
	 * Initialize all members implementing `\MvcCore\Interfaces\IController` marked
	 * in doc comments as `@autoinit` into `\MvcCore\Controller::$controllers` array
	 * and into member property itself. This method is always called inside
	 * `\MvcCore\Controller::Init();` method, after session has been started.
	 * @return void
	 */
	protected function autoInitProperties () {
		$type = new \ReflectionClass($this);
		/** @var $props \ReflectionProperty[] */
		$props = $type->getProperties(
			\ReflectionProperty::IS_PUBLIC |
			\ReflectionProperty::IS_PROTECTED |
			\ReflectionProperty::IS_PRIVATE
		);
		$toolsClass = $this->application->GetToolClass();
		foreach ($props as $prop) {
			$docComment = $prop->getDocComment();
			if (mb_strpos($docComment, '@autoinit') === FALSE) continue;
			$pos = mb_strpos($docComment, '@var ');
			if ($pos === FALSE) continue;
			$docComment = str_replace(["\r","\n","\t", "*/"], " ", mb_substr($docComment, $pos + 5));
			$pos = mb_strpos($docComment, ' ');
			if ($pos === FALSE) continue;
			$className = trim(mb_substr($docComment, 0, $pos));
			if (!@class_exists($className)) continue;
			if (!$toolsClass::CheckClassInterface($className, 'MvcCore\\Interfaces\\IController', FALSE, TRUE)) continue;
			$instance = $className::CreateInstance();
			$this->AddChildController($instance, $prop->getName());
			if (!$prop->isPublic()) $prop->setAccessible(TRUE);
			$prop->setValue($this, $instance);
		}
	}

	/**
	 * Application pre render common action - always used in application controllers.
	 * This is best time to define any common properties or common view properties,
	 * which are the same for multiple actions in controller etc.
	 * There is also called `\MvcCore\Controller::PreDispatch();` method on each subcontroller.
	 * @return void
	 */
	public function PreDispatch () {
		if ($this->dispatchState > 1) return;
		if ($this->dispatchState == 0) $this->Init();
		if ($this->viewEnabled) {
			$viewClass = $this->application->GetViewClass();
			$this->view = $viewClass::CreateInstance()->SetController($this);
		}
		foreach ($this->childControllers as $controller) {
			$controller->PreDispatch();
			if ($controller->dispatchState == 5) break;
		}
		if ($this->dispatchState == 1) 
			$this->dispatchState = 2;
	}

	/**
	 * Get param value from `$_GET`, `$_POST` or `php://input`, filtered by
	 * "rule to keep defined characters only", defined in second argument (by `preg_replace()`).
	 * Place into second argument only char groups you want to keep.
	 * Shortcut for: `\MvcCore\Request::GetParam();`
	 * @param string $name Parametter string name.
	 * @param string|array $pregReplaceAllowedChars If String - list of regular expression characters to only keep, if array - `preg_replace()` pattern and reverse.
	 * @param mixed $ifNullValue Default value returned if given param name is null.
	 * @param string $targetType Target type to retype param value or default if-null value. If param is an array, every param item will be retyped into given target type.
	 * @return string|string[]|mixed
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
	public function & GetApplication () {
		return $this->application;
	}

	/**
	 * Sets up `\MvcCore\Application` singleton object.
	 * This is INTERNAL, not TEMPLATE method, internally called in
	 * `\MvcCore\Application::DispatchControllerAction()` before controller is dispatched.
	 * Usually call this as soon as possible after controller creation.
	 * @param \MvcCore\Application $application
	 * @return \MvcCore\Controller
	 */
	public function & SetApplication (\MvcCore\Interfaces\IApplication & $application) {
		$this->application = & $application;
		return $this;
	}

	/**
	 * Get current application request object as reference.
	 * @return \MvcCore\Request
	 */
	public function & GetRequest () {
		return $this->request;
	}

	/**
	 * Sets up `\MvcCore\Request` object and other protected properties.
	 * This is INTERNAL, not TEMPLATE method, internally called in
	 * `\MvcCore\Application::DispatchControllerAction();` before controller is dispatched.
	 * Usually call this as soon as possible after controller creation
	 * to set up following controller properties:
	 * - `\MvcCore\Controller::$request`
	 * - `\MvcCore\Controller::$controllerName`
	 * - `\MvcCore\Controller::$actionName`
	 * - `\MvcCore\Controller::$ajax`
	 * @param \MvcCore\Request|\MvcCore\Interfaces\IRequest $request
	 * @return \MvcCore\Controller
	 */
	public function & SetRequest (\MvcCore\Interfaces\IRequest & $request) {
		/** @var $request \MvcCore\Request */
		$this->request = & $request;
		$this->controllerName = $request->GetControllerName();
		$this->actionName = $request->GetActionName();
		$this->ajax = $request->IsAjax();
		if ($this->ajax || (
			$this->controllerName == 'controller' && $this->actionName == 'asset'
		)) $this->SetViewEnabled(FALSE);
		return $this;
	}

	/**
	 * Get current application response object as reference.
	 * @return \MvcCore\Response
	 */
	public function & GetResponse () {
		return $this->response;
	}

	/**
	 * Sets up `\MvcCore\Response` object.
	 * This is INTERNAL, not TEMPLATE method, internally called in
	 * `\MvcCore::DispatchControllerAction()` before controller is dispatched.
	 * Usually call this as soon as possible after controller creation.
	 * @param \MvcCore\Response $response
	 * @return \MvcCore\Controller
	 */
	public function & SetResponse (\MvcCore\Interfaces\IResponse & $response) {
		$this->response = & $response;
		return $this;
	}

	/**
	 * Get current application router object as reference.
	 * @return \MvcCore\Router
	 */
	public function & GetRouter () {
		return $this->router;
	}

	/**
	 * Sets up `\MvcCore\Router` object.
	 * This is INTERNAL, not TEMPLATE method, internally called in
	 * `\MvcCore::DispatchControllerAction()` before controller is dispatched.
	 * Usually call this as soon as possible after controller creation.
	 * @param \MvcCore\Router $router
	 * @return \MvcCore\Controller
	 */
	public function & SetRouter (\MvcCore\Interfaces\IRouter & $router) {
		$this->router = & $router;
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
	 * @return \MvcCore\Model|\MvcCore\Interfaces\IModel
	 */
	public function & GetUser () {
		return $this->user;
	}

	/**
	 * Set user model instance.
	 * @param \MvcCore\Model|\MvcCore\Interfaces\IModel $user
	 * @return \MvcCore\Controller
	 */
	public function & SetUser (& $user) {
		$this->user = $user;
		return $this;
	}

	/**
	 * Return current controller view object if any.
	 * Before `\MvcCore\Controller::PreDispatch();` is called
	 * in controller lifecycle, this property will be still `NULL`.
	 * @return \MvcCore\View|NULL
	 */
	public function & GetView () {
		return $this->view;
	}

	/**
	 * Set current controller view object.
	 * @param \MvcCore\View $view
	 * @return \MvcCore\Controller
	 */
	public function & SetView (\MvcCore\Interfaces\IView & $view) {
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
	 * @return \MvcCore\Controller
	 */
	public function & SetLayout ($layout = '') {
		$this->layout = $layout;
		return $this;
	}

	/**
	 * Get customized subcontrols template path value. `NULL` by default.
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
	 * Get customized subcontrols template path value. `NULL` by default.
	 * You need to set into this property any custom string as relative path to 
	 * your template file placed somewhere in `/App/Views/Scripts/`. 
	 * For example if you want to render template file placed in:
	 * `/App/Views/Scripts/something/completely/custom.phtml`, you need to set
	 * up this property to value `something/completely` and then there is 
	 * necessary to render your template only by calling controller rendering by:
	 * `$subcontrollerInstance->Render('custom');`
	 * @param string|NULL $viewScriptsPath
	 * @return \MvcCore\Controller
	 */
	public function & SetViewScriptsPath ($viewScriptsPath = NULL) {
		$this->viewScriptsPath = $viewScriptsPath;
		return $this;
	}

	/**
	 * Get `TRUE` if view is automaticly created in base controler `PreDispatch()` 
	 * method and if view is automaticly rendered with wrapping layout view 
	 * around after controller action is called. Or get `FALSE` if no view 
	 * automaticly rendered. Default value is `TRUE` for all non-ajax requests.
	 * @return bool
	 */
	public function GetViewEnabled () {
		return $this->viewEnabled;
	}

	/**
	 * Set `TRUE` if view object will be automaticly created in base controler
	 * `PreDispatch()` method and if view will be automaticly rendered with wrapping
	 * layout view around after controller action is called. Or set `FALSE` 
	 * otherwise to not render any view. Default value is `TRUE` for all non-ajax requests.
	 * @return \MvcCore\Controller
	 */
	public function & SetViewEnabled ($viewEnabled = TRUE) {
		$this->viewEnabled = $viewEnabled;
		return $this;
	}

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
	 * @param \MvcCore\Controller &$controller
	 * @param string|int $index
	 * @return \MvcCore\Controller
	 */
	public function AddChildController (\MvcCore\Interfaces\IController & $controller, $index = NULL) {
		self::$allControllers[spl_object_hash($controller)] = & $controller;
		if (!in_array($controller, $this->childControllers)) {
			if ($index === NULL) {
				$this->childControllers[] = & $controller;
			} else {
				$this->childControllers[$index] = & $controller;
			}
			$controller
				->SetParentController($this)
				->SetApplication($this->application)
				// Method `SetRequest()` also sets `ajax`, `viewEnabled`, `controllerName` and `actionName`.
				->SetRequest($this->request)
				->SetResponse($this->response)
				->SetRouter($this->router)
				->SetLayout($this->layout)
				->SetUser($this->user);
		}
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
	 * @param \MvcCore\Controller|\MvcCore\Interfaces\IController|NULL $parentController
	 * @return \MvcCore\Controller
	 */
	public function & SetParentController (\MvcCore\Interfaces\IController & $parentController = NULL) {
		$this->parentController = $parentController;
		return $this;
	}

	/**
	 * Get all child controllers array, indexed by
	 * subcontroller property string name or by
	 * custom string name or by custom numeric index.
	 * @return \MvcCore\Controller[]
	 */
	public function GetChildControllers () {
		return $this->childControllers;
	}

	/**
	 * Set all child controllers array, indexed by
	 * subcontroller property string name or by
	 * custom string name or by custom numeric index.
	 * This method is dangerous, because it replace all
	 * previous child controllers with given child controllers.
	 * If you want only to add child controller, use method:
	 * \MvcCore\Controller::Addchildcontroller();` instead.
	 * @param \MvcCore\Controller[]|\MvcCore\Interfaces\IController[] $childControllers
	 * @return \MvcCore\Controller
	 */
	public function & SetChildControllers (array & $childControllers = []) {
		$this->childControllers = & $childControllers;
		return $this;
	}

	/**
	 * Get child controller at specific index.
	 * Subcontroller index should be string by parent controller
	 * property name or custom string name or numeric index.
	 * @param string|int $index
	 * @return \MvcCore\Controller
	 */
	public function GetChildController ($index = NULL) {
		return $this->childControllers[$index];
	}

	/**
	 * Return small assets content with proper headers
	 * in single file application mode and immediately exit.
	 * @throws \Exception If file path is not allowed (500) or file not found (404).
	 * @return void
	 */
	public function AssetAction () {
		$ext = '';
		$path = $this->GetParam('path', 'a-zA-Z0-9_\-\/\.');
		$path = '/' . ltrim(str_replace('..', '', $path), '/');
		if (
			strpos($path, static::$staticPath) !== 0 &&
			strpos($path, static::$tmpPath) !== 0
		) {
			throw new \ErrorException("[".__CLASS__."] File path: '$path' is not allowed.", 500);
		}
		$path = $this->request->GetAppRoot() . $path;
		if (!file_exists($path)) {
			throw new \ErrorException("[".__CLASS__."] File not found: '$path'.", 404);
		}
		$lastDotPos = strrpos($path, '.');
		if ($lastDotPos !== FALSE) {
			$ext = substr($path, $lastDotPos + 1);
		}
		if (isset(self::$_assetsMimeTypes[$ext])) {
			header('Content-Type: ' . self::$_assetsMimeTypes[$ext]);
		}
		header_remove('X-Powered-By');
		header('Vary: Accept-Encoding');
		$assetMTime = @filemtime($path);
		if ($assetMTime) header('Last-Modified: ' . gmdate('D, d M Y H:i:s T', $assetMTime));
		readfile($path);
		exit;
	}

	/**
	 * - This method is called INTERNALLY in lifecycle dispatching process,
	 *   but you can use it sooner or in any different time for custom render purposes.
	 * - Render prepared controller/action view in path by default:
	 * `"/App/Views/Scripts/<ctrl-dashed-name>/<action-dashed-name>.phtml"`.
	 * - If controller has no other parent controller, render layout view aroud action view.
	 * - For top most parent controller - store rendered action and layout view in response object and return empty string.
	 * - For child controller - return rendered action view as string.
	 * @param string $controllerOrActionNameDashed
	 * @param string $actionNameDashed
	 * @return string
	 */
	public function Render ($controllerOrActionNameDashed = NULL, $actionNameDashed = NULL) {
		if ($this->dispatchState == 0) $this->Init();
		if ($this->dispatchState == 1) $this->PreDispatch();
		if ($this->dispatchState < 4 && $this->viewEnabled) {
			$currentCtrlIsTopMostParent = $this->parentController === NULL;
			if (!$currentCtrlIsTopMostParent) {
				$this->view->SetUpStore($this->parentController->GetView(), FALSE);
			}
			foreach ($this->childControllers as $ctrlKey => $childCtrl) {
				if (!is_numeric($ctrlKey) && !isset($this->view->$ctrlKey))
					$this->view->$ctrlKey = $childCtrl;
			}
			// complete paths
			$viewScriptPath = $this->renderGetViewScriptPath($controllerOrActionNameDashed, $actionNameDashed);
			// render content string
			$actionResult = $this->view->RenderScript($viewScriptPath);
			if ($currentCtrlIsTopMostParent) {
				// create top most parent layout view, set up and render to outputResult
				$viewClass = $this->application->GetViewClass();
				/** @var $layout \MvcCore\View */
				$layout = $viewClass::CreateInstance()
					->SetController($this)
					->SetUpStore($this->view, TRUE);
				$outputResult = $layout->RenderLayoutAndContent($this->layout, $actionResult);
				unset($layout, $this->view);
				// set up response only
				$this->XmlResponse($outputResult);
			} else {
				// return response
				$this->dispatchState = 4;
				return $actionResult;
			}
		}
		$this->dispatchState = 4;
		return '';
	}

	/**
	 * Complete view script path by given controller and action or only by given action rendering arguments.
	 * @param string $controllerOrActionNameDashed
	 * @param string $actionNameDashed
	 * @return string
	 */
	protected function renderGetViewScriptPath ($controllerOrActionNameDashed = NULL, $actionNameDashed = NULL) {
		$currentCtrlIsTopMostParent = $this->parentController === NULL;
		if ($this->viewScriptsPath !== NULL) {
			$resultPathItems = [$this->viewScriptsPath];
			if ($controllerOrActionNameDashed !== NULL) $resultPathItems[] = $controllerOrActionNameDashed;
			if ($actionNameDashed !== NULL) $resultPathItems[] = $actionNameDashed;
			return str_replace(['_', '\\'], '/', implode('/', $resultPathItems));
		}
		if ($actionNameDashed !== NULL) { // if action defined - take first argument controller
			$controllerNameDashed = $controllerOrActionNameDashed;
		} else { // if no action defined - we need to complete controller dashed name
			if ($currentCtrlIsTopMostParent) { // if controller is tom most one - take routed controller name
				$controllerNameDashed = $this->controllerName;
			} else {
				// if controller is child controller - translate classs name
				// without default controllers directory into dashed name
				$ctrlsDefaultNamespace = $this->application->GetAppDir() . '\\' 
					. $this->application->GetControllersDir();
				$currentCtrlClassName = get_class($this);
				if (mb_strpos($currentCtrlClassName, $ctrlsDefaultNamespace) === 0)
					$currentCtrlClassName = mb_substr($currentCtrlClassName, mb_strlen($ctrlsDefaultNamespace) + 1);
				$currentCtrlClassName = str_replace('\\', '/', $currentCtrlClassName);
				$toolClass = $this->application->GetToolClass();
				$controllerNameDashed = $toolClass::GetDashedFromPascalCase($currentCtrlClassName);
			}
			if ($controllerOrActionNameDashed !== NULL) {
				$actionNameDashed = $controllerOrActionNameDashed;
			} else {
				if ($currentCtrlIsTopMostParent) {// if controller is top most parent - use routed action name
					$actionNameDashed = $this->actionName;
				} else {// if no action name defined - use default action name from core - usually `index`
					$defaultCtrlAction = $this->application->GetDefaultControllerAndActionNames();
					$actionNameDashed = $toolClass::GetDashedFromPascalCase($defaultCtrlAction[1]);
				}
			}
		}
		$controllerPath = str_replace(['_', '\\'], '/', $controllerNameDashed);
		return implode('/', [$controllerPath, $actionNameDashed]);
	}

	/**
	 * Store rendered HTML output inside `\MvcCore\Controller::$response`
	 * to send into client browser later in `MvcCore::Terminate();`.
	 * @param string $output
	 * @param bool $terminate
	 * @return void
	 */
	public function HtmlResponse ($output = '', $terminate = FALSE) {
		if (!$this->response->HasHeader('Content-Type')) {
			$contentTypeHeaderValue = strpos(
				\MvcCore\View::GetDoctype(), \MvcCore\View::DOCTYPE_XHTML
			) !== FALSE ? 'application/xhtml+xml' : 'text/html' ;
			$this->response->SetHeader('Content-Type', $contentTypeHeaderValue);
		}
		$this->response
			->SetCode(\MvcCore\Interfaces\IResponse::OK)
			->SetBody($output);
		if ($terminate) $this->Terminate();
	}

	/**
	 * Store rendered XML output inside `\MvcCore\Controller::$response`
	 * to send into client browser later in `MvcCore::Terminate();`.
	 * @param string $output
	 * @param bool $terminate
	 * @return void
	 */
	public function XmlResponse ($output = '', $terminate = FALSE) {
		if (!$this->response->HasHeader('Content-Type'))
			$this->response->SetHeader('Content-Type', 'application/xml');
		$this->response
			->SetCode(\MvcCore\Interfaces\IResponse::OK)
			->SetBody($output);
		if ($terminate) $this->Terminate();
	}

	/**
	 * Serialize any PHP value into `JSON string` and store
	 * it inside `\MvcCore\Controller::$response` to send it
	 * into client browser later in `MvcCore::Terminate();`.
	 * @param mixed $data
	 * @param bool  $terminate
	 * @return void
	 */
	public function JsonResponse ($data = NULL, $terminate = FALSE) {
		$toolClass = $this->application->GetToolClass();
		$output = $toolClass::EncodeJson($data);
		if (!$this->response->HasHeader('Content-Type'))
			$this->response->SetHeader('Content-Type', 'text/javascript');
		$this->response
			->SetCode(\MvcCore\Interfaces\IResponse::OK)
			->SetHeader('Content-Length', strlen($output))
			->SetBody($output);
		if ($terminate) $this->Terminate();
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
	 * - Nice rewrited url by routes configuration
	 *   (for apps with URL rewrite support (Apache `.htaccess` or IIS URL rewrite module)
	 *   and when first param is key in routes configuration array).
	 * - For all other cases is url form like: `"index.php?controller=ctrlName&amp;action=actionName"`
	 *	 (when first param is not founded in routes configuration array).
	 * @param string $controllerActionOrRouteName	Should be `"Controller:Action"` combination or just any route name as custom specific string.
	 * @param array  $params						Optional, array with params, key is param name, value is param value.
	 * @return string
	 */
	public function Url ($controllerActionOrRouteName = 'Index:Index', $params = []) {
		return $this->router->Url($controllerActionOrRouteName, $params);
	}

	/**
	 * Return asset path or single file mode url for small assets
	 * handled by internal controller action `"Controller:Asset"`.
	 * @param string $path
	 * @return string
	 */
	public function AssetUrl ($path = '') {
		return $this->router->Url('Controller:Asset', ['path' => $path]);
	}

	/**
	 * Alias for `\MvcCore\Session::GetNamespace($name);`
	 * but called with configured session core class name.
	 * @param mixed $name
	 * @return \MvcCore\Interfaces\ISession
	 */
	public function GetSessionNamespace ($name = \MvcCore\Interfaces\ISession::DEFAULT_NAMESPACE_NAME) {
		$sessionClass = $this->application->GetSessionClass();
		return $sessionClass::GetNamespace($name);
	}

	/**
	 * Render error controller and error action
	 * for any dispatch exception or error as
	 * rendered html response or as plain text response.
	 * @param string $exceptionMessage
	 * @return void
	 */
	public function RenderError ($exceptionMessage = '') {
		if ($this->application->IsErrorDispatched()) return;
		throw new \ErrorException(
			$exceptionMessage ? $exceptionMessage :
			"Server error: `" . htmlspecialchars($this->request->GetFullUrl()) . "`.",
			500
		);
	}

	/**
	 * Render not found controller and not found action
	 * for any dispatch exception with code 404 as
	 * rendered html response or as plain text response.
	 * @return void
	 */
	public function RenderNotFound () {
		if ($this->application->IsNotFoundDispatched()) return;
		throw new \ErrorException(
			"Page not found: `" . htmlspecialchars($this->request->GetFullUrl()) . "`.", 404
		);
	}

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
	public function Terminate () {
		$this->dispatchState = 5;
		$this->application->Terminate();
	}
}
