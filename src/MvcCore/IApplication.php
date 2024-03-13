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
 * Responsibility - singleton, instancing all core classes and handling request.
 * - Global store and managing singleton application instance.
 * - Main application objects container (request, response, controller, etc.).
 * - MvcCore compile mode managing (single file mode, php, phar, or no package).
 * - Global store for all main core class names, to use them as modules,
 *   to be changed any time (request class, response class, debug class, etc.).
 * - Dispatching application http request/response (`\MvcCore\Application::Dispatch();`):
 *   - Completing request and response.
 *   - Calling pre/post handlers.
 *   - Controller/action dispatching.
 *   - Error handling and error responses.
 * @phpstan-type CustomHandlerCallable callable(\MvcCore\IRequest, \MvcCore\IResponse): (false|void)
 * @phpstan-type CustomHandlerRecord array{0: bool, 1: CustomHandlerCallable}
 */
interface IApplication extends \MvcCore\Application\IConstants {

	/***********************************************************************************
	 *                      `\MvcCore\Application` - Static Calls                      *
	 ***********************************************************************************/

	/**
	 * Returns singleton `\MvcCore\Application` instance as reference.
	 * @return \MvcCore\Application
	 */
	public static function GetInstance ();


	/***********************************************************************************
	 *                        `\MvcCore\Application` - Getters                         *
	 ***********************************************************************************/

	/**
	 * Return internal property in raw form.
	 * @param  string $propName 
	 * @return mixed
	 */
	public function __get ($propName);

	/**
	 * Get if application is running as standard php project or as single file application.
	 * It should has values from:
	 * - `\MvcCore\IApplication::COMPILED_PHP`
	 * - `\MvcCore\IApplication::COMPILED_PHAR`
	 * - `\MvcCore\IApplication::COMPILED_SFU`
	 * - `\MvcCore\IApplication::NOT_COMPILED`
	 * Read more about every mode in interface: `\MvcCore\IApplication`.
	 * @return string
	 */
	public function GetCompiled ();
	
	
	/**
	 * Get CSRF protection mode. Only one mode could be used.
	 * Default protection is by hidden form input for older browsers
	 * because of maximum compatibility.
	 * Modes:
	 * - `\MvcCore\IApplication::CSRF_DISABLED`              - both modes disabled.
	 * - `\MvcCore\IApplication::CSRF_PROTECTION_FORM_INPUT` - enabled mode for older 
	 *                                                         browser by form hidden input.
	 * - `\MvcCore\IApplication::CSRF_PROTECTION_COOKIE`     - enabled mode for newer
	 *                                                         browsers by http cookie.
	 * @return int
	 */
	public function GetCsrfProtection ();

	/**
	 * Get prefered PHP classes and properties anontation preference.
	 * PHP8+ attributes anotation is default. `FALSE` value means
	 * to prefer PhpDocs tags anotation instead.
	 * @return bool
	 */
	public function GetAttributesAnotations ();

	/**
	 * Get application environment class implementing `\MvcCore\IEnvironment`.
	 * Class to detect and manage environment name.
	 * @return \MvcCore\Environment|string
	 */
	public function GetEnvironmentClass ();

	/**
	 * Get application config class implementing `\MvcCore\IConfig`.
	 * Class to load and parse (system) config(s).
	 * @return \MvcCore\Config|string
	 */
	public function GetConfigClass ();

	/**
	 * Get application config class implementing `\MvcCore\IController`.
	 * Class to create default controller for request targeting views only
	 * and to handle small assets inside packed application.
	 * @return \MvcCore\Controller|string
	 */
	public function GetControllerClass ();

	/**
	 * Get application debug class implementing `\MvcCore\IDebug`.
	 * Class to handle any application error to render the error in browser or log in HDD.
	 * @return \MvcCore\Debug|string
	 */
	public function GetDebugClass ();

	/**
	 * Get application request class implementing `\MvcCore\IRequest`.
	 * Class to create describing HTTP request object.
	 * @return \MvcCore\Request|string
	 */
	public function GetRequestClass ();

	/**
	 * Get application response class implementing `\MvcCore\IResponse`.
	 * Class to create HTTP response object to store response headers and response content.
	 * @return \MvcCore\Response|string
	 */
	public function GetResponseClass ();

