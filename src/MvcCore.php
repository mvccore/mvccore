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

require_once('MvcCore/Request.php');
require_once('MvcCore/Response.php');
require_once('MvcCore/Router.php');
require_once('MvcCore/Route.php');
require_once('MvcCore/Config.php');
require_once('MvcCore/Debug.php');

/**
 * Application MVC core:
 * - main application objects container
 * - MvcCore compile mode managing
 * - store for main core class names to use them later
 *   for run, creating instances or static usage
 * - application run processing:
 *   - completing request and response
 *   - calling pre/post handlers
 *   - controller/action dispatching
 *   - error handling and error responses
 */
class MvcCore
{
	/**
	 * MvcCore - version:
	 * Comparation by PHP function version_compare();
	 * @see http://php.net/manual/en/function.version-compare.php
	 */
	const VERSION = '4.3.1';
	/**
	 * MvcCore application mode describing that the application is compiled in one big php file.
	 * In PHP app mode should be packed php files or any asset files - phtml templates, ini files
	 * or any static files. Unknown asset files or binary files are included as binary or base64 string.
	 * This mode has always best speed, because it shoud not work with hard drive if you dont want.
	 * Only with many or with large asset files, there may be greater demands on memory and processor,
	 * which shouldn't be good for your application. Be aware to do that.
	 * Result application packed in PHP mode has special '\Packager_Php_Wrapper' class included
	 * before any application content. This special class handles allowed file operations and assets
	 * as binary or base64 encoded. Everything shoud be configured before PHP packing.
	 * This mode has always four submodes started with PHP substring. All PHP package modes are:
	 * 'PHP_PRESERVE_HDD', 'PHP_PRESERVE_PACKAGE', 'PHP_STRICT_HDD' and 'PHP_STRICT_PACKAGE'.
	 * So to check if app is in PHP package mode - check it by substr();
	 * @var string
	 */
	const COMPILED_PHP = 'PHP';

	/**
	 * MvcCore application mode describing that the application is compiled in one big phar file.
	 * There could be any content included but there is no speed advantages, but it is
	 * still good way to pack your app into single file tool for any web-hosting needs:-)
	 * This mode has always lower speed then PHP mode, because it fully emulates hard drive
	 * for content of this file and it costs a time. But it has lower memory usage then PHP mode.
	 * @see http://php.net/manual/en/phar.creating.php
	 * @var string
	 */
	const COMPILED_PHAR = 'PHAR';

	/**
	 * MvcCore application mode describing that the application is in the state before
	 * their own compilation into PHP or PHAR archive. This mode is always used to generate final
	 * javascript and css files into teporary directory to pack them into result php/phar file.
	 * Shortcut SFU means "Single File Url". Application running in this mode has to generate
	 * single file urls in form: "index.php?..." and everithing has to work properly before
	 * application will be compiled into PHP/PHAR package. Use this mode in index.php before
	 * application compilation to generate and test everything necessary before app compilation by:
	 * MvcCore::GetInstance()->Run(TRUE); --> true means switch temporary into SFU mode.
	 * @var string
	 */
	const COMPILED_SFU = 'SFU';

	/**
	 * MvcCore application mode describing that the application is running as standard php project
	 * in many files using autoloading or anything else.
	 * @var string
	 */
	const NOT_COMPILED = '';

	/**
	 * Application instance for current request.
	 * @var MvcCore
	 */
	protected static $instance;

	/**
	 * Describes application running as standard php project or as single file.
	 * It shoud has values 'PHP', 'PHAR', 'SFU' or ''.
	 * @var string
	 */
	protected $compiled = null;

	/**
	 * Time when \MvcCore::Run has been called.
	 * @var int
	 */
	protected $microtime = 0;

	/**
	 * Application currently dispatched controller instance
	 * @var \MvcCore\Controller
	 */
	protected $controller = NULL;

	/**
	 * Request object - parsed uri, query params, app paths...
	 * @var \MvcCore\Request
	 */
	protected $request;

