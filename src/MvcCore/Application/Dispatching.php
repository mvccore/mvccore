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
use MvcCore\Ext\Models\Db\Exception;

/**
 * Trait as partial class for `\MvcCore\Application`:
 * - Dispatching application http request/response (`\MvcCore\Application::Dispatch();`):
 *   - Completing request and response.
 *   - Calling pre/post handlers.
 *   - Controller/action dispatching.
 *   - Error handling and error responses.
 * @mixin \MvcCore\Application
 * @phpstan-type CustomHandlerCallable callable(\MvcCore\IRequest, \MvcCore\IResponse): (false|void)
 * @phpstan-type CustomHandlerRecord array{0: bool, 1: CustomHandlerCallable}
 */
trait Dispatching {

	/***************************************************************************
	 *               `\MvcCore\Application` - Normal Dispatching               *
	 **************************************************************************/

	/**
	 * @inheritDoc
	 * @return \MvcCore\Application
	 */
	public function Dispatch () {
		try {
			// PHP >= 7.0 compatible code
			try {
				$this->DispatchInit();
				if ($this->DispatchExec() !== FALSE)
					$this->Terminate();
			} catch (\Throwable $e1) {
				$this->DispatchException($e1);
				$this->Terminate();
			}
		} catch (\Exception $e2) {
			// PHP < 7.0 compatible code
			$this->DispatchException($e2, $e2->getCode());
			$this->Terminate();
		}
		// Post-dispatch handlers processing moved to: `$this->Terminate();` to process them every time.
		// if (!$this->processCustomHandlers($this->postDispatchHandlers))	return $this->Terminate();
		return $this;
	}

	/**
	 * @inheritDoc
	 * @throws \Throwable
	 * @return void
	 */
	public function DispatchInit () {
		// all 3 getters bellow triggers class instance creation:
		$this->GetEnvironment()->GetName();
		$this->GetRequest();
		$this->GetResponse();
		$debugClass = $this->debugClass;
		$debugClass::Init();
	}
	
	/**
	 * @inheritDoc
	 * @throws \Throwable
	 * @return bool|NULL
	 */
	public function DispatchExec () {
		if (!$this->ProcessCustomHandlers($this->preRouteHandlers))	
			return TRUE; // stopped, go to termination
		if (!$this->RouteRequest())	
			return TRUE; // stopped, go to termination
		if (!$this->ProcessCustomHandlers($this->postRouteHandlers))
			return TRUE; // stopped, go to termination
		$this->SetUpController();
		if (!$this->ProcessCustomHandlers($this->preDispatchHandlers)) 
			return TRUE; // stopped, go to termination
		if (!$this->controller->Dispatch())
			return FALSE; // stopped and already terminated
		return NULL; // everything processed, nothing stopped, go to termination
	}

	/**
	 * @inheritDoc
	 * @throws \LogicException|\InvalidArgumentException
	 * @return bool
	 */
	public function RouteRequest () {
		$router = $this->GetRouter()->SetRequest($this->request);
		/**
			* `Route()` method could throws those exceptions:
			* @throws \LogicException Route configuration property is missing.
			* @throws \InvalidArgumentException Wrong route pattern format.
			*/
		return $router->Route();
	}

	/**
	 * @inheritDoc
	 * @param  CustomHandlerRecord[] $handlers
	 * @throws \Throwable
	 * @return bool
	 */
	public function ProcessCustomHandlers (& $handlers = []) {
		if (!$handlers || $this->request->IsInternalRequest() === TRUE) return TRUE;
		$result = TRUE;
		reset($handlers);
		ksort($handlers, SORT_NUMERIC);
		foreach ($handlers as $handlerRecords) {
			foreach ($handlerRecords as $closureCallingAndHandler) {
				/** @var CustomHandlerRecord $closureCallingAndHandler */
				list($closureCalling, $handler) = $closureCallingAndHandler;
				$subResult = NULL;
				if ($closureCalling) {
					$subResult = $handler($this->request, $this->response);
				} else {
					$subResult = call_user_func($handler, $this->request, $this->response);
				}
				if ($subResult === FALSE) {
					$result = FALSE;
					break;
				}
			}
		}
		return $result;
	}

