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

trait Rendering
{
	/**
	 * - This method is called INTERNALLY in lifecycle dispatching process,
	 *   but you can use it sooner or in any different time for custom render purposes.
	 * - Render prepared controller/action view in path by default:
	 * `"/App/Views/Scripts/<ctrl-dashed-name>/<action-dashed-name>.phtml"`.
	 * - If controller has no other parent controller, render layout view around action view.
	 * - For top most parent controller - store rendered action and layout view in response object and return empty string.
	 * - For child controller - return rendered action view as string.
	 * @param string $controllerOrActionNameDashed
	 * @param string $actionNameDashed
	 * @return string
	 */
	public function & Render ($controllerOrActionNameDashed = NULL, $actionNameDashed = NULL) {
		/** @var $this \MvcCore\Controller */
		if ($this->dispatchState == 0)
			$this->Init();
		if ($this->dispatchState == 1)
			$this->PreDispatch();
		if ($this->dispatchState < 4 && $this->viewEnabled) {
			$topMostParentCtrl = $this->parentController === NULL;
			// if this is child controller - set up view store with parent controller view store
			if (!$topMostParentCtrl)
				$this->view->SetUpStore($this->parentController->GetView(), FALSE);
			// set up child controllers into view if any of them is named by string index
			foreach ($this->childControllers as $ctrlKey => $childCtrl) {
				if (!is_numeric($ctrlKey) && !isset($this->view->{$ctrlKey}))
					$this->view->{$ctrlKey} = $childCtrl;
			}
			// render this view or view with layout by render mode:
			if (($this->renderMode & \MvcCore\IView::RENDER_WITH_OB_FROM_ACTION_TO_LAYOUT) != 0) {
				return $this->renderWithObFromActionToLayout(
					$controllerOrActionNameDashed,
					$actionNameDashed,
					$topMostParentCtrl
				);
			} else /*if (($this->renderMode & \MvcCore\IView::RENDER_WITHOUT_OB_CONTINUOUSLY) != 0)*/ {
				$sessionClass = $this->GetApplication()->GetSessionClass();
				if ($sessionClass::GetStarted()) {
					$sessionClass::SendCookie();
					$sessionClass::Close();
				}

				$this->response->SendHeaders();
				if (ob_get_length() !== FALSE) ob_end_flush();
				return $this->renderWithoutObContinuously(
					$controllerOrActionNameDashed,
					$actionNameDashed,
					$topMostParentCtrl
				);
			}
		}
		$this->dispatchState = 4;
		$result = '';
		return $result;
	}

	/**
	 * Store rendered HTML output inside `\MvcCore\Controller::$response`
	 * to send into client browser later in `\MvcCore\Application::GetInstance()->Terminate();`.
	 * @param string $output
	 * @param bool $terminate
	 * @return void
	 */
	public function HtmlResponse ($output = '', $terminate = TRUE) {
		if (!$this->response->HasHeader('Content-Type')) {
			$contentTypeHeaderValue = strpos(
				\MvcCore\View::GetDoctype(), \MvcCore\View::DOCTYPE_XHTML
			) !== FALSE ? 'application/xhtml+xml' : 'text/html' ;
			$this->response->SetHeader('Content-Type', $contentTypeHeaderValue);
		}
		$this->response
			->SetCode(\MvcCore\IResponse::OK)
			->SetBody($output);
		if ($terminate) $this->Terminate();
	}

	/**
	 * Store rendered XML output inside `\MvcCore\Controller::$response`
	 * to send into client browser later in `\MvcCore\Application::GetInstance()->Terminate();`.
	 * @param string $output
	 * @param bool $terminate
	 * @return void
	 */
	public function XmlResponse ($output = '', $terminate = TRUE) {
		$res = $this->response;
		if (!$res->HasHeader('Content-Type'))
			$res->SetHeader('Content-Type', 'application/xml');
		$res->SetBody($output);
		if ($res->GetCode() === NULL)
			$res->SetCode(\MvcCore\IResponse::OK);
		if ($terminate) $this->Terminate();
	}

	/**
	 * Store rendered text output inside `\MvcCore\Controller::$response`
	 * to send into client browser later in `\MvcCore\Application::GetInstance()->Terminate();`.
	 * @param string $output
	 * @param bool $terminate
	 * @return void
	 */
	public function TextResponse ($output = '', $terminate = TRUE) {
		$res = $this->response;
		if (!$res->HasHeader('Content-Type'))
			$res->SetHeader('Content-Type', 'text/plain');
		$res->SetBody($output);
		if ($res->GetCode() === NULL)
			$res->SetCode(\MvcCore\IResponse::OK);
		if ($terminate) $this->Terminate();
	}

