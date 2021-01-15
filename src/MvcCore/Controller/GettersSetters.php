<?php

/**
 * MvcCore
 *
 * This source file is subject to the BSD 3 License
 * For the full copyright and license information, please view
 * the LICENSE.md file that are distributed with this source code.
 *
 * @copyright	Copyright (c) 2016 Tom Flidr (https://github.com/mvccore)
 * @license  https://mvccore.github.io/docs/mvccore/5.0.0/LICENCE.md
 */

namespace MvcCore\Controller;

trait GettersSetters {

	/**
	 * @inheritDocs
	 * @param string $name Parameter string name.
	 * @param string|array|bool $pregReplaceAllowedChars If String - list of regular expression characters to only keep, if array - `preg_replace()` pattern and reverse, if `FALSE`, raw value is returned.
	 * @param mixed $ifNullValue Default value returned if given param name is null.
	 * @param string $targetType Target type to retype param value or default if-null value. If param is an array, every param item will be retyped into given target type.
	 * @return string|\string[]|int|\int[]|bool|\bool[]|array|mixed
	 */
	public function GetParam (
		$name = "",
		$pregReplaceAllowedChars = "a-zA-Z0-9_;, /\-\@\:",
		$ifNullValue = NULL,
		$targetType = NULL
	) {
		/** @var $this \MvcCore\Controller */
		return $this->request->GetParam(
			$name, $pregReplaceAllowedChars, $ifNullValue, $targetType
		);
	}

	/**
	 * @inheritDocs
	 * @return \MvcCore\Application
	 */
	public function GetApplication () {
		/** @var $this \MvcCore\Controller */
		return $this->application;
	}

	/**
	 * @inheritDocs
	 * @param \MvcCore\Application $application
	 * @return \MvcCore\Controller
	 */
	public function SetApplication (\MvcCore\IApplication $application) {
		/** @var $this \MvcCore\Controller */
		/** @var $application \MvcCore\Application */
		$this->application = $application;
		return $this;
	}

	/**
	 * @inheritDocs
	 * @return \MvcCore\Environment
	 */
	public function GetEnvironment() {
		/** @var $this \MvcCore\Controller */
		return $this->environment;
	}

	/**
	 * @inheritDocs
	 * @param \MvcCore\Environment $environment
	 * @return \MvcCore\Controller
	 */
	public function SetEnvironment (\MvcCore\IEnvironment $environment) {
		/** @var $this \MvcCore\Controller */
		/** @var $environment \MvcCore\Environment */
		$this->environment = $environment;
		return $this;
	}

	/**
	 * @inheritDocs
	 * @return \MvcCore\Request
	 */
	public function GetRequest () {
		/** @var $this \MvcCore\Controller */
		return $this->request;
	}

	/**
	 * @inheritDocs
	 * @param \MvcCore\Request $request
	 * @return \MvcCore\Controller
	 */
	public function SetRequest (\MvcCore\IRequest $request) {
		/** @var $this \MvcCore\Controller */
		/** @var $request \MvcCore\Request */
		$this->request = $request;
		$this->controllerName = ltrim($request->GetControllerName(), '/');
		$this->actionName = $request->GetActionName();
		$this->ajax = $request->IsAjax();
		return $this;
	}

	/**
	 * @inheritDocs
	 * @return string
	 */
	public function GetControllerName () {
		/** @var $this \MvcCore\Controller */
		return $this->controllerName;
	}

	/**
	 * @inheritDocs
	 * @param string $controllerName 
	 * @return \MvcCore\Controller
	 */
	public function SetControllerName ($controllerName) {
		/** @var $this \MvcCore\Controller */
		$this->controllerName = $controllerName;
		return $this;
	}

	/**
	 * @inheritDocs
	 * @return string
	 */
	public function GetActionName () {
		/** @var $this \MvcCore\Controller */
		return $this->actionName;
	}

	/**
	 * @inheritDocs
	 * @param string $actionName
	 * @return \MvcCore\Controller
	 */
	public function SetActionName ($actionName) {
		/** @var $this \MvcCore\Controller */
		$this->actionName = $actionName;
		return $this;
	}

	/**
	 * @inheritDocs
	 * @return \MvcCore\Response
	 */
	public function GetResponse () {
		/** @var $this \MvcCore\Controller */
		return $this->response;
	}

	/**
	 * @inheritDocs
	 * @param \MvcCore\Response $response
	 * @return \MvcCore\Controller
	 */
	public function SetResponse (\MvcCore\IResponse $response) {
		/** @var $this \MvcCore\Controller */
		/** @var $response \MvcCore\Response */
		$this->response = $response;
		return $this;
	}

	/**
	 * @inheritDocs
	 * @return \MvcCore\Router
	 */
	public function GetRouter () {
		/** @var $this \MvcCore\Controller */
		return $this->router;
	}

	/**
	 * @inheritDocs
	 * @param \MvcCore\Router $router
	 * @return \MvcCore\Controller
	 */
	public function SetRouter (\MvcCore\IRouter $router) {
		/** @var $this \MvcCore\Controller */
		/** @var $router \MvcCore\Router */
		$this->router = $router;
		return $this;
	}

	/**
	 * @inheritDocs
	 * @return boolean
	 */
	public function IsAjax () {
		/** @var $this \MvcCore\Controller */
		return $this->ajax;
	}
	
	/**
	 * @inheritDocs
	 * @param boolean $ajax 
	 * @return \MvcCore\Controller
	 */
	public function SetIsAjax ($ajax) {
		/** @var $this \MvcCore\Controller */
		$this->ajax = $ajax;
		return $this;
	}