	/**
	 * Response object - headers and rendered body
	 * @var \MvcCore\Response
	 */
	protected $response;

	/**
	 * Application http route.
	 * @var \MvcCore\Router
	 */
	protected $router = null;

	/**
	 * Pre route custom closure calls.
	 * Params in closure function has to be:
	 *	- reference for request
	 *	- reference for response
	 * @example
	 *	 \MvcCore::AddPreRouteHandler(function (\MvcCore\Request & $request, \MvcCore\Response & $response) {
	 *	 	$request->customVar = 'custom_value';
	 *	 });
	 * @var callable[]
	 */
	protected $preRouteHandlers = array();

	/**
	 * Pre dispatch custom closure calls.
	 * Params in closure function has to be:
	 *	- reference for request
	 *	- reference for response
	 * @example
	 *	 \MvcCore::AddPreDispatchHandler(function (\MvcCore\Request & $request, \MvcCore\Response & $response) {
	 *	 	$request->customVar = 'custom_value';
	 *	 });
	 * @var callable[]
	 */
	protected $preDispatchHandlers = array();

	/**
	 * Post dispatch custom closure calls.
	 * Params in closure function has to be:
	 *	- reference for request
	 *	- reference for response
	 * @example
	 *	 \MvcCore::AddPostDispatchHandler(function (\MvcCore\Request & $request, \MvcCore\Response & $response) {
	 *	 	$response->Output = 'custom_value';
	 *	 });
	 * @var callable[]
	 */
	protected $postDispatchHandlers = array();

	/**
	 * Class to load and parse system config.
	 * @var string
	 */
	protected $configClass = '\MvcCore\Config';

	/**
	 * Class to configure session proxy class.
	 * @var string
	 */
	protected $sessionClass = '\MvcCore\Session';

	/**
	 * Class to create http request object.
	 * @var string
	 */
	protected $requestClass = '\MvcCore\Request';

	/**
	 * Class to create http response object.
	 * @var string
	 */
	protected $responseClass = '\MvcCore\Response';

	/**
	 * Class to create and dispatch request by its routes.
	 * @var string
	 */
	protected $routerClass = '\MvcCore\Router';

	/**
	 * Class to create and render controller view.
	 * @var string
	 */
	protected $viewClass = '\MvcCore\View';

	/**
	 * Class to handle any application error to render or log.
	 * @var string
	 */
	protected $debugClass = '\MvcCore\Debug';

	/**
	 * Application directory with subdirectories by default:
	 * 'Controllers', 'Models' and 'Views'.
	 * @var string
	 */
	protected $appDir = 'App';

	/**
	 * Controllers directory name for all controller classes,
	 * it has to be placed directly in application directory.
	 * @var string
	 */
	protected $controllersDir = 'Controllers';

	/**
	 * Views directory name for all view elements, it has to be
	 * placed directly in application directory.
	 * @var string
	 */
	protected $viewsDir = 'Views';

	/**
	 * Default controller name.
	 * @var string
	 */
	protected $defaultControllerName = 'Index';

	/**
	 * Default controller action name.
	 * @var string
	 */
	protected $defaultControllerDefaultActionName = 'Index';

	/**
	 * Default controller error action name.
	 * @var string
	 */
	protected $defaultControllerErrorActionName = 'Error';

	/**
	 * Default controller error action name.
	 * @var string
	 */
	protected $defaultControllerNotFoundActionName = 'NotFound';


	/***********************************************************************************
	 *                                  static calls
	 ***********************************************************************************/

	/**
	 * Static constructor (called internaly - do not call this in application).
	 * It initializes application compilation mode before \MvcCore::GetInstance()->Run();
	 * @return void
	 */
	public static function StaticInit () {
		$instance = static::GetInstance();
		$instance->microtime = microtime(TRUE);
		if (is_null($instance->compiled)) {
			$compiled = static::NOT_COMPILED;
			if (strpos(__FILE__, 'phar://') === 0) {
				$compiled = static::COMPILED_PHAR;
			} else if (class_exists('\Packager_Php_Wrapper')) {
				$compiled = constant('\Packager_Php_Wrapper::FS_MODE');
			}
			$instance->compiled = $compiled;
		}
	}

