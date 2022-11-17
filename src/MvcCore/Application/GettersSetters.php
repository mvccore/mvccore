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
 */
trait GettersSetters {

	/***********************************************************************************
	 *                        `\MvcCore\Application` - Getters                         *
	 ***********************************************************************************/

	/**
	 * @inheritDocs
	 * @param  string $propName 
	 * @return mixed
	 */
	public function __get ($propName) {
		if (isset($this->{$propName}))
			return $this->{$propName};
		return NULL;
	}

	/**
	 * @inheritDocs
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
	 * @inheritDocs
	 * @return int
	 */
	public function GetCsrfProtection () {
		return $this->csrfProtection;
	}

	/**
	 * @inheritDocs
	 * @return bool
	 */
	public function GetAttributesAnotations () {
		return $this->attributesAnotation;
	}

	/**
	 * @inheritDocs
	 * @return \MvcCore\Environment|string
	 */
	public function GetEnvironmentClass () {
		return $this->environmentClass;
	}

	/**
	 * @inheritDocs
	 * @return \MvcCore\Config|string
	 */
	public function GetConfigClass () {
		return $this->configClass;
	}

	/**
	 * @inheritDocs
	 * @return \MvcCore\Controller|string
	 */
	public function GetControllerClass () {
		return $this->controllerClass;
	}

	/**
	 * @inheritDocs
	 * @return \MvcCore\Debug|string
	 */
	public function GetDebugClass () {
		return $this->debugClass;
	}

	/**
	 * @inheritDocs
	 * @return \MvcCore\Request|string
	 */
	public function GetRequestClass () {
		return $this->requestClass;
	}

	/**
	 * @inheritDocs
	 * @return \MvcCore\Response|string
	 */
	public function GetResponseClass () {
		return $this->responseClass;
	}

	/**
	 * @inheritDocs
	 * @return \MvcCore\Route|string
	 */
	public function GetRouteClass () {
		return $this->routeClass;
	}

	/**
	 * @inheritDocs
	 * @return \MvcCore\Router|string
	 */
	public function GetRouterClass () {
		return $this->routerClass;
	}

	/**
	 * @inheritDocs
	 * @return \MvcCore\Session|string
	 */
	public function GetSessionClass () {
		return $this->sessionClass;
	}

	/**
	 * @inheritDocs
	 * @return \MvcCore\Tool|string
	 */
	public function GetToolClass () {
		return $this->toolClass;
	}

	/**
	 * @inheritDocs
	 * @return \MvcCore\View|string
	 */
	public function GetViewClass () {
		return $this->viewClass;
	}

	/**
	 * @inheritDocs
	 * @var \MvcCore\Config|NULL
	 */
	public function GetConfig () {
		if ($this->config === NULL) {
			$configClass = $this->configClass;
			$this->config = $configClass::GetConfigSystem();
		}
		return $this->config;
	}

	/**
	 * @inheritDocs
	 * @var \MvcCore\Environment
	 */
	public function GetEnvironment () {
		if ($this->environment === NULL) {
			$environmentClass = $this->environmentClass;
			$this->environment = $environmentClass::CreateInstance();
		}
		return $this->environment;
	}

	/**
	 * @inheritDocs
	 * @return \MvcCore\Controller
	 */
	public function GetController () {
		return $this->controller;
	}

	/**
	 * @inheritDocs
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
	 * @inheritDocs
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
	 * @inheritDocs
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
	 * @inheritDocs
	 * @return string
	 */
	public function GetAppDir () {
		return $this->appDir;
	}
	
	/**
	 * @inheritDocs
	 * @return string
	 */
	public function GetCliDir () {
		return $this->cliDir;
	}

	/**
	 * @inheritDocs
	 * @return string
	 */
	public function GetControllersDir () {
		return $this->controllersDir;
	}

	/**
	 * @inheritDocs
	 * @return string
	 */
	public function GetViewsDir () {
		return $this->viewsDir;
	}

