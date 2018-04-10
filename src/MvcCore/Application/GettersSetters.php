<?php

/**
 * MvcCore
 *
 * This source file is subject to the BSD 3 License
 * For the full copyright and license information, please view
 * the LICENSE.md file that are distributed with this source code.
 *
 * @copyright	Copyright (c) 2016 Tom Flídr (https://github.com/mvccore/mvccore)
 * @license		https://mvccore.github.io/docs/mvccore/4.0.0/LICENCE.md
 */

namespace MvcCore\Application;

/**
 * `\MvcCore\Application` - Properties, Getters & Setters
 */
trait GettersSetters
{
	/***********************************************************************************
	 *                       `\MvcCore\Application` - Properties                       *
	 ***********************************************************************************/

	/**
	 * Application instance for current request. Singleton instance storrage.
	 * @var \MvcCore\Application
	 */
	protected static $instance;

	/**
	 * Describes if application is running as standard php project or as single file application.
	 * It shoud has values from:
	 * - `\MvcCore\Interfaces\IApplication::COMPILED_PHP`
	 * - `\MvcCore\Interfaces\IApplication::COMPILED_PHAR`
	 * - `\MvcCore\Interfaces\IApplication::COMPILED_SFU`
	 * - `\MvcCore\Interfaces\IApplication::NOT_COMPILED`
	 * Read more about every mode in interface: `\MvcCore\Interfaces\IApplication`.
	 * @var string
	 */
	protected $compiled = null;

	/**
	 * Microtime, when `\MvcCore`Application` class has been declarated.
	 * @var int
	 */
	protected $microtime = 0;

	/**
	 * Top most parent controller instance currently dispatched by application.
	 * @var \MvcCore\Controller
	 */
	protected $controller = NULL;

	/**
	 * Request object - parsed uri, query params, app paths...
	 * @var \MvcCore\Request
	 */
	protected $request;

	/**
	 * Response object - storrage for response headers and rendered body.
	 * @var \MvcCore\Response
	 */
	protected $response;

	/**
	 * Application http router to route request and build url addresses.
	 * @var \MvcCore\Router
	 */
	protected $router = null;


	/**
	 * Pre route custom closure calls storrage.
	 * Every item in this array has to be `callable`.
	 * Params in callable should be two with following types:
	 *	- `\MvcCore\Request`
	 *	- `\MvcCore\Response`
	 * Example:
	 * `\MvcCore\Application::GetInstance()->AddPreRouteHandler(function(
	 *		\MvcCore\Request & $request,
	 *		\MvcCore\Response & $response
	 * ) {
	 *		$request->customVar = 'custom_value';
	 * });`
	 * @var callable[]
	 */
	protected $preRouteHandlers = array();

	/**
	 * Pre dispatch custom calls storrage.
	 * Every item in this array has to be `callable`.
	 * Params in `callable` should be two with following types:
	 *	- `\MvcCore\Request`
	 *	- `\MvcCore\Response`
	 * Example:
	 * `\MvcCore\Application::GetInstance()->AddPreDispatchHandler(function(
	 *		\MvcCore\Request & $request,
	 *		\MvcCore\Response & $response
	 * ) {
	 *		$request->customVar = 'custom_value';
	 * });`
	 * @var callable[]
	 */
	protected $preDispatchHandlers = array();

	/**
	 * Post dispatch custom calls storrage.
	 * Every item in this array has to be `callable`.
	 * Params in `callable` should be two with following types:
	 *	- `\MvcCore\Request`
	 *	- `\MvcCore\Response`
	 * Example:
	 * `\MvcCore\Application::GetInstance()->AddPostDispatchHandler(function(
	 *		\MvcCore\Request & $request,
	 *		\MvcCore\Response & $response
	 * ) {
	 *		$request->customVar = 'custom_value';
	 * });`
	 * @var callable[]
	 */
	protected $postDispatchHandlers = array();


	/**
	 * Class to load and parse (system) config(s) and manage environment string.
	 * @var string
	 */
	protected $configClass = '\MvcCore\Config';

