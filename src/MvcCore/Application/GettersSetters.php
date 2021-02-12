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
 */
trait GettersSetters {

	/***********************************************************************************
	 *                        `\MvcCore\Application` - Getters                         *
	 ***********************************************************************************/

	/**
	 * @inheritDocs
	 * @return string
	 */
	public function GetCompiled () {
		/** @var $this \MvcCore\Application */
		if ($this->compiled === NULL) {
			$compiled = static::NOT_COMPILED;
			if (strpos(__FILE__, 'phar://') === 0) {
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
	 * @return \MvcCore\Environment|string
	 */
	public function GetEnvironmentClass () {
		/** @var $this \MvcCore\Application */
		return $this->environmentClass;
	}

	/**
	 * @inheritDocs
	 * @return \MvcCore\Config|string
	 */
	public function GetConfigClass () {
		/** @var $this \MvcCore\Application */
		return $this->configClass;
	}

	/**
	 * @inheritDocs
	 * @return \MvcCore\Controller|string
	 */
	public function GetControllerClass () {
		/** @var $this \MvcCore\Application */
		return $this->controllerClass;
	}

	/**
	 * @inheritDocs
	 * @return \MvcCore\Debug|string
	 */
	public function GetDebugClass () {
		/** @var $this \MvcCore\Application */
		return $this->debugClass;
	}

	/**
	 * @inheritDocs
	 * @return \MvcCore\Request|string
	 */
	public function GetRequestClass () {
		/** @var $this \MvcCore\Application */
		return $this->requestClass;
	}

	/**
	 * @inheritDocs
	 * @return \MvcCore\Response|string
	 */
	public function GetResponseClass () {
		/** @var $this \MvcCore\Application */
		return $this->responseClass;
	}

	/**
	 * @inheritDocs
	 * @return \MvcCore\Route|string
	 */
	public function GetRouteClass () {
		/** @var $this \MvcCore\Application */
		return $this->routeClass;
	}

	/**
	 * @inheritDocs
	 * @return \MvcCore\Router|string
	 */
	public function GetRouterClass () {
		/** @var $this \MvcCore\Application */
		return $this->routerClass;
	}

	/**
	 * @inheritDocs
	 * @return \MvcCore\Session|string
	 */
	public function GetSessionClass () {
		/** @var $this \MvcCore\Application */
		return $this->sessionClass;
	}

	/**
	 * @inheritDocs
	 * @return \MvcCore\Tool|string
	 */
	public function GetToolClass () {
		/** @var $this \MvcCore\Application */
		return $this->toolClass;
	}

	/**
	 * @inheritDocs
	 * @return \MvcCore\View|string
	 */
	public function GetViewClass () {
		/** @var $this \MvcCore\Application */
		return $this->viewClass;
	}

	/**
	 * @inheritDocs
	 * @var \MvcCore\Environment
	 */
	public function GetEnvironment () {
		/** @var $this \MvcCore\Application */
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
		/** @var $this \MvcCore\Application */
		return $this->controller;
	}

	/**
	 * @inheritDocs
	 * @return \MvcCore\Request
	 */
	public function GetRequest () {
		/** @var $this \MvcCore\Application */
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
		/** @var $this \MvcCore\Application */
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
		/** @var $this \MvcCore\Application */
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
		/** @var $this \MvcCore\Application */
		return $this->appDir;
	}

	/**
	 * @inheritDocs
	 * @return string
	 */
	public function GetControllersDir () {
		/** @var $this \MvcCore\Application */
		return $this->controllersDir;
	}

	/**
	 * @inheritDocs
	 * @return string
	 */
	public function GetViewsDir () {
		/** @var $this \MvcCore\Application */
		return $this->viewsDir;
	}

	/**
	 * @inheritDocs
	 * @return \string[]
	 */
	public function GetDefaultControllerAndActionNames () {
		/** @var $this \MvcCore\Application */
		return [$this->defaultControllerName, $this->defaultControllerDefaultActionName];
	}


	/***********************************************************************************
	 *                        `\MvcCore\Application` - Setters                         *
	 ***********************************************************************************/

	/**
	 * @inheritDocs
	 * @param  string $compiled
	 * @return \MvcCore\Application
	 */
	public function SetCompiled ($compiled = '') {
		/** @var $this \MvcCore\Application */
		$this->compiled = $compiled;
		return $this;
	}


	/**
	 * @inheritDocs
	 * @param  string $environmentClass
	 * @return \MvcCore\Application
	 */
	public function SetEnvironmentClass ($environmentClass) {
		/** @var $this \MvcCore\Application */
		return $this->setCoreClass($environmentClass, 'environmentClass', 'MvcCore\IEnvironment');
	}

	/**
	 * @inheritDocs
	 * @param  string $configClass
	 * @return \MvcCore\Application
	 */
	public function SetConfigClass ($configClass) {
		/** @var $this \MvcCore\Application */
		return $this->setCoreClass($configClass, 'configClass', 'MvcCore\IConfig');
	}

	/**
	 * @inheritDocs
	 * @param  string $controllerClass
	 * @return \MvcCore\Application
	 */
	public function SetControllerClass ($controllerClass) {
		/** @var $this \MvcCore\Application */
		return $this->setCoreClass($controllerClass, 'controllerClass', 'MvcCore\IController');
	}

	/**
	 * @inheritDocs
	 * @param  string $debugClass
	 * @return \MvcCore\Application
	 */
	public function SetDebugClass ($debugClass) {
		/** @var $this \MvcCore\Application */
		return $this->setCoreClass($debugClass, 'debugClass', 'MvcCore\IDebug');
	}

	/**
	 * @inheritDocs
	 * @param  string $requestClass
	 * @return \MvcCore\Application
	 */
	public function SetRequestClass ($requestClass) {
		/** @var $this \MvcCore\Application */
		return $this->setCoreClass($requestClass, 'requestClass', 'MvcCore\IRequest');
	}

	/**
	 * @inheritDocs
	 * @param  string $responseClass
	 * @return \MvcCore\Application
	 */
	public function SetResponseClass ($responseClass) {
		/** @var $this \MvcCore\Application */
		return $this->setCoreClass($responseClass, 'responseClass', 'MvcCore\IResponse');
	}

	/**
	 * @inheritDocs
	 * @param  string $routeClass
	 * @return \MvcCore\Application
	 */
	public function SetRouteClass ($routeClass) {
		/** @var $this \MvcCore\Application */
		return $this->setCoreClass($routeClass, 'routeClass', 'MvcCore\IRoute');
	}

	/**
	 * @inheritDocs
	 * @param  string $routerClass
	 * @return \MvcCore\Application
	 */
	public function SetRouterClass ($routerClass) {
		/** @var $this \MvcCore\Application */
		return $this->setCoreClass($routerClass, 'routerClass', 'MvcCore\IRouter');
	}

	/**
	 * @inheritDocs
	 * @param  string $sessionClass
	 * @return \MvcCore\Application
	 */
	public function SetSessionClass ($sessionClass) {
		/** @var $this \MvcCore\Application */
		return $this->setCoreClass($sessionClass, 'sessionClass', 'MvcCore\ISession');
	}

	/**
	 * @inheritDocs
	 * @param  string $toolClass
	 * @return \MvcCore\Application
	 */
	public function SetToolClass ($toolClass) {
		/** @var $this \MvcCore\Application */
		return $this->setCoreClass($toolClass, 'toolClass', 'MvcCore\ITool');
	}

	/**
	 * @inheritDocs
	 * @param  string $viewClass
	 * @return \MvcCore\Application
	 */
	public function SetViewClass ($viewClass) {
		/** @var $this \MvcCore\Application */
		return $this->setCoreClass($viewClass, 'viewClass', 'MvcCore\IView');
	}


	/**
	 * @inheritDocs
	 * @param  \MvcCore\Controller $controller
	 * @return \MvcCore\Application
	 */
	public function SetController (\MvcCore\IController $controller) {
		/** @var $this \MvcCore\Application */
		$this->controller = $controller;
		return $this;
	}


	/**
	 * @inheritDocs
	 * @param  string $appDir
	 * @return \MvcCore\Application
	 */
	public function SetAppDir ($appDir) {
		/** @var $this \MvcCore\Application */
		$this->appDir = $appDir;
		return $this;
	}

	/**
	 * @inheritDocs
	 * @param  string $controllersDir
	 * @return \MvcCore\Application
	 */
	public function SetControllersDir ($controllersDir) {
		/** @var $this \MvcCore\Application */
		$this->controllersDir = $controllersDir;
		return $this;
	}

	/**
	 * @inheritDocs
	 * @param  string $viewsDir
	 * @return \MvcCore\Application
	 */
	public function SetViewsDir ($viewsDir) {
		/** @var $this \MvcCore\Application */
		$this->viewsDir = $viewsDir;
		return $this;
	}

	/**
	 * @inheritDocs
	 * @param  string $defaultControllerName
	 * @return \MvcCore\Application
	 */
	public function SetDefaultControllerName ($defaultControllerName) {
		/** @var $this \MvcCore\Application */
		$this->defaultControllerName = $defaultControllerName;
		return $this;
	}

	/**
	 * @inheritDocs
	 * @param  string $defaultActionName
	 * @return \MvcCore\Application
	 */
	public function SetDefaultControllerDefaultActionName ($defaultActionName) {
		/** @var $this \MvcCore\Application */
		$this->defaultControllerDefaultActionName = $defaultActionName;
		return $this;
	}

	/**
	 * @inheritDocs
	 * @param  string $defaultControllerErrorActionName
	 * @return \MvcCore\Application
	 */
	public function SetDefaultControllerErrorActionName ($defaultControllerErrorActionName) {
		/** @var $this \MvcCore\Application */
		$this->defaultControllerErrorActionName = $defaultControllerErrorActionName;
		return $this;
	}

	/**
	 * @inheritDocs
	 * @param  string $defaultControllerNotFoundActionName
	 * @return \MvcCore\Application
	 */
	public function SetDefaultControllerNotFoundActionName ($defaultControllerNotFoundActionName) {
		/** @var $this \MvcCore\Application */
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
		/** @var $this \MvcCore\Application */
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
		/** @var $this \MvcCore\Application */
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
	public function AddPreDispatchHandler (callable $handler, $priorityIndex = NULL) {
		/** @var $this \MvcCore\Application */
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
		/** @var $this \MvcCore\Application */
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
		/** @var $this \MvcCore\Application */
		if (!is_callable($handler))
			throw new \InvalidArgumentException(
				"[".get_class()."] Post terminate handler is not callable (handler: {$handler}, priorityIndex: {$priorityIndex})."
			);
		return $this->setHandler($this->postTerminateHandlers, $handler, $priorityIndex);
	}
}
