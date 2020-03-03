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

namespace MvcCore\Controller;

trait Dispatching
{
	/**
	 * Return always new instance of statically called class, no singleton.
	 * Always called from `\MvcCore\Application::DispatchControllerAction()` before controller is dispatched,
	 * or always called in `\MvcCore\Controller::autoInitMembers();` in base controller initialization.
	 * This is place where to customize any controller creation process,
	 * before it's created by MvcCore framework to dispatch it.
	 * @return \MvcCore\Controller|\MvcCore\IController
	 */
	public static function & CreateInstance () {
		/** @var $instance \MvcCore\Controller */
		$instance = new static();
		self::$allControllers[spl_object_hash($instance)] = $instance;
		return $instance;
	}

	/**
	 * Try to determinate `\MvcCore\Controller` instance from `debug_bactrace()`,
	 * where was form created, if no form instance given into form constructor.
	 * If no previous controller instance founded, `NULL` is returned.
	 * @return \MvcCore\Controller|\MvcCore\IController|NULL
	 */
	public static function GetCallerControllerInstance () {
		$result = NULL;
		$backtraceItems = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT);
		if (count($backtraceItems) < 3) return $result;
		$calledClass = \PHP_VERSION_ID >= 50500 ? static::class : get_called_class();
		foreach ($backtraceItems as $backtraceItem) {
			if (!isset($backtraceItem['object']) || !$backtraceItem['object']) continue;
			$object = & $backtraceItem['object'];
			$class = & $backtraceItem['class'];
			if (
				$object instanceof \MvcCore\IController &&
				$class !== $calledClass
			) {
				$result = & $object;
				break;
			}
		}
		return $result;
	}

	/**
	 * Sets up `\MvcCore\Request` object and other protected properties.
	 * This is INTERNAL, not TEMPLATE method, internally called in
	 * `\MvcCore\Application::DispatchControllerAction();` before controller is dispatched.
	 * Usually call this as soon as possible after controller creation
	 * to set up following controller properties:
	 * - `\MvcCore\Controller::$request`
	 * - `\MvcCore\Controller::$controllerName`
	 * - `\MvcCore\Controller::$actionName`
	 * - `\MvcCore\Controller::$ajax`
	 * @param \MvcCore\Request|\MvcCore\IRequest $request
	 * @return \MvcCore\Controller
	 */
	public function SetRequest (\MvcCore\IRequest $request) {
		/** @var $this \MvcCore\Controller */
		/** @var $request \MvcCore\Request */
		$this->request = $request;
		$this->controllerName = ltrim($request->GetControllerName(), '/');
		$this->actionName = $request->GetActionName();
		$this->ajax = $request->IsAjax();
		if ($this->ajax || (
			$this->controllerName == 'controller' && $this->actionName == 'asset'
		)) $this->SetViewEnabled(FALSE);
		return $this;
	}

	/**
	 * Dispatching controller life cycle by given action.
	 * This is INTERNAL, not TEMPLATE method, internally
	 * called in `\MvcCore::DispatchControllerAction();`.
	 * Call this immediately after calling controller methods:
	 * - `\MvcCore\Controller::__construct()`
	 * - `\MvcCore\Controller::SetApplication($application)`
	 * - `\MvcCore\Controller::SetRequest($request)`
	 * - `\MvcCore\Controller::SetResponse($response)`
	 * - `\MvcCore\Controller::SetRouter($router)`
	 * This function automatically complete (through controller lifecycle)
	 * protected `\MvcCore\Response` object with response headers and content,
	 * which you can send to client browser by method
	 * `\MvcCore\Controller::Terminate()` or which you can store
	 * anywhere in cache to use it later etc.
	 * @param string $actionName PHP code action name in PascalCase.
	 *							 This value is used to call your desired function
	 *							 in controller without any change.
	 * @return void
	 */
	public function Dispatch ($actionName = "IndexAction") {
		/** @var $this \MvcCore\Controller */

		// \MvcCore\Debug::Timer('dispatch');
		$actionNameStart = $this->actionName;

		if ($this->dispatchState < 1)
			$this->Init();
		if ($this->dispatchState == 5) return; // terminated or redirected
		if ($this->dispatchState < 1) $this->dispatchState = 1;// for cases somebody forget to call parent init
		// \MvcCore\Debug::Timer('dispatch');

		if ($this->dispatchState < 2)
			$this->PreDispatch();
		if ($this->dispatchState == 5) return; // terminated or redirected
		if ($this->dispatchState < 2) $this->dispatchState = 2;// for cases somebody forget to call parent pre-dispatch
		// \MvcCore\Debug::Timer('dispatch');

		if ($this->actionName !== $actionNameStart) {
			$toolClass = $this->application->GetToolClass();
			$actionName = $toolClass::GetPascalCaseFromDashed($this->actionName) . 'Action';
		}
		if ($this->dispatchState < 3 && method_exists($this, $actionName))
			$this->{$actionName}();
		if ($this->dispatchState == 5) return; // terminated or redirected
		if ($this->dispatchState < 3) $this->dispatchState = 3;
		// \MvcCore\Debug::Timer('dispatch');

		if ($this->dispatchState < 4)
			$this->Render(
				$this->controllerName,	// dashed ctrl name
				$this->actionName		// dashed action name
			);
		// \MvcCore\Debug::Timer('dispatch');
	}

	/**
	 * Application controllers initialization.
	 * This is best time to initialize language, locale, session etc.
	 * There is also called auto initialization processing - instance creation
	 * on each controller class member implementing `\MvcCore\IController`
	 * and marked in doc comments as `@autoinit`.
	 * then there is of course called `\MvcCore\Controller::Init();` method on each
	 * automatically created sub-controller.
	 * @return void
	 */
	public function Init () {
		if ($this->dispatchState > 0) return;
		self::$allControllers[spl_object_hash($this)] = $this;
		if ($this->parentController === NULL && !$this->request->IsCli()) {
			if ($this->autoStartSession)
				$this->application->SessionStart();
			$responseContentType = $this->ajax ? 'text/javascript' : 'text/html';
			$this->response->SetHeader('Content-Type', $responseContentType);
		}
		if ($this->autoInitProperties)
			$this->processAutoInitProperties();
		foreach ($this->childControllers as $controller) {
			$controller->Init();
			if ($controller->dispatchState == 5) break;
		}
		if ($this->dispatchState === 0)
			$this->dispatchState = 1;
	}

	/**
	 * Initialize all members implementing `\MvcCore\IController` marked
	 * in doc comments as `@autoinit` into `\MvcCore\Controller::$controllers` array
	 * and into member property itself. This method is always called inside
	 * `\MvcCore\Controller::Init();` method, after session has been started.
	 * Create every new instance by calling existing method named as
	 * `[_]create<PascalCasePropertyName>` and returning new instance or by doc
	 * comment type defined by `@var` over static method `$ClassName::CreateInstance()`.
	 * @return void
	 */
	protected function processAutoInitProperties () {
		/** @var $this \MvcCore\Controller */
		$type = new \ReflectionClass($this);
		/** @var $props \ReflectionProperty[] */
		$props = $type->getProperties(
			\ReflectionProperty::IS_PUBLIC |
			\ReflectionProperty::IS_PROTECTED |
			\ReflectionProperty::IS_PRIVATE
		);
		$toolsClass = $this->application->GetToolClass();
		$phpWithTypes = PHP_VERSION_ID >= 70400;
		foreach ($props as $prop) {
			$docComment = $prop->getDocComment();
			if (mb_strpos($docComment, '@autoinit') === FALSE)
				continue;
			$propName = $prop->getName();
			$methodName = 'create' . ucfirst($propName);
			$hasMethod = $type->hasMethod($methodName);
			if (!$hasMethod) {
				$methodName = '_'.$methodName;
				$hasMethod = $type->hasMethod($methodName);
			}
			if ($hasMethod) {
				$method = $type->getMethod($methodName);
				if (!$method->isPublic()) $method->setAccessible(TRUE);
				$instance = $method->invoke($this);
				$implementsController = $instance instanceof \MvcCore\IController;
			} else {
				$className = NULL;
				if ($phpWithTypes && $prop->hasType()) {
					$refType = $prop->getType();
					if ($refType !== NULL)
						$className = $refType->getName();
				} else {
					$pos = mb_strpos($docComment, '@var ');
					if ($pos !== FALSE) {
						$docComment = str_replace(["\r","\n","\t", "*/"], " ", mb_substr($docComment, $pos + 5));
						$pos = mb_strpos($docComment, ' ');
						if ($pos === FALSE) {
							$className = trim(mb_substr($docComment, 0, $pos));
							$pos = mb_strpos($className, '|');
							if ($pos !== FALSE)
								$className = mb_substr($className, 0, $pos);
						}
					}
				}
				if ($className === NULL)
					continue;
				if (!@class_exists($className)) {
					$className = $prop->getDeclaringClass()->getNamespaceName() . '\\' . $className;
					if (!@class_exists($className)) continue;
				}
				$implementsController = $toolsClass::CheckClassInterface(
					$className, 'MvcCore\\IController', FALSE, FALSE
				);
				if ($implementsController) {
					$instance = $className::CreateInstance();
				} else {
					$instance = new $className();
				}
			}
			if ($implementsController)
				$this->AddChildController($instance, $propName);
			if (!$prop->isPublic()) $prop->setAccessible(TRUE);
			$prop->setValue($this, $instance);
		}
	}

	/**
	 * Application pre render common action - always used in application controllers.
	 * This is best time to define any common properties or common view properties,
	 * which are the same for multiple actions in controller etc.
	 * There is also called `\MvcCore\Controller::PreDispatch();` method on each sub-controller.
	 * @return void
	 */
	public function PreDispatch () {
		if ($this->dispatchState > 1) return;
		if ($this->dispatchState == 0) $this->Init();
		// check if view is still `NULL`, because it could be created by some parent class
		if ($this->viewEnabled && $this->view === NULL) {
			$viewClass = $this->application->GetViewClass();
			$this->view = $viewClass::CreateInstance()->SetController($this);
		}
		foreach ($this->childControllers as $controller) {
			$controller->PreDispatch();
			if ($controller->dispatchState == 5) break;
		}
		if ($this->dispatchState == 1)
			$this->dispatchState = 2;
	}

	/**
	 * - Register child controller to process dispatching on it later.
	 * - This method is always called INTERNALLY, but you can use it for custom purposes.
	 * - This method automatically assigns into child controller(s) properties from parent:
	 *   - `\MvcCore\Controller::$_parentController`
	 *   - `\MvcCore\Controller::$request`
	 *   - `\MvcCore\Controller::$response`
	 *   - `\MvcCore\Controller::$router`
	 *   - `\MvcCore\Controller::$layout`
	 *   - `\MvcCore\Controller::$viewEnabled`
	 *   - `\MvcCore\Controller::$user`
	 * @param \MvcCore\Controller &$controller
	 * @param string|int $index
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
		}
		$controller
			->SetParentController($this)
			->SetApplication($this->application)
			// Method `SetRequest()` also sets `ajax`, `viewEnabled`, `controllerName` and `actionName`.
			->SetRequest($this->request)
			->SetResponse($this->response)
			->SetRouter($this->router)
			->SetLayout($this->layout)
			->SetUser($this->user);
		return $this;
	}

	/**
	 * Alias for `\MvcCore\Session::GetNamespace($name);`
	 * but called with configured session core class name.
	 * @param mixed $name
	 * @return \MvcCore\ISession
	 */
	public function GetSessionNamespace ($name = \MvcCore\ISession::DEFAULT_NAMESPACE_NAME) {
		$sessionClass = $this->application->GetSessionClass();
		return $sessionClass::GetNamespace($name);
	}

	/**
	 * Redirect client browser to another place by `"Location: ..."`
	 * header and call `\MvcCore\Application::GetInstance()->Terminate();`.
	 * @param string		$location
	 * @param int			$code
	 * @param string|NULL	$reason	Any optional text header for reason why.
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
			$controller->dispatchState = 5;
		$app->Terminate();
	}

	/**
	 * Terminate request.
	 * - Send headers if possible.
	 * - Echo response body.
	 * - Write session.
	 * This method is always called INTERNALLY after controller
	 * lifecycle has been dispatched. But you can use it any
	 * time sooner for custom purposes.
	 * This method is only shortcut for: `\MvcCore\Application::GetInstance()->Terminate();`.
	 * @return void
	 */
	public function Terminate () {
		$this->dispatchState = 5;
		$this->application->Terminate();
	}

	/**
	 * Return small assets content with proper headers
	 * in single file application mode and immediately exit.
	 * @throws \Exception If file path is not allowed (500) or file not found (404).
	 * @return void
	 */
	public function AssetAction () {
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
