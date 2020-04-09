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

namespace MvcCore\Application;

/**
 * Trait as partial class for `\MvcCore\Application`:
 * - Dispatching application http request/response (`\MvcCore\Application::Dispatch();`):
 *   - Completing request and response.
 *   - Calling pre/post handlers.
 *   - Controller/action dispatching.
 *   - Error handling and error responses.
 */
trait Dispatching
{
	/***************************************************************************
	 *               `\MvcCore\Application` - Normal Dispatching               *
	 **************************************************************************/

	/**
	 * Dispatch http request/response.
	 * - 1. Complete and init:
	 *	  - Complete describing environment object `\MvcCore\Request`.
	 *	  - Complete describing request object `\MvcCore\Request`.
	 *	  - Complete response storage object `\MvcCore\Response`.
	 *	  - Init debugging and logging by `\MvcCore\Debug::Init();`.
	 * - 2. (Process pre-route handlers queue.)
	 * - 3. Route request by your router or with `\MvcCore\Router::Route()` by default.
	 * - 4. (Process post-route handlers queue.)
	 * - 5. Create and set up controller instance.
	 * - 6. (Process pre-dispatch handlers queue.)
	 * - 7. Dispatch controller life cycle.
	 *  	- Call `\MvcCore\Controller::Init()` and `\MvcCore\Controller::PreDispatch()`.
	 *	  - Call routed action method.
	 *	  - Call `\MvcCore\Controller::Render()` to render all views.
	 * - 6. Terminate request:
	 *	  - (Process post-dispatch handlers queue.)
	 *	  - Write session in `register_shutdown_function()` handler.
	 *	  - Send response headers if possible and echo response body.
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
	 * Starts a session, standardly called from `\MvcCore\Controller::Init();`.
	 * But is should be called anytime sooner, for example in any pre request handler
	 * to redesign request before MVC dispatching or anywhere else.
	 * @return void
	 */
	public function SessionStart () {
		$sessionClass = $this->sessionClass;
		$sessionClass::Start();
	}