	/**
	 * Get application route class implementing `\MvcCore\IRoute`.
	 * Class to describe single route with match and replace pattern,
	 * controller, action, params default values and params constraints.
	 * @return \MvcCore\Route|string
	 */
	public function GetRouteClass ();

	/**
	 * Get application router class implementing `\MvcCore\IRouter`.
	 * Class to store all routes, dispatch request by routes and generate URL addresses by routes.
	 * @return \MvcCore\Router|string
	 */
	public function GetRouterClass ();

	/**
	 * Get application session class implementing `\MvcCore\ISession`.
	 * Class to configure session namespaces, session opening, writing and expirations.
	 * @return \MvcCore\Session|string
	 */
	public function GetSessionClass ();

	/**
	 * Get application tool class implementing `\MvcCore\ITool`.
	 * Class to handle helper calls from MvcCore core modules.
	 * @return \MvcCore\Tool|string
	 */
	public function GetToolClass ();

	/**
	 * Get application view class implementing `\MvcCore\IView`.
	 * Class to prepare and render controller view, sub-views and wrapper layout.
	 * @return \MvcCore\View|string
	 */
	public function GetViewClass ();

	/**
	 * Get (optionally cached) system config INI file as `stdClass` or `array`,
	 * placed by default in: `"/App/config.ini"`.
	 * @return \MvcCore\Config|NULL
	 */
	public function GetConfig ();

	/**
	 * Returns environment detection instance.
	 * @return \MvcCore\Environment
	 */
	public function GetEnvironment ();

	/**
	 * Returns currently dispatched controller instance.
	 * @return \MvcCore\Controller
	 */
	public function GetController ();

	/**
	 * Returns currently used request instance.
	 * @return \MvcCore\Request
	 */
	public function GetRequest ();

	/**
	 * Returns currently used response instance.
	 * @return \MvcCore\Response
	 */
	public function GetResponse ();

	/**
	 * Returns currently used router instance.
	 * @return \MvcCore\Router
	 */
	public function GetRouter ();

	/**
	 * Get application scripts and views directory name as `"App"` by default,
	 * where are following subdirectories by default:
	 * - `/App/Controllers`
	 * - `/App/Models`
	 * - `/App/Views`
	 * It should by reconfigured to custom value in the very application beginning.
	 * @return string
	 */
	public function GetAppDir ();

	/**
	 * Get CLI scripts directory name as `"Cli"` by default.
	 * It should by reconfigured to custom value in the very application beginning.
	 * @return string
	 */
	public function GetCliDir ();

	/**
	 * Get controllers directory name as `"Controllers"` by default, for all controller classes,
	 * it's placed directly in application directory by default.
	 * It should by reconfigured to custom value in the very application beginning.
	 * @return string
	 */
	public function GetControllersDir ();

	/**
	 * Get views directory name as `"views"` by default, for all view elements,
	 * it's placed directly in application directory above by default.
	 * It should by reconfigured to custom value in the very application beginning.
	 * @return string
	 */
	public function GetViewsDir ();

	/**
	 * Returns array with:
	 * - `0 => "index"` - Default controller name, from protected `\MvcCore\Application::$defaultControllerName`.
	 * - `1 => "index"` - Default action name, from protected `\MvcCore\Application::$defaultControllerDefaultActionName`.
	 * @return array<string>
	 */
	public function GetDefaultControllerAndActionNames ();

	/**
	 * Get default controller name, `"Index"` by default.
	 * @return string
	 */
	public function GetDefaultControllerName ();
	
	/**
	 * Get default controller error action name, `"Error"` by default.
	 * @return string
	 */
	public function GetDefaultControllerErrorActionName ();

	/**
	 * Get default controller not found error action name, `"NotFound"` by default.
	 * @return string
	 */
	public function GetDefaultControllerNotFoundActionName ();

	/**
	 * Return `TRUE` if application is already terminated, `FALSE` otherwise.
	 * @return bool
	 */
	public function GetTerminated ();


	/***********************************************************************************
	 *                        `\MvcCore\Application` - Setters                         *
	 ***********************************************************************************/