	/**
	 * @inheritDoc
	 * @throws \Exception
	 * @return bool
	 */
	public function SetUpController () {
		/** @var \MvcCore\Route|NULL $route */
		$route = $this->router->GetCurrentRoute();
		if ($route === NULL) 
			throw new \Exception('No route for request', 404);
		list ($ctrlPc, $actionPc) = [$route->GetController(), $route->GetAction()];
		$viewClass = $this->viewClass;
		
		$checkViewIfNoCtrl = FALSE;
		if ($ctrlPc === NULL || $ctrlPc === 'Controller') {
			$controllerName = $this->controllerClass;
		} else if ($this->controller !== NULL) {
			$controllerName = '\\'.get_class($this->controller);
		} else {
			// `App_Controllers_<$ctrlPc>`
			$controllerName = $this->CompleteControllerName($ctrlPc);
			$checkViewIfNoCtrl = TRUE;
		}

		/** @var \MvcCore\View $viewClass */
		$viewsDirFullPath = $route->GetControllerHasAbsoluteNamespace()
			? $viewClass::GetExtViewsDirFullPath($this, ltrim($controllerName, '\\'), TRUE)
			: $viewClass::GetDefaultViewsDirFullPath($this);

		$viewScriptFullPath = $viewClass::GetViewScriptFullPath(
			$viewsDirFullPath . '/' . $viewClass::GetScriptsDir(),
			$this->request->GetControllerName() . '/' . $this->request->GetActionName()
		);
		
		// Controller file or view file could contain syntax error:
		if ($checkViewIfNoCtrl && !class_exists($controllerName, TRUE)) {
			// if controller doesn't exists - check if at least view exists
			if (!file_exists($viewScriptFullPath)) 
				throw new \Exception(
					"Controller class `{$controllerName}` doesn't exist.", 404
				);
			// if view exists - change controller name to core 
			// controller, if not let it go to exception:
			$controllerName = $this->controllerClass;
		}

		return $this->CreateController(
			$controllerName, $actionPc, $viewScriptFullPath
		);
	}

	/**
	 * @inheritDoc
	 * @param  string   $ctrlClassFullName
	 * @param  string   $actionNamePc
	 * @param  string   $viewScriptFullPath
	 * @throws \Exception
	 * @return bool
	 */
	public function CreateController (
		$ctrlClassFullName, $actionNamePc, $viewScriptFullPath
	) {
		if ($this->controller === NULL) {
			$controller = NULL;
			try {
				$controller = $ctrlClassFullName::CreateInstance();
			} catch (\Throwable $e) {
				throw new \Exception($e->getMessage(), 404);
			}
			$this->controller = $controller;
		}
		/** @var \MvcCore\Controller $controller */
		$controller = $this->controller;
		$controller
			->SetApplication($this)
			->SetEnvironment($this->environment)
			->SetRequest($this->request)
			->SetResponse($this->response)
			->SetRouter($this->router);
		$type = new \ReflectionClass($controller);
		$initName = $actionNamePc . 'Init';
		$actionName = $actionNamePc . 'Action';
		$initExists = $type->hasMethod($initName) && $type->getMethod($initName)->isPublic();
		$actionExists = $type->hasMethod($actionName) && $type->getMethod($actionName)->isPublic();
		if (!$initExists && !$actionExists && $ctrlClassFullName !== $this->controllerClass) {
			if (!file_exists($viewScriptFullPath)) {
				$appRoot = $this->request->GetAppRoot();
				$viewScriptPath = mb_strpos($viewScriptFullPath, $appRoot) === FALSE
					? $viewScriptFullPath
					: mb_substr($viewScriptFullPath, mb_strlen($appRoot));
				$ctrlClassFullName = $this->request->GetControllerName();
				throw new \Exception(
					"Controller class `{$ctrlClassFullName}` "
					."has no methods `{$initName}` or `{$actionName}` \n"
					."or view doesn't exist: `{$viewScriptPath}`.",
					404
				);
			}
		}
		return TRUE;
	}
	
