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
		if ($this->compiled === NULL) {
			$compiled = static::NOT_COMPILED;
			if (strlen(\Phar::running()) > 0) {
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
	public function GetAppDir () {
		if ($this->appDir === NULL) {
			if (!defined('MVCCORE_APP_ROOT_DIRNAME')) 
				define('MVCCORE_APP_ROOT_DIRNAME', 'App');
			$this->appDir = constant('MVCCORE_APP_ROOT_DIRNAME');
		}
		return $this->appDir;
	}
	
	/**
	 * @inheritDoc
	 * @return string
	 */
	public function GetDocRootDir () {
		if ($this->docRootDir === NULL) {
			if (!defined('MVCCORE_DOC_ROOT_DIRNAME')) 
				define('MVCCORE_DOC_ROOT_DIRNAME', 'www');
			$this->docRootDir = constant('MVCCORE_DOC_ROOT_DIRNAME');
		}
		return $this->docRootDir;
	}
	
	/**
	 * @inheritDoc
	 * @return string
	 */
	public function GetCliDir () {
		return $this->cliDir;
	}

	/**
	 * @inheritDoc
	 * @return string
	 */
	public function GetControllersDir () {
		return $this->controllersDir;
	}

	/**
	 * @inheritDoc
	 * @return string
	 */
	public function GetViewsDir () {
		return $this->viewsDir;
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
	 * @param  string $appDir
	 * @return \MvcCore\Application
	 */
	public function SetAppDir ($appDir) {
		$this->appDir = $appDir;
		return $this;
	}

	/**
	 * @inheritDoc
	 * @param  string $docRootDir
	 * @return \MvcCore\Application
	 */
	public function SetDocRootDir ($docRootDir) {
		$this->docRootDir = $docRootDir;
		return $this;
	}
	
	/**
	 * @inheritDoc
	 * @param  string $cliDir
	 * @return \MvcCore\Application
	 */
	public function SetCliDir ($cliDir) {
		$this->cliDir = $cliDir;
		return $this;
	}

	/**
	 * @inheritDoc
	 * @param  string $controllersDir
	 * @return \MvcCore\Application
	 */
	public function SetControllersDir ($controllersDir) {
		$this->controllersDir = $controllersDir;
		return $this;
	}

	/**
	 * @inheritDoc
	 * @param  string $viewsDir
	 * @return \MvcCore\Application
	 */
	public function SetViewsDir ($viewsDir) {
		$this->viewsDir = $viewsDir;
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
	public function AddPostTerminateHandler (callable $handler, $priorityIndex = NULL) {
		return $this->setHandler($this->postTerminateHandlers, $handler, $priorityIndex);
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