	/**
	 * Set if application is running as standard php project or as single file application.
	 * First param `$compiled` should has values from:
	 * - `\MvcCore\IApplication::COMPILED_PHP`
	 * - `\MvcCore\IApplication::COMPILED_PHAR`
	 * - `\MvcCore\IApplication::COMPILED_SFU`
	 * - `\MvcCore\IApplication::NOT_COMPILED`
	 * Read more about every mode in interface: `\MvcCore\IApplication`.
	 * Core configuration method.
	 * @param  string $compiled
	 * @return \MvcCore\Application
	 */
	public function SetCompiled ($compiled);

	
	/**
	 * Set CSRF protection mode. Only one mode could be used.
	 * Default protection is by hidden form input for older browsers
	 * because of maximum compatibility.
	 * Modes:
	 * - `\MvcCore\IApplication::CSRF_DISABLED`              - both modes disabled.
	 * - `\MvcCore\IApplication::CSRF_PROTECTION_FORM_INPUT` - enabled mode for older 
	 *                                                         browser by form hidden input.
	 * - `\MvcCore\IApplication::CSRF_PROTECTION_COOKIE`     - enabled mode for newer
	 *                                                         browsers by http cookie.
	 * @param  int $csrfProtection
	 * @return \MvcCore\Application
	 */
	public function SetCsrfProtection ($csrfProtection = \MvcCore\IApplication::CSRF_PROTECTION_COOKIE);

	/**
	 * Set prefered PHP classes and properties anontation preference.
	 * PHP8+ attributes anotation is default. Set value to `FALSE`
	 * to prefer PhpDocs tags anotation instead.
	 * @param  bool $attributesAnotations
	 * @return \MvcCore\Application
	 */
	public function SetAttributesAnotations ($attributesAnotations = TRUE);

	/**
	 * Set application environment class implementing `\MvcCore\IEnvironment`.
	 * Class to detect and manage environment name.
	 * Core configuration method.
	 * @param  string $environmentClass
	 * @return \MvcCore\Application
	 */
	public function SetEnvironmentClass ($environmentClass);

	/**
	 * Set application config class implementing `\MvcCore\IConfig`.
	 * Class to load and parse (system) config(s).
	 * Core configuration method.
	 * @param  string $configClass
	 * @return \MvcCore\Application
	 */
	public function SetConfigClass ($configClass);

	/**
	 * Set application controller class implementing `\MvcCore\IController`.
	 * Class to create default controller for request targeting views only
	 * and to handle small assets inside packed application.
	 * Core configuration method.
	 * @param  string $controllerClass
	 * @return \MvcCore\Application
	 */
	public function SetControllerClass ($controllerClass);

	/**
	 * Set application debug class implementing `\MvcCore\IDebug`.
	 * Class to handle any application error to render the error in browser or log in HDD.
	 * Core configuration method.
	 * @param  string $debugClass
	 * @return \MvcCore\Application
	 */
	public function SetDebugClass ($debugClass);

	/**
	 * Set application request class implementing `\MvcCore\IRequest`.
	 * Class to create describing HTTP request object.
	 * Core configuration method.
	 * @param  string $requestClass
	 * @return \MvcCore\Application
	 */
	public function SetRequestClass ($requestClass);

	/**
	 * Set application response class implementing `\MvcCore\IResponse`.
	 * Class to create HTTP response object to store response headers and response content.
	 * Core configuration method.
	 * @param  string $responseClass
	 * @return \MvcCore\Application
	 */
	public function SetResponseClass ($responseClass);

	/**
	 * Set application route class implementing `\MvcCore\IRoute`.
	 * Class to describe single route with match and replace pattern,
	 * controller, action, params default values and params constraints.
	 * Core configuration method.
	 * @param  string $routerClass
	 * @return \MvcCore\Application
	 */
	public function SetRouteClass ($routerClass);

	/**
	 * Set application router class implementing `\MvcCore\IRouter`.
	 * Class to store all routes, dispatch request by routes and generate URL addresses by routes.
	 * Core configuration method.
	 * @param  string $routerClass
	 * @return \MvcCore\Application
	 */
	public function SetRouterClass ($routerClass);

	/**
	 * Set application session class implementing `\MvcCore\ISession`.
	 * Class to configure session namespaces, session opening, writing and expirations.
	 * Core configuration method.
	 * @param  string $sessionClass
	 * @return \MvcCore\Application
	 */
	public function SetSessionClass ($sessionClass);

