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
 * - Dispatching application http request/response (`\MvcCore\Application::Dispatch();`):
 *   - Completing request and response.
 *   - Calling pre/post handlers.
 *   - Controller/action dispatching.
 *   - Error handling and error responses.
 * @mixin \MvcCore\Application
 */
trait Dispatching {

	/***************************************************************************
	 *               `\MvcCore\Application` - Normal Dispatching               *
	 **************************************************************************/

	/**
	 * @inheritDocs
	 * @return \MvcCore\Application
	 */
	public function Dispatch () {
		try {
			// all 3 getters triggers creation:
			$this->GetEnvironment();
			$this->GetRequest();
			$this->GetResponse();
			$debugClass = $this->debugClass;
			$debugClass::Init();
		} catch (\Exception $e) { // backward compatibility
			$this->DispatchException($e);
			return $this->Terminate();
		} catch (\Throwable $e) {
			$this->DispatchException($e);
			return $this->Terminate();
		}
		if (!$this->ProcessCustomHandlers($this->preRouteHandlers))		return $this->Terminate();
		if (!$this->RouteRequest())										return $this->Terminate();
		if (!$this->ProcessCustomHandlers($this->postRouteHandlers))	return $this->Terminate();
		if (!$this->DispatchRequest())									return $this->Terminate();
		// Post-dispatch handlers processing moved to: `$this->Terminate();` to process them every time.
		// if (!$this->processCustomHandlers($this->postDispatchHandlers))	return $this->Terminate();
		return $this->Terminate();
	}

	/**
	 * @inheritDocs
	 * @return void
	 */
	public function SessionStart () {
		/** @var \MvcCore\Session $sessionClass */
		$sessionClass = $this->sessionClass;
		$sessionClass::Start();
	}

	/**
	 * @inheritDocs
	 * @return bool
	 */
	public function RouteRequest () {
		$router = $this->GetRouter()->SetRequest($this->GetRequest());
		try {
			/**
			 * `Route()` method could throws those exceptions:
			 * @throws \LogicException Route configuration property is missing.
			 * @throws \InvalidArgumentException Wrong route pattern format.
			 */
			$result = $router->Route();
		} catch (\Exception $e) { // backward compatibility
			$this->DispatchException($e);
			$result = FALSE;
		} catch (\Throwable $e) {
			$this->DispatchException($e);
			$result = FALSE;
		}
		return $result;
	}

	/**
	 * @inheritDocs
	 * @param  \callable[] $handlers
	 * @return bool
	 */
	public function ProcessCustomHandlers (& $handlers = []) {
		if (!$handlers || $this->request->IsInternalRequest() === TRUE) return TRUE;
		$result = TRUE;
		foreach ($handlers as $handlerRecord) {
			list ($closureCalling, $handler) = $handlerRecord;
			$subResult = NULL;
			try {
				if ($closureCalling) {
					$subResult = $handler($this->request, $this->response);
				} else {
					$subResult = call_user_func($handler, $this->request, $this->response);
				}
				if ($subResult === FALSE) {
					$result = FALSE;
					break;
				}
			} catch (\Exception $e) { // backward compatibility
				$this->DispatchException($e);
				$result = FALSE;
				break;
			} catch (\Throwable $e) {
				$this->DispatchException($e);
				$result = FALSE;
				break;
			}
		}
		return $result;
	}