	/**
	 * Class to create default controller for request targeting views only
	 * and to handle small assets inside packed application.
	 * @var string
	 */
	protected $controllerClass = '\MvcCore\Controller';

	/**
	 * Class to handle any application error to render the error in browser or log in HDD.
	 * @var string
	 */
	protected $debugClass = '\MvcCore\Debug';

	/**
	 * Class to create describing HTTP request object.
	 * @var string
	 */
	protected $requestClass = '\MvcCore\Request';

	/**
	 * Class to create HTTP response object to store response headers and response content.
	 * @var string
	 */
	protected $responseClass = '\MvcCore\Response';

	/**
	 * Class to describe single route with match and replace pattern,
	 * controller, action, params default values and params constraints.
	 * @var string
	 */
	protected $routeClass = '\MvcCore\Route';

	/**
	 * Class to store all routes, dispatch request by routes and generate url addresses by routes.
	 * @var string
	 */
	protected $routerClass = '\MvcCore\Router';

	/**
	 * Class to configure session namespaces, session opening, writing and expirations.
	 * @var string
	 */
	protected $sessionClass = '\MvcCore\Session';

	/**
	 * Class to handle helper calls from MvcCore core modules.
	 * @var string
	 */
	protected $toolClass = '\MvcCore\Tool';

	/**
	 * Class to prepare and render controller view, subviews and wrapper layout.
	 * @var string
	 */
	protected $viewClass = '\MvcCore\View';


	/**
	 * Application scripts and views directory name as `"App"` by default,
	 * where are following subdirectories by default:
	 * - `/App/Controllers`
	 * - `/App/Models`
	 * - `/App/Views`
	 * It should by refonfigured to custom value in the very application beginning.
	 * @var string
	 */
	protected $appDir = 'App';

	/**
	 * Controllers directory name as `"Controllers"` by default, for all controller classes,
	 * it's placed directly in application directory by default.
	 * It should by reconfigured to custom value in the very application beginning.
	 * @var string
	 */
	protected $controllersDir = 'Controllers';

	/**
	 * Views directory name as `"views"` by default, for all view elements,
	 * it's placed directly in application directory above by default.
	 * It should by reconfigured to custom value in the very application beginning.
	 * @var string
	 */
	protected $viewsDir = 'Views';


	/**
	 * Default controller name, `"Index"` by default.
	 * @var string
	 */
	protected $defaultControllerName = 'Index';

	/**
	 * Default controller default action name, `"Index"` by default.
	 * @var string
	 */
	protected $defaultControllerDefaultActionName = 'Index';

	/**
	 * Default controller error action name, `"Error"` by default.
	 * @var string
	 */
	protected $defaultControllerErrorActionName = 'Error';

	/**
	 * Default controller not found error action name, `"NotFound"` by default.
	 * @var string
	 */
	protected $defaultControllerNotFoundActionName = 'NotFound';


	/***********************************************************************************
	 *                        `\MvcCore\Application` - Getters                         *
	 ***********************************************************************************/

	/**
	 * Get if application is running as standard php project or as single file application.
	 * It shoud has values from:
	 * - `\MvcCore\Interfaces\IApplication::COMPILED_PHP`
	 * - `\MvcCore\Interfaces\IApplication::COMPILED_PHAR`
	 * - `\MvcCore\Interfaces\IApplication::COMPILED_SFU`
	 * - `\MvcCore\Interfaces\IApplication::NOT_COMPILED`
	 * Read more about every mode in interface: `\MvcCore\Interfaces\IApplication`.
	 * @return string
	 */
	public function GetCompiled () {
		return $this->compiled;
	}


	/**
	 * Get application config class implementing `\MvcCore\Interfaces\IConfig`.
	 * Class to load and parse (system) config(s) and manage environment string.
	 * @return string
	 */
	public function GetConfigClass () {
		return $this->configClass;
	}

	/**
	 * Get application controller class implementing `\MvcCore\Interfaces\IController`.
	 * Class to create default controller for request targeting views only
	 * and to handle small assets inside packed application.
	 * @return string
	 */
	public function GetControllerClass () {
		return $this->controllerClass;
	}

