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

namespace MvcCore\Controller;

/**
 * @mixin \MvcCore\Controller
 */
trait GettersSetters {

	/**
	 * @inheritDoc
	 * @param  string                               $name
	 * Parameter string name.
	 * @param  string|array{0:string,1:string}|bool $pregReplaceAllowedChars
	 * If `string` - list of regular expression characters to only keep,
	 * if `array` - `preg_replace()` pattern and reverse,
	 * if `FALSE`, raw value is returned.
	 * @param  mixed                                $ifNullValue
	 * Default value returned if given param name is null.
	 * @param  string|NULL                          $targetType
	 * Target type to retype param value or default if-null value. 
	 * If param is an array, every param item will be retyped into given target type.
	 * @return string|array<string>|int|array<int>|bool|array<bool>|array|mixed
	 */
	public function GetParam (
		$name = "",
		$pregReplaceAllowedChars = "a-zA-Z0-9_;, /\-\@\:",
		$ifNullValue = NULL,
		$targetType = NULL
	) {
		return $this->request->GetParam(
			$name, $pregReplaceAllowedChars, $ifNullValue, $targetType
		);
	}

	/**
	 * @inheritDoc
	 * @return \MvcCore\Application
	 */
	public function GetApplication () {
		return $this->application;
	}

	/**
	 * @inheritDoc
	 * @param  \MvcCore\Application $application
	 * @return \MvcCore\Controller
	 */
	public function SetApplication (\MvcCore\IApplication $application) {
		/** @var \MvcCore\Application $application */
		$this->application = $application;
		return $this;
	}

	/**
	 * @inheritDoc
	 * @return \MvcCore\Environment
	 */
	public function GetEnvironment() {
		return $this->environment;
	}

	/**
	 * @inheritDoc
	 * @param  \MvcCore\Environment $environment
	 * @return \MvcCore\Controller
	 */
	public function SetEnvironment (\MvcCore\IEnvironment $environment) {
		/** @var \MvcCore\Environment $environment */
		$this->environment = $environment;
		return $this;
	}

	/**
	 * @inheritDoc
	 * @return \MvcCore\Request
	 */
	public function GetRequest () {
		return $this->request;
	}

	/**
	 * @inheritDoc
	 * @param  \MvcCore\Request $request
	 * @return \MvcCore\Controller
	 */
	public function SetRequest (\MvcCore\IRequest $request) {
		/** @var \MvcCore\Request $request */
		$this->request = $request;
		$this->controllerName = ltrim($request->GetControllerName(), '/');
		$this->actionName = $request->GetActionName();
		$this->ajax = $request->IsAjax();
		return $this;
	}

	/**
	 * @inheritDoc
	 * @return string
	 */
	public function GetControllerName () {
		return $this->controllerName;
	}

	/**
	 * @inheritDoc
	 * @param  string $controllerName 
	 * @return \MvcCore\Controller
	 */
	public function SetControllerName ($controllerName) {
		$this->controllerName = $controllerName;
		return $this;
	}

	/**
	 * @inheritDoc
	 * @return string
	 */
	public function GetActionName () {
		return $this->actionName;
	}

	/**
	 * @inheritDoc
	 * @param  string $actionName
	 * @return \MvcCore\Controller
	 */
	public function SetActionName ($actionName) {
		$this->actionName = $actionName;
		return $this;
	}

	/**
	 * @inheritDoc
	 * @return \MvcCore\Response
	 */
	public function GetResponse () {
		return $this->response;
	}

	/**
	 * @inheritDoc
	 * @param \MvcCore\Response $response
	 * @return \MvcCore\Controller
	 */
	public function SetResponse (\MvcCore\IResponse $response) {
		/** @var \MvcCore\Response $response */
		$this->response = $response;
		return $this;
	}

	/**
	 * @inheritDoc
	 * @return \MvcCore\Router
	 */
	public function GetRouter () {
		return $this->router;
	}

	/**
	 * @inheritDoc
	 * @param  \MvcCore\Router $router
	 * @return \MvcCore\Controller
	 */
	public function SetRouter (\MvcCore\IRouter $router) {
		/** @var \MvcCore\Router $router */
		$this->router = $router;
		return $this;
	}

	/**
	 * @inheritDoc
	 * @return boolean
	 */
	public function IsAjax () {
		return $this->ajax;
	}
	
	/**
	 * @inheritDoc
	 * @param  boolean $ajax 
	 * @return \MvcCore\Controller
	 */
	public function SetIsAjax ($ajax) {
		$this->ajax = $ajax;
		return $this;
	}
	
	/**
	 * @inheritDoc
	 * @return int
	 */
	public function GetDispatchState () {
		return $this->dispatchState;
	}

	/**
	 * @inheritDoc
	 * @param  int $dispatchState
	 * @return \MvcCore\Controller
	 */
	public function SetDispatchState ($dispatchState) {
		$this->dispatchState = $dispatchState;
		return $this;
	}

	/**
	 * @inheritDoc
	 * @return \MvcCore\Model
	 */
	public function GetUser () {
		return $this->user;
	}

