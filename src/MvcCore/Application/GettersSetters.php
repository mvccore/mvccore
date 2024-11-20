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
 */
trait GettersSetters {

	/***********************************************************************************
	 *                        `\MvcCore\Application` - Getters                         *
	 ***********************************************************************************/

	/**
	 * @inheritDoc
	 * @param  string $propName 
	 * @return mixed
	 */
	public function __get ($propName) {
		if (isset($this->{$propName}))
			return $this->{$propName};
		return NULL;
	}

	/**
	 * @inheritDoc
	 * @return string
	 */
	public function GetCompiled () {
		/** @var \MvcCore\Application $this */
		if ($this->compiled === NULL) {
			$compiled = static::NOT_COMPILED;
			if (class_exists('\Phar') && strlen(\Phar::running()) > 0) {
				$compiled = static::COMPILED_PHAR;
			} else if (class_exists('\Packager_Php_Wrapper')) {
				$compiled = constant('\Packager_Php_Wrapper::FS_MODE');
			}
			$this->compiled = $compiled;
		}
		return $this->compiled;
	}

	
	/**
	 * @inheritDoc
	 * @return int
	 */
	public function GetCsrfProtection () {
		return $this->csrfProtection;
	}

	/**
	 * @inheritDoc
	 * @return bool
	 */
	public function GetAttributesAnotations () {
		return $this->attributesAnotations;
	}

	/**
	 * @inheritDoc
	 * @return \MvcCore\Environment|string
	 */
	public function GetEnvironmentClass () {
		return $this->environmentClass;
	}

	/**
	 * @inheritDoc
	 * @return \MvcCore\Config|string
	 */
	public function GetConfigClass () {
		return $this->configClass;
	}

	/**
	 * @inheritDoc
	 * @return \MvcCore\Controller|string
	 */
	public function GetControllerClass () {
		return $this->controllerClass;
	}

	/**
	 * @inheritDoc
	 * @return \MvcCore\Debug|string
	 */
	public function GetDebugClass () {
		return $this->debugClass;
	}

	/**
	 * @inheritDoc
	 * @return \MvcCore\Request|string
	 */
	public function GetRequestClass () {
		return $this->requestClass;
	}

	/**
	 * @inheritDoc
	 * @return \MvcCore\Response|string
	 */
	public function GetResponseClass () {
		return $this->responseClass;
	}

	/**
	 * @inheritDoc
	 * @return \MvcCore\Route|string
	 */
	public function GetRouteClass () {
		return $this->routeClass;
	}

	/**
	 * @inheritDoc
	 * @return \MvcCore\Router|string
	 */
	public function GetRouterClass () {
		return $this->routerClass;
	}

	/**
	 * @inheritDoc
	 * @return \MvcCore\Session|string
	 */
	public function GetSessionClass () {
		return $this->sessionClass;
	}

	/**
	 * @inheritDoc
	 * @return \MvcCore\Tool|string
	 */
	public function GetToolClass () {
		return $this->toolClass;
	}

	/**
	 * @inheritDoc
	 * @return \MvcCore\View|string
	 */
	public function GetViewClass () {
		return $this->viewClass;
	}

	/**
	 * @inheritDoc
	 * @return \MvcCore\Config|NULL
	 */
	public function GetConfig () {
		if ($this->config === NULL) {
			$configClass = $this->configClass;
			$this->config = $configClass::GetConfigSystem();
		}
		return $this->config;
	}

	/**
	 * @inheritDoc
	 * @return \MvcCore\Environment
	 */
	public function GetEnvironment () {
		if ($this->environment === NULL) {
			$environmentClass = $this->environmentClass;
			$this->environment = $environmentClass::CreateInstance();
		}
		return $this->environment;
	}

	/**
	 * @inheritDoc
	 * @return \MvcCore\Controller
	 */
	public function GetController () {
		return $this->controller;
	}

	/**
	 * @inheritDoc
	 * @return \MvcCore\Request
	 */
	public function GetRequest () {
		if ($this->request === NULL) {
			$requestClass = $this->requestClass;
			$this->request = $requestClass::CreateInstance();
		}
		return $this->request;
	}