	/**
	 * @inheritDocs
	 * @return \string[]
	 */
	public function GetDefaultControllerAndActionNames () {
		return [$this->defaultControllerName, $this->defaultControllerDefaultActionName];
	}

	/**
	 * @inheritDocs
	 * @return string
	 */
	public function GetDefaultControllerName () {
		return $this->defaultControllerName;
	}

	/**
	 * @inheritDocs
	 * @return string
	 */
	public function GetDefaultControllerErrorActionName () {
		return $this->defaultControllerErrorActionName;
	}

	/**
	 * @inheritDocs
	 * @return string
	 */
	public function GetDefaultControllerNotFoundActionName () {
		return $this->defaultControllerNotFoundActionName;
	}

	/**
	 * @inheritDocs
	 * @return bool
	 */
	public function GetTerminated () {
		return $this->terminated;
	}


	/***********************************************************************************
	 *                        `\MvcCore\Application` - Setters                         *
	 ***********************************************************************************/

	/**
	 * @inheritDocs
	 * @param  string $compiled
	 * @return \MvcCore\Application
	 */
	public function SetCompiled ($compiled) {
		$this->compiled = $compiled;
		return $this;
	}

	
	/**
	 * @inheritDocs
	 * @param  int $csrfProtection
	 * @return \MvcCore\Application
	 */
	public function SetCsrfProtection ($csrfProtection = \MvcCore\IApplication::CSRF_PROTECTION_COOKIE) {
		$this->csrfProtection = $csrfProtection;
		return $this;
	}

	/**
	 * @inheritDocs
	 * @param  bool $attributesAnotation
	 * @return \MvcCore\Application
	 */
	public function SetAttributesAnotations ($attributesAnotation = TRUE) {
		$this->attributesAnotation = $attributesAnotation;
		return $this;
	}
	
	/**
	 * @inheritDocs
	 * @param  string $environmentClass
	 * @return \MvcCore\Application
	 */
	public function SetEnvironmentClass ($environmentClass) {
		return $this->setCoreClass($environmentClass, 'environmentClass', 'MvcCore\IEnvironment');
	}

	/**
	 * @inheritDocs
	 * @param  string $configClass
	 * @return \MvcCore\Application
	 */
	public function SetConfigClass ($configClass) {
		return $this->setCoreClass($configClass, 'configClass', 'MvcCore\IConfig');
	}

	/**
	 * @inheritDocs
	 * @param  string $controllerClass
	 * @return \MvcCore\Application
	 */
	public function SetControllerClass ($controllerClass) {
		return $this->setCoreClass($controllerClass, 'controllerClass', 'MvcCore\IController');
	}

	/**
	 * @inheritDocs
	 * @param  string $debugClass
	 * @return \MvcCore\Application
	 */
	public function SetDebugClass ($debugClass) {
		return $this->setCoreClass($debugClass, 'debugClass', 'MvcCore\IDebug');
	}

	/**
	 * @inheritDocs
	 * @param  string $requestClass
	 * @return \MvcCore\Application
	 */
	public function SetRequestClass ($requestClass) {
		return $this->setCoreClass($requestClass, 'requestClass', 'MvcCore\IRequest');
	}

	/**
	 * @inheritDocs
	 * @param  string $responseClass
	 * @return \MvcCore\Application
	 */
	public function SetResponseClass ($responseClass) {
		return $this->setCoreClass($responseClass, 'responseClass', 'MvcCore\IResponse');
	}

	/**
	 * @inheritDocs
	 * @param  string $routeClass
	 * @return \MvcCore\Application
	 */
	public function SetRouteClass ($routeClass) {
		return $this->setCoreClass($routeClass, 'routeClass', 'MvcCore\IRoute');
	}

	/**
	 * @inheritDocs
	 * @param  string $routerClass
	 * @return \MvcCore\Application
	 */
	public function SetRouterClass ($routerClass) {
		return $this->setCoreClass($routerClass, 'routerClass', 'MvcCore\IRouter');
	}