	/**
	 * Set application tool class implementing `\MvcCore\ITool`.
	 * Class to handle helper calls from MvcCore core modules.
	 * Core configuration method.
	 * @param  string $toolClass
	 * @return \MvcCore\Application
	 */
	public function SetToolClass ($toolClass);

	/**
	 * Set application view class implementing `\MvcCore\IView`.
	 * Class to prepare and render controller view, sub-views and wrapper layout.
	 * Core configuration method.
	 * @param  string $viewClass
	 * @return \MvcCore\Application
	 */
	public function SetViewClass ($viewClass);


	/**
	 * Set currently dispatched controller instance.
	 * @param  \MvcCore\Controller $controller
	 * @return \MvcCore\Application
	 */
	public function SetController (\MvcCore\IController $controller);


	/**
	 * Set application scripts and views directory name (`"App"` by default),
	 * where are following subdirectories by default:
	 * - `/App/Controllers`
	 * - `/App/Models`
	 * - `/App/Views`
	 * It should by reconfigured to custom value in the very application beginning.
	 * Core configuration method.
	 * @param  string $appDir
	 * @return \MvcCore\Application
	 */
	public function SetAppDir ($appDir);
	
	/**
	 * Set CLI scripts directory name as `"Cli"` by default.
	 * It should by reconfigured to custom value in the very application beginning.
	 * Core configuration method.
	 * @param  string $cliDir
	 * @return \MvcCore\Application
	 */
	public function SetCliDir ($cliDir);

	/**
	 * Set controllers directory name (`"Controllers"` by default), for all controller classes,
	 * it's placed directly in application directory by default.
	 * It should by reconfigured to custom value in the very application beginning.
	 * Core configuration method.
	 * @param  string $controllersDir
	 * @return \MvcCore\Application
	 */
	public function SetControllersDir ($controllersDir);

	/**
	 * Set views directory name (`"views"` by default), for all view elements,
	 * it's placed directly in application directory above by default.
	 * It should by reconfigured to custom value in the very application beginning.
	 * Core configuration method.
	 * @param string $viewsDir
	 * @return \MvcCore\Application
	 */
	public function SetViewsDir ($viewsDir);

	/**
	 * Set default controller name. `"Index"` value by default.
	 * Core configuration method.
	 * @param  string $defaultControllerName
	 * @return \MvcCore\Application
	 */
	public function SetDefaultControllerName ($defaultControllerName);

	/**
	 * Set default controller default action name. `"Index"` value by default.
	 * Core configuration method.
	 * @param  string $defaultActionName
	 * @return \MvcCore\Application
	 */
	public function SetDefaultControllerDefaultActionName ($defaultActionName);

	/**
	 * Set default controller common error action name. `"Error"` value by default.
	 * Core configuration method.
	 * @param  string $defaultControllerErrorActionName
	 * @return \MvcCore\Application
	 */
	public function SetDefaultControllerErrorActionName ($defaultControllerErrorActionName);

	/**
	 * Set default controller not found error action name. `"NotFound"` value by default.
	 * Core configuration method.
	 * @param  string $defaultControllerNotFoundActionName
	 * @return \MvcCore\Application
	 */
	public function SetDefaultControllerNotFoundActionName ($defaultControllerNotFoundActionName);

	/**
	 * Add pre route handler into pre route handlers queue to process them after
	 * every request has been completed into `\MvcCore\Request` describing object and before
	 * every request will be routed by `\MvcCore\Router::Route();` call.
	 * Callable should be void and it's params should be two with following types:
	 * - `\MvcCore\Request`
	 * - `\MvcCore\Response`
	 * Example:
	 * ````
	 *   \MvcCore\Application::GetInstance()->AddPreRouteHandler(function(
	 *       \MvcCore\Request $request,
	 *       \MvcCore\Response $response
	 *   ) {
	 *       $request->customVar = 'custom_value';
	 *   });
	 * ````
	 * @param  CustomHandlerCallable $handler
	 * @param  int|NULL              $priorityIndex
	 * @return \MvcCore\Application
	 */
	public function AddPreRouteHandler (callable $handler, $priorityIndex = NULL);