	/**
	 * @inheritDoc
	 * @return \MvcCore\Response
	 */
	public function GetResponse () {
		if ($this->response === NULL) {
			$responseClass = $this->responseClass;
			$this->response = $responseClass::CreateInstance();
		}
		return $this->response;
	}

	/**
	 * @inheritDoc
	 * @return \MvcCore\Router
	 */
	public function GetRouter () {
		if ($this->router === NULL) {
			$routerClass = $this->routerClass;
			$this->router = $routerClass::GetInstance();
		}
		return $this->router;
	}

	/**
	 * @inheritDoc
	 * @return string
	 */
	public function GetControllersBaseNamespace () {
		if ($this->controllersBaseNamespace === NULL) {
			$appRootFp = $this->GetPathAppRoot();
			$ctrlsFp = $this->GetPathControllers(TRUE);
			if (mb_strpos($ctrlsFp, $appRootFp) === 0) {
				// controllers are inside app root
				$ctrlsPath = mb_substr($ctrlsFp, mb_strlen($appRootFp));
				$this->controllersBaseNamespace = str_replace('/', "\\", $ctrlsPath);
			} else {
				// controllers are outside app root
				$this->controllersBaseNamespace = str_replace('/', "\\", ltrim($this->GetPathControllers(FALSE), '~/'));
			}
		}
		return $this->controllersBaseNamespace;
	}

	/**
	 * @inheritDoc
	 * @param  string $pathName
	 * @param  bool   $absolute
	 * @param  bool   $public
	 * @return string
	 */
	public function GetPath ($pathName, $absolute = FALSE, $public = FALSE) {
		if (!isset($this->paths[$pathName])) {
			$this->SetPath($pathName, $this->{$pathName}, $public);
		}
		return $this->paths[$pathName][$absolute ? 1 : 0];
	}

	/**
	 * @inheritDoc
	 * @return string
	 */
	public function GetPathAppRoot () {
		if ($this->pathAppRoot === NULL) {
			if (defined('MVCCORE_APP_ROOT')) {
				$this->pathAppRoot = constant('MVCCORE_APP_ROOT');
				$insidePhar = class_exists('\Phar') && strlen(\Phar::running()) > 0;
				if (!$insidePhar)
					$this->pathAppRoot = ucfirst($this->pathAppRoot);
			} else {
				$docRoot = $this->GetPathDocRoot();
				$docRootDirName = static::getPathDocRootDirName();
				$docRootDirNamePos = mb_strrpos($docRoot, '/' . $docRootDirName);
				$estimatedPos = mb_strlen($docRoot) - mb_strlen($docRootDirName) - 1;
				$this->pathAppRoot = $docRootDirNamePos !== FALSE && $docRootDirNamePos === $estimatedPos
					? mb_substr($docRoot, 0, $estimatedPos)
					: $docRoot;
				define('MVCCORE_APP_ROOT', $this->pathAppRoot);
			}
		}
		return $this->pathAppRoot;
	}

	/**
	 * @inheritDoc
	 * @return string|NULL
	 */
	public function GetPathAppRootVendor () {
		if ($this->vendorAppDispatch === NULL)
			$this->initVendorProps();
		return $this->pathAppRootVendor;
	}
	