	/**
	 * @inheritDocs
	 * @param  string $sessionClass
	 * @return \MvcCore\Application
	 */
	public function SetSessionClass ($sessionClass) {
		return $this->setCoreClass($sessionClass, 'sessionClass', 'MvcCore\ISession');
	}

	/**
	 * @inheritDocs
	 * @param  string $toolClass
	 * @return \MvcCore\Application
	 */
	public function SetToolClass ($toolClass) {
		return $this->setCoreClass($toolClass, 'toolClass', 'MvcCore\ITool');
	}

	/**
	 * @inheritDocs
	 * @param  string $viewClass
	 * @return \MvcCore\Application
	 */
	public function SetViewClass ($viewClass) {
		return $this->setCoreClass($viewClass, 'viewClass', 'MvcCore\IView');
	}


	/**
	 * @inheritDocs
	 * @param  \MvcCore\Controller $controller
	 * @return \MvcCore\Application
	 */
	public function SetController (\MvcCore\IController $controller) {
		$this->controller = $controller;
		return $this;
	}


	/**
	 * @inheritDocs
	 * @param  string $appDir
	 * @return \MvcCore\Application
	 */
	public function SetAppDir ($appDir) {
		$this->appDir = $appDir;
		return $this;
	}
	
	/**
	 * @inheritDocs
	 * @param  string $cliDir
	 * @return \MvcCore\Application
	 */
	public function SetCliDir ($cliDir) {
		$this->cliDir = $cliDir;
		return $this;
	}

	/**
	 * @inheritDocs
	 * @param  string $controllersDir
	 * @return \MvcCore\Application
	 */
	public function SetControllersDir ($controllersDir) {
		$this->controllersDir = $controllersDir;
		return $this;
	}

	/**
	 * @inheritDocs
	 * @param  string $viewsDir
	 * @return \MvcCore\Application
	 */
	public function SetViewsDir ($viewsDir) {
		$this->viewsDir = $viewsDir;
		return $this;
	}

	/**
	 * @inheritDocs
	 * @param  string $defaultControllerName
	 * @return \MvcCore\Application
	 */
	public function SetDefaultControllerName ($defaultControllerName) {
		$this->defaultControllerName = $defaultControllerName;
		return $this;
	}

	/**
	 * @inheritDocs
	 * @param  string $defaultActionName
	 * @return \MvcCore\Application
	 */
	public function SetDefaultControllerDefaultActionName ($defaultActionName) {
		$this->defaultControllerDefaultActionName = $defaultActionName;
		return $this;
	}

	/**
	 * @inheritDocs
	 * @param  string $defaultControllerErrorActionName
	 * @return \MvcCore\Application
	 */
	public function SetDefaultControllerErrorActionName ($defaultControllerErrorActionName) {
		$this->defaultControllerErrorActionName = $defaultControllerErrorActionName;
		return $this;
	}

	/**
	 * @inheritDocs
	 * @param  string $defaultControllerNotFoundActionName
	 * @return \MvcCore\Application
	 */
	public function SetDefaultControllerNotFoundActionName ($defaultControllerNotFoundActionName) {
		$this->defaultControllerNotFoundActionName = $defaultControllerNotFoundActionName;
		return $this;
	}

	/**
	 * @inheritDocs`
	 * @param  callable $handler
	 * @param  int|NULL $priorityIndex
	 * @return \MvcCore\Application
	 */
	public function AddPreRouteHandler (callable $handler, $priorityIndex = NULL) {
		if (!is_callable($handler))
			throw new \InvalidArgumentException(
				"[".get_class()."] Pre route handler is not callable (handler: {$handler}, priorityIndex: {$priorityIndex})."
			);
		return $this->setHandler($this->preRouteHandlers, $handler, $priorityIndex);
	}