	/**
	 * Serialize any PHP value into `JSON string` and store
	 * it inside `\MvcCore\Controller::$response` to send it
	 * into client browser later in `\MvcCore\Application::GetInstance()->Terminate();`.
	 * @param mixed $data
	 * @param bool  $terminate
	 * @throws \Exception JSON encoding error.
	 * @return void
	 */
	public function JsonResponse ($data = NULL, $terminate = TRUE) {
		$res = $this->response;
		$toolClass = $this->application->GetToolClass();
		$output = $toolClass::EncodeJson($data);
		ob_clean(); // remove any possible warnings to break client's `JSON.parse();`
		if (!$res->HasHeader('Content-Type'))
			$res->SetHeader('Content-Type', 'text/javascript');
		$res
			->SetHeader('Content-Length', strlen($output))
			->SetBody($output);
		if ($res->GetCode() === NULL)
			$res->SetCode(\MvcCore\IResponse::OK);
		if ($terminate) $this->Terminate();
	}

	/**
	 * Serialize any PHP value into `JSON string`, wrap around prepared public
	 * javascript function in target window sent as `$_GET` param under
	 * variable `$callbackParamName` (allowed chars: `a-zA-Z0-9\.\-_\$`) and
	 * store it inside `\MvcCore\Controller::$response` to send it
	 * into client browser later in `\MvcCore\Application::GetInstance()->Terminate();`.
	 * @param mixed $data
	 * @param string $callbackParamName
	 * @param bool  $terminate
	 * @throws \Exception JSON encoding error.
	 * @return void
	 */
	public function JsonpResponse ($data = NULL, $callbackParamName = 'callback', $terminate = TRUE) {
		$res = $this->response;
		$toolClass = $this->application->GetToolClass();
		$output = $toolClass::EncodeJson($data);
		ob_clean(); // remove any possible warnings to break client's `JSON.parse();`
		if (!$res->HasHeader('Content-Type'))
			$res->SetHeader('Content-Type', 'text/javascript');
		$callbackParam = $this->GetParam($callbackParamName, 'a-zA-Z0-9\.\-_\$', $callbackParamName, 'string');
		$output = $callbackParam . '(' . $output . ');';
		$res
			->SetHeader('Content-Length', strlen($output))
			->SetBody($output);
		if ($res->GetCode() === NULL)
			$res->SetCode(\MvcCore\IResponse::OK);
		if ($terminate) $this->Terminate();
	}

	/**
	 * Render error controller and error action
	 * for any dispatch exception or error as
	 * rendered html response or as plain text response.
	 * @param string $exceptionMessage
	 * @return void
	 */
	public function RenderError ($exceptionMessage = '') {
		if ($this->application->IsErrorDispatched()) return;
		throw new \ErrorException(
			$exceptionMessage ? $exceptionMessage :
			"Server error: `" . htmlspecialchars($this->request->GetFullUrl()) . "`.",
			500
		);
	}

	/**
	 * Render not found controller and not found action
	 * for any dispatch exception with code 404 as
	 * rendered html response or as plain text response.
	 * @return void
	 */
	public function RenderNotFound () {
		if ($this->application->IsNotFoundDispatched()) return;
		throw new \ErrorException(
			"Page not found: `" . htmlspecialchars($this->request->GetFullUrl()) . "`.", 404
		);
	}

