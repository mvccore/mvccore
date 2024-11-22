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
 * @phpstan-type DispatchParams object{
 *    "called":array<string,bool>,
 *    "count":int,
 *    "state":int,
 *    "method":string,
 *    "children":bool
 * }
 */
trait Dispatching {

	/**
	 * @inheritDoc
	 * @return \MvcCore\Controller
	 */
	public static function CreateInstance () {
		/** @var \MvcCore\Controller $instance */
		$instance = new static(); /** @phpstan-ignore-line */
		$ctrlHash = spl_object_hash($instance);
		if (!isset(self::$allControllers[$ctrlHash]))
			self::$allControllers[$ctrlHash] = [$instance, new \ReflectionClass($instance)];
		return $instance;
	}
	
	/**
	 * @inheritDoc
	 * @param  \MvcCore\IController $controller
	 * @return void
	 */
	public static function RemoveController (\MvcCore\IController $controller) {
		$ctrlHash = spl_object_hash($controller);
		if (isset(self::$allControllers[$ctrlHash]))
			unset(self::$allControllers[$ctrlHash]);
		if (isset($controller->parentController)) {
			$parentController = $controller->parentController;
			$index = array_search($controller, $parentController->childControllers, TRUE);
			if ($index !== FALSE)
				unset($parentController->childControllers[$index]);
		}
	}
	
	/**
	 * @inheritDoc
	 * @return void
	 */
	public static function RemoveAllControllers () {
		foreach (self::$allControllers as $controllerAndType)
			static::RemoveController($controllerAndType[0]);
		self::$allControllers = [];
	}