	/**
	 * @inheritDoc
	 * @return void
	 */
	public function SessionStart () {
		/** @var \MvcCore\Session $sessionClass */
		$sessionClass = $this->sessionClass;
		$sessionClass::Start();
	}
	
	/**
	 * @inheritDoc
	 * @param  string               $controllerActionOrRouteName
	 * Should be `"Controller:Action"` combination or just any route name as custom specific string.
	 * @param  array<string, mixed> $params
	 * Optional, array with params, key is param name, value is param value.
	 * @return string
	 */
	public function Url ($controllerActionOrRouteName = 'Index:Index', $params = []) {
		return $this->router->Url($controllerActionOrRouteName, $params);
	}

	/**
	 * @inheritDoc
	 * @return \MvcCore\Application
	 */
	public function Terminate () {
		if ($this->terminated) return $this;
		$this->ProcessCustomHandlers($this->postDispatchHandlers);
		if (!$this->response->IsSentHeaders()) {
			// headers (if still possible) and echo
			$sessionClass = $this->sessionClass;
			if ($sessionClass::GetStarted()) {
				$sessionClass::SendSessionIdCookie();
				$sessionClass::SendRefreshedCsrfCookie();
				$sessionClass::Close();
			}
			$this->response->SendHeaders();
		}
		if (
			!$this->response->IsSentBody() &&
			!$this->request->GetMethod() !== \MvcCore\IRequest::METHOD_HEAD
		)
			$this->response->SendBody();
		// exit; // Why to force exit? What if we want to do something more?
		$this->terminated = TRUE;
		if ($this->controller) {
			$ctrlType = new \ReflectionClass($this->controller);
			$dispatchStateProperty = $ctrlType->getProperty('dispatchState');
			$dispatchStateProperty->setAccessible(TRUE);
			$dispatchStateProperty->setValue(
				$this->controller, \MvcCore\IController::DISPATCH_STATE_TERMINATED
			);
		}
		$this->ProcessCustomHandlers($this->postTerminateHandlers);
		return $this;
	}


	/***************************************************************************
	 *           `\MvcCore\Application` - Request Error Dispatching            *
	 **************************************************************************/

	/**
	 * @inheritDoc
	 * @param  \Throwable|\Exception|string $exceptionOrMessage
	 * @param  int|NULL                     $code
	 * @return bool
	 */
	public function DispatchException ($exceptionOrMessage, $code = NULL) {
		if (class_exists('\Packager_Php')) return FALSE; // packing process
		$exception = NULL;
		if ($exceptionOrMessage instanceof \Throwable) {
			$exception = $exceptionOrMessage;
		} else if ($exceptionOrMessage instanceof \Exception) { /** @phpstan-ignore-line */
			$exception = $exceptionOrMessage;
		} else {
			try {
				// PHP >= 7.0 compatible code
				try {
					if ($code === NULL) throw new \Exception($exceptionOrMessage);
					throw new \ErrorException($exceptionOrMessage, $code);
				} catch (\Throwable $e1) {
					$exception = $e1;
				}
			} catch (\Exception $e2) { // @phpstan-ignore-line
				// PHP < 7.0 compatible code
				$exception = $e2;
			}
		}
		static $lastExceptionStr = NULL;
		if ($lastExceptionStr === NULL) {
			$lastExceptionStr = $exception->getMessage();
		} else if ($lastExceptionStr === $exception->getMessage()) {
			return $code === 404
				? $this->RenderError404PlainText($lastExceptionStr)
				: $this->RenderError500PlainText($lastExceptionStr);
		}
		/** @var \MvcCore\Debug $debugClass */
		$debugClass = $this->debugClass;
		if ($exception->getCode() == 404) {
			$debugClass::Log($exception->getMessage().": ".$this->request->GetFullUrl(), \MvcCore\IDebug::INFO);
			return $this->RenderNotFound($exception->getMessage());
		} else if ($this->environment->IsDevelopment() && $debugClass::GetDebugging()) {
			$this->ProcessCustomHandlers($this->postDispatchHandlers);
			if (!$this->response->IsSentHeaders()) {
				// headers (if still possible) and echo
				$sessionClass = $this->sessionClass;
				if ($sessionClass::GetStarted()) {
					$sessionClass::SendSessionIdCookie();
					$sessionClass::SendRefreshedCsrfCookie();
					$sessionClass::Close();
				}
				$this->response->SendHeaders();
			}
			$debugClass::Exception($exception);
			return FALSE;
		} else {
			$debugClass::Log($exception, \MvcCore\IDebug::EXCEPTION);
			return $this->RenderError($exception);
		}
	}