	/**
	 * Complete view script path by given controller and action or only by given action rendering arguments.
	 * @param string $controllerOrActionNameDashed
	 * @param string $actionNameDashed
	 * @return string
	 */
	public function GetViewScriptPath ($controllerOrActionNameDashed = NULL, $actionNameDashed = NULL) {
		$currentCtrlIsTopMostParent = $this->parentController === NULL;
		if ($this->viewScriptsPath !== NULL) {
			$resultPathItems = [$this->viewScriptsPath];
			if ($controllerOrActionNameDashed !== NULL) $resultPathItems[] = $controllerOrActionNameDashed;
			if ($actionNameDashed !== NULL) $resultPathItems[] = $actionNameDashed;
			return str_replace(['_', '\\'], '/', implode('/', $resultPathItems));
		}
		if ($actionNameDashed !== NULL) { // if action defined - take first argument controller
			$controllerNameDashed = $controllerOrActionNameDashed;
		} else { // if no action defined - we need to complete controller dashed name
			$toolClass = '';
			if ($currentCtrlIsTopMostParent) { // if controller is tom most one - take routed controller name
				$controllerNameDashed = $this->controllerName;
			} else {
				// if controller is child controller - translate class name
				// without default controllers directory into dashed name
				$ctrlsDefaultNamespace = $this->application->GetAppDir() . '\\'
					. $this->application->GetControllersDir();
				$currentCtrlClassName = get_class($this);
				if (mb_strpos($currentCtrlClassName, $ctrlsDefaultNamespace) === 0)
					$currentCtrlClassName = mb_substr($currentCtrlClassName, mb_strlen($ctrlsDefaultNamespace) + 1);
				$currentCtrlClassName = str_replace('\\', '/', $currentCtrlClassName);
				$toolClass = $this->application->GetToolClass();
				$controllerNameDashed = $toolClass::GetDashedFromPascalCase($currentCtrlClassName);
			}
			if ($controllerOrActionNameDashed !== NULL) {
				$actionNameDashed = $controllerOrActionNameDashed;
			} else {
				if ($currentCtrlIsTopMostParent) {// if controller is top most parent - use routed action name
					$actionNameDashed = $this->actionName;
				} else {// if no action name defined - use default action name from core - usually `index`
					$defaultCtrlAction = $this->application->GetDefaultControllerAndActionNames();
					$actionNameDashed = $toolClass::GetDashedFromPascalCase($defaultCtrlAction[1]);
				}
			}
		}
		$controllerPath = str_replace(['_', '\\'], '/', $controllerNameDashed);
		return implode('/', [$controllerPath, $actionNameDashed]);
	}

	/**
	 * Default rendering mode.
	 * Render action view first into output buffer, then render layout view
	 * wrapped around rendered action view string also into output buffer.
	 * Then set up rendered content from output buffer into response object
	 * and then send HTTP headers and content after all.
	 * @param string $controllerOrActionNameDashed
	 * @param string $actionNameDashed
	 * @param bool   $topMostParentCtrl
	 * @return string
	 */
	protected function & renderWithObFromActionToLayout ($controllerOrActionNameDashed, $actionNameDashed, $topMostParentCtrl) {
		/** @var $this \MvcCore\Controller */
		// complete paths
		$viewScriptPath = $this->GetViewScriptPath($controllerOrActionNameDashed, $actionNameDashed);
		// render action view into string
		$this->view->SetUpRender(
			$this->renderMode, $controllerOrActionNameDashed, $actionNameDashed
		);
		$actionResult = $this->view->RenderScript($viewScriptPath);
		if (!$topMostParentCtrl) {
			$this->dispatchState = 4;
			return $actionResult;
		}
		// create top most parent layout view, set up and render to outputResult
		$viewClass = $this->application->GetViewClass();
		/** @var $layout \MvcCore\View */
		$layout = $viewClass::CreateInstance()
			->SetController($this)
			->SetUpStore($this->view, TRUE)
			->SetUpRender(
				$this->renderMode, $controllerOrActionNameDashed, $actionNameDashed
			);
		$outputResult = $layout->RenderLayoutAndContent($this->layout, $actionResult);
		unset($layout, $this->view);
		// set up response only
		$this->XmlResponse($outputResult, FALSE);
		$this->dispatchState = 4;
		$result = '';
		return $result;
	}

	/**
	 * Special rendering mode to continuously sent larger data to client.
	 * Render layout view and render action view together inside it without
	 * output buffering. There is not used reponse object body property for
	 * this rendering mode. Http headers are sent before view rendering.
	 * @param string $controllerOrActionNameDashed
	 * @param string $actionNameDashed
	 * @param bool   $topMostParentCtrl
	 * @return string
	 */
	protected function & renderWithoutObContinuously ($controllerOrActionNameDashed, $actionNameDashed, $topMostParentCtrl) {
		/** @var $this \MvcCore\Controller */
		if ($topMostParentCtrl) {
			// render layout view and action view inside it:
			$viewClass = $this->application->GetViewClass();
			/** @var $layout \MvcCore\View */
			$layout = $viewClass::CreateInstance()
				->SetController($this)
				->SetUpStore($this->view, TRUE)
				->SetUpRender(
					$this->renderMode, $controllerOrActionNameDashed, $actionNameDashed
				);
			// render layout continuously with action view inside
			$layout->RenderLayout($this->layout);
		} else {
			// complete paths
			$viewScriptPath = $this->GetViewScriptPath($controllerOrActionNameDashed, $actionNameDashed);
			// render action view into string
			$this->view->RenderScript($viewScriptPath);
		}
		$this->dispatchState = 4;
		$result = '';
		return $result;
	}
}