	/**
	 * Returns singleton \MvcCore application instance.
	 * @return \MvcCore
	 */
	public static function & GetInstance () {
		if (!static::$instance) static::$instance = new static();
		return static::$instance;
	}

	/**
	 * Add pre route handler into queue.
	 * Closure functions has to be void.
	 * Params in closure function has to be:
	 *	- reference for request
	 *	- reference for response
	 * @param callable $handler
	 * @return void
	 */
	public static function AddPreRouteHandler (callable $handler) {
		static::GetInstance()->preRouteHandlers[] = $handler;
	}

	/**
	 * Add pre dispatch handler into queue.
	 * Closure functions has to be void.
	 * Params in closure function has to be:
	 *	- reference for request
	 *	- reference for response
	 * @param callable $handler
	 * @return void
	 */
	public static function AddPreDispatchHandler (callable $handler) {
		static::GetInstance()->preDispatchHandlers[] = $handler;
	}

	/**
	 * Add post dispatch handler into queue.
	 * Closure functions has to be void.
	 * Params in closure function has to be:
	 *	- reference for request
	 *	- reference for response
	 * @param callable $handler
	 * @return void
	 */
	public static function AddPostDispatchHandler (callable $handler) {
		static::GetInstance()->postDispatchHandlers[] = $handler;
	}

	/**
	 * Starts a session, standardly called in \MvcCore\Controller::Init();
	 * But is shoud be called anywhere before, for example in any prerequest handler
	 * to redesign request before MVC dispatching.
	 * @return void
	 */
	public static function SessionStart () {
		$sessionClass = \MvcCore::GetInstance()->sessionClass;
		$sessionClass::Start();
	}


	/***********************************************************************************
	 *                                getters and setters
	 ***********************************************************************************/

	/**
	 * Get application compilation state value.
	 * @return string
	 */
	public function GetCompiled () {
		return $this->compiled;
	}

	/**
	 * Get application request class, extended from \MvcCore\Request.
	 * @return string
	 */
	public function GetRequestClass () {
		return $this->requestClass;
	}

	/**
	 * Get application response class, extended from \MvcCore\Response.
	 * @return string
	 */
	public function GetResponseClass () {
		return $this->responseClass;
	}

	/**
	 * Get application router class, extended from \MvcCore\Router.
	 * @return string
	 */
	public function GetRouterClass () {
		return $this->routerClass;
	}

	/**
	 * Get application config class, extended from \MvcCore\Config.
	 * @return string
	 */
	public function GetConfigClass () {
		return $this->configClass;
	}

	/**
	 * Get application session class, extended from \MvcCore\Session.
	 * @return string
	 */
	public function GetSessionClass () {
		return $this->sessionClass;
	}

	/**
	 * Get application view class, extended from \MvcCore\View.
	 * @return string
	 */
	public function GetViewClass () {
		return $this->viewClass;
	}

	/**
	 * Get application debug class, extended from \MvcCore\Debug.
	 * @return string
	 */
	public function GetDebugClass () {
		return $this->debugClass;
	}

	/**
	 * Get microtime, when MvcCore.php has been declarated.
	 * @return string
	 */
	public function GetMicrotime () {
		return $this->microtime;
	}

	/**
	 * Returns currently used instance of \MvcCore\Router
	 * @return \MvcCore\Router
	 */
	public function GetRouter () {
		return $this->router;
	}

	/**
	 * Returns instance of \MvcCore\Controller, currently dispatched by request.
	 * @return \MvcCore\Controller
	 */
	public function & GetController () {
		return $this->controller;
	}

	/**
	 * Get application request instance, extended from \MvcCore\Request
	 * @return \MvcCore\Request
	 */
	public function & GetRequest () {
		return $this->request;
	}