	/**
	 * @inheritDoc
	 * @param  \Throwable $e
	 * @return bool
	 */
	public function RenderError ($e) {
		$defaultCtrlFullName = $this->GetDefaultControllerIfHasAction(
			$this->defaultControllerErrorActionName
		);
		$exceptionMessage = $e->getMessage();
		if (!$this->GetRequest()->IsCli() && $defaultCtrlFullName && $this->router !== NULL) {
			$debugClass = $this->debugClass;
			$viewClass = $this->viewClass;
			$this->router->SetOrCreateDefaultRouteAsCurrent(
				\MvcCore\IRouter::DEFAULT_ROUTE_NAME_ERROR,
				$this->defaultControllerName,
				$this->defaultControllerErrorActionName,
				TRUE
			);
			$exceptionCode = $e->getCode();
			$exceptionCode = $exceptionCode > 0 ? $exceptionCode : 500;
			$this->request
				->SetParam('code', $exceptionCode, \MvcCore\IRequest::PARAM_TYPE_URL_REWRITE)
				->SetParam('message', $exceptionMessage, \MvcCore\IRequest::PARAM_TYPE_URL_REWRITE);
			$this->response->SetCode($exceptionCode);
			$this->controller = NULL;
			try {
				$this->CreateController(
					$defaultCtrlFullName,
					$this->defaultControllerErrorActionName . "Action",
					$viewClass::GetViewScriptFullPath(
						$viewClass::GetDefaultViewsDirFullPath($this) . '/' . $viewClass::GetScriptsDir(),
						$this->request->GetControllerName() . '/' . $this->request->GetActionName()
					)
				);
				$this->controller->Dispatch(); // @phpstan-ignore-line
			} catch (\Throwable $e2) {
				$this->router->RemoveRoute(\MvcCore\IRouter::DEFAULT_ROUTE_NAME_NOT_FOUND);
				if ($this->environment->IsDevelopment()) {
					$debugClass::Exception($e2);
				} else {
					$debugClass::Log($e2, \MvcCore\IDebug::EXCEPTION);
					$this->RenderError500PlainText($exceptionMessage . PHP_EOL . PHP_EOL . $e2->getMessage());
				}
			}
			return FALSE;
		} else {
			return $this->RenderError500PlainText($exceptionMessage);
		}
	}

