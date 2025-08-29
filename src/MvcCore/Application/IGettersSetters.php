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
 * @phpstan-type CustomHandlerCallable callable(\MvcCore\IRequest, \MvcCore\IResponse): (false|void)
 */
interface IGettersSetters {
	
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
	 * @return ?\MvcCore\Config
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
	 * Get application default controller namespace, usually completed as "`App\Controllers`".
	 * This variable is completed in runtime by application paths configuration.
	 * @return string
	 */
	public function GetControllersBaseNamespace ();

	/**
	 * Get internal application path, relative or absolute.
	 * When path is not defined yet, it's initialized automatically 
	 * by value from application internal instance property. Then is 
	 * necessary to use third param to set up absolute path variant correctly.
	 * @param  string $pathName
	 * @param  bool   $absolute
	 * @param  bool   $public
	 * @return string
	 */
	public function GetPath ($pathName, $absolute = FALSE, $public = FALSE);

	/**
	 * Get application root directory full path. All other application 
	 * non-public paths are always completed from this value.
	 * 
	 * This value is completed in runtime. If there is already 
	 * defined constant `MVCCORE_APP_ROOT`, value is completed 
	 * by this constant. 
	 * 
	 * There is the same value with webserver document root 
	 * path for single file projects.
	 * 
	 * Example: `"/var/www/html/my-project"`
	 * @return string
	 */
	public function GetPathAppRoot ();

	/**
	 * Get path into composer package root directory, where is dispatched 
	 * main controller from composer package. 
	 * 
	 * This value is completed in runtime. `NULL` by default.
	 * 
	 * Composer package has to have simillar structure as main application module.
	 * 
	 * Example: `"/var/www/html/my-project/vendor/org-name/module-name/src"`
	 * @return string
	 */
	public function GetPathAppRootVendor ();

	/**
	 * Get application document root directory full path. All other 
	 * application public paths are always completed from this value.
	 * 
	 * Application document root is location configured in webserver
	 * containing `index.php` (not in single file projects).
	 * 
	 * This value is completed in runtime. If there is already 
	 * defined constant `MVCCORE_DOC_ROOT`, value is completed 
	 * by this constant. 
	 * 
	 * There is the same value with application root
	 * path for single file projects.
	 * 
	 * Example: `"/var/www/html/my-project/www"`
	 * @return string
	 */
	public function GetPathDocRoot ();

	/**
	 * Get path into `www` sub-directory in composer package root directory (composer package, 
	 * where is dispatched main controller) and where are placed static files for this package. 
	 * 
	 * This value is completed in runtime. `NULL` by default.
	 * 
	 * Composer package has to have simillar structure as main application module.
	 * 
	 * Example: `"/var/www/html/my-project/vendor/org-name/module-name/src/www"`
	 * @return string
	 */
	public function GetPathDocRootVendor ();

	/**
	 * Get application PHP files path. This directory always contains 
	 * `Controllers`, `Models`, `Views` and other application main directories.
	 * 
	 * This value is completed in runtime as relative value from aplication root. 
	 * If there is already defined constant `MVCCORE_APP_ROOT_DIRNAME`, value 
	 * is completed as application root and this constant. 
	 * 
	 * Absolute path variant is completed from app root.
	 * Example: `"~/App"`
	 * @param  bool $absolute
	 * @return string
	 */
	public function GetPathApp ($absolute = FALSE);

	/**
	 * Get path from app root to CLI scripts directory,
	 * `"~/App/Cli"` by default. Absolute path is completed from app root.
	 * @param  bool $absolute
	 * @return string
	 */
	public function GetPathCli ($absolute = FALSE);

	/**
	 * Get path from app root to application controllers base dir,
	 * `"~/App/Controllers"` by default. Absolute path is completed from app root.
	 * @param  bool $absolute
	 * @return string
	 */
	public function GetPathControllers ($absolute = FALSE);

	/**
	 * Get path from app root to application view components base dir,
	 * `"~/App/Views"` by default. Absolute path is completed from app root.
	 * @param  bool $absolute
	 * @return string
	 */
	public function GetPathViews ($absolute = FALSE);

	/**
	 * Get path from app root to application view helpers base dir,
	 * `"~/App/Views/Helpers"` by default. Absolute path is completed from app root.
	 * @param  bool $absolute
	 * @return string
	 */
	public function GetPathViewHelpers ($absolute = FALSE);