	/**
	 * @inheritDocs
	 * @return bool
	 */
	public function DispatchRequest () {
		/** @var \MvcCore\Route $route */
		$route = $this->router->GetCurrentRoute();
		if ($route === NULL) return $this->DispatchException('No route for request', 404);
		list ($ctrlPc, $actionPc) = [$route->GetController(), $route->GetAction()];
		$actionName = $actionPc . 'Action';
		$viewClass = $this->viewClass;
		$viewScriptFullPath = $viewClass::GetViewScriptFullPath(
			$viewClass::GetScriptsDir(),
			$this->request->GetControllerName() . '/' . $this->request->GetActionName()
		);
		if ($ctrlPc == 'Controller') {
			$controllerName = $this->controllerClass;
		} else if ($this->controller !== NULL) {
			$controllerName = '\\'.get_class($this->controller);
		} else {
			// `App_Controllers_<$ctrlPc>`
			$controllerName = $this->CompleteControllerName($ctrlPc);
			if (!class_exists($controllerName)) {
				// if controller doesn't exists - check if at least view exists
				if (file_exists($viewScriptFullPath)) {
					// if view exists - change controller name to core controller, if not let it go to exception
					$controllerName = $this->controllerClass;
				} else {
					return $this->DispatchException("Controller class `{$controllerName}` doesn't exist.", 404);
				}
			}
		}
		return $this->DispatchControllerAction(
			$controllerName,
			$actionName,
			$viewScriptFullPath,
			function ($e) {
				return $this->DispatchException($e);
			}
		);
	}

	/**
	 * @inheritDocs
	 * @param  string   $ctrlClassFullName
	 * @param  string   $actionNamePc
	 * @param  string   $viewScriptFullPath
	 * @param  callable $exceptionCallback
	 * @return bool
	 */
	public function DispatchControllerAction (
		$ctrlClassFullName,
		$actionNamePc,
		$viewScriptFullPath,
		callable $exceptionCallback
	) {
		if ($this->controller === NULL) {
			$controller = NULL;
			try {
				$controller = $ctrlClassFullName::CreateInstance();
			} catch (\Exception $e) { // backward compatibility
				return $this->DispatchException($e->getMessage(), 404);
			} catch (\Throwable $e) {
				return $this->DispatchException($e->getMessage(), 404);
			}
			$this->controller = $controller;
		}
		/** @var \MvcCore\Controller $ctrl */
		$ctrl = $this->controller;
		$ctrl
			->SetApplication($this)
			->SetEnvironment($this->environment)
			->SetRequest($this->request)
			->SetResponse($this->response)
			->SetRouter($this->router);
		if (!method_exists($this->controller, $actionNamePc) && $ctrlClassFullName !== $this->controllerClass) {
			if (!file_exists($viewScriptFullPath)) {
				$appRoot = $this->request->GetAppRoot();
				$viewScriptPath = mb_strpos($viewScriptFullPath, $appRoot) === FALSE
					? $viewScriptFullPath
					: mb_substr($viewScriptFullPath, mb_strlen($appRoot));
				$ctrlClassFullName = $this->request->GetControllerName();
				return $this->DispatchException(
					"Controller class `{$ctrlClassFullName}` "
					."has not method `{$actionNamePc}` \n"
					."or view doesn't exist: `{$viewScriptPath}`.",
					404
				);
			}
		}
		if (!$this->ProcessCustomHandlers($this->preDispatchHandlers)) return FALSE;
		try {
			$this->controller->Dispatch();
		} catch (\Exception $e) { // backward compatibility
			return $exceptionCallback($e);
		} catch (\Throwable $e) {
			return $exceptionCallback($e);
		}
		return TRUE;
	}

	/**
	 * @inheritDocs
	 * @param  string $controllerActionOrRouteName Should be `"Controller:Action"` combination or just any route name as custom specific string.
	 * @param  array  $params                      Optional, array with params, key is param name, value is param value.
	 * @return string
	 */
	public function Url ($controllerActionOrRouteName = 'Index:Index', $params = []) {
		return $this->router->Url($controllerActionOrRouteName, $params);
	}