	/**
	 * Get application response instance, extended from \MvcCore\Response
	 * @return \MvcCore\Response
	 */
	public function & GetResponse () {
		return $this->response;
	}

	/**
	 * Get application directory, where are 'Controllers',
	 * 'Models' and 'Views' directories located. 'App' value by default.
	 * @return string
	 */
	public function GetAppDir () {
		return $this->appDir;
	}

	/**
	 * Get controllers directory, where are controllers located.
	 * 'Controllers' directory shoud be located in Application directory.
	 * 'Controllers' value by default.
	 * @return string
	 */
	public function GetControllersDir () {
		return $this->controllersDir;
	}

	/**
	 * Get views directory, where are views located.
	 * 'Views' directory shoud be located in Application directory.
	 * 'Views' value by default.
	 * @return string
	 */
	public function GetViewsDir () {
		return $this->viewsDir;
	}

	/**
	 * Returns array with default controller name and default action nam.
	 * @return array
	 */
	public function GetDefaultControllerAndActionNames () {
		return array($this->defaultControllerName, $this->defaultControllerDefaultActionName);
	}

	/**
	 * Set application compilation state value.
	 * @param string $compiled
	 * @return \MvcCore
	 */
	public function SetCompiled ($compiled = '') {
		$this->compiled = $compiled;
		return $this;
	}

	/**
	 * Set config class, extended from \MvcCore\Config.
	 * Core configuration method.
	 * @param string $configClass
	 * @return \MvcCore
	 */
	public function SetConfigClass ($configClass) {
		@class_exists($configClass); // load the class
		$this->configClass = $configClass;
		return $this;
	}

	/**
	 * Set session class, extended from \MvcCore\Session.
	 * Core configuration method.
	 * @param string $sessionClass
	 * @return \MvcCore
	 */
	public function SetSessionClass ($sessionClass) {
		@class_exists($sessionClass); // load the class
		$this->sessionClass = $sessionClass;
		return $this;
	}

	/**
	 * Set request class, extended from \MvcCore\Request.
	 * Core configuration method.
	 * @param string $requestClass
	 * @return \MvcCore
	 */
	public function SetRequestClass ($requestClass) {
		@class_exists($requestClass); // load the class
		$this->requestClass = $requestClass;
		return $this;
	}

	/**
	 * Set request class, extended from \MvcCore\Response.
	 * Core configuration method.
	 * @param string $responseClass
	 * @return \MvcCore
	 */
	public function SetResponseClass ($responseClass) {
		@class_exists($responseClass); // load the class
		$this->responseClass = $responseClass;
		return $this;
	}

	/**
	 * Set router class, extended from \MvcCore\Router.
	 * Core configuration method.
	 * @param string $routerClass
	 * @return \MvcCore
	 */
	public function SetRouterClass ($routerClass) {
		@class_exists($routerClass); // load the class
		$this->routerClass = $routerClass;
		return $this;
	}

	/**
	 * Set view class, extended from \MvcCore\View.
	 * Core configuration method.
	 * @param string $viewClass
	 * @return \MvcCore
	 */
	public function SetViewClass ($viewClass) {
		@class_exists($viewClass); // load the class
		$this->viewClass = $viewClass;
		return $this;
	}

	/**
	 * Set debug class, extended from \MvcCore\Debug.
	 * Core configuration method.
	 * @param string $debugClass
	 * @return \MvcCore
	 */
	public function SetDebugClass ($debugClass) {
		@class_exists($debugClass); // load the class
		$this->debugClass = $debugClass;
		return $this;
	}

	/**
	 * Set application directory, where are 'Controllers',
	 * 'Models' and 'Views' directories located. 'App' value by default.
	 * Core configuration method.
	 * @param string $appDir
	 * @return \MvcCore
	 */
	public function SetAppDir ($appDir) {
		$this->appDir = $appDir;
		return $this;
	}