	/**
	 * Get path from app root to application view layouts base dir,
	 * `"~/App/Views/Layouts"` by default. Absolute path is completed from app root.
	 * @param  bool $absolute
	 * @return string
	 */
	public function GetPathViewLayouts ($absolute = FALSE);

	/**
	 * Get path from app root to application view scripts base dir,
	 * `"~/App/Views/Scripts"` by default. Absolute path is completed from app root.
	 * @param  bool $absolute
	 * @return string
	 */
	public function GetPathViewScripts ($absolute = FALSE);

	/**
	 * Get path from app root to form view scripts base dir,
	 * `"~/App/Views/Forms"` by default. Absolute path is completed from app root.
	 * This property is used only in forms extensions.
	 * @param  bool $absolute
	 * @return string
	 */
	public function GetPathViewForms ($absolute = FALSE);

	/**
	 * Get path from app root to form field view scripts base dir,
	 * `"~/App/Views/Forms/Fields"` by default. Absolute path is completed from app root.
	 * This property is used only in forms extensions.
	 * @param  bool $absolute
	 * @return string
	 */
	public function GetPathViewFormsFields ($absolute = FALSE);

	/**
	 * Get path from app root to variable data directory,
	 * `"~/Var"` by default. Absolute path is completed from app root.
	 * @param  bool $absolute
	 * @return string
	 */
	public function GetPathVar ($absolute = FALSE);

	/**
	 * Get path from app root to temporary directory for application temporary files,
	 * `"~/Var/Tmp"` by default. Absolute path is completed from app root.
	 * @param  bool $absolute
	 * @return string
	 */
	public function GetPathTmp ($absolute = FALSE);

	/**
	 * Get path from app root to store any log information,
	 * `"~/Var/Logs"` by default. Absolute path is completed from app root.
	 * @param  bool $absolute
	 * @return string
	 */
	public function GetPathLogs ($absolute = FALSE);

	/**
	 * Get path from public document root to all static files,
	 * css, js, images and fonts, `"~/static"` by default.
	 * Absolute path is completed from public document root.
	 * @param  bool $absolute
	 * @return string
	 */
	public function GetPathStatic ($absolute = FALSE);

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
	 * Set application default controller namespace, usually completed as "`App\Controllers`".
	 * This variable is completed in runtime by application paths configuration.
	 * @param  string $controllersBaseNamespace
	 * @return \MvcCore\Application
	 */
	public function SetControllersBaseNamespace ($controllersBaseNamespace);

	/**
	 * Set internal application path (relative and absolute) from relative base.
	 * Use `TRUE` in public param for paths relative from 
	 * public document root or `FALSE` for paths from application root.
	 * @param  string $pathName
	 * @param  string $relPath
	 * @param  bool   $public
	 * @return \MvcCore\Application
	 */
	public function SetPath ($pathName, $relPath, $public = FALSE);
	
	/**
	 * Set application root directory full path. All other application 
	 * non-public paths are always completed from this value.
	 * 
	 * This value is completed in runtime. If there is already 
	 * defined constant `MVCCORE_APP_ROOT`, value is completed 
	 * by this constant. 
	 * 
	 * There is the same value with webserver document root 
	 * path for single file projects.
	 * 
	 * Example: `"/var/www/html/my-project"`
	 * @param  string $absPath
	 * @return \MvcCore\Application
	 */
	public function SetPathAppRoot ($absPath);
	
	/**
	 * Get path into composer package root directory, where is dispatched 
	 * main controller from composer package. 
	 * 
	 * This value is completed in runtime. `NULL` by default.
	 * 
	 * Composer package has to have simillar structure as main application module.
	 * 
	 * Example: `"/var/www/html/my-project/vendor/org-name/module-name/src"`
	 * @param  string $absPath
	 * @return \MvcCore\Application
	 */
	public function SetPathAppRootVendor ($absPath);
	
	/**
	 * Set application document root directory full path. All other 
	 * application public paths are always completed from this value.
	 * 
	 * Application document root is location configured in webserver
	 * containing `index.php` (not in single file projects).
	 * 
	 * This value is completed in runtime. If there is already 
	 * defined constant `MVCCORE_DOC_ROOT`, value is completed 
	 * by this constant. 
	 * 
	 * There is the same value with application root
	 * path for single file projects.
	 * 
	 * Example: `"/var/www/html/my-project/www"`
	 * @param  string $absPath
	 * @return \MvcCore\Application
	 */
	public function SetPathDocRoot ($absPath);
	