	/**
	 * Add post route handler into post route handlers queue to process them after
	 * every request has been completed into `\MvcCore\Request` describing object, after
	 * every request has been routed by `\MvcCore\Router::Route();` call and before
	 * every request has created target controller instance.
	 * Callable should be void and it's params should be two with following types:
	 * - `\MvcCore\Request`
	 * - `\MvcCore\Response`
	 * Example:
	 * ````
	 *   \MvcCore\Application::GetInstance()->AddPostRouteHandler(function(
	 *       \MvcCore\Request $request,
	 *       \MvcCore\Response $response
	 *   ) {
	 *       $request->customVar = 'custom_value';
	 *   });
	 * ````
	 * @param  CustomHandlerCallable $handler
	 * @param  int|NULL              $priorityIndex
	 * @return \MvcCore\Application
	 */
	public function AddPostRouteHandler (callable $handler, $priorityIndex = NULL);

	/**
	 * Add pre dispatch handler into pre dispatch handlers queue to process them after
	 * every request has been routed by `\MvcCore\Router::Route();` call, after
	 * every request has been dispatched by `\MvcCore\Controller::Dispatch();` and
	 * after every request has created and prepared target controller instance to dispatch.
	 * Callable should be void and it's params should be two with following types:
	 * - `\MvcCore\Request`
	 * - `\MvcCore\Response`
	 * Example:
	 * ````
	 * \MvcCore\Application::GetInstance()->AddPreDispatchHandler(function(
	 *       \MvcCore\Request $request,
	 *       \MvcCore\Response $response
	 *   ) {
	 *       $request->customVar = 'custom_value';
	 *   });
	 * ````
	 * @param  CustomHandlerCallable $handler
	 * @param  int|NULL              $priorityIndex
	 * @return \MvcCore\Application
	 */
	public function AddPreDispatchHandler (callable $handler, $priorityIndex = NULL);

	/**
	 * Pre sent headers custom calls storage.
	 * Handlers are not executed if request is via CLI.
	 * Every item in this array has to be `callable`.
	 * Params in `callable` should be two with following types:
	 * - `\MvcCore\Request`
	 * - `\MvcCore\Response`
	 * Example:
	 * ````
	 *   \MvcCore\Application::GetInstance()->AddPreSentHeadersHandler(function(
	 *       \MvcCore\Request $request,
	 *       \MvcCore\Response $response
	 *   ) {
	 *       $request->customVar = 'custom_value';
	 *   });
	 * ````
	 * @param  CustomHandlerCallable $handler
	 * @param  int|NULL              $priorityIndex
	 * @return \MvcCore\Application
	 */
	public function AddPreSentHeadersHandler (callable $handler, $priorityIndex = NULL);

	/**
	 * Add pre sent body custom calls storage.
	 * Every item in this array has to be `callable`.
	 * Params in `callable` should be two with following types:
	 * - `\MvcCore\Request`
	 * - `\MvcCore\Response`
	 * Example:
	 * ````
	 *   \MvcCore\Application::GetInstance()->AddPreSentBodyHandler(function(
	 *       \MvcCore\Request $request,
	 *       \MvcCore\Response $response
	 *   ) {
	 *       $request->customVar = 'custom_value';
	 *   });
	 * ````
	 * @param  CustomHandlerCallable $handler
	 * @param  int|NULL              $priorityIndex
	 * @return \MvcCore\Application
	 */
	public function AddPreSentBodyHandler (callable $handler, $priorityIndex = NULL);

	/**
	 * Add post dispatch handler into post dispatch handlers queue to process them
	 * before every request is terminated by `\MvcCore\Application::Terminate();`.
	 * Every request terminated sooner has executed this post dispatch handlers queue.
	 * Callable should be void and it's params should be two with following types:
	 * - `\MvcCore\Request`
	 * - `\MvcCore\Response`
	 * Example:
	 * ````
	 *   \MvcCore\Application::GetInstance()->AddPostDispatchHandler(function(
	 *       \MvcCore\Request $request,
	 *       \MvcCore\Response $response
	 *   ) {
	 *       $request->customVar = 'custom_value';
	 *   });
	 * ````
	 * @param  CustomHandlerCallable $handler
	 * @param  int|NULL              $priorityIndex
	 * @return \MvcCore\Application
	 */
	public function AddPostDispatchHandler (callable $handler, $priorityIndex = NULL);