	/**
	 * Get application debug class implementing `\MvcCore\Interfaces\IDebug`.
	 * Class to handle any application error to render the error in browser or log in HDD.
	 * @return string
	 */
	public function GetDebugClass () {
		return $this->debugClass;
	}

	/**
	 * Get application request class implementing `\MvcCore\Interfaces\IRequest`.
	 * Class to create describing HTTP request object.
	 * @return string
	 */
	public function GetRequestClass () {
		return $this->requestClass;
	}

	/**
	 * Get application response class implementing `\MvcCore\Interfaces\IResponse`.
	 * Class to create HTTP response object to store response headers and response content.
	 * @return string
	 */
	public function GetResponseClass () {
		return $this->responseClass;
	}

	/**
	 * Get application route class implementing `\MvcCore\Interfaces\IRoute`.
	 * Class to describe single route with match and replace pattern,
	 * controller, action, params default values and params constraints.
	 * @return string
	 */
	public function GetRouteClass () {
		return $this->routerClass;
	}

	/**
	 * Get application router class implementing `\MvcCore\Interfaces\IRouter`.
	 * Class to store all routes, dispatch request by routes and generate url addresses by routes.
	 * @return string
	 */
	public function GetRouterClass () {
		return $this->routerClass;
	}

	/**
	 * Get application session class implementing `\MvcCore\Interfaces\ISession`.
	 * Class to configure session namespaces, session opening, writing and expirations.
	 * @return string
	 */
	public function GetSessionClass () {
		return $this->sessionClass;
	}

	/**
	 * Get application tool class implementing `\MvcCore\Interfaces\ITool`.
	 * Class to handle helper calls from MvcCore core modules.
	 * @return string
	 */
	public function GetToolClass () {
		return $this->toolClass;
	}

	/**
	 * Get application view class implementing `\MvcCore\Interfaces\IView`.
	 * Class to prepare and render controller view, subviews and wrapper layout.
	 * @return string
	 */
	public function GetViewClass () {
		return $this->viewClass;
	}

	/**
	 * Get microtime, when `\MvcCore\Application` class has been declarated.
	 * @return string
	 */
	public function GetMicrotime () {
		return $this->microtime;
	}

	/**
	 * Returns currently used instance of protected `\MvcCore\Application::$router;`.
	 * @return \MvcCore\Router
	 */
	public function & GetRouter () {
		return $this->router;
	}

	/**
	 * Returns currently dispatched instance of protected `\MvcCore\Application::$controller;`.
	 * @return \MvcCore\Controller
	 */
	public function & GetController () {
		return $this->controller;
	}

	/**
	 * Returns currently used instance of protected `\MvcCore\Application::$request;`.
	 * @return \MvcCore\Request
	 */
	public function & GetRequest () {
		return $this->request;
	}

	/**
	 * Returns currently used instance of protected `\MvcCore\Application::response;`.
	 * @return \MvcCore\Response
	 */
	public function & GetResponse () {
		return $this->response;
	}

	/**
	 * Get application scripts and views directory name as `"App"` by default,
	 * where are following subdirectories by default:
	 * - `/App/Controllers`
	 * - `/App/Models`
	 * - `/App/Views`
	 * It should by refonfigured to custom value in the very application beginning.
	 * @return string
	 */
	public function GetAppDir () {
		return $this->appDir;
	}

	/**
	 * Get controllers directory name as `"Controllers"` by default, for all controller classes,
	 * it's placed directly in application directory by default.
	 * It should by reconfigured to custom value in the very application beginning.
	 * @return string
	 */
	public function GetControllersDir () {
		return $this->controllersDir;
	}

	/**
	 * Get views directory name as `"views"` by default, for all view elements,
	 * it's placed directly in application directory above by default.
	 * It should by reconfigured to custom value in the very application beginning.
	 * @return string
	 */
	public function GetViewsDir () {
		return $this->viewsDir;
	}