	/**
	 * @inheritDoc
	 * @return string
	 */
	public function GetPathDocRoot () {
		if ($this->pathDocRoot === NULL) {
			// `ucfirst()` - cause IIS has lower case drive name here - different from __DIR__ value
			$insidePhar = class_exists('\Phar') && strlen(\Phar::running()) > 0;
			if (defined('MVCCORE_DOC_ROOT')) {
				$this->pathDocRoot = constant('MVCCORE_DOC_ROOT');
				if (!$insidePhar)
					$this->pathDocRoot = ucfirst($this->pathDocRoot);
			} else {
				$globalServer = $this->GetRequest()->GetGlobalCollection('server');
				if (php_sapi_name() !== 'cli') {
					// running in web server:
					$scriptFilename = $globalServer['SCRIPT_FILENAME'];
				} else {
					// running in CLI:
					$backtraceItems = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
					$scriptFilename = $backtraceItems[count($backtraceItems) - 1]['file'];
					// If php is running by direct input like `php -r "/* php code */":
					if (
						mb_strpos($scriptFilename, DIRECTORY_SEPARATOR) === FALSE && // $indexFilePath = "Command line code"
						empty($globalServer['SCRIPT_FILENAME'])
					) {
						// Try to define app root and document root 
						// by possible Composer class location:
						$composerFullClassName = 'Composer\\Autoload\\ClassLoader';
						if (class_exists($composerFullClassName, TRUE)) {
							$ccType = new \ReflectionClass($composerFullClassName);
							$scriptFilename = dirname($ccType->getFileName(), 2);
						} else {
							// If there is no composer class, define 
							// document root by called current working directory:
							$scriptFilename = getcwd() . '/php';
						}
					}
				}
				$docRoot = $insidePhar
					? $scriptFilename
					: dirname($scriptFilename);
				// `ucfirst()` - cause IIS has lower case drive name here - different from __DIR__ value
				$docRoot = str_replace(['\\', '//'], '/', ucfirst($docRoot));
				$this->pathDocRoot = $insidePhar 
					? 'phar://' . $docRoot 
					: $docRoot;
				define('MVCCORE_DOC_ROOT', $this->pathDocRoot);
			}
		}
		return $this->pathDocRoot;
	}

	/**
	 * @inheritDoc
	 * @param  bool $absolute
	 * @return string
	 */
	public function GetPathApp ($absolute = FALSE) {
		if (!isset($this->paths['pathApp'])) {
			if (!isset($this->pathApp)) {
				$appRootDirName = static::getPathAppRootDirName();
				$this->pathApp = '~/' . $appRootDirName;
				$absPath = $this->pathAppRoot . '/' . $appRootDirName;
			} else {
				if (mb_substr($this->pathApp, 0, 2) === '~/') {
					$absPath = $this->pathAppRoot . mb_substr($this->pathApp, 1);
				} else {
					$absPath = $this->pathApp;
				}
			}
			$this->paths['pathApp'] = [$this->pathApp, $absPath];
		}
		return $this->paths['pathApp'][$absolute ? 1 : 0];
	}
	
	/**
	 * @inheritDoc
	 * @param  bool $absolute
	 * @return string
	 */
	public function GetPathCli ($absolute = FALSE) {
		return $this->GetPath('pathCli', $absolute, FALSE);
	}
	
	/**
	 * @inheritDoc
	 * @param  bool $absolute
	 * @return string
	 */
	public function GetPathControllers ($absolute = FALSE) {
		return $this->GetPath('pathControllers', $absolute, FALSE);
	}
	
	/**
	 * @inheritDoc
	 * @param  bool $absolute
	 * @return string
	 */
	public function GetPathViews ($absolute = FALSE) {
		return $this->GetPath('pathViews', $absolute, FALSE);
	}
	
	/**
	 * @inheritDoc
	 * @param  bool $absolute
	 * @return string
	 */
	public function GetPathViewHelpers ($absolute = FALSE) {
		return $this->GetPath('pathViewHelpers', $absolute, FALSE);
	}
	
	/**
	 * @inheritDoc
	 * @param  bool $absolute
	 * @return string
	 */
	public function GetPathViewLayouts ($absolute = FALSE) {
		return $this->GetPath('pathViewLayouts', $absolute, FALSE);
	}
	
	/**
	 * @inheritDoc
	 * @param  bool $absolute
	 * @return string
	 */
	public function GetPathViewScripts ($absolute = FALSE) {
		return $this->GetPath('pathViewScripts', $absolute, FALSE);
	}
	
	/**
	 * @inheritDoc
	 * @param  bool $absolute
	 * @return string
	 */
	public function GetPathViewForms ($absolute = FALSE) {
		return $this->GetPath('pathViewForms', $absolute, FALSE);
	}
	
	/**
	 * @inheritDoc
	 * @param  bool $absolute
	 * @return string
	 */
	public function GetPathViewFormsFields ($absolute = FALSE) {
		return $this->GetPath('pathViewFormsFields', $absolute, FALSE);
	}

	// forms, fields, 
	
	/**
	 * @inheritDoc
	 * @param  bool $absolute
	 * @return string
	 */
	public function GetPathVar ($absolute = FALSE) {
		return $this->GetPath('pathVar', $absolute, FALSE);
	}
	