	/**
	 * Set application PHP files path. This directory always contains 
	 * `Controllers`, `Models`, `Views` and other application main directories.
	 * 
	 * This value is completed in runtime as relative value from aplication root. 
	 * If there is already defined constant `MVCCORE_APP_ROOT_DIRNAME`, value 
	 * is completed as application root and this constant. 
	 * 
	 * Absolute path variant is completed from app root.
	 * Example: `"~/App"`
	 * @param  string $relPath
	 * @return \MvcCore\Application
	 */
	public function SetPathApp ($relPath);
	
	/**
	 * Set path from app root to CLI scripts directory,
	 * `"~/App/Cli"` by default. Absolute path is completed from app root.
	 * @param  string $relPath
	 * @return \MvcCore\Application
	 */
	public function SetPathCli ($relPath);

	/**
	 * Set path from app root to application controllers base dir,
	 * `"~/Var/Controllers"` by default. Absolute path is completed from app root.
	 * @param  string $relPath
	 * @return \MvcCore\Application
	 */
	public function SetPathControllers ($relPath);

	/**
	 * Set path from app root to application view components base dir,
	 * `"~/Var/Views"` by default. Absolute path is completed from app root.
	 * @param  string $relPath
	 * @return \MvcCore\Application
	 */
	public function SetPathViews ($relPath);

	/**
	 * Set path from app root to application view helpers base dir,
	 * `"~/Var/Views/Helpers"` by default. Absolute path is completed from app root.
	 * @param  string $relPath
	 * @return \MvcCore\Application
	 */
	public function SetPathViewHelpers ($relPath);

	/**
	 * Set path from app root to application view layouts base dir,
	 * `"~/Var/Views/Layouts"` by default. Absolute path is completed from app root.
	 * @param  string $relPath
	 * @return \MvcCore\Application
	 */
	public function SetPathViewLayouts ($relPath);

	/**
	 * Set path from app root to application view scripts base dir,
	 * `"~/Var/Views/Scripts"` by default. Absolute path is completed from app root.
	 * @param  string $relPath
	 * @return \MvcCore\Application
	 */
	public function SetPathViewScripts ($relPath);

	/**
	 * Set path from app root to form view scripts base dir,
	 * `"~/Var/Views/Forms"` by default. Absolute path is completed from app root.
	 * This property is used only in forms extensions.
	 * @param  string $relPath
	 * @return \MvcCore\Application
	 */
	public function SetPathViewForms ($relPath);

	/**
	 * Set path from app root to form field view scripts base dir,
	 * `"~/Var/Views/Forms/Fields"` by default. Absolute path is completed from app root.
	 * This property is used only in forms extensions.
	 * @param  string $relPath
	 * @return \MvcCore\Application
	 */
	public function SetPathViewFormsFields ($relPath);

	/**
	 * Set path from app root to variable data directory,
	 * `"~/Var"` by default. Absolute path is completed from app root.
	 * @param  string $relPath
	 * @return \MvcCore\Application
	 */
	public function SetPathVar ($relPath);

	/**
	 * Set path from app root to temporary directory for application temporary files,
	 * `"~/Var/Tmp"` by default. Absolute path is completed from app root.
	 * @param  string $relPath
	 * @return \MvcCore\Application
	 */
	public function SetPathTmp ($relPath);

	/**
	 * Set path from app root to store any log information,
	 * `"~/Var/Logs"` by default. Absolute path is completed from app root.
	 * @param  string $relPath
	 * @return \MvcCore\Application
	 */
	public function SetPathLogs ($relPath);