	/**
	 * @inheritDoc
	 * @return \MvcCore\IController|NULL
	 */
	public static function GetCallerControllerInstance () {
		$result = NULL;
		$backtraceItems = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS);
		if (count($backtraceItems) < 3) return $result;
		$calledClass = get_called_class();
		foreach ($backtraceItems as $backtraceItem) {
			if (!isset($backtraceItem['object'])) continue;
			$object = $backtraceItem['object'];
			$class = $backtraceItem['class'];
			if (
				($object instanceof \MvcCore\IController) && // @phpstan-ignore-line
				$class !== $calledClass &&
				!is_subclass_of($calledClass, $class)
			) {
				$result = $object;
				break;
			}
		}
		return $result;
	}
	
	/**
	 * @inheritDoc
	 * @param  string      $location
	 * @param  int         $code
	 * @param  string|NULL $reason   Any optional text header for reason why.
	 * @throws \MvcCore\Controller\TerminateException
	 * @return void
	 */
	public static function Redirect ($location, $code = \MvcCore\IResponse::SEE_OTHER, $reason = NULL) {
		$app = \MvcCore\Application::GetInstance();
		$response = $app->GetResponse();
		$response
			->SetCode($code)
			->SetHeader('Location', $location);
		if ($reason !== NULL)
			$response->SetHeader('X-Reason', $reason);
		$state = static::DISPATCH_STATE_TERMINATED;
		$mainCtrl = $app->GetController();
		$mainCtrl->dispatchState = $state;
		foreach (self::$allControllers as $controllerAndType) {
			list($controller) = $controllerAndType;
			$controller->dispatchState = $state;
		}
		self::$allControllers = [];
		$app->Terminate();
		throw new \MvcCore\Controller\TerminateException(__FILE__.":".__LINE__);
	}

	/**
	 * @inheritDoc
	 * @internal
	 * @param  string|NULL $actionName
	 * Optional, PHP code action name, it has to be in PascalCase 
	 * without any suffix (`Init` or `Action'). This value is used 
	 * later to call your desired functions in controller with this changes:
	 * - `$controller->{$actionName . 'Init'}()`,
	 * - `$controller->{$actionName . 'Action'}()`.
	 * @return bool
	 * Return `FALSE` if application has been already terminated.
	 */
	public function Dispatch ($actionName = NULL) {
		/** @var \MvcCore\Controller $this */
		$result = TRUE;
		try {
			list(
				$toolClass, $actionNameDcStart, $actionNamePc, $dispatchOrphans
			) = $this->dispatchGetActionParams($actionName);
			
			$this->dispatchMethods(
				$this, 'Init', 
				static::DISPATCH_STATE_INITIALIZED, $dispatchOrphans
			);

			if ($this->actionName !== $actionNameDcStart) 
				$actionNamePc = $toolClass::GetPascalCaseFromDashed($this->actionName);
			$this->dispatchMethods(
				$this, $actionNamePc . 'Init', 
				static::DISPATCH_STATE_ACTION_INITIALIZED, $dispatchOrphans
			);
			
			$this->dispatchMethods(
				$this, 'PreDispatch', 
				static::DISPATCH_STATE_PRE_DISPATCHED, $dispatchOrphans
			);

			if ($this->actionName !== $actionNameDcStart) 
				$actionNamePc = $toolClass::GetPascalCaseFromDashed($this->actionName);
			$this->dispatchMethods(
				$this, $actionNamePc . 'Action', 
				static::DISPATCH_STATE_ACTION_EXECUTED, $dispatchOrphans
			);

			$this->dispatchRender();

		} catch (\MvcCore\Controller\TerminateException $e) {
			$result = FALSE;
			if (!$this->application->GetTerminated()) {
				$this->terminateControllers();
				$this->application->Terminate();
			}
		}
		return $result;
	}

	/**
	 * @inheritDoc
	 * @param  int $state 
	 * Dispatch state, that is required to be completed. Possible values are:
	 * - `\MvcCore\IController::DISPATCH_STATE_CREATED`,
	 * - `\MvcCore\IController::DISPATCH_STATE_INITIALIZED`,
	 * - `\MvcCore\IController::DISPATCH_STATE_ACTION_INITIALIZED`,
	 * - `\MvcCore\IController::DISPATCH_STATE_PRE_DISPATCHED`,
	 * - `\MvcCore\IController::DISPATCH_STATE_ACTION_EXECUTED`,
	 * - `\MvcCore\IController::DISPATCH_STATE_RENDERED`,
	 * - `\MvcCore\IController::DISPATCH_STATE_TERMINATED`.
	 * @return bool
	 */
	public function DispatchStateCheck ($state) {
		if ($this->dispatchState >= $state) 
			return FALSE;
		// here is always `$this->dispatchState < $state`:
		if ($this->dispatchStateSemaphore) 
			return TRUE;
		$this->dispatchStateSemaphore = TRUE;
		
		list(
			$toolClass, $actionNameDcStart, $actionNamePc, $dispatchOrphans
		) = $this->dispatchGetActionParams();

		if ($state > static::DISPATCH_STATE_INITIALIZED)
			$this->dispatchMethods(
				$this, 'Init', 
				static::DISPATCH_STATE_INITIALIZED, $dispatchOrphans
			);

		if ($state > static::DISPATCH_STATE_ACTION_INITIALIZED) {
			if ($this->actionName !== $actionNameDcStart) 
				$actionNamePc = $toolClass::GetPascalCaseFromDashed($this->actionName);
			$this->dispatchMethods(
				$this, $actionNamePc . 'Init', 
				static::DISPATCH_STATE_ACTION_INITIALIZED, $dispatchOrphans
			);
		}

		if ($state > static::DISPATCH_STATE_PRE_DISPATCHED)
			$this->dispatchMethods(
				$this, 'PreDispatch', 
				static::DISPATCH_STATE_PRE_DISPATCHED, $dispatchOrphans
			);

		if ($state > static::DISPATCH_STATE_ACTION_EXECUTED) {
			if ($this->actionName !== $actionNameDcStart) 
				$actionNamePc = $toolClass::GetPascalCaseFromDashed($this->actionName);
			$this->dispatchMethods(
				$this, $actionNamePc . 'Action', 
				static::DISPATCH_STATE_ACTION_EXECUTED, $dispatchOrphans
			);
		}

		if ($state > static::DISPATCH_STATE_RENDERED)
			$this->dispatchRender();

		$this->dispatchStateSemaphore = FALSE;
		return TRUE;
	}
	
	/**
	 * Prepare controller dispatching variables collection.
	 * Return array with those dispatching variables:
	 * `[string $toolClass, string $this->actionName, string $actionName]`.
	 * If given first argument `$actionName` param is `NULL`, return in last 2 items 
	 * a pascal case and dashed case action by `$this->actionName`. If first argument 
	 * `$actionName` param has any value, set up `$this->actionName` by first argument.
	 * and return in last two items a pascal and dashed case by that.
	 * @internal
	 * @param  string|NULL $actionName
	 * Optional, PHP code action name, it has to be in PascalCase 
	 * without any suffix (`Init` or `Action'). This value is used 
	 * later to call your desired functions in controller with this changes:
	 * - `$controller->{$actionName . 'Init'}()`,
	 * - `$controller->{$actionName . 'Action'}()`,
	 * @return array{0:class-string,1:string,2:string,3:bool}
	 */
	protected function dispatchGetActionParams ($actionName = NULL) {
		$toolClass = $this->application->GetToolClass();
		if ($actionName === NULL) {
			$actionName = $toolClass::GetPascalCaseFromDashed($this->actionName);
		} else {
			$this->actionName = $toolClass::GetDashedFromPascalCase($actionName);
		}
		$currentCtrlHash = spl_object_hash($this);
		$mainCtrlHash = spl_object_hash($this->application->GetController());
		$dispatchOrphans = $currentCtrlHash === $mainCtrlHash;
		return [$toolClass, $this->actionName, $actionName, $dispatchOrphans];
	}

	/**
	 * Execute method on controller and all it's children or possible orphans to target dispatch state.
	 * Execute only if controller is before target state and if given method exists as public.
	 * @param  \MvcCore\IController $controller 
	 * Main controller dispatching context or dispatching checking controller context.
	 * @param  string               $methodName 
	 * Controller public method name, possible values are:
	 * - `Init`,
	 * - `<action>Init`,
	 * - `PreDispatch`,
	 * - `<action>Action`.
	 * @param  int                  $targetDispatchState 
	 * Dispatch state, that is required to be completed. Possible values are:
	 * - `\MvcCore\IController::DISPATCH_STATE_INITIALIZED`,
	 * - `\MvcCore\IController::DISPATCH_STATE_ACTION_INITIALIZED`,
	 * - `\MvcCore\IController::DISPATCH_STATE_PRE_DISPATCHED`,
	 * - `\MvcCore\IController::DISPATCH_STATE_ACTION_EXECUTED`.
	 * @param  bool                 $dispatchOrphans
	 * Dispatch orphans is `TRUE` only for main controller instance.
	 * @return void
	 */
	protected function dispatchMethods (\MvcCore\IController $controller, $methodName, $targetDispatchState, $dispatchOrphans = FALSE) {
		$ctrlHash = spl_object_hash($controller);
		/** @var \MvcCore\Controller $controller */
		if ($controller->dispatchState < $targetDispatchState) {
			list(, $levelCtrlType) = self::$allControllers[$ctrlHash];
			if (
				$levelCtrlType->hasMethod($methodName) &&
				$levelCtrlType->getMethod($methodName)->isPublic()
			) {
				$this->dispatchMethod(
					$controller, $methodName, $targetDispatchState
				);
			}
		}
		// execute current controller context and all it's children:
		$dispatchParams = (object) [
			'called'	=> [$ctrlHash => TRUE],
			'count'		=> 1,
			'state'		=> $targetDispatchState,
			'method'	=> $methodName,
			'children'	=> !in_array($methodName, ['Init', 'PreDispatch'])
		];
		if (count($controller->childControllers) > 0)
			$this->dispatchMethodsChildren($dispatchParams, $controller->childControllers);
		// execute possible orphans:
		if ($dispatchOrphans && $dispatchParams->count < count(self::$allControllers)) {
			$orphanControllers = [];
			foreach (self::$allControllers as $ctrlHash => $controllerAndType) {
				list($orphanController) = $controllerAndType;
				if (
					isset($dispatchParams->ctrls[$ctrlHash]) ||
					$orphanController->dispatchState >= $targetDispatchState
				) continue;
				$orphanControllers[] = $orphanController;
			}
			if (count($orphanControllers) > 0) {
				$this->dispatchMethodsChildren($dispatchParams, $orphanControllers);
			}
		}
	}

	/**
	 * Execute method on controllers array to target dispatch state.
	 * Execute only if controller is before target state and if given method exists as public.
	 * @param  DispatchParams             $dispatchParams 
	 * Recursive variables store:
	 * - `called`   - array with controller hashes and booleans,
	 * - `count`    - called controllers count,
	 * - `state`    - target dispatch state,
	 * - `method`   - method to execute on every controller,
	 * - `children` - boolean to execute children chontrollers or not.
	 * @param  array<\MvcCore\Controller> $levelControllers 
	 * Controllers in level or orphan controllers.
	 * @return void
	 */
	protected function dispatchMethodsChildren (& $dispatchParams, array $levelControllers) {
		$calledCtrls = & $dispatchParams->called;
		$targetDispatchState = $dispatchParams->state;
		$methodName = $dispatchParams->method;
		$execChildren = $dispatchParams->children;
		foreach ($levelControllers as $levelCtrl) {
			$dispatchParams->count++;
			$ctrlHash = spl_object_hash($levelCtrl);
			if (isset($calledCtrls[$ctrlHash])) 
				continue;
			$calledCtrls[$ctrlHash] = TRUE;
			if ($levelCtrl->dispatchState < $targetDispatchState) {
				list(, $levelCtrlType) = self::$allControllers[$ctrlHash];
				if (
					$levelCtrlType->hasMethod($methodName) &&
					$levelCtrlType->getMethod($methodName)->isPublic()
				) {
					$this->dispatchMethod(
						$levelCtrl, $methodName, $targetDispatchState
					);
				}
			}
			if ($execChildren && count($levelCtrl->childControllers) > 0)
				$this->dispatchMethodsChildren($dispatchParams, $levelCtrl->childControllers);
		}
	}

	/**
	 * Execute given controller method and move dispatch state.
	 * This method doesn't check if method exists on given controller context.
	 * @param  \MvcCore\Controller $controller 
	 * Any level controller context.
	 * @param  string              $methodName 
	 * Controller public method name, possible values are:
	 * - `Init`,
	 * - `<action>Init`,
	 * - `PreDispatch`,
	 * - `<action>Action`.
	 * @param  int                 $targetDispatchState 
	 * Dispatch state, that is required to be completed. Possible values are:
	 * - `\MvcCore\IController::DISPATCH_STATE_INITIALIZED`,
	 * - `\MvcCore\IController::DISPATCH_STATE_ACTION_INITIALIZED`,
	 * - `\MvcCore\IController::DISPATCH_STATE_PRE_DISPATCHED`,
	 * - `\MvcCore\IController::DISPATCH_STATE_ACTION_EXECUTED`.
	 * @return void
	 */
	protected function dispatchMethod (\MvcCore\IController $controller, $methodName, $targetDispatchState) {
		if ($targetDispatchState === static::DISPATCH_STATE_ACTION_EXECUTED) {
			// This dispatch state is exceptional and it's necessary to set it before execution:
			$controller->dispatchMoveState($targetDispatchState);
		}
		$controller->{$methodName}();
		if ($targetDispatchState !== static::DISPATCH_STATE_ACTION_EXECUTED) {
			// For cases somebody forget to call parent action method:
			$controller->dispatchMoveState($targetDispatchState);
		}
	}

	/**
	 * Dispatch controller render method if view is enabled.
	 * This is usually used to call controller method
	 * `$controller->Render($this->controllerName, $this->actionName)`.
	 * @internal
	 * @return void
	 */
	protected function dispatchRender () {
		if ($this->viewEnabled && $this->dispatchState < static::DISPATCH_STATE_RENDERED)
			$this->Render(
				$this->controllerName,	// dashed ctrl name
				$this->actionName		// dashed action name
			);
	}

	/**
	 * Move dispatching state to given point if 
	 * `$this->dispatchState` is lower than given point.
	 * @internal
	 * @param  int $targetDispatchState 
	 * @return \MvcCore\Controller
	 */
	protected function dispatchMoveState ($targetDispatchState) {
		if ($this->dispatchState < $targetDispatchState)
			$this->dispatchState = $targetDispatchState;
		return $this;
	}
	

	/**
	 * @inheritDoc
	 * @return void
	 */
	public function Init () {
		if (!$this->DispatchStateCheck(static::DISPATCH_STATE_INITIALIZED))
			return;
		$ctrlHash = spl_object_hash($this);
		if (!isset(self::$allControllers[$ctrlHash]))
			self::$allControllers[$ctrlHash] = [$this, new \ReflectionClass($this)];
		if ($this->parentController === NULL && !$this->request->IsCli()) {
			if ($this->autoStartSession)
				$this->application->SessionStart();
			if ($this->ajax || (
				$this->controllerName == 'controller' &&
				$this->actionName == 'asset'
			)) $this->viewEnabled = FALSE;
			$responseContentType = $this->ajax ? 'application/json' : 'text/html';
			$this->response->SetHeader('Content-Type', $responseContentType);
		}
		if ($this->autoInitProperties)
			$this->autoInitializeProperties();
		foreach ($this->childControllers as $controller) {
			if ($controller->dispatchState < static::DISPATCH_STATE_INITIALIZED)
				$controller->Init();
			if ($controller->dispatchState === static::DISPATCH_STATE_TERMINATED) 
				break;
		}
		$this->dispatchMoveState(static::DISPATCH_STATE_INITIALIZED);
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
		/** @var \ReflectionClass<\MvcCore\IController> $ctrl */
		$ctrl = new \ReflectionClass($this);
		/** @var \ReflectionProperty[] $props */
		$props = $ctrl->getProperties(
			\ReflectionProperty::IS_PUBLIC |
			\ReflectionProperty::IS_PROTECTED |
			\ReflectionProperty::IS_PRIVATE
		);
		$attrsAnotations = $this->application->GetAttributesAnotations();
		/** @var \MvcCore\Tool $toolsClass */
		$toolsClass = $this->application->GetToolClass();
		$attrClassName = '\\MvcCore\\Controller\\AutoInit';
		$attrClassNameWithoutSlash = mb_substr($attrClassName, 1);
		$phpDocsTagName = $attrClassName::PHP_DOCS_TAG_NAME;
		$autoInitsFixedSort = [];
		$autoInitsNaturalSort = [];
		foreach ($props as $prop) {
			$factoryMethodName = NULL;
			if ($attrsAnotations) {
				$attrArgs = $toolsClass::GetAttrCtorArgs($prop, $attrClassNameWithoutSlash, TRUE);
			} else {
				$attrArgs = $toolsClass::GetPhpDocsTagArgs($prop, $phpDocsTagName, TRUE);
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
			$initIndex = isset($attrArgs[1]) && is_numeric($attrArgs[1])
				? intval($attrArgs[1])
				: NULL;
			if ($initIndex === NULL) {
				$autoInitsNaturalSort[] = [$prop, $factoryMethodName];
			} else {
				if (isset($autoInitsFixedSort[$initIndex])) {
					list($prevProp) = $autoInitsFixedSort[$initIndex];
					throw new \Exception(
						"Controller `{$ctrl->name}` has already occupied property "
						."auto initialization index: {$initIndex}. `{$prevProp->name}`."
					);
				}
				$autoInitsFixedSort[$initIndex] = [$prop, $factoryMethodName];
			}
		}
		ksort($autoInitsFixedSort);
		foreach ($autoInitsFixedSort as $propAndFactName)
			$this->autoInitializeProperty($ctrl, $propAndFactName[0], $propAndFactName[1]);
		foreach ($autoInitsNaturalSort as $propAndFactName)
			$this->autoInitializeProperty($ctrl, $propAndFactName[0], $propAndFactName[1]);

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
	 * @param  \ReflectionClass<\MvcCore\IController> $ctrl 
	 * @param  \ReflectionProperty                    $prop 
	 * @param  string|NULL                            $factoryMethodName 
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
				/** @var \ReflectionNamedType $refType */
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
		/** @var \MvcCore\Controller $instance */
		if ($instance instanceof \MvcCore\IController)
			$this->AddChildController($instance, $prop->name);
		if (!$prop->isPublic()) $prop->setAccessible(TRUE);
		$prop->setValue($this, $instance);
		return TRUE;
	}

	/**
	 * @inheritDoc
	 * @return void
	 */
	public function PreDispatch () {
		if (!$this->DispatchStateCheck(static::DISPATCH_STATE_PRE_DISPATCHED))
			return;
		// check if view is still `NULL`, because it could be created by some parent class
		if ($this->viewEnabled && $this->view === NULL) 
			$this->view = $this->createView(TRUE);
		foreach ($this->childControllers as $controller) {
			if ($controller->dispatchState < static::DISPATCH_STATE_PRE_DISPATCHED)
				$controller->PreDispatch();
			if ($controller->dispatchState === static::DISPATCH_STATE_TERMINATED) 
				break;
		}
		$this->dispatchMoveState(static::DISPATCH_STATE_PRE_DISPATCHED);
	}

	/**
	 * View instance factory method.
	 * @param  bool $actionView
	 * @return \MvcCore\View
	 */
	protected function createView ($actionView = TRUE) {
		$viewClass = $this->application->GetViewClass();
		return $viewClass::CreateInstance()
			->SetController($this)
			->SetEncoding($this->response->GetEncoding());
	}

	/**
	 * @inheritDoc
	 * @param  \MvcCore\Controller $controller
	 * @param  string|int|NULL     $index
	 * @return \MvcCore\Controller
	 */
	public function AddChildController (\MvcCore\IController $controller, $index = NULL) {
		/** @var \MvcCore\Controller $controller */
		$ctrlHash = spl_object_hash($controller);
		if (!isset(self::$allControllers[$ctrlHash]))
			self::$allControllers[$ctrlHash] = [$controller, new \ReflectionClass($controller)];
		if (!in_array($controller, $this->childControllers, TRUE)) {
			if ($index === NULL) {
				$this->childControllers[] = $controller;
			} else if (isset($this->childControllers[$index]) && $this->childControllers[$index] !== $controller) {
				throw new \InvalidArgumentException(
					"[".get_class($this)."] Child controller with type `".get_class($this->childControllers[$index])."` under index `$index` already exists."
				);
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
	 * @inheritDoc
	 * @param  mixed $name
	 * @return \MvcCore\Session
	 */
	public function GetSessionNamespace ($name = \MvcCore\ISession::DEFAULT_NAMESPACE_NAME) {
		$sessionClass = $this->application->GetSessionClass();
		return $sessionClass::GetNamespace($name);
	}

	/**
	 * @inheritDoc
	 * @throws \MvcCore\Controller\TerminateException
	 * @return void
	 */
	public function Terminate () {
		$this->terminateControllers();
		$this->application->Terminate();
		throw new \MvcCore\Controller\TerminateException(__FILE__.":".__LINE__);
	}

	/**
	 * Set up terminated dispatch state to all registered 
	 * controllers and empty static controllers array.
	 * @return void
	 */
	protected function terminateControllers () {
		$state = static::DISPATCH_STATE_TERMINATED;
		$this->dispatchState = $state;
		$this->application->GetController()->dispatchState = $state;
		foreach (self::$allControllers as $controllerAndType) {
			list($controller) = $controllerAndType;
			$controller->dispatchState = $state;
		}
		self::$allControllers = [];
	}

	/**
	 * @inheritDoc
	 * @throws \Exception If file path is not allowed (500) or file not found (404).
	 * @return void
	 */
	public function AssetAction () {
		$ext = '';
		$pathReq = $this->GetParam('path', 'a-zA-Z0-9_\-\/\.');
		$pathReq = '/' . ltrim(str_replace(['../', './'], '/', $pathReq), '/');
		$pathReqFromDocRootRel = '~' . $pathReq;
		$pathStaticRel = $this->application->GetPathStatic(FALSE);
		$pathStaticAbs = $this->application->GetPathStatic(TRUE);
		$pathVarRel = $this->application->GetPathVar(FALSE);
		$pathVarAbs = $this->application->GetPathVar(TRUE);
		$pathDocRootAbs = $this->application->GetPathDocRoot();
		$req2Static = (
			mb_strpos($pathStaticAbs, $pathDocRootAbs) === 0 && 
			mb_strpos($pathReqFromDocRootRel, $pathStaticRel . '/') === 0
		);
		$req2Var = (
			mb_strpos($pathVarAbs, $pathDocRootAbs) === 0 && 
			mb_strpos($pathReqFromDocRootRel, $pathVarRel . '/') === 0
		);
		if (!$req2Static && !$req2Var)
			throw new \ErrorException("[".get_class($this)."] File path: '{$pathReq}' is not allowed.", 500);
		$fullPath = $pathDocRootAbs . $pathReq;
		if (!file_exists($fullPath) || !is_file($fullPath))
			throw new \ErrorException("[".get_class($this)."] File not found: '{$pathReq}'.", 404);
		$lastDotPos = strrpos($pathReq, '.');
		if ($lastDotPos !== FALSE)
			$ext = substr($pathReq, $lastDotPos + 1);
		if (isset(self::$_assetsMimeTypes[$ext]))
			header('Content-Type: ' . self::$_assetsMimeTypes[$ext]);
		header_remove('X-Powered-By');
		header('Vary: Accept-Encoding');
		$assetMTime = @filemtime($fullPath);
		if ($assetMTime !== FALSE) {
			$dateHeader = gmdate('D, d M Y H:i:s T', $assetMTime);
			header('Date: ' . $dateHeader);
			header('Last-Modified: ' . $dateHeader);
		}
		if ($this->request->GetMethod() === \MvcCore\IRequest::METHOD_GET)
			readfile($fullPath);
		exit;
	}
}