	/**
	 * @inheritDoc
	 * @param  bool $absolute
	 * @return string
	 */
	public function GetPathTmp ($absolute = FALSE) {
		return $this->GetPath('pathTmp', $absolute, FALSE);
	}
	
	/**
	 * @inheritDoc
	 * @param  bool $absolute
	 * @return string
	 */
	public function GetPathLogs ($absolute = FALSE) {
		return $this->GetPath('pathLogs', $absolute, FALSE);
	}
	
	/**
	 * @inheritDoc
	 * @param  bool $absolute
	 * @return string
	 */
	public function GetPathStatic ($absolute = FALSE) {
		return $this->GetPath('pathStatic', $absolute, TRUE);
	}

	/**
	 * @inheritDoc
	 * @return array<string>
	 */
	public function GetDefaultControllerAndActionNames () {
		return [$this->defaultControllerName, $this->defaultControllerDefaultActionName];
	}

	/**
	 * @inheritDoc
	 * @return string
	 */
	public function GetDefaultControllerName () {
		return $this->defaultControllerName;
	}

	/**
	 * @inheritDoc
	 * @return string
	 */
	public function GetDefaultControllerErrorActionName () {
		return $this->defaultControllerErrorActionName;
	}

	/**
	 * @inheritDoc
	 * @return string
	 */
	public function GetDefaultControllerNotFoundActionName () {
		return $this->defaultControllerNotFoundActionName;
	}

	/**
	 * @inheritDoc
	 * @return bool
	 */
	public function GetTerminated () {
		return $this->terminated;
	}
	
	/**
	 * Internal getter to init or define constant 
	 * `MVCCORE_APP_ROOT_DIRNAME`, which always contains 
	 * main application directory name `"App"`.
	 * @return string
	 */
	protected static function getPathAppRootDirName () {
		if (!defined('MVCCORE_APP_ROOT_DIRNAME')) 
			define('MVCCORE_APP_ROOT_DIRNAME', 'App');
		return MVCCORE_APP_ROOT_DIRNAME;
	}
	
	/**
	 * Internal getter to init or define constant 
	 * `MVCCORE_DOC_ROOT_DIRNAME`, which always contains 
	 * document root name `"www"`.
	 * @return string
	 */
	protected static function getPathDocRootDirName () {
		if (!defined('MVCCORE_DOC_ROOT_DIRNAME')) 
			define('MVCCORE_DOC_ROOT_DIRNAME', 'www');
		return MVCCORE_DOC_ROOT_DIRNAME;
	}


	/***********************************************************************************
	 *                        `\MvcCore\Application` - Setters                         *
	 ***********************************************************************************/

	/**
	 * @inheritDoc
	 * @param  string $compiled
	 * @return \MvcCore\Application
	 */
	public function SetCompiled ($compiled) {
		$this->compiled = $compiled;
		return $this;
	}

	
	/**
	 * @inheritDoc
	 * @param  int $csrfProtection
	 * @return \MvcCore\Application
	 */
	public function SetCsrfProtection ($csrfProtection = \MvcCore\IApplication::CSRF_PROTECTION_COOKIE) {
		$this->csrfProtection = $csrfProtection;
		return $this;
	}

	/**
	 * @inheritDoc
	 * @param  bool $attributesAnotations
	 * @return \MvcCore\Application
	 */
	public function SetAttributesAnotations ($attributesAnotations = TRUE) {
		$this->attributesAnotations = $attributesAnotations;
		return $this;
	}
	
	/**
	 * @inheritDoc
	 * @param  string $environmentClass
	 * @return \MvcCore\Application
	 */
	public function SetEnvironmentClass ($environmentClass) {
		return $this->setCoreClass($environmentClass, 'environmentClass', 'MvcCore\IEnvironment');
	}

	/**
	 * @inheritDoc
	 * @param  string $configClass
	 * @return \MvcCore\Application
	 */
	public function SetConfigClass ($configClass) {
		return $this->setCoreClass($configClass, 'configClass', 'MvcCore\IConfig');
	}

	/**
	 * @inheritDoc
	 * @param  string $controllerClass
	 * @return \MvcCore\Application
	 */
	public function SetControllerClass ($controllerClass) {
		return $this->setCoreClass($controllerClass, 'controllerClass', 'MvcCore\IController');
	}