	/**
	 * @inheritDocs
	 * @return \MvcCore\Model
	 */
	public function GetUser () {
		/** @var $this \MvcCore\Controller */
		return $this->user;
	}

	/**
	 * @inheritDocs
	 * @param \MvcCore\Model $user
	 * @return \MvcCore\Controller
	 */
	public function SetUser ($user) {
		/** @var $this \MvcCore\Controller */
		$this->user = $user;
		return $this;
	}

	/**
	 * @inheritDocs
	 * @return \MvcCore\View|NULL
	 */
	public function GetView () {
		/** @var $this \MvcCore\Controller */
		return $this->view;
	}

	/**
	 * @inheritDocs
	 * @param \MvcCore\View $view
	 * @return \MvcCore\Controller
	 */
	public function SetView (\MvcCore\IView $view) {
		/** @var $this \MvcCore\Controller */
		/** @var $view \MvcCore\View */
		$this->view = $view;
		return $this;
	}

	/**
	 * @inheritDocs
	 * @return int
	 */
	public function GetRenderMode () {
		/** @var $this \MvcCore\Controller */
		return $this->renderMode;
	}

	/**
	 * @inheritDocs
	 * @param int $renderMode
	 * @return \MvcCore\Controller
	 */
	public function SetRenderMode ($renderMode = \MvcCore\IView::RENDER_WITH_OB_FROM_ACTION_TO_LAYOUT) {
		/** @var $this \MvcCore\Controller */
		$this->renderMode = $renderMode;
		return $this;
	}

	/**
	 * @inheritDocs
	 * @return string
	 */
	public function GetLayout () {
		/** @var $this \MvcCore\Controller */
		return $this->layout;
	}

	/**
	 * @inheritDocs
	 * @param string $layout
	 * @return \MvcCore\Controller
	 */
	public function SetLayout ($layout = '') {
		/** @var $this \MvcCore\Controller */
		$this->layout = $layout;
		return $this;
	}

	/**
	 * @inheritDocs
	 * @return string|NULL
	 */
	public function GetViewScriptsPath () {
		/** @var $this \MvcCore\Controller */
		return $this->viewScriptsPath;
	}

	/**
	 * @inheritDocs
	 * @param string|NULL $viewScriptsPath
	 * @return \MvcCore\Controller
	 */
	public function SetViewScriptsPath ($viewScriptsPath = NULL) {
		/** @var $this \MvcCore\Controller */
		$this->viewScriptsPath = $viewScriptsPath;
		return $this;
	}

	/**
	 * @inheritDocs
	 * @return bool
	 */
	public function GetViewEnabled () {
		/** @var $this \MvcCore\Controller */
		return $this->viewEnabled;
	}

	/**
	 * @inheritDocs
	 * @return \MvcCore\Controller
	 */
	public function SetViewEnabled ($viewEnabled = TRUE) {
		/** @var $this \MvcCore\Controller */
		$this->viewEnabled = $viewEnabled;
		return $this;
	}

	/**
	 * @inheritDocs
	 * @return \MvcCore\Controller|NULL
	 */
	public function GetParentController () {
		/** @var $this \MvcCore\Controller */
		return $this->parentController;
	}

	/**
	 * @inheritDocs
	 * @param \MvcCore\Controller|NULL $parentController
	 * @return \MvcCore\Controller
	 */
	public function SetParentController (\MvcCore\IController $parentController = NULL) {
		/** @var $this \MvcCore\Controller */
		/** @var $parentController \MvcCore\Controller */
		$this->parentController = $parentController;
		return $this;
	}

	/**
	 * @inheritDocs
	 * @return \MvcCore\Controller[]
	 */
	public function GetChildControllers () {
		/** @var $this \MvcCore\Controller */
		return $this->childControllers;
	}

	/**
	 * @inheritDocs
	 * @param \MvcCore\Controller[] $childControllers
	 * @return \MvcCore\Controller
	 */
	public function SetChildControllers (array $childControllers = []) {
		/** @var $this \MvcCore\Controller */
		$this->childControllers = $childControllers;
		return $this;
	}

	/**
	 * @inheritDocs
	 * @param string|int $index
	 * @return \MvcCore\Controller
	 */
	public function GetChildController ($index = NULL) {
		/** @var $this \MvcCore\Controller */
		return $this->childControllers[$index];
	}

	/**
	 * @inheritDocs
	 * @param string $appRootRelativePath Any config relative path like `'/%appPath%/website.ini'`.
	 * @return \MvcCore\Config|NULL
	 */
	public function GetConfig ($appRootRelativePath) {
		/** @var $this \MvcCore\Controller */
		$configClass = $this->application->GetConfigClass();
		return $configClass::GetConfig($appRootRelativePath);
	}

	/**
	 * @inheritDocs
	 * @return \MvcCore\Config|NULL
	 */
	public function GetSystemConfig () {
		/** @var $this \MvcCore\Controller */
		$configClass = $this->application->GetConfigClass();
		return $configClass::GetSystem();
	}

	/**
	 * @inheritDocs
	 * @param string $controllerActionOrRouteName	Should be `"Controller:Action"` combination or just any route name as custom specific string.
	 * @param array  $params						Optional, array with params, key is param name, value is param value.
	 * @return string
	 */
	public function Url ($controllerActionOrRouteName = 'Index:Index', array $params = []) {
		/** @var $this \MvcCore\Controller */
		return $this->router->Url($controllerActionOrRouteName, $params);
	}

	/**
	 * @inheritDocs
	 * @param string $path
	 * @return string
	 */
	public function AssetUrl ($path = '') {
		/** @var $this \MvcCore\Controller */
		return $this->router->Url('Controller:Asset', ['path' => $path]);
	}
}