	/**
	 * @inheritDocs
	 * @return \MvcCore\Application
	 */
	public function Terminate () {
		if ($this->terminated) return $this;
		/** @var $this->response \MvcCore\Response */
		$this->ProcessCustomHandlers($this->postDispatchHandlers);
		if (!$this->response->IsSentHeaders()) {
			// headers (if still possible) and echo
			$sessionClass = $this->sessionClass;
			if ($sessionClass::GetStarted()) {
				$sessionClass::SendCookie();
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
	 * @inheritDocs
	 * @param  \Throwable|string $exceptionOrMessage
	 * @param  int|NULL          $code
	 * @return bool
	 */
	public function DispatchException ($exceptionOrMessage, $code = NULL) {
		if (class_exists('\Packager_Php')) return FALSE; // packing process
		$exception = NULL;
		if ($exceptionOrMessage instanceof \Throwable) {
			$exception = $exceptionOrMessage;
		} else {
			try {
				if ($code === NULL) throw new \Exception($exceptionOrMessage);
				throw new \ErrorException($exceptionOrMessage, $code);
			} catch (\Exception $e) { // backward compatibility
				$exception = $e;
			} catch (\Throwable $e) {
				$exception = $e;
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
		$debugClass = $this->debugClass;
		if ($exception->getCode() == 404) {
			$debugClass::Log($exception->getMessage().": ".$this->request->GetFullUrl(), \MvcCore\IDebug::INFO);
			return $this->RenderNotFound($exception->getMessage());
		} else if ($this->environment->IsDevelopment()) {
			$debugClass::Exception($exception);
			return FALSE;
		} else {
			$debugClass::Log($exception, \MvcCore\IDebug::EXCEPTION);
			return $this->RenderError($exception);
		}
	}

	/**
	 * @inheritDocs
	 * @param  \Throwable $e
	 * @return bool
	 */
	public function RenderError ($e) {
		$defaultCtrlFullName = $this->GetDefaultControllerIfHasAction(
			$this->defaultControllerErrorActionName
		);
		$exceptionMessage = $e->getMessage();
		if (!$this->GetRequest()->IsCli() && $defaultCtrlFullName) {
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
			$this->DispatchControllerAction(
				$defaultCtrlFullName,
				$this->defaultControllerErrorActionName . "Action",
				$viewClass::GetViewScriptFullPath(
					$viewClass::GetScriptsDir(),
					$this->request->GetControllerName() . '/' . $this->request->GetActionName()
				),
				function (\Throwable $e2) use ($exceptionMessage, $debugClass) {
					$this->router->RemoveRoute(\MvcCore\IRouter::DEFAULT_ROUTE_NAME_NOT_FOUND);
					if ($this->environment->IsDevelopment()) {
						$debugClass::Exception($e2);
					} else {
						$debugClass::Log($e2, \MvcCore\IDebug::EXCEPTION);
						$this->RenderError500PlainText($exceptionMessage . PHP_EOL . PHP_EOL . $e2->getMessage());
					}
				}
			);
			return FALSE;
		} else {
			return $this->RenderError500PlainText($exceptionMessage);
		}
	}

	/**
	 * @inheritDocs
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
				->SetParam('code', 404, \MvcCore\IRequest::PARAM_TYPE_URL_REWRITE)
				->SetParam('message', $exceptionMessage, \MvcCore\IRequest::PARAM_TYPE_URL_REWRITE);
			$this->response->SetCode(404);
			$this->controller = NULL;
			$this->DispatchControllerAction(
				$defaultCtrlFullName,
				$this->defaultControllerNotFoundActionName . "Action",
				$viewClass::GetViewScriptFullPath(
					$viewClass::GetScriptsDir(),
					$this->request->GetControllerName() . '/' . $this->request->GetActionName()
				),
				function (\Throwable $e) use ($exceptionMessage, $debugClass) {
					$this->router->RemoveRoute(\MvcCore\IRouter::DEFAULT_ROUTE_NAME_NOT_FOUND);
					if ($this->environment->IsDevelopment()) {
						$debugClass::Exception($e);
					} else {
						$debugClass::Log($e, \MvcCore\IDebug::EXCEPTION);
						$this->RenderError404PlainText($exceptionMessage);
					}
				}
			);
			return FALSE;
		} else {
			return $this->RenderError404PlainText($exceptionMessage);
		}
	}

	/**
	 * @inheritDocs
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
	 * @inheritDocs
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