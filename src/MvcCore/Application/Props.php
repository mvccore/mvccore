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

namespace MvcCore\Application;

/**
 * Trait as partial class for `\MvcCore\Application`:
 * - Main application objects container (request, response, controller, etc.).
 * - MvcCore compile mode managing (single file mode, php, phar, or no package).
 * - Global store for all main core class names, to use them as modules,
 *   to be changed any time (request class, response class, debug class, etc.).
 * @mixin \MvcCore\Application
 * @phpstan-type CustomHandlerCallable callable(\MvcCore\IRequest, \MvcCore\IResponse): (false|void)
 * @phpstan-type CustomHandlerRecord array{0: bool, 1: CustomHandlerCallable}
 */
trait Props {

	/***********************************************************************************
	 *                       `\MvcCore\Application` - Properties                       *
	 ***********************************************************************************/

	/**
	 * Application instance for current request. Singleton instance storage.
	 * @var \MvcCore\Application
	 */
	protected static $instance;

	/**
	 * Describes if application is running as standard 
	 * php project or as single file application.
	 * It should has values from:
	 * - `\MvcCore\IApplication::COMPILED_PHP`
	 * - `\MvcCore\IApplication::COMPILED_PHAR`
	 * - `\MvcCore\IApplication::COMPILED_SFU`
	 * - `\MvcCore\IApplication::NOT_COMPILED`
	 * Read more about every mode in interface: `\MvcCore\IApplication`.
	 * @var string|NULL
	 */
	protected $compiled = NULL;

	/**
	 * Describes if main application controller
	 * is from any composer vendor project.
	 * Compilled applications doesn't support 
	 * dispatching in vendor directories.
	 * @var bool|NULL
	 */
	protected $vendorAppDispatch = NULL;

	/**
	 * Application root in composer vendor project, 
	 * determinated by dispatched main controller.
	 * Compilled applications doesn't support 
	 * dispatching in vendor directories.
	 * @var string|NULL
	 */
	protected $vendorAppRoot = NULL;

	/**
	 * CSRF protection mode. Only one mode could be used.
	 * Default protection is by hidden form input for older browsers
	 * because of maximum compatibility.
	 * Modes:
	 * - `\MvcCore\IApplication::CSRF_DISABLED`              - both modes disabled.
	 * - `\MvcCore\IApplication::CSRF_PROTECTION_FORM_INPUT` - enabled mode for older 
	 *                                                         browser by form hidden input.
	 * - `\MvcCore\IApplication::CSRF_PROTECTION_COOKIE`     - enabled mode for newer
	 *                                                         browsers by http cookie.
	 * @var int
	 */
	protected $csrfProtection = \MvcCore\IApplication::CSRF_PROTECTION_FORM_INPUT;

	/**
	 * Prefered PHP classes and properties anontation.
	 * `FALSE` by default, older PHP Docs tags anotations are default
	 * because of maximum compatibility.
	 * @var bool
	 */
	protected $attributesAnotations = FALSE;

	/**
	 * System config INI file as `stdClass` or `array`,
	 * placed by default in: `"~/%appPath%/config.ini"`.
	 * @var \MvcCore\Config|NULL
	 */
	protected $config = NULL;

	/**
	 * Environment detection instance.
	 * @var \MvcCore\Environment|NULL
	 */
	protected $environment = NULL;

	/**
	 * Top most parent controller instance currently dispatched by application.
	 * @var \MvcCore\Controller|NULL
	 */
	protected $controller = NULL;

	/**
	 * Request object - parsed URI, query params, app paths...
	 * @var \MvcCore\Request|NULL
	 */
	protected $request = NULL;

	/**
	 * Response object - storage for response headers and rendered body.
	 * @var \MvcCore\Response|NULL
	 */
	protected $response = NULL;

	/**
	 * Application http router to route request and build URL addresses.
	 * @var \MvcCore\Router|NULL
	 */
	protected $router = NULL;


	/**
	 * Pre route custom closure calls storage.
	 * Params in callable should be two with following types:
	 * - `\MvcCore\Request`
	 * - `\MvcCore\Response`
	 * Example:
	 * ```
	 *   \MvcCore\Application::GetInstance()->AddPreRouteHandler(function(
	 *       \MvcCore\Request $request,
	 *       \MvcCore\Response $response
	 *   ) {
	 *       $request->customVar = 'custom_value';
	 *   });
	 * ```
	 * @var array<int, array<int, CustomHandlerRecord>>
	 */
	protected $preRouteHandlers = [];