	/**
	 * Returns array with:
	 * - `0 => "index"` - Default controller name, from protected `\MvcCore\Application::$defaultControllerName`.
	 * - `1 => "index"` - Default action name, from protected `\MvcCore\Application::$defaultControllerDefaultActionName`.
	 * @return string[]
	 */
	public function GetDefaultControllerAndActionNames () {
		return array($this->defaultControllerName, $this->defaultControllerDefaultActionName);
	}


	/***********************************************************************************
	 *                        `\MvcCore\Application` - Setters                         *
	 ***********************************************************************************/

	/**
	 * Set if application is running as standard php project or as single file application.
	 * First param `$compiled` shoud has values from:
	 * - `\MvcCore\Interfaces\IApplication::COMPILED_PHP`
	 * - `\MvcCore\Interfaces\IApplication::COMPILED_PHAR`
	 * - `\MvcCore\Interfaces\IApplication::COMPILED_SFU`
	 * - `\MvcCore\Interfaces\IApplication::NOT_COMPILED`
	 * Read more about every mode in interface: `\MvcCore\Interfaces\IApplication`.
	 * Core configuration method.
	 * @param string $compiled
	 * @return \MvcCore\Application
	 */
	public function & SetCompiled ($compiled = '') {
		$this->compiled = $compiled;
		return $this;
	}


	/**
	 * Set application config class implementing `\MvcCore\Interfaces\IConfig`.
	 * Class to create default controller for request targeting views only
	 * and to handle small assets inside packed application.
	 * Core configuration method.
	 * @param string $configClass
	 * @return \MvcCore\Application
	 */
	public function & SetConfigClass ($configClass) {
		return $this->setCoreClass($configClass, 'configClass', '\MvcCore\Interfaces\IConfig');
	}

	/**
	 * Set application controller class implementing `\MvcCore\Interfaces\IController`.
	 * Class to create default controller for request targeting views only
	 * and to handle small assets inside packed application.
	 * Core configuration method.
	 * @param string $controllerClass
	 * @return \MvcCore\Application
	 */
	public function & SetControllerClass ($controllerClass) {
		return $this->setCoreClass($controllerClass, 'configClass', '\MvcCore\Interfaces\IController');
	}

	/**
	 * Set application debug class implementing `\MvcCore\Interfaces\IDebug`.
	 * Class to handle any application error to render the error in browser or log in HDD.
	 * Core configuration method.
	 * @param string $debugClass
	 * @return \MvcCore\Application
	 */
	public function & SetDebugClass ($debugClass) {
		return $this->setCoreClass($debugClass, 'debugClass', '\MvcCore\Interfaces\IDebug');
	}

	/**
	 * Set application request class implementing `\MvcCore\Interfaces\IRequest`.
	 * Class to create describing HTTP request object.
	 * Core configuration method.
	 * @param string $requestClass
	 * @return \MvcCore\Application
	 */
	public function & SetRequestClass ($requestClass) {
		return $this->setCoreClass($requestClass, 'requestClass', '\MvcCore\Interfaces\IRequest');
	}

	/**
	 * Set application response class implementing `\MvcCore\Interfaces\IResponse`.
	 * Class to create HTTP response object to store response headers and response content.
	 * Core configuration method.
	 * @param string $responseClass
	 * @return \MvcCore\Application
	 */
	public function & SetResponseClass ($responseClass) {
		return $this->setCoreClass($responseClass, 'responseClass', '\MvcCore\Interfaces\IResponse');
	}

	/**
	 * Set application route class implementing `\MvcCore\Interfaces\IRoute`.
	 * Class to describe single route with match and replace pattern,
	 * controller, action, params default values and params constraints.
	 * Core configuration method.
	 * @param string $routeClass
	 * @return \MvcCore\Application
	 */
	public function & SetRouteClass ($routeClass) {
		return $this->setCoreClass($routeClass, 'routerClass', '\MvcCore\Interfaces\IRoute');
	}

	/**
	 * Set application router class implementing `\MvcCore\Interfaces\IRouter`.
	 * Class to store all routes, dispatch request by routes and generate url addresses by routes.
	 * Core configuration method.
	 * @param string $routerClass
	 * @return \MvcCore\Application
	 */
	public function & SetRouterClass ($routerClass) {
		return $this->setCoreClass($routerClass, 'routerClass', '\MvcCore\Interfaces\IRouter');
	}