	/**
	 * @inheritDoc
	 * @param  string $debugClass
	 * @return \MvcCore\Application
	 */
	public function SetDebugClass ($debugClass) {
		return $this->setCoreClass($debugClass, 'debugClass', 'MvcCore\IDebug');
	}

	/**
	 * @inheritDoc
	 * @param  string $requestClass
	 * @return \MvcCore\Application
	 */
	public function SetRequestClass ($requestClass) {
		return $this->setCoreClass($requestClass, 'requestClass', 'MvcCore\IRequest');
	}

	/**
	 * @inheritDoc
	 * @param  string $responseClass
	 * @return \MvcCore\Application
	 */
	public function SetResponseClass ($responseClass) {
		return $this->setCoreClass($responseClass, 'responseClass', 'MvcCore\IResponse');
	}

	/**
	 * @inheritDoc
	 * @param  string $routeClass
	 * @return \MvcCore\Application
	 */
	public function SetRouteClass ($routeClass) {
		return $this->setCoreClass($routeClass, 'routeClass', 'MvcCore\IRoute');
	}

	/**
	 * @inheritDoc
	 * @param  string $routerClass
	 * @return \MvcCore\Application
	 */
	public function SetRouterClass ($routerClass) {
		return $this->setCoreClass($routerClass, 'routerClass', 'MvcCore\IRouter');
	}

	/**
	 * @inheritDoc
	 * @param  string $sessionClass
	 * @return \MvcCore\Application
	 */
	public function SetSessionClass ($sessionClass) {
		return $this->setCoreClass($sessionClass, 'sessionClass', 'MvcCore\ISession');
	}

	/**
	 * @inheritDoc
	 * @param  string $toolClass
	 * @return \MvcCore\Application
	 */
	public function SetToolClass ($toolClass) {
		return $this->setCoreClass($toolClass, 'toolClass', 'MvcCore\ITool');
	}

	/**
	 * @inheritDoc
	 * @param  string $viewClass
	 * @return \MvcCore\Application
	 */
	public function SetViewClass ($viewClass) {
		return $this->setCoreClass($viewClass, 'viewClass', 'MvcCore\IView');
	}


	/**
	 * @inheritDoc
	 * @param  \MvcCore\Controller $controller
	 * @return \MvcCore\Application
	 */
	public function SetController (\MvcCore\IController $controller) {
		$this->controller = $controller;
		return $this;
	}

	/**
	 * @inheritDoc
	 * @param  string $controllersBaseNamespace
	 * @return string
	 */
	public function SetControllersBaseNamespace ($controllersBaseNamespace) {
		$this->controllersBaseNamespace = $controllersBaseNamespace;
		return $this;
	}

	/**
	 * @inheritDoc
	 * @param  string $pathName
	 * @param  string $relPath
	 * @param  bool   $public
	 * @return \MvcCore\Application
	 */
	public function SetPath ($pathName, $relPath, $public = FALSE) {
		if (mb_substr($relPath, 0, 2) === '~/') {
			$rootDir = $public 
				? $this->GetPathDocRoot()
				: $this->GetPathAppRoot();
			$toolClass = $this->toolClass;
			$absPath = $toolClass::RealPathVirtual($rootDir . mb_substr($relPath, 1));
		} else {
			$absPath = $relPath;
		}
		$this->paths[$pathName] = [$relPath, $absPath];
		return $this;
	}
	
	/**
	 * @inheritDoc
	 * @param  string $absPath
	 * @return \MvcCore\Application
	 */
	public function SetPathAppRoot ($absPath) {
		$this->pathAppRoot = $absPath;
		return $this;
	}
	
	/**
	 * @inheritDoc
	 * @param  string $absPath
	 * @return \MvcCore\Application
	 */
	public function SetPathAppRootVendor ($absPath) {
		if ($this->vendorAppDispatch === NULL)
			$this->initVendorProps();
		if ($this->vendorAppRoot) {
			$this->pathAppRootVendor = $absPath;
		}
		return $this;
	}
	
	/**
	 * @inheritDoc
	 * @param  string $absPath
	 * @return \MvcCore\Application
	 */
	public function SetPathDocRoot ($absPath) {
		$this->pathDocRoot = $absPath;
		return $this;
	}
	
