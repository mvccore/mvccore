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

namespace MvcCore\Application;

/**
 * Trait as partial class for `\MvcCore\Application`:
 * - Main application objects container (request, response, controller, etc.).
 * - MvcCore compile mode managing (single file mode, php, phar, or no package).
 * - Global store for all main core class names, to use them as modules,
 *   to be changed any time (request class, response class, debug class, etc.).
 */
trait Props {

	/***********************************************************************************
	 *					   `\MvcCore\Application` - Properties					   *
	 ***********************************************************************************/

	/**
	 * Application instance for current request. Singleton instance storage.
	 * @var \MvcCore\Application
	 */
	protected static $instance;

	/**
	 * Describes if application is running as standard php project or as single file application.
	 * It should has values from:
	 * - `\MvcCore\IApplication::COMPILED_PHP`
	 * - `\MvcCore\IApplication::COMPILED_PHAR`
	 * - `\MvcCore\IApplication::COMPILED_SFU`
	 * - `\MvcCore\IApplication::NOT_COMPILED`
	 * Read more about every mode in interface: `\MvcCore\IApplication`.
	 * @var string
	 */
	protected $compiled = NULL;

	/**
	 * Environment detection instance.
	 * @var \MvcCore\Environment
	 */
	protected $environment = NULL;

	/**
	 * Top most parent controller instance currently dispatched by application.
	 * @var \MvcCore\Controller
	 */
	protected $controller = NULL;

	/**
	 * Request object - parsed URI, query params, app paths...
	 * @var \MvcCore\Request
	 */
	protected $request = NULL;

	/**
	 * Response object - storage for response headers and rendered body.
	 * @var \MvcCore\Response
	 */
	protected $response = NULL;

	/**
	 * Application http router to route request and build URL addresses.
	 * @var \MvcCore\Router
	 */
	protected $router = NULL;


	/**
	 * Pre route custom closure calls storage.
	 * Every item in this array has to be `callable`.
	 * Params in callable should be two with following types:
	 *	- `\MvcCore\Request`
	 *	- `\MvcCore\Response`
	 * Example:
	 * `\MvcCore\Application::GetInstance()->AddPreRouteHandler(function(
	 *		\MvcCore\Request $request,
	 *		\MvcCore\Response $response
	 * ) {
	 *		$request->customVar = 'custom_value';
	 * });`
	 * @var \array[]
	 */
	protected $preRouteHandlers = [];

	/**
	 * Post route custom closure calls storage.
	 * Every item in this array has to be `callable`.
	 * Params in callable should be two with following types:
	 *	- `\MvcCore\Request`
	 *	- `\MvcCore\Response`
	 * Example:
	 * `\MvcCore\Application::GetInstance()->AddPostRouteHandler(function(
	 *		\MvcCore\Request $request,
	 *		\MvcCore\Response $response
	 * ) {
	 *		$request->customVar = 'custom_value';
	 * });`
	 * @var \array[]
	 */
	protected $postRouteHandlers = [];

	/**
	 * Pre dispatch custom calls storage.
	 * Every item in this array has to be `callable`.
	 * Params in `callable` should be two with following types:
	 *	- `\MvcCore\Request`
	 *	- `\MvcCore\Response`
	 * Example:
	 * `\MvcCore\Application::GetInstance()->AddPreDispatchHandler(function(
	 *		\MvcCore\Request $request,
	 *		\MvcCore\Response $response
	 * ) {
	 *		$request->customVar = 'custom_value';
	 * });`
	 * @var \array[]
	 */
	protected $preDispatchHandlers = [];

	/**
	 * Post dispatch custom calls storage.
	 * Every item in this array has to be `callable`.
	 * Params in `callable` should be two with following types:
	 *	- `\MvcCore\Request`
	 *	- `\MvcCore\Response`
	 * Example:
	 * `\MvcCore\Application::GetInstance()->AddPostDispatchHandler(function(
	 *		\MvcCore\Request $request,
	 *		\MvcCore\Response $response
	 * ) {
	 *		$request->customVar = 'custom_value';
	 * });`
	 * @var \array[]
	 */
	protected $postDispatchHandlers = [];

	/**
	 * Post terminate custom calls storage.
	 * Every item in this array has to be `callable`.
	 * Params in `callable` should be two with following types:
	 *	- `\MvcCore\Request`
	 *	- `\MvcCore\Response`
	 * Example:
	 * `\MvcCore\Application::GetInstance()->AddPostTerminateHandler(function(
	 *		\MvcCore\Request $request,
	 *		\MvcCore\Response $response
	 * ) {
	 *		// close connection by previously configured
	 *		// header: header('Connection: close');
	 *		// and run background process now:
	 * });`
	 * @var \array[]
	 */
	protected $postTerminateHandlers = [];


	/**
	 * Class to detect and manage environment name.
	 * @var string
	 */
	protected $environmentClass = '\MvcCore\Environment';

	/**
	 * Class to load and parse (system) config(s).
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
	 * Class to store all routes, dispatch request by routes and generate URL addresses by routes.
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
	 * Class to prepare and render controller view, sub-views and wrapper layout.
	 * @var string
	 */
	protected $viewClass = '\MvcCore\View';


	/**
	 * Application scripts and views directory name as `"App"` by default,
	 * where are following subdirectories by default:
	 * - `/App/Controllers`
	 * - `/App/Models`
	 * - `/App/Views`
	 * It should by reconfigured to custom value in the very application beginning.
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

	/**
	 * Boolean flag if request has been already terminated or not
	 * to process `\MvcCore\Application::Terminate();` only once.
	 * Default value is `FALSE`.
	 * @var bool
	 */
	protected $terminated = FALSE;
}