	/**
	 * @inheritDoc
	 * @param  string $exceptionMessage
	 * @return bool
	 */
	public function RenderNotFound ($exceptionMessage = '') {
		if (!$exceptionMessage) $exceptionMessage = 'Page not found.';
		$defaultCtrlFullName = $this->GetDefaultControllerIfHasAction(
			$this->defaultControllerNotFoundActionName
		);
		if (!$this->GetRequest()->IsCli() && $defaultCtrlFullName) {
			$debugClass = $this->debugClass;
			$viewClass = $this->viewClass;
			$this->router->SetOrCreateDefaultRouteAsCurrent(
				\MvcCore\IRouter::DEFAULT_ROUTE_NAME_NOT_FOUND,
				$this->defaultControllerName,
				$this->defaultControllerNotFoundActionName,
				TRUE
			);
			$this->request
				->SetParam('code', '404', \MvcCore\IRequest::PARAM_TYPE_URL_REWRITE)
				->SetParam('message', $exceptionMessage, \MvcCore\IRequest::PARAM_TYPE_URL_REWRITE);
			$this->response->SetCode(404);
			$this->controller = NULL;
			try {
				$this->CreateController(
					$defaultCtrlFullName,
					$this->defaultControllerNotFoundActionName . "Action",
					$viewClass::GetViewScriptFullPath(
						$viewClass::GetDefaultViewsDirFullPath($this) . '/' . $viewClass::GetScriptsDir(),
						$this->request->GetControllerName() . '/' . $this->request->GetActionName()
					)
				);
				$this->controller->Dispatch(); // @phpstan-ignore-line
			} catch (\Throwable $e) {
				$this->router->RemoveRoute(\MvcCore\IRouter::DEFAULT_ROUTE_NAME_NOT_FOUND);
				if ($this->environment->IsDevelopment()) {
					$debugClass::Exception($e);
				} else {
					$debugClass::Log($e, \MvcCore\IDebug::EXCEPTION);
					$this->RenderError404PlainText($exceptionMessage);
				}
			}
			return FALSE;
		} else {
			return $this->RenderError404PlainText($exceptionMessage);
		}
	}

	/**
	 * @inheritDoc
	 * @param  string $text
	 * @return bool
	 */
	public function RenderError500PlainText ($text = '') {
		$htmlResponse = FALSE;
		$responseClass = $this->responseClass;
		if (!$this->environment->IsDevelopment()) {
			$text = 'Error 500: Internal Server Error.'.PHP_EOL;
		} else {
			$obContent = ob_get_clean();
			if (mb_strlen($obContent) > 0)
				$htmlResponse = mb_strpos($obContent, '<') !== FALSE && mb_strpos($obContent, '>') !== FALSE;
			if ($htmlResponse) {
				$text = '<pre><big>Error 500</big>: '.PHP_EOL.PHP_EOL.$text.'</pre>'.$obContent;
			} else {
				$text = 'Error 500: '.PHP_EOL.PHP_EOL.$text.PHP_EOL.$obContent;
			}
		}
		$this->response = $responseClass::CreateInstance(
			\MvcCore\IResponse::INTERNAL_SERVER_ERROR,
			['Content-Type' => $htmlResponse ? 'text/html' : 'text/plain'],
			$text
		);
		return FALSE;
	}

	/**
	 * @inheritDoc
	 * @param  string $text
	 * @return bool
	 */
	public function RenderError404PlainText ($text = '') {
		$htmlResponse = FALSE;
		$responseClass = $this->responseClass;
		if (!$this->environment->IsDevelopment()) {
			$text = 'Error 404: Page not found.'.PHP_EOL;
		} else {
			$obContent = ob_get_clean();
			if (mb_strlen($obContent) > 0)
				$htmlResponse = mb_strpos($obContent, '<') !== FALSE && mb_strpos($obContent, '>') !== FALSE;
			if ($htmlResponse) {
				$text = '<pre><big>Error 404</big>: '.PHP_EOL.PHP_EOL.$text.'</pre>'.$obContent;
			} else {
				$text = 'Error 404: '.PHP_EOL.PHP_EOL.$text.PHP_EOL.$obContent;
			}
		}
		$this->response = $responseClass::CreateInstance(
			\MvcCore\IResponse::NOT_FOUND,
			['Content-Type' => $htmlResponse ? 'text/html' : 'text/plain'],
			$text
		);
		return FALSE;
	}
}