	/**
	 * @inheritDoc
	 * @param  string $relPath
	 * @return \MvcCore\Application
	 */
	public function SetPathApp ($relPath) {
		$this->pathApp = $relPath;
		$this->SetPath('pathApp', $relPath, FALSE);
		return $this;
	}
	
	/**
	 * @inheritDoc
	 * @param  string $relPath
	 * @return \MvcCore\Application
	 */
	public function SetPathCli ($relPath) {
		$this->SetPath('pathClis', $relPath, FALSE);
		return $this;
	}
	
	/**
	 * @inheritDoc
	 * @param  string $relPath
	 * @return \MvcCore\Application
	 */
	public function SetPathControllers ($relPath) {
		$this->SetPath('pathControllers', $relPath, FALSE);
		return $this;
	}

	/**
	 * @inheritDoc
	 * @param  string $relPath
	 * @return \MvcCore\Application
	 */
	public function SetPathViews ($relPath) {
		$this->SetPath('pathViews', $relPath, FALSE);
		return $this;
	}
	
	/**
	 * @inheritDoc
	 * @param  string $relPath
	 * @return \MvcCore\Application
	 */
	public function SetPathViewHelpers ($relPath) {
		$this->SetPath('pathViewHelpers', $relPath, FALSE);
		return $this;
	}

	/**
	 * @inheritDoc
	 * @param  string $relPath
	 * @return \MvcCore\Application
	 */
	public function SetPathViewLayouts ($relPath) {
		$this->SetPath('pathViewLayouts', $relPath, FALSE);
		return $this;
	}

	/**
	 * @inheritDoc
	 * @param  string $relPath
	 * @return \MvcCore\Application
	 */
	public function SetPathViewScripts ($relPath) {
		$this->SetPath('pathViewScripts', $relPath, FALSE);
		return $this;
	}

	/**
	 * @inheritDoc
	 * @param  string $relPath
	 * @return \MvcCore\Application
	 */
	public function SetPathViewForms ($relPath) {
		$this->SetPath('pathViewForms', $relPath, FALSE);
		return $this;
	}

	/**
	 * @inheritDoc
	 * @param  string $relPath
	 * @return \MvcCore\Application
	 */
	public function SetPathViewFormsFields ($relPath) {
		$this->SetPath('pathViewFormsFields', $relPath, FALSE);
		return $this;
	}

	/**
	 * @inheritDoc
	 * @param  string $relPath
	 * @return \MvcCore\Application
	 */
	public function SetPathVar ($relPath) {
		$this->SetPath('pathVar', $relPath, FALSE);
		return $this;
	}
	
	/**
	 * @inheritDoc
	 * @param  string $relPath
	 * @return \MvcCore\Application
	 */
	public function SetPathTmp ($relPath) {
		$this->SetPath('pathTmp', $relPath, FALSE);
		return $this;
	}
	
	/**
	 * @inheritDoc
	 * @param  string $relPath
	 * @return \MvcCore\Application
	 */
	public function SetPathLogs ($relPath) {
		$this->SetPath('pathLogs', $relPath, FALSE);
		return $this;
	}
	
	/**
	 * @inheritDoc
	 * @param  string $relPath
	 * @return \MvcCore\Application
	 */
	public function SetPathStatic ($relPath) {
		$this->SetPath('pathStatic', $relPath, TRUE);
		return $this;
	}

	/**
	 * @inheritDoc
	 * @param  string $defaultControllerName
	 * @return \MvcCore\Application
	 */
	public function SetDefaultControllerName ($defaultControllerName) {
		$this->defaultControllerName = $defaultControllerName;
		return $this;
	}

	/**
	 * @inheritDoc
	 * @param  string $defaultActionName
	 * @return \MvcCore\Application
	 */
	public function SetDefaultControllerDefaultActionName ($defaultActionName) {
		$this->defaultControllerDefaultActionName = $defaultActionName;
		return $this;
	}

	/**
	 * @inheritDoc
	 * @param  string $defaultControllerErrorActionName
	 * @return \MvcCore\Application
	 */
	public function SetDefaultControllerErrorActionName ($defaultControllerErrorActionName) {
		$this->defaultControllerErrorActionName = $defaultControllerErrorActionName;
		return $this;
	}