	/**
	 * Set application session class implementing `\MvcCore\Interfaces\ISession`.
	 * Class to configure session namespaces, session opening, writing and expirations.
	 * Core configuration method.
	 * @param string $sessionClass
	 * @return \MvcCore\Application
	 */
	public function & SetSessionClass ($sessionClass) {
		return $this->setCoreClass($sessionClass, 'sessionClass', '\MvcCore\Interfaces\ISession');
	}

	/**
	 * Set application tool class implementing `\MvcCore\Interfaces\ITool`.
	 * Class to handle helper calls from MvcCore core modules.
	 * Core configuration method.
	 * @param string $toolClass
	 * @return \MvcCore\Application
	 */
	public function & SetToolClass ($toolClass) {
		return $this->setCoreClass($toolClass, 'toolClass', '\MvcCore\ITool');
	}

	/**
	 * Set application view class implementing `\MvcCore\Interfaces\IView`.
	 * Class to prepare and render controller view, subviews and wrapper layout.
	 * Core configuration method.
	 * @param string $viewClass
	 * @return \MvcCore\Application
	 */
	public function & SetViewClass ($viewClass) {
		return $this->setCoreClass($viewClass, 'viewClass', '\MvcCore\IView');
	}


	/**
	 * Set application scripts and views directory name (`"App"` by default),
	 * where are following subdirectories by default:
	 * - `/App/Controllers`
	 * - `/App/Models`
	 * - `/App/Views`
	 * It should by refonfigured to custom value in the very application beginning.
	 * Core configuration method.
	 * @param string $appDir
	 * @return \MvcCore\Application
	 */
	public function & SetAppDir ($appDir) {
		$this->appDir = $appDir;
		return $this;
	}

	/**
	 * Set controllers directory name (`"Controllers"` by default), for all controller classes,
	 * it's placed directly in application directory by default.
	 * It should by reconfigured to custom value in the very application beginning.
	 * Core configuration method.
	 * @param string $controllersDir
	 * @return \MvcCore\Application
	 */
	public function & SetControllersDir ($controllersDir) {
		$this->controllersDir = $controllersDir;
		return $this;
	}

	/**
	 * Set views directory name (`"views"` by default), for all view elements,
	 * it's placed directly in application directory above by default.
	 * It should by reconfigured to custom value in the very application beginning.
	 * Core configuration method.
	 * @param string $viewsDir
	 * @return \MvcCore\Application
	 */
	public function & SetViewsDir ($viewsDir) {
		$this->viewsDir = $viewsDir;
		return $this;
	}

	/**
	 * Set default controller name. `"Index"` value by default.
	 * Core configuration method.
	 * @param string $defaultControllerName
	 * @return \MvcCore\Application
	 */
	public function & SetDefaultControllerName ($defaultControllerName) {
		$this->defaultControllerName = $defaultControllerName;
		return $this;
	}

	/**
	 * Set default controller default action name. `"Index"` value by default.
	 * Core configuration method.
	 * @param string $defaultActionName
	 * @return \MvcCore\Application
	 */
	public function & SetDefaultControllerDefaultActionName ($defaultActionName) {
		$this->defaultControllerDefaultActionName = $defaultActionName;
		return $this;
	}

	/**
	 * Set default controller common error action name. `"Error"` value by default.
	 * Core configuration method.
	 * @param string $defaultControllerErrorActionName
	 * @return \MvcCore\Application
	 */
	public function & SetDefaultControllerErrorActionName ($defaultControllerErrorActionName) {
		$this->defaultControllerErrorActionName = $defaultControllerErrorActionName;
		return $this;
	}

	/**
	 * Set default controller not found error action name. `"Not Found"` value by default.
	 * Core configuration method.
	 * @param string $defaultControllerNotFoundActionName
	 * @return \MvcCore\Application
	 */
	public function & SetDefaultControllerNotFoundActionName ($defaultControllerNotFoundActionName) {
		$this->defaultControllerNotFoundActionName = $defaultControllerNotFoundActionName;
		return $this;
	}