	/**
	 * Set controllers directory, where are controllers located.
	 * 'Controllers' directory shoud be located in Application directory.
	 * 'Controllers' value by default.
	 * Core configuration method.
	 * @param string $controllersDir
	 * @return \MvcCore
	 */
	public function SetControllersDir ($controllersDir) {
		$this->controllersDir = $controllersDir;
		return $this;
	}

	/**
	 * Set views directory, where are views located.
	 * 'Views' directory shoud be located in Application directory.
	 * 'Views' value by default.
	 * Core configuration method.
	 * @param string $viewsDir
	 * @return \MvcCore
	 */
	public function SetViewsDir ($viewsDir) {
		$this->viewsDir = $viewsDir;
		return $this;
	}

	/**
	 * Set default controller name.
	 * 'Default' value by default.
	 * Core configuration method.
	 * @param string $defaultControllerName
	 * @return \MvcCore
	 */
	public function SetDefaultControllerName ($defaultControllerName) {
		$this->defaultControllerName = $defaultControllerName;
		return $this;
	}

	/**
	 * Set default controller default action name.
	 * 'Default' value by default.
	 * Core configuration method.
	 * @param string $defaultActionName
	 * @return \MvcCore
	 */
	public function SetDefaultControllerDefaultActionName ($defaultActionName) {
		$this->defaultControllerDefaultActionName = $defaultActionName;
		return $this;
	}

	/**
	 * Set default controller common error action name.
	 * 'Error' value by default.
	 * Core configuration method.
	 * @param string $defaultControllerErrorActionName
	 * @return \MvcCore
	 */
	public function SetDefaultControllerErrorActionName ($defaultControllerErrorActionName) {
		$this->defaultControllerErrorActionName = $defaultControllerErrorActionName;
		return $this;
	}

	/**
	 * Set default controller not found error action name.
	 * 'NotFound' value by default.
	 * Core configuration method.
	 * @param string $defaultControllerNotFoundActionName
	 * @return \MvcCore
	 */
	public function SetDefaultControllerNotFoundActionName ($defaultControllerNotFoundActionName) {
		$this->defaultControllerNotFoundActionName = $defaultControllerNotFoundActionName;
		return $this;
	}


	/***********************************************************************************
	 *                                normal dispatching
	 ***********************************************************************************/

	/**
	 * Run application.
	 * @param bool $singleFileUrl Set 'Single File Url' mode to compile assets and test everything before compilation
	 * @return \MvcCore
	 */
	public function Run ($singleFileUrl = FALSE) {
		if ($singleFileUrl) $this->compiled = static::COMPILED_SFU;
		return $this->process();
	}

	/**
	 * Process request.
	 *	1. Init debuginf and loging handlers if necessary
	 *	2. Complete request.
	 *	3. Call pre route handlers queue.
	 *	4. Route request by configured router.
	 *	5. Call pre dispatch handlers queue.
	 *	6. Dispatch request
	 *		- process controller methods
	 *		- render and send view result
	 *	7. Call post dispatch handlers queue.
	 *	8. Write session and exit
	 * @return \MvcCore
	 */
	protected function process () {
		$this->request = \MvcCore\Request::GetInstance($_SERVER, $_GET, $_POST);
		$this->response = \MvcCore\Response::GetInstance();
		$debugClass = $this->debugClass;
		$debugClass::Init();
		if (!$this->processCustomHandlers($this->preRouteHandlers))			return $this->Terminate();
		if (!$this->routeRequest())											return $this->Terminate();
		if (!$this->processCustomHandlers($this->preDispatchHandlers))		return $this->Terminate();
		if (!$this->DispatchMvcRequest($this->router->GetCurrentRoute()))	return $this->Terminate();
		if (!$this->processCustomHandlers($this->postDispatchHandlers))		return $this->Terminate();
		return $this->Terminate();
	}