	/**
	 * @inheritDoc
	 * @param  \MvcCore\Model $user
	 * @return \MvcCore\Controller
	 */
	public function SetUser ($user) {
		$this->user = $user;
		return $this;
	}

	/**
	 * @inheritDoc
	 * @return \MvcCore\View|NULL
	 */
	public function GetView () {
		return $this->view;
	}

	/**
	 * @inheritDoc
	 * @param  \MvcCore\View $view
	 * @return \MvcCore\Controller
	 */
	public function SetView (\MvcCore\IView $view) {
		/** @var \MvcCore\View $view */
		$this->view = $view;
		return $this;
	}

	/**
	 * @inheritDoc
	 * @return int
	 */
	public function GetRenderMode () {
		return $this->renderMode;
	}

	/**
	 * @inheritDoc
	 * @param  int $renderMode
	 * @return \MvcCore\Controller
	 */
	public function SetRenderMode ($renderMode = \MvcCore\IView::RENDER_WITH_OB_FROM_ACTION_TO_LAYOUT) {
		$this->renderMode = $renderMode;
		return $this;
	}

	/**
	 * @inheritDoc
	 * @return string|NULL
	 */
	public function GetLayout () {
		return $this->layout;
	}

	/**
	 * @inheritDoc
	 * @param  string|NULL $layout
	 * @return \MvcCore\Controller
	 */
	public function SetLayout ($layout) {
		$this->layout = $layout;
		return $this;
	}

	/**
	 * @inheritDoc
	 * @return string|NULL
	 */
	public function GetViewScriptsPath () {
		return $this->viewScriptsPath;
	}

	/**
	 * @inheritDoc
	 * @param  string|NULL $viewScriptsPath
	 * @return \MvcCore\Controller
	 */
	public function SetViewScriptsPath ($viewScriptsPath = NULL) {
		$this->viewScriptsPath = $viewScriptsPath;
		return $this;
	}

	/**
	 * @inheritDoc
	 * @return bool
	 */
	public function GetViewEnabled () {
		return $this->viewEnabled;
	}

	/**
	 * @inheritDoc
	 * @param  bool $viewEnabled
	 * @return \MvcCore\Controller
	 */
	public function SetViewEnabled ($viewEnabled = TRUE) {
		$this->viewEnabled = $viewEnabled;
		return $this;
	}

	/**
	 * @inheritDoc
	 * @return \MvcCore\Controller|NULL
	 */
	public function GetParentController () {
		return $this->parentController;
	}

	/**
	 * @inheritDoc
	 * @param  \MvcCore\Controller|NULL $parentController
	 * @return \MvcCore\Controller
	 */
	public function SetParentController (/*\MvcCore\IController*/ $parentController = NULL) {
		if (
			$parentController !== NULL && 
			!($parentController instanceof \MvcCore\IController) // @phpstan-ignore-line
		)
			throw new \RuntimeException("Parent controller doesn't implement interface `\MvcCore\IController`.");
		/** @var \MvcCore\Controller $parentController */
		$this->parentController = $parentController;
		return $this;
	}

	/**
	 * @inheritDoc
	 * @return \MvcCore\Controller[]
	 */
	public function GetChildControllers () {
		return $this->childControllers;
	}

	/**
	 * @inheritDoc
	 * @param  \MvcCore\Controller[] $childControllers
	 * @return \MvcCore\Controller
	 */
	public function SetChildControllers (array $childControllers = []) {
		$this->childControllers = $childControllers;
		return $this;
	}

	/**
	 * @inheritDoc
	 * @param  string|int $index
	 * @return \MvcCore\Controller
	 */
	public function GetChildController ($index = NULL) {
		return $this->childControllers[$index];
	}

	/**
	 * @inheritDoc
	 * @param  string $appRootRelativePath Any config relative path from application root dir like `'~/App/website.ini'`.
	 * @return \MvcCore\Config|NULL
	 */
	public function GetConfig ($appRootRelativePath) {
		$configClass = $this->application->GetConfigClass();
		return $configClass::GetConfig($appRootRelativePath);
	}

	/**
	 * @inheritDoc
	 * @return \MvcCore\Config|NULL
	 */
	public function GetConfigSystem () {
		$configClass = $this->application->GetConfigClass();
		return $configClass::GetConfigSystem();
	}

	/**
	 * @inheritDoc
	 * @param  string              $controllerActionOrRouteName
	 * Should be `"Controller:Action"` combination or just any route name as custom specific string.
	 * @param  array<string,mixed> $params
	 * Optional, array with params, key is param name, value is param value.
	 * @return string
	 */
	public function Url ($controllerActionOrRouteName = 'Index:Index', array $params = []) {
		return $this->router->Url($controllerActionOrRouteName, $params);
	}

	/**
	 * @inheritDoc
	 * @param  string $path
	 * @return string
	 */
	public function AssetUrl ($path) {
		return $this->router->Url('Controller:Asset', ['path' => $path]);
	}
}