	/**
	 * Add pre route handler into pre route handlers queue to process them after
	 * every request has been completed into `\MvcCore\Request` describing object and before
	 * every request will be routed by `\MvcCore\Router::Route();` call.
	 * Callable should be void and it's params should be two with following types:
	 *	- `\MvcCore\Request`
	 *	- `\MvcCore\Response`
	 * Example:
	 * `\MvcCore\Application::GetInstance()->AddPreRouteHandler(function(
	 *		\MvcCore\Request & $request,
	 *		\MvcCore\Response & $response
	 * ) {
	 *		$request->customVar = 'custom_value';
	 * });`
	 * @param callable $handler
	 * @param int $priorityIndex
	 * @return \MvcCore\Application
	 */
	public function & AddPreRouteHandler (callable $handler, $priorityIndex = NULL) {
		if (!is_callable($handler)) throw new \InvalidArgumentException(
			"[".__CLASS__."] Pre route handler is not callable (handler: $handler, priorityIndex: $priorityIndex)."
		);
		if (is_null($priorityIndex)) {
			$this->preRouteHandlers[] = $handler;
		} else {
			$this->preRouteHandlers[$priorityIndex] = $handler;
		}
		return $this;
	}

	/**
	 * Add pre dispatch handler into pre dispatch handlers queue to process them after
	 * every request has been routed by `\MvcCore\Router::Route();` call and before
	 * every request will be dispatched by `\MvcCore\Controller::Dispatch();`, which triggers
	 * methods `\MvcCore\Controller::Init();`, `\MvcCore\Controller::PreDispatch();` etc.
	 * Callable should be void and it's params should be two with following types:
	 *	- `\MvcCore\Request`
	 *	- `\MvcCore\Response`
	 * Example:
	 * `\MvcCore\Application::GetInstance()->AddPreDispatchHandler(function(
	 *		\MvcCore\Request & $request,
	 *		\MvcCore\Response & $response
	 * ) {
	 *		$request->customVar = 'custom_value';
	 * });`
	 * @param callable $handler
	 * @param int $priorityIndex
	 * @return \MvcCore\Application
	 */
	public function & AddPreDispatchHandler (callable $handler, $priorityIndex = NULL) {
		if (!is_callable($handler)) throw new \InvalidArgumentException(
			"[".__CLASS__."] Pre dispatch handler is not callable (handler: $handler, priorityIndex: $priorityIndex)."
		);
		if (is_null($priorityIndex)) {
			$this->preDispatchHandlers[] = $handler;
		} else {
			$this->preDispatchHandlers[$priorityIndex] = $handler;
		}
		return $this;
	}

	/**
	 * Add post dispatch handler into post dispatch handlers queue to process them
	 * before every request is terminated by `\MvcCore\Application::Terminate();`.
	 * Every request terminated sooner has executed this post dispatch handlers queue.
	 * Callable should be void and it's params should be two with following types:
	 *	- `\MvcCore\Request`
	 *	- `\MvcCore\Response`
	 * Example:
	 * `\MvcCore\Application::GetInstance()->AddPostDispatchHandler(function(
	 *		\MvcCore\Request & $request,
	 *		\MvcCore\Response & $response
	 * ) {
	 *		$request->customVar = 'custom_value';
	 * });`
	 * @param callable $handler
	 * @param int $priorityIndex
	 * @return \MvcCore\Application
	 */
	public function & AddPostDispatchHandler (callable $handler, $priorityIndex = NULL) {
		if (!is_callable($handler)) throw new \InvalidArgumentException(
			"[".__CLASS__."] Post dispatch handler is not callable (handler: $handler, priorityIndex: $priorityIndex)."
		);
		if (is_null($priorityIndex)) {
			$this->postDispatchHandlers[] = $handler;
		} else {
			$this->postDispatchHandlers[$priorityIndex] = $handler;
		}
		return $this;
	}
}