	/**
	 * Route request by router obtained by \MvcCore\Router::GetInstance();
	 * Store requested route inside router class to get it later by:
	 * MvcCore\Router::GetCurrentRoute();
	 * @return bool
	 */
	protected function routeRequest () {
		$routerClass = $this->routerClass;
		$this->router = $routerClass::GetInstance();
		try {
			$this->router->Route($this->request);
			return TRUE;
		} catch (\Exception $e) {
			return $this->DispatchException($e);
		}
	}

	/**
	 * Process preroute, prerequest and postdispatch handlers queue by queues index
	 * @param callable[] $handlers
	 * @return bool
	 */
	protected function processCustomHandlers (& $handlers = array()) {
		if (!$this->IsAppRequest()) return TRUE;
		$result = TRUE;
		foreach ($handlers as $handler) {
			if (is_callable($handler)) {
				try {
					$handler($this->request, $this->response);
				} catch (\Exception $e) {
					$this->DispatchException($e);
					$result = FALSE;
					break;
				}
			}
		}
		return $result;
	}

	/**
	 * If controller class exists - try to dispatch controller, if only view file exists - try to render this view file.
	 * @param \MvcCore\Request $request
	 * @param \MvcCore\Route $route
	 * @return bool
	 */
	public function DispatchMvcRequest (\MvcCore\Route & $route = NULL) {
		if (is_null($route)) return $this->DispatchException(new \Exception('No route for request', 404));
		list ($controllerNamePascalCase, $actionNamePascalCase) = array($route->Controller, $route->Action);
		$actionName = $actionNamePascalCase . 'Action';
		$coreControllerName = '\MvcCore\Controller';
		$requestParams = $this->request->Params;
		$viewScriptFullPath = \MvcCore\View::GetViewScriptFullPath(
			\MvcCore\View::$ScriptsDir,
			$requestParams['controller'] . '/' . $requestParams['action']
		);
		if ($controllerNamePascalCase == 'Controller') {
			$controllerName = $coreControllerName;
		} else {
			// App_Controllers_$controllerNamePascalCase
			$controllerName = $this->CompleteControllerName($controllerNamePascalCase);
			if (!class_exists($controllerName)) {
				// if controller doesn't exists - check if at least view exists
				if (file_exists($viewScriptFullPath)) {
					// if view exists - change controller name to core controller, if not let it go to exception
					$controllerName = $coreControllerName;
				}
			}
		}
		return $this->DispatchControllerAction($controllerName, $actionName, $viewScriptFullPath, function (\Exception & $e) {
			return $this->DispatchException($e);
		});
	}

	/**
	 * Dispatch controller by full class name and by action name.
	 * @param string $controllerClassFullName
	 * @param string $actionName
	 * @param callable $exceptionCallback
	 * @return bool
	 */
	public function DispatchControllerAction ($controllerClassFullName, $actionName, $viewScriptFullPath, callable $exceptionCallback) {
		$this->controller = NULL;
		try {
			$this->controller = new $controllerClassFullName($this->request, $this->response);
		} catch (\Exception $e) {
			return $this->DispatchException(new \ErrorException($e->getMessage(), 404));
		}
		if (!method_exists($this->controller, $actionName) && $controllerClassFullName !== '\MvcCore\Controller') {
			if (!file_exists($viewScriptFullPath)) {
				return $this->DispatchException(new \ErrorException(
					"Controller '$controllerClassFullName' has not method '$actionName' "
					."or view doesn't exists in path: '$viewScriptFullPath'.", 404
				));
			}
		}
		try {
			$this->controller->Run($actionName);
		} catch (\Exception $e) {
			return $exceptionCallback($e);
		}
		return TRUE;
	}