	/**
	 * @inheritDocs
	 * @param  callable $handler
	 * @param  int|NULL $priorityIndex
	 * @return \MvcCore\Application
	 */
	public function AddPostRouteHandler (callable $handler, $priorityIndex = NULL) {
		if (!is_callable($handler))
			throw new \InvalidArgumentException(
				"[".get_class()."] Post route handler is not callable (handler: {$handler}, priorityIndex: {$priorityIndex})."
			);
		return $this->setHandler($this->postRouteHandlers, $handler, $priorityIndex);
	}

	/**
	 * @inheritDocs
	 * @param  callable $handler
	 * @param  int|NULL $priorityIndex
	 * @return \MvcCore\Application
	 */
	public function AddPreSentHeadersHandler (callable $handler, $priorityIndex = NULL) {
		if (!is_callable($handler))
			throw new \InvalidArgumentException(
				"[".get_class()."] Pre sent headers handler is not callable (handler: {$handler}, priorityIndex: {$priorityIndex})."
			);
		return $this->setHandler($this->preSentHeadersHandlers, $handler, $priorityIndex);
	}

	/**
	 * @inheritDocs
	 * @param  callable $handler
	 * @param  int|NULL $priorityIndex
	 * @return \MvcCore\Application
	 */
	public function AddPreSentBodyHandler (callable $handler, $priorityIndex = NULL) {
		if (!is_callable($handler))
			throw new \InvalidArgumentException(
				"[".get_class()."] Pre sent body handler is not callable (handler: {$handler}, priorityIndex: {$priorityIndex})."
			);
		return $this->setHandler($this->preSentBodyHandlers, $handler, $priorityIndex);
	}

	/**
	 * @inheritDocs
	 * @param  callable $handler
	 * @param  int|NULL $priorityIndex
	 * @return \MvcCore\Application
	 */
	public function AddPreDispatchHandler (callable $handler, $priorityIndex = NULL) {
		if (!is_callable($handler))
			throw new \InvalidArgumentException(
				"[".get_class()."] Pre dispatch handler is not callable (handler: {$handler}, priorityIndex: {$priorityIndex})."
			);
		return $this->setHandler($this->preDispatchHandlers, $handler, $priorityIndex);
	}

	/**
	 * @inheritDocs
	 * @param  callable $handler
	 * @param  int|NULL $priorityIndex
	 * @return \MvcCore\Application
	 */
	public function AddPostDispatchHandler (callable $handler, $priorityIndex = NULL) {
		if (!is_callable($handler))
			throw new \InvalidArgumentException(
				"[".get_class()."] Post dispatch handler is not callable (handler: {$handler}, priorityIndex: {$priorityIndex})."
			);
		return $this->setHandler($this->postDispatchHandlers, $handler, $priorityIndex);
	}

	/**
	 * @inheritDocs
	 * @param  callable $handler
	 * @param  int|NULL $priorityIndex
	 * @return \MvcCore\Application
	 */
	public function AddPostTerminateHandler (callable $handler, $priorityIndex = NULL) {
		if (!is_callable($handler))
			throw new \InvalidArgumentException(
				"[".get_class()."] Post terminate handler is not callable (handler: {$handler}, priorityIndex: {$priorityIndex})."
			);
		return $this->setHandler($this->postTerminateHandlers, $handler, $priorityIndex);
	}

	/**
	 * @inheritDocs
	 * @param  callable $handler
	 * @param  int|NULL $priorityIndex
	 * @return \MvcCore\Application
	 */
	public function AddCsrfErrorHandler (callable $handler, $priorityIndex = NULL) {
		if (!is_callable($handler))
			throw new \InvalidArgumentException(
				"[".get_class()."] CSRF error handler is not callable (handler: {$handler}, priorityIndex: {$priorityIndex})."
			);
		return $this->setHandler($this->csrfErrorHandlers, $handler, $priorityIndex);
	}
}