	/**
	 * Post route custom closure calls storage.
	 * Params in callable should be two with following types:
	 * - `\MvcCore\Request`
	 * - `\MvcCore\Response`
	 * Example:
	 * ```
	 *   \MvcCore\Application::GetInstance()->AddPostRouteHandler(function(
	 *       \MvcCore\Request $request,
	 *       \MvcCore\Response $response
	 *   ) {
	 *       $request->customVar = 'custom_value';
	 *   });
	 * ```
	 * @var array<int, array<int, CustomHandlerRecord>>
	 */
	protected $postRouteHandlers = [];

	/**
	 * Pre dispatch custom calls storage.
	 * Params in `callable` should be two with following types:
	 * - `\MvcCore\Request`
	 * - `\MvcCore\Response`
	 * Example:
	 * ```
	 *   \MvcCore\Application::GetInstance()->AddPreDispatchHandler(function(
	 *       \MvcCore\Request $request,
	 *       \MvcCore\Response $response
	 *   ) {
	 *       $request->customVar = 'custom_value';
	 *   });
	 * ```
	 * @var array<int, array<int, CustomHandlerRecord>>
	 */
	protected $preDispatchHandlers = [];

	/**
	 * Pre sent headers custom calls storage.
	 * Params in `callable` should be two with following types:
	 * - `\MvcCore\Request`
	 * - `\MvcCore\Response`
	 * Example:
	 * ```
	 *   \MvcCore\Application::GetInstance()->AddPreSentHeadersHandler(function(
	 *       \MvcCore\Request $request,
	 *       \MvcCore\Response $response
	 *   ) {
	 *       $request->customVar = 'custom_value';
	 *   });
	 * ```
	 * @var array<int, array<int, CustomHandlerRecord>>
	 */
	protected $preSentHeadersHandlers = [];

	/**
	 * Pre sent body custom calls storage.
	 * Params in `callable` should be two with following types:
	 * - `\MvcCore\Request`
	 * - `\MvcCore\Response`
	 * Example:
	 * ```
	 *   \MvcCore\Application::GetInstance()->AddPreSentBodyHandler(function(
	 *       \MvcCore\Request $request,
	 *       \MvcCore\Response $response
	 *   ) {
	 *       $request->customVar = 'custom_value';
	 *   });
	 * ```
	 * @var array<int, array<int, CustomHandlerRecord>>
	 */
	protected $preSentBodyHandlers = [];

	/**
	 * Post dispatch custom calls storage.
	 * Params in `callable` should be two with following types:
	 * - `\MvcCore\Request`
	 * - `\MvcCore\Response`
	 * Example:
	 * ```
	 *   \MvcCore\Application::GetInstance()->AddPostDispatchHandler(function(
	 *       \MvcCore\Request $request,
	 *       \MvcCore\Response $response
	 *   ) {
	 *       $request->customVar = 'custom_value';
	 *   });
	 * ```
	 * @var array<int, array<int, CustomHandlerRecord>>
	 */
	protected $postDispatchHandlers = [];

	/**
	 * Post terminate custom calls storage.
	 * Params in `callable` should be two with following types:
	 * - `\MvcCore\Request`
	 * - `\MvcCore\Response`
	 * Example:
	 * ```
	 * \MvcCore\Application::GetInstance()->AddPostTerminateHandler(function(
	 *       \MvcCore\Request $request,
	 *       \MvcCore\Response $response
	 *   ) {
	 *       $request->customVar = 'custom_value';
	 *   });
	 * ```
	 * @var array<int, array<int, CustomHandlerRecord>>
	 */
	protected $postTerminateHandlers = [];
	
	/**
	 * CSRF protection error custom calls storage.
	 * Params in `callable` should be two with following types:
	 * - `\MvcCore\Request`
	 * - `\MvcCore\Response`
	 * Example:
	 * ```
	 * \MvcCore\Application::GetInstance()->AddCsrfErrorHandlers(function(
	 *       \MvcCore\Request $request,
	 *       \MvcCore\Response $response
	 *   ) {
	 *       $request->customVar = 'custom_value';
	 *   });
	 * ```
	 * @var array<int, array<int, CustomHandlerRecord>>
	 */
	protected $csrfErrorHandlers = [];


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
	 * It should by reconfigured to custom value in the very 
	 * application beginning or by constant `MVCCORE_APP_ROOT_DIRNAME`.
	 * @var string|NULL
	 */
	protected $appDir = NULL;

	/**
	 * Application web server document root directory name as `"www"` 
	 * by default, where is placed `index.php` startup script.
	 * It should by reconfigured to custom value in the very 
	 * application beginning or by constant `MVCCORE_DOC_ROOT_DIRNAME`.
	 * @var string|NULL
	 */
	protected $docRootDir = NULL;
	
	/**
	 * CLI scripts directory name as `"Cli"` by default.
	 * It should by reconfigured to custom value in the very application beginning.
	 * @var string
	 */
	protected $cliDir = 'Cli';

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