	/**
	 * Generates url by:
	 * - 'Controller:Action' name and params array
	 *   (for routes configuration when routes array has keys with 'Controller:Action' strings
	 *   and routes has not controller name and action name defined inside)
	 * - route name and params array
	 *	 (route name is key in routes configuration array, should be any string
	 *	 but routes must have information about controller name and action name inside)
	 * Result address should have two forms:
	 * - nice rewrited url by routes configuration
	 *   (for apps with .htaccess supporting url_rewrite and when first param is key in routes configuration array)
	 * - for all other cases is url form: index.php?controller=ctrlName&action=actionName
	 *	 (when first param is not founded in routes configuration array)
	 * @param string $controllerActionOrRouteName	Should be 'Controller:Action' combination or just any route name as custom specific string
	 * @param array  $params						optional
	 * @return string
	 */
	public function Url ($controllerActionOrRouteName = 'Index:Index', $params = array()) {
		return \MvcCore\Router::GetInstance()->Url($controllerActionOrRouteName, $params);
	}

	/**
	 * Terminates any request, writes session and exits.
	 * The only place in application where is called 'echo ....';
	 * @return void
	 */
	public function Terminate () {
		$sessionClass = $this->sessionClass;
		$sessionClass::Close();
		$this->response->Send(); // headers (if necessary) and echo
		exit;
	}


	/***********************************************************************************
	 *                          request error dispatching
	 ***********************************************************************************/

	/**
	 * Process exception:
	 *	- if PHP package packing to determinate dependencies
	 *		- do not log or render nothing
	 *	- if production mode
	 *		- log error and try to render error page by App_Controller_Default::Error();
	 *	- if development
	 *		- print exception into browser
	 * @param \Exception $e
	 * @return bool
	 */
	public function DispatchException (\Exception $e) {
		if (class_exists('\Packager_Php')) return FALSE; // packing process
		if ($e->getCode() == 404) {
			\MvcCore\Debug::Log($e, \MvcCore\Debug::ERROR);
			return $this->RenderNotFound($e->getMessage());
		} else if (\MvcCore\Config::IsDevelopment()) {
			\MvcCore\Debug::Exception($e);
			return FALSE;
		} else {
			\MvcCore\Debug::Log($e, \MvcCore\Debug::EXCEPTION);
			return $this->RenderError($e);
		}
	}

	/**
	 * Render error by Default controller Error action,
	 * if there is no controller/action like that or any other exception happends,
	 * it is processed very simple response with 500 http code.
	 * @param \Exception $e
	 * @return bool
	 */
	public function RenderError (\Exception $e) {
		$defaultCtrlFullName = $this->GetDefaultControllerIfHasAction(
			$this->defaultControllerErrorActionName
		);
		$exceptionMessage = $e->getMessage();
		if ($defaultCtrlFullName) {
			$this->request->Params = array_merge($this->request->Params, array(
				'code'		=> 500,
				'message'	=> $exceptionMessage,
				'controller'=> \MvcCore\Tool::GetDashedFromPascalCase($this->defaultControllerName),
				'action'	=> \MvcCore\Tool::GetDashedFromPascalCase($this->defaultControllerErrorActionName),
			));
			return $this->DispatchControllerAction(
				$defaultCtrlFullName,
				$this->defaultControllerErrorActionName . "Action",
				'',
				function (\Exception & $e) use ($exceptionMessage) {
					\MvcCore\Debug::Log($e, \MvcCore\Debug::EXCEPTION);
					$this->RenderError500PlainText($exceptionMessage . PHP_EOL . PHP_EOL . $e->getMessage());
				}
			);
		} else {
			return $this->RenderError500PlainText($exceptionMessage);
		}
	}

	/**
	 * Render not found controller action or not found plain text response.
	 * @return bool
	 */
	public function RenderNotFound ($exceptionMessage = '') {
		if (!$exceptionMessage) $exceptionMessage = 'Page not found.';
		$defaultCtrlFullName = $this->GetDefaultControllerIfHasAction(
			$this->defaultControllerNotFoundActionName
		);
		if ($defaultCtrlFullName) {
			$this->request->Params = array_merge($this->request->Params, array(
				'code'		=> 404,
				'message'	=> $exceptionMessage,
				'controller'=> \MvcCore\Tool::GetDashedFromPascalCase($this->defaultControllerName),
				'action'	=> \MvcCore\Tool::GetDashedFromPascalCase($this->defaultControllerNotFoundActionName),
			));
			return $this->DispatchControllerAction(
				$defaultCtrlFullName,
				$this->defaultControllerNotFoundActionName . "Action",
				'',
				function (\Exception & $e) {
					\MvcCore\Debug::Log($e, \MvcCore\Debug::EXCEPTION);
					$this->RenderError404PlainText();
				}
			);
		} else {
			return $this->RenderError404PlainText();
		}
	}