	/**
	 * Add post terminate handler into post terminate handlers queue to process them
	 * after every request is terminated by `\MvcCore\Application::Terminate();`.
	 * Callable should be void and it's params should be two with following types:
	 * - `\MvcCore\Request`
	 * - `\MvcCore\Response`
	 * Example:
	 * ````
	 *   \MvcCore\Application::GetInstance()->AddPostTerminateHandler(function(
	 *       \MvcCore\Request $request,
	 *       \MvcCore\Response $response
	 *   ) {
	 *       // close connection by previously configured
	 *       // header: header('Connection: close');
	 *       // and run background process now:
	 *   });
	 * ````
	 * @param  CustomHandlerCallable $handler
	 * @param  int|NULL              $priorityIndex
	 * @return \MvcCore\Application
	 */
	public function AddPostTerminateHandler (callable $handler, $priorityIndex = NULL);

	/**
	 * Add CSRF protection error handler into handlers queue 
	 * to process them when any CSRF calidation error happends.
	 * Callable should be void and it's params should be two with following types:
	 * - `\MvcCore\Request`
	 * - `\MvcCore\Response`
	 * Example:
	 * ````
	 *   \MvcCore\Application::GetInstance()->AddCsrfErrorHandler(function(
	 *       \MvcCore\Request $request,
	 *       \MvcCore\Response $response
	 *   ) {
	 *       // redirect user to homepage or sign out authenticated user.
	 *   });
	 * ````
	 * @param  CustomHandlerCallable $handler
	 * @param  int|NULL              $priorityIndex
	 * @return \MvcCore\Application
	 */
	public function AddCsrfErrorHandler (callable $handler, $priorityIndex = NULL);


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
	 * @param  string $exceptionMessage
	 * @return bool
	 */
	public function RenderNotFound ($exceptionMessage = '');

	/**
	 * Prepare very simple response with internal server error (500)
	 * as plain text response into `\MvcCore\Application::$response`.
	 * @param  string $text
	 * @return bool
	 */
	public function RenderError500PlainText ($text = '');

	/**
	 * Prepare very simple response with not found error (404)
	 * as plain text response into `\MvcCore\Application::$response`.
	 * @param  string $text
	 * @return bool
	 */
	public function RenderError404PlainText ($text = '');


	/***********************************************************************************
	 *                     `\MvcCore\Application` - Helper Methods                     *
	 ***********************************************************************************/

	/**
	 * Check if default application controller (`\App\Controllers\Index` by default) has specific action.
	 * If default controller has specific action - return default controller full name, else empty string.
	 * @param  string $actionName
	 * @return string
	 */
	public function GetDefaultControllerIfHasAction ($actionName);

	/**
	 * Complete standard MvcCore application controller full name in form:
	 * `\App\Controllers\<$controllerNamePascalCase>`.
	 * @param  string $controllerNamePascalCase
	 * @return string
	 */
	public function CompleteControllerName ($controllerNamePascalCase);

	/**
	 * Return `TRUE` if current request is default controller error action dispatching process.
	 * @return bool
	 */
	public function IsErrorDispatched ();

	/**
	 * Return `TRUE` if current request is default controller not found error action dispatching process.
	 * @return bool
	 */
	public function IsNotFoundDispatched ();

	/**
	 * Get `TRUE` if main application controller
	 * is from any composer vendor project.
	 * Compilled applications doesn't support 
	 * dispatching in vendor directories.
	 * @throws \Exception
	 * @return bool
	 */
	public function GetVendorAppDispatch ();

	/**
	 * Get application root in composer vendor project, 
	 * determinated by dispatched main controller.
	 * Compilled applications doesn't support 
	 * dispatching in vendor directories.
	 * If main controller is not dispatched from
	 * composer vendor project, `NULL` is returned.
	 * @throws \Exception
	 * @return string|NULL
	 */
	public function GetVendorAppRoot ();

	/**
	 * Validate CSRF protection by http(s) cookie and session secret value.
	 * If CSRF protection is not enabled in cookie mode, return `NULL`.
	 * If protection validation is enabled and validated successfully,
	 * return `TRUE`, if validation fails, return `FALSE`.
	 * @inheritDoc
	 * @return bool|NULL
	 */
	public function ValidateCsrfProtection ();
}