	/**
	 * Route request by router obtained by default by calling:
	 * `\MvcCore\Router::GetInstance();`.
	 * Store requested route inside configured
	 * router class to get it later by calling:
	 * `\MvcCore\Router::GetCurrentRoute();`
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
		} catch (\Throwable $e) {
			$this->DispatchException($e);
			$result = FALSE;
		}
		return $result;
	}

	/**
	 * Process pre-route, pre-request or post-dispatch
	 * handlers queue by queue index. Call every handler in queue
	 * in try catch mode to catch any exceptions to call:
	 * `\MvcCore\Application::DispatchException($e);`.
	 * @param callable[] $handlers
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
			} catch (\Throwable $e) {
				$this->DispatchException($e);
				$result = FALSE;
				break;
			}
		}
		return $result;
	}

	/**
	 * If controller class exists - try to dispatch controller,
	 * if only view file exists - try to render targeted view file
	 * with configured core controller instance (`\MvcCore\Controller` by default).
	 * @return bool
	 */
	public function DispatchRequest () {
		/** @var \MvcCore\IRoute */
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
					return $this->DispatchException("Controller class `$controllerName` doesn't exist.", 404);
				}
			}
		}
		return $this->DispatchControllerAction(
			$controllerName,
			$actionName,
			$viewScriptFullPath,
			function (\Throwable $e) {
				return $this->DispatchException($e);
			}
		);
	}

	/**
	 * Dispatch controller by:
	 * - By full class name and by action name
	 * - Or by view script full path
	 * Call exception callback if there is caught any
	 * exception in controller life cycle dispatching process
	 * with first argument as caught exception.
	 * @param string $ctrlClassFullName
	 * @param string $actionNamePc
	 * @param string $viewScriptFullPath
	 * @param callable $exceptionCallback
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
			} catch (\Throwable $e) {
				return $this->DispatchException($e->getMessage(), 404);
			}
			$this->controller = $controller;
		}
		/** @var $ctrl \MvcCore\Controller */
		$ctrl = $this->controller;
		/** @var $this \MvcCore\Application */
		$ctrl
			->SetApplication($this)
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
					"Controller class `$ctrlClassFullName` has not method `$actionNamePc` \n"
					."or view doesn't exist: `$viewScriptPath`.",
					404
				);
			}
		}
		if (!$this->ProcessCustomHandlers($this->preDispatchHandlers)) return FALSE;
		try {
			$this->controller->Dispatch($actionNamePc);
		} catch (\Throwable $e) {
			return $exceptionCallback($e);
		}
		return TRUE;
	}

	/**
	 * Generates url:
	 * - By `"Controller:Action"` name and params array
	 *   (for routes configuration when routes array has keys with `"Controller:Action"` strings
	 *   and routes has not controller name and action name defined inside).
	 * - By route name and params array
	 *	 (route name is key in routes configuration array, should be any string
	 *	 but routes must have information about controller name and action name inside).
	 * Result address (url string) should have two forms:
	 * - Nice rewritten URL by routes configuration
	 *   (for apps with URL rewrite support (Apache `.htaccess` or IIS URL rewrite module)
	 *   and when first param is key in routes configuration array).
	 * - For all other cases is URL form like: `"index.php?controller=ctrlName&amp;action=actionName"`
	 *	 (when first param is not founded in routes configuration array).
	 * @param string $controllerActionOrRouteName	Should be `"Controller:Action"` combination or just any route name as custom specific string.
	 * @param array  $params						Optional, array with params, key is param name, value is param value.
	 * @return string
	 */
	public function Url ($controllerActionOrRouteName = 'Index:Index', $params = []) {
		return $this->router->Url($controllerActionOrRouteName, $params);
	}

	/**
	 * Terminate request.
	 * The only place in application where is called `echo '....'` without output buffering.
	 * - Process post-dispatch handlers queue.
	 * - Write session through registered handler into `register_shutdown_function()`.
	 * - Send HTTP headers (if still possible).
	 * - Echo response body.
	 * This method is always called INTERNALLY after controller
	 * life cycle has been dispatched. But you can use it any
	 * time sooner for custom purposes.
	 * @return \MvcCore\Application
	 */
	public function Terminate () {
		//$stop();
		/** @var $this \MvcCore\Application */
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
		if (!$this->response->IsSentBody())
			$this->response->SendBody();
		// exit; // Why to force exit? What if we want to do something more?
		$this->terminated = TRUE;
		if ($this->controller) {
			$ctrlType = new \ReflectionClass($this->controller);
			$dispatchStateProperty = $ctrlType->getProperty('dispatchState');
			$dispatchStateProperty->setAccessible(TRUE);
			$dispatchStateProperty->setValue($this->controller, 5);
		}
		$this->ProcessCustomHandlers($this->postTerminateHandlers);
		return $this;
	}


	/***************************************************************************
	 *           `\MvcCore\Application` - Request Error Dispatching            *
	 **************************************************************************/

	/**
	 * Dispatch caught exception:
	 *	- If request is processing PHP package packing to determinate current script dependencies:
	 *		- Do not log or render nothing.
	 *	- If request is production mode:
	 *		- Print exception in browser.
	 *	- If request is not in development mode:
	 *		- Log error and try to render error page by configured controller and error action:,
	 *		  `\App\Controllers\Index::Error();` by default.
	 * @param \Throwable|string $exceptionOrMessage
	 * @param int|NULL $code
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
			} catch (\Exception $e) {
				$exception = $e;
			}
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
	 * Render error by configured default controller and error action,
	 * `\App\Controllers\Index::Error();` by default.
	 * If there is no controller/action like that or any other exception happens,
	 * it's processed very simple plain text response with 500 http code.
	 * @param \Throwable $e
	 * @return bool
	 */
	public function RenderError (\Throwable $e) {
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
			$newParams = array_merge($this->request->GetParams(FALSE), [
				'code'		=> $exceptionCode,
				'message'	=> $exceptionMessage,
			]);
			$this->request->SetParams($newParams);
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
	 * Render error by configured default controller and not found error action,
	 * `\App\Controllers\Index::NotFound();` by default.
	 * If there is no controller/action like that or any other exception happens,
	 * it's processed very simple plain text response with 404 http code.
	 * @param \Throwable $e
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
			$newParams = array_merge($this->request->GetParams(FALSE), [
				'code'		=> 404,
				'message'	=> $exceptionMessage,
			]);
			$this->request->SetParams($newParams);
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
	 * Prepare very simple response with internal server error (500)
	 * as plain text response into `\MvcCore\Application::$response`.
	 * @param string $text
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
	 * Prepare very simple response with not found error (404)
	 * as plain text response into `\MvcCore\Application::$response`.
	 * @param string $text
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