	/**
	 * @inheritDoc
	 * @param  string $defaultControllerNotFoundActionName
	 * @return \MvcCore\Application
	 */
	public function SetDefaultControllerNotFoundActionName ($defaultControllerNotFoundActionName) {
		$this->defaultControllerNotFoundActionName = $defaultControllerNotFoundActionName;
		return $this;
	}

	/**
	 * @inheritDoc`
	 * @param  CustomHandlerCallable $handler
	 * @param  int|NULL              $priorityIndex
	 * @return \MvcCore\Application
	 */
	public function AddPreRouteHandler (callable $handler, $priorityIndex = NULL) {
		return $this->setHandler($this->preRouteHandlers, $handler, $priorityIndex);
	}

	/**
	 * @inheritDoc
	 * @param  CustomHandlerCallable $handler
	 * @param  int|NULL              $priorityIndex
	 * @return \MvcCore\Application
	 */
	public function AddPostRouteHandler (callable $handler, $priorityIndex = NULL) {
		return $this->setHandler($this->postRouteHandlers, $handler, $priorityIndex);
	}
	
	/**
	 * @inheritDoc
	 * @param  CustomHandlerCallable $handler
	 * @param  int|NULL              $priorityIndex
	 * @return \MvcCore\Application
	 */
	public function AddPreDispatchHandler (callable $handler, $priorityIndex = NULL) {
		return $this->setHandler($this->preDispatchHandlers, $handler, $priorityIndex);
	}
	
	/**
	 * @inheritDoc
	 * @param  CustomHandlerCallable $handler
	 * @param  int|NULL              $priorityIndex
	 * @return \MvcCore\Application
	 */
	public function AddPostDispatchHandler (callable $handler, $priorityIndex = NULL) {
		return $this->setHandler($this->postDispatchHandlers, $handler, $priorityIndex);
	}

	/**
	 * @inheritDoc
	 * @param  CustomHandlerCallable $handler
	 * @param  int|NULL              $priorityIndex
	 * @return \MvcCore\Application
	 */
	public function AddPreSentHeadersHandler (callable $handler, $priorityIndex = NULL) {
		return $this->setHandler($this->preSentHeadersHandlers, $handler, $priorityIndex);
	}

	/**
	 * @inheritDoc
	 * @param  CustomHandlerCallable $handler
	 * @param  int|NULL              $priorityIndex
	 * @return \MvcCore\Application
	 */
	public function AddPreSentBodyHandler (callable $handler, $priorityIndex = NULL) {
		return $this->setHandler($this->preSentBodyHandlers, $handler, $priorityIndex);
	}

	/**
	 * @inheritDoc
	 * @param  CustomHandlerCallable $handler
	 * @param  int|NULL              $priorityIndex
	 * @return \MvcCore\Application
	 */
	public function AddPostTerminateHandler (callable $handler, $priorityIndex = NULL) {
		return $this->setHandler($this->postTerminateHandlers, $handler, $priorityIndex);
	}
	
	/**
	 * @inheritDoc
	 * @param  CustomHandlerCallable $handler
	 * @param  int|NULL              $priorityIndex
	 * @return \MvcCore\Application
	 */
	public function AddPreSessionStartHandler (callable $handler, $priorityIndex = NULL) {
		return $this->setHandler($this->preSessionStartHandlers, $handler, $priorityIndex);
	}
	
	/**
	 * @inheritDoc
	 * @param  CustomHandlerCallable $handler
	 * @param  int|NULL              $priorityIndex
	 * @return \MvcCore\Application
	 */
	public function AddPostSessionStartHandler (callable $handler, $priorityIndex = NULL) {
		return $this->setHandler($this->postSessionStartHandlers, $handler, $priorityIndex);
	}

	/**
	 * @inheritDoc
	 * @param  CustomHandlerCallable $handler
	 * @param  int|NULL              $priorityIndex
	 * @return \MvcCore\Application
	 */
	public function AddCsrfErrorHandler (callable $handler, $priorityIndex = NULL) {
		return $this->setHandler($this->csrfErrorHandlers, $handler, $priorityIndex);
	}
}