	/**
	 * Set path from public document root to all static files,
	 * css, js, images and fonts, `"~/static"` by default.
	 * Absolute path is completed from public document root.
	 * @param  string $relPath
	 * @return \MvcCore\Application
	 */
	public function SetPathStatic ($relPath);

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
	 * ```
	 *   \MvcCore\Application::GetInstance()->AddPreRouteHandler(function(
	 *       \MvcCore\Request $request,
	 *       \MvcCore\Response $response
	 *   ) {
	 *       $request->customVar = 'custom_value';
	 *   });
	 * ```
	 * @param  CustomHandlerCallable $handler
	 * @param  ?int                  $priorityIndex
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
	 * ```
	 *   \MvcCore\Application::GetInstance()->AddPostRouteHandler(function(
	 *       \MvcCore\Request $request,
	 *       \MvcCore\Response $response
	 *   ) {
	 *       $request->customVar = 'custom_value';
	 *   });
	 * ```
	 * @param  CustomHandlerCallable $handler
	 * @param  ?int                  $priorityIndex
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
	 * ```
	 * \MvcCore\Application::GetInstance()->AddPreDispatchHandler(function(
	 *       \MvcCore\Request $request,
	 *       \MvcCore\Response $response
	 *   ) {
	 *       $request->customVar = 'custom_value';
	 *   });
	 * ```
	 * @param  CustomHandlerCallable $handler
	 * @param  ?int                  $priorityIndex
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
	 * ```
	 *   \MvcCore\Application::GetInstance()->AddPreSentHeadersHandler(function(
	 *       \MvcCore\Request $request,
	 *       \MvcCore\Response $response
	 *   ) {
	 *       $request->customVar = 'custom_value';
	 *   });
	 * ```
	 * @param  CustomHandlerCallable $handler
	 * @param  ?int                  $priorityIndex
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
	 * ```
	 *   \MvcCore\Application::GetInstance()->AddPreSentBodyHandler(function(
	 *       \MvcCore\Request $request,
	 *       \MvcCore\Response $response
	 *   ) {
	 *       $request->customVar = 'custom_value';
	 *   });
	 * ```
	 * @param  CustomHandlerCallable $handler
	 * @param  ?int                  $priorityIndex
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
	 * ```
	 *   \MvcCore\Application::GetInstance()->AddPostDispatchHandler(function(
	 *       \MvcCore\Request $request,
	 *       \MvcCore\Response $response
	 *   ) {
	 *       $request->customVar = 'custom_value';
	 *   });
	 * ```
	 * @param  CustomHandlerCallable $handler
	 * @param  ?int                  $priorityIndex
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
	 * ```
	 *   \MvcCore\Application::GetInstance()->AddPostTerminateHandler(function(
	 *       \MvcCore\Request $request,
	 *       \MvcCore\Response $response
	 *   ) {
	 *       // close connection by previously configured
	 *       // header: header('Connection: close');
	 *       // and run background process now:
	 *   });
	 * ```
	 * @param  CustomHandlerCallable $handler
	 * @param  ?int                  $priorityIndex
	 * @return \MvcCore\Application
	 */
	public function AddPostTerminateHandler (callable $handler, $priorityIndex = NULL);
	
	/**
	 * Add handler before session has been started (before PHP `session_start()` call, 
	 * when session id is resolved.
	 * Callable should be void and it's params should be two with following types:
	 * - `\MvcCore\Request`
	 * - `\MvcCore\Response`
	 * Example:
	 * ```
	 *   \MvcCore\Application::GetInstance()->AddPreSessionStartHandler(function(
	 *       \MvcCore\Request $request,
	 *       \MvcCore\Response $response
	 *   ) {
	 *       $sessionId = session_id();
	 *       // do anything special with session...
	 *   });
	 * ```
	 * @param  CustomHandlerCallable $handler
	 * @param  ?int                  $priorityIndex
	 * @return \MvcCore\Application
	 */
	public function AddPreSessionStartHandler (callable $handler, $priorityIndex = NULL);
	
	/**
	 * Add handler after session has been started and after session has been 
	 * fully initialized by session metadata. 
	 * Callable should be void and it's params should be two with following types:
	 * - `\MvcCore\Request`
	 * - `\MvcCore\Response`
	 * Example:
	 * ```
	 *   \MvcCore\Application::GetInstance()->AddPostSessionStartHandler(function(
	 *       \MvcCore\Request $request,
	 *       \MvcCore\Response $response
	 *   ) {
	 *       $_SESSION['special_value'] = TRUE;
	 *   });
	 * ```
	 * @param  CustomHandlerCallable $handler
	 * @param  ?int                  $priorityIndex
	 * @return \MvcCore\Application
	 */
	public function AddPostSessionStartHandler (callable $handler, $priorityIndex = NULL);

	/**
	 * Add CSRF protection error handler into handlers queue 
	 * to process them when any CSRF calidation error happends.
	 * Callable should be void and it's params should be two with following types:
	 * - `\MvcCore\Request`
	 * - `\MvcCore\Response`
	 * Example:
	 * ```
	 *   \MvcCore\Application::GetInstance()->AddCsrfErrorHandler(function(
	 *       \MvcCore\Request $request,
	 *       \MvcCore\Response $response
	 *   ) {
	 *       // redirect user to homepage or sign out authenticated user.
	 *   });
	 * ```
	 * @param  CustomHandlerCallable $handler
	 * @param  int|NULL              $priorityIndex
	 * @return \MvcCore\Application
	 */
	public function AddCsrfErrorHandler (callable $handler, $priorityIndex = NULL);

}