	/**
	 * Process very simple internal server error (500) as plain text response.
	 * @param string $text
	 * @return bool
	 */
	public function RenderError500PlainText ($text = '') {
		if (!$text) $text = 'Internal Server Error.';
		$this->response = \MvcCore\Response::GetInstance(
			\MvcCore\Response::INTERNAL_SERVER_ERROR,
			array('Content-Type' => 'text/plain'),
			"Error 500:".PHP_EOL.PHP_EOL.$text
		);
		return TRUE;
	}

	/**
	 * Process very simple not found error (404) as plain text response.
	 * @return true
	 */
	public function RenderError404PlainText () {
		$this->response = \MvcCore\Response::GetInstance(
			\MvcCore\Response::NOT_FOUND,
			array('Content-Type' => 'text/plain'),
			'Error 404 – Page Not Found.'
		);
		return TRUE;
	}


	/***********************************************************************************
	 *                                  helper methods
	 ***********************************************************************************/

	/**
	 * Check if Default application controller ('App_Controllers_Default') has specific action.
	 * If default controller has specific action - return default controller full name, else empty string.
	 * @param string $actionName
	 * @return string
	 */
	public function GetDefaultControllerIfHasAction ($actionName) {
		$defaultControllerName = $this->CompleteControllerName($this->defaultControllerName);
		if (class_exists($defaultControllerName) && method_exists($defaultControllerName, $actionName.'Action')) {
			return $defaultControllerName;
		}
		return '';
	}

	/**
	 * Complete MvcCore application controller full name always in form:
	 * 'App_Controller_$controllerNamePascalCase'
	 * @param string $controllerNamePascalCase
	 * @return string
	 */
	public function CompleteControllerName ($controllerNamePascalCase) {
		$firstChar = substr($controllerNamePascalCase, 0, 1);
		if ($firstChar == '\\') return $controllerNamePascalCase;
		return implode('\\', array(
			$this->appDir,
			$this->controllersDir,
			$controllerNamePascalCase
		));
	}

	/**
	 * Return if this is default controller error action dispatching process
	 * @return bool
	 */
	public function IsErrorDispatched () {
		$defaultCtrlName = \MvcCore\Tool::GetDashedFromPascalCase($this->defaultControllerName);
		$errorActionName = \MvcCore\Tool::GetDashedFromPascalCase($this->defaultControllerErrorActionName);
		$params = $this->request->Params;
		return $params['controller'] == $defaultCtrlName && $params['action'] == $errorActionName;
	}

	/**
	 * Return if this is default controller not found action dispatching process
	 * @return bool
	 */
	public function IsNotFoundDispatched () {
		$defaultCtrlName = \MvcCore\Tool::GetDashedFromPascalCase($this->defaultControllerName);
		$errorActionName = \MvcCore\Tool::GetDashedFromPascalCase($this->defaultControllerNotFoundActionName);
		$params = $this->request->Params;
		return $params['controller'] == $defaultCtrlName && $params['action'] == $errorActionName;
	}

	/**
	 * Return true if request is on any application script,
	 * return false if request is on any asset
	 * @return bool
	 */
	public function IsAppRequest () {
		$params = $this->request->Params;
		$ctrlName = isset($params['controller']) ? $params['controller'] : '';
		if ($ctrlName != 'controller') return true;
		$actionName = isset($params['action']) ? $params['action'] : '';
		return $actionName != 'asset';
	}
}
\MvcCore::StaticInit();