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

trait Dispatching {

	/**
	 * @inheritDocs
	 * @return \MvcCore\Controller
	 */
	public static function CreateInstance () {
		/** @var $instance \MvcCore\Controller */
		$instance = new static();
		self::$allControllers[spl_object_hash($instance)] = $instance;
		return $instance;
	}

	/**
	 * @inheritDocs
	 * @return \MvcCore\Controller|NULL
	 */
	public static function GetCallerControllerInstance () {
		$result = NULL;
		$backtraceItems = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS);
		if (count($backtraceItems) < 3) return $result;
		$calledClass = get_called_class();
		foreach ($backtraceItems as $backtraceItem) {
			if (!isset($backtraceItem['object']) || !$backtraceItem['object']) continue;
			$object = $backtraceItem['object'];
			$class = $backtraceItem['class'];
			if (
				$object instanceof \MvcCore\IController &&
				$class !== $calledClass
			) {
				$result = $object;
				break;
			}
		}
		return $result;
	}

	/**
	 * @inheritDocs
	 * @param  string $actionName PHP code action name in PascalCase.
	 *                            This value is used to call your desired function
	 *                            in controller without any change.
	 * @return void
	 */
	public function Dispatch ($actionName = "IndexAction") {
		/** @var $this \MvcCore\Controller */

		// \MvcCore\Debug::Timer('dispatch');
		$actionNameStart = $this->actionName;

		// Call `Init()` method only if dispatch state is not initialized yet:
		if ($this->dispatchState < \MvcCore\IController::DISPATCH_STATE_INITIALIZED)
			$this->Init();
		// If terminated or redirected inside `Init()` method:
		if ($this->dispatchState == \MvcCore\IController::DISPATCH_STATE_TERMINATED) 
			return;
		// For cases somebody forget to call parent `Init()`:
		if ($this->dispatchState < \MvcCore\IController::DISPATCH_STATE_INITIALIZED) 
			$this->dispatchState = \MvcCore\IController::DISPATCH_STATE_INITIALIZED;
		// \MvcCore\Debug::Timer('dispatch');

		// Call `PreDispatch()` method only if dispatch state is not pre-dispatched yet:
		if ($this->dispatchState < \MvcCore\IController::DISPATCH_STATE_PRE_DISPATCHED)
			$this->PreDispatch();
		// If terminated or redirected inside `PreDispatch()` method:
		if ($this->dispatchState == \MvcCore\IController::DISPATCH_STATE_TERMINATED) 
			return;
		// For cases somebody forget to call parent `PreDispatch()`:
		if ($this->dispatchState < \MvcCore\IController::DISPATCH_STATE_PRE_DISPATCHED) 
			$this->dispatchState = \MvcCore\IController::DISPATCH_STATE_PRE_DISPATCHED;
		// \MvcCore\Debug::Timer('dispatch');


		if ($this->actionName !== $actionNameStart) {
			$toolClass = $this->application->GetToolClass();
			$actionName = $toolClass::GetPascalCaseFromDashed($this->actionName) . 'Action';
		}
		// Call action method only if dispatch state is not action-executed yet:
		if (
			$this->dispatchState < \MvcCore\IController::DISPATCH_STATE_ACTION_EXECUTED && 
			method_exists($this, $actionName)
		)
			$this->{$actionName}();
		// If terminated or redirected inside action method:
		if ($this->dispatchState == \MvcCore\IController::DISPATCH_STATE_TERMINATED) 
			return;
		// For cases somebody forget to call parent action method:
		if ($this->dispatchState < \MvcCore\IController::DISPATCH_STATE_ACTION_EXECUTED) 
			$this->dispatchState = \MvcCore\IController::DISPATCH_STATE_ACTION_EXECUTED;
		// \MvcCore\Debug::Timer('dispatch');
		
		// Call `Render()` method only if dispatch state is not rendered yet:
		if ($this->viewEnabled && $this->dispatchState < \MvcCore\IController::DISPATCH_STATE_RENDERED)
			$this->Render(
				$this->controllerName,	// dashed ctrl name
				$this->actionName		// dashed action name
			);
		// \MvcCore\Debug::Timer('dispatch');
	}

	/**
	 * @inheritDocs
	 * @return void
	 */
	public function Init () {
		/** @var $this \MvcCore\Controller */
		if ($this->dispatchState > \MvcCore\IController::DISPATCH_STATE_CREATED) 
			return;
		self::$allControllers[spl_object_hash($this)] = $this;
		if ($this->parentController === NULL && !$this->request->IsCli()) {
			if ($this->autoStartSession)
				$this->application->SessionStart();
			if ($this->ajax || (
				$this->controllerName == 'controller' &&
				$this->actionName == 'asset'
			)) $this->viewEnabled = FALSE;
			$responseContentType = $this->ajax ? 'text/javascript' : 'text/html';
			$this->response->SetHeader('Content-Type', $responseContentType);
		}
		if ($this->autoInitProperties)
			$this->autoInitializeProperties();
		foreach ($this->childControllers as $controller) {
			$controller->Init();
			if ($controller->dispatchState == \MvcCore\IController::DISPATCH_STATE_TERMINATED) 
				break;
		}
		if ($this->dispatchState < \MvcCore\IController::DISPATCH_STATE_INITIALIZED)
			$this->dispatchState = \MvcCore\IController::DISPATCH_STATE_INITIALIZED;
	}

	/**
	 * Automatically initialize all properties with PHP Docs tag `@autoInit` 
	 * or with PHP8+ attribute `\MvcCore\ControllerAutoInit`.
	 * This method is always called inside `\MvcCore\Controller::Init();` 
	 * method, after session has been started.
	 * If there is defined factory method name in PHP Docs tag, use that method to 
	 * initialize property or try to find method with name '[_]create' + upper
	 * cased property name. 
	 * If there is no factory method found, try to get property type. First by 
	 * property type in PHP 7.4+, than by Php Docs tag `@var`. Than try to create 
	 * instance by calling property type static method `CreateInstance()`. 
	 * If there is no such static method, create instance by property type 
	 * constructor with no arguments. If property instance implements 
	 * `\MvcCore\IController`, add instance into child controllers.
	 * @return void
	 */
	protected function autoInitializeProperties () {
		/** @var $this \MvcCore\Controller */
		/** @var $ctrl \ReflectionClass */
		$ctrl = new \ReflectionClass($this);
		/** @var $props \ReflectionProperty[] */
		$props = $ctrl->getProperties(
			\ReflectionProperty::IS_PUBLIC |
			\ReflectionProperty::IS_PROTECTED |
			\ReflectionProperty::IS_PRIVATE
		);
		$toolsClass = $this->application->GetToolClass();
		$attrsAnotations = $toolsClass::GetAttributesAnotations();
		$attrClassName = '\\MvcCore\\Controller\\AutoInit';
		$attrClassNameWithoutSlash = mb_substr($attrClassName, 1);
		$phpDocsTagName = $attrClassName::PHP_DOCS_TAG_NAME;
		foreach ($props as $prop) {

			if ($attrsAnotations) {
				$attrArgs = $toolsClass::GetAttrCtorArgs($prop, $attrClassNameWithoutSlash);
			} else {
				$attrArgs = $toolsClass::GetPhpDocsTagArgs($prop, $phpDocsTagName);
			}
			if ($attrArgs === NULL) continue;

			$factoryMethodName = isset($attrArgs[0]) && is_string($attrArgs[0])
				? $attrArgs[0]
				: 'create' . ucfirst($prop->name);
			$hasMethod = $ctrl->hasMethod($factoryMethodName);
			if (!$hasMethod) {
				$factoryMethodName = '_'.$factoryMethodName;
				if (!$ctrl->hasMethod($factoryMethodName))
					$factoryMethodName = NULL;
			}

			$this->autoInitializeProperty($ctrl, $prop, $factoryMethodName);
		}
	}

	/**
	 * Automatically initialize given class and property with PHP Docs tag `@autoInit` 
	 * or with PHP8+ attribute `\MvcCore\Controller\AutoInit`.
	 * This method is always called inside `\MvcCore\Controller::Init();` 
	 * method, after session has been started.
	 * If there is given `$factoryMethodName`, initialize  property with calling
	 * that method. If factory method is `NULL`, try to get property type. First by 
	 * property type in PHP 7.4+, than by Php Docs tag `@var`. Than try to create 
	 * instance by calling property type static method `CreateInstance()`. 
	 * If there is no such static method, create instance by property type 
	 * constructor with no arguments. If property instance implements 
	 * `\MvcCore\IController`, add instance into child controllers.
	 * @param  \ReflectionClass    $ctrl 
	 * @param  \ReflectionProperty $prop 
	 * @param  string|NULL         $factoryMethodName 
	 * @return bool
	 */
	protected function autoInitializeProperty (\ReflectionClass $ctrl, \ReflectionProperty $prop, $factoryMethodName) {
		$phpWithTypes = PHP_VERSION_ID >= 70400;
		if ($factoryMethodName !== NULL) {
			$method = $ctrl->getMethod($factoryMethodName);
			if (!$method->isPublic()) $method->setAccessible(TRUE);
			$instance = $method->invoke($this, []);
		} else {
			$className = NULL;
			if ($phpWithTypes && $prop->hasType()) {
				$refType = $prop->getType();
				if ($refType !== NULL)
					$className = $refType->getName();
			}
			if ($className === NULL) {
				$toolsClass = $this->application->GetToolClass();
				$attrArgs = $toolsClass::GetPhpDocsTagArgs($prop, '@var');
				if (isset($attrArgs[0]) && is_string($attrArgs[0]))
					$className = $attrArgs[0];
			}
			if ($className === NULL)
				return FALSE;
			if (!@class_exists($className)) {
				$className = $prop->getDeclaringClass()->getNamespaceName() . '\\' . $className;
				if (!@class_exists($className)) return FALSE;
			}
			if (is_callable("{$className}::CreateInstance")) {
				$instance = $className::CreateInstance();
			} else {
				$instance = new $className();
			}
		}
		if ($instance instanceof \MvcCore\IController)
			$this->AddChildController($instance, $prop->name);
		if (!$prop->isPublic()) $prop->setAccessible(TRUE);
		$prop->setValue($this, $instance);
		return TRUE;
	}

	/**
	 * @inheritDocs
	 * @return void
	 */
	public function PreDispatch () {
		/** @var $this \MvcCore\Controller */
		if ($this->dispatchState > \MvcCore\IController::DISPATCH_STATE_INITIALIZED) 
			return;
		if ($this->dispatchState == \MvcCore\IController::DISPATCH_STATE_CREATED) 
			$this->Init();
		// check if view is still `NULL`, because it could be created by some parent class
		if ($this->viewEnabled && $this->view === NULL) {
			$viewClass = $this->application->GetViewClass();
			$this->view = $viewClass::CreateInstance()
				->SetController($this);
		}
		foreach ($this->childControllers as $controller) {
			$controller->PreDispatch();
			if ($controller->dispatchState == \MvcCore\IController::DISPATCH_STATE_TERMINATED) 
				break;
		}
		if ($this->dispatchState < \MvcCore\IController::DISPATCH_STATE_PRE_DISPATCHED)
			$this->dispatchState = \MvcCore\IController::DISPATCH_STATE_PRE_DISPATCHED;
	}

	/**
	 * @inheritDocs
	 * @param  \MvcCore\Controller $controller
	 * @param  string|int|NULL     $index
	 * @return \MvcCore\Controller
	 */
	public function AddChildController (\MvcCore\IController $controller, $index = NULL) {
		/** @var $this \MvcCore\Controller */
		/** @var $controller \MvcCore\Controller */
		self::$allControllers[spl_object_hash($controller)] = $controller;
		if (!in_array($controller, $this->childControllers, TRUE)) {
			if ($index === NULL) {
				$this->childControllers[] = $controller;
			} else {
				$this->childControllers[$index] = $controller;
			}
			$controller
				->SetParentController($this)
				->SetApplication($this->application)
				->SetEnvironment($this->environment)
				// Method `SetRequest()` also sets `ajax`, `controllerName` and `actionName`.
				//->SetIsAjax($this->ajax)
				//->SetControllerName($this->controllerName)
				//->SetActionName($this->actionName)
				->SetRequest($this->request)
				->SetResponse($this->response)
				->SetRouter($this->router)
				->SetRenderMode($this->renderMode)
				->SetLayout($this->layout)
				->SetViewEnabled($this->viewEnabled)
				->SetUser($this->user);
		}
		return $this;
	}

	/**
	 * @inheritDocs
	 * @param  mixed $name
	 * @return \MvcCore\Session
	 */
	public function GetSessionNamespace ($name = \MvcCore\ISession::DEFAULT_NAMESPACE_NAME) {
		/** @var $this \MvcCore\Controller */
		$sessionClass = $this->application->GetSessionClass();
		return $sessionClass::GetNamespace($name);
	}

	/**
	 * @inheritDocs
	 * @param  string      $location
	 * @param  int         $code
	 * @param  string|NULL $reason   Any optional text header for reason why.
	 * @return void
	 */
	public static function Redirect ($location = '', $code = \MvcCore\IResponse::SEE_OTHER, $reason = NULL) {
		$app = \MvcCore\Application::GetInstance();
		$response = $app->GetResponse();
		$response
			->SetCode($code)
			->SetHeader('Location', $location);
		if ($reason !== NULL)
			$response->SetHeader('X-Reason', $reason);
		foreach (self::$allControllers as & $controller)
			$controller->dispatchState = \MvcCore\IController::DISPATCH_STATE_TERMINATED;
		$app->Terminate();
	}

	/**
	 * @inheritDocs
	 * @return void
	 */
	public function Terminate () {
		/** @var $this \MvcCore\Controller */
		$this->dispatchState = \MvcCore\IController::DISPATCH_STATE_TERMINATED;
		self::$allControllers = [];
		$this->application->Terminate();
	}

	/**
	 * @inheritDocs
	 * @throws \Exception If file path is not allowed (500) or file not found (404).
	 * @return void
	 */
	public function AssetAction () {
		/** @var $this \MvcCore\Controller */
		$ext = '';
		$path = $this->GetParam('path', 'a-zA-Z0-9_\-\/\.');
		$path = '/' . ltrim(str_replace('..', '', $path), '/');
		if (
			strpos($path, static::$staticPath) !== 0 &&
			strpos($path, static::$tmpPath) !== 0
		)
			throw new \ErrorException("[".get_class($this)."] File path: '$path' is not allowed.", 500);
		$path = $this->request->GetAppRoot() . $path;
		if (!file_exists($path))
			throw new \ErrorException("[".get_class($this)."] File not found: '$path'.", 404);
		$lastDotPos = strrpos($path, '.');
		if ($lastDotPos !== FALSE)
			$ext = substr($path, $lastDotPos + 1);
		if (isset(self::$_assetsMimeTypes[$ext]))
			header('Content-Type: ' . self::$_assetsMimeTypes[$ext]);
		header_remove('X-Powered-By');
		header('Vary: Accept-Encoding');
		$assetMTime = @filemtime($path);
		if ($assetMTime)
			header('Last-Modified: ' . gmdate('D, d M Y H:i:s T', $assetMTime));
		readfile($path);
		exit;
	}
}
