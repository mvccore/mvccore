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
trait Rendering {

	/**
	 * @inheritDocs
	 * @return string
	 */
	public function __toString () {
		return $this->Render();
	}

	/**
	 * @inheritDocs
	 * @param  string|NULL $controllerOrActionNameDashed
	 * @param  string|NULL $actionNameDashed
	 * @return string
	 */
	public function Render ($controllerOrActionNameDashed = NULL, $actionNameDashed = NULL) {
		if (!$this->renderCheckDispatchState()) return '';

		$topMostParentCtrl = $this->parentController === NULL;
		
		// If this is child controller - set up view store with parent controller view store, do not overwrite existing keys:
		if (!$topMostParentCtrl)
			$this->view->SetUpStore($this->parentController->GetView(), FALSE);
		
		// Set up child controllers into view if any of them is named by string index:
		foreach ($this->childControllers as $ctrlKey => $childCtrl) {
			if (!is_numeric($ctrlKey) && !isset($this->view->{$ctrlKey}))
				$this->view->{$ctrlKey} = $childCtrl;
		}
		
		// Render this view or view with layout by render mode:
		if (($this->renderMode & \MvcCore\IView::RENDER_WITH_OB_FROM_ACTION_TO_LAYOUT) != 0) {
			$this->renderWithObFromActionToLayout(
				$controllerOrActionNameDashed, $actionNameDashed, $topMostParentCtrl
			);

		} else /*if (($this->renderMode & \MvcCore\IView::RENDER_WITHOUT_OB_CONTINUOUSLY) != 0)*/ {
			if ($topMostParentCtrl) {
				$sessionClass = $this->GetApplication()->GetSessionClass();
				if ($sessionClass::GetStarted() && !$this->response->IsSentHeaders()) {
					$sessionClass::SendCookie();
					$sessionClass::Close();
				}
				$this->response->SendHeaders();
				if ($this->request->GetMethod() === \MvcCore\IRequest::METHOD_HEAD) {
					$this->Terminate();
					return '';
				}
			}
			//if (ob_get_length() !== FALSE) // flush out any previous content
			//	while (ob_get_level() > 0) ob_end_flush();
			$this->renderWithoutObContinuously(
				$controllerOrActionNameDashed,$actionNameDashed, $topMostParentCtrl
			);
		}
		
		$this->dispatchState = \MvcCore\IController::DISPATCH_STATE_RENDERED;
		return '';
	}

	/**
	 * @inheritDocs
	 * @param  string $output
	 * @param  bool   $terminate
	 * @return void
	 */
	public function HtmlResponse ($output, $terminate = TRUE) {
		if (!$this->response->HasHeader('Content-Type')) {
			$viewClass = $this->application->GetViewClass();
			$contentTypeHeaderValue = strpos(
				$viewClass::GetDoctype(), \MvcCore\IView::DOCTYPE_XHTML
			) !== FALSE ? 'application/xhtml+xml' : 'text/html' ;
			$this->response->SetHeader('Content-Type', $contentTypeHeaderValue);
		}
		$this->response
			->SetCode(\MvcCore\IResponse::OK)
			->SetBody($output);
		if ($terminate) $this->Terminate();
	}

	/**
	 * @inheritDocs
	 * @param  string $output
	 * @param  bool   $terminate
	 * @return void
	 */
	public function XmlResponse ($output, $terminate = TRUE) {
		$res = $this->response;
		if (!$res->HasHeader('Content-Type'))
			$res->SetHeader('Content-Type', 'application/xml');
		$res->SetBody($output);
		if ($res->GetCode() === NULL)
			$res->SetCode(\MvcCore\IResponse::OK);
		if ($terminate) $this->Terminate();
	}

	/**
	 * @inheritDocs
	 * @param  string $output
	 * @param  bool   $terminate
	 * @return void
	 */
	public function TextResponse ($output, $terminate = TRUE) {
		$res = $this->response;
		if (!$res->HasHeader('Content-Type'))
			$res->SetHeader('Content-Type', 'text/plain');
		$res->SetBody($output);
		if ($res->GetCode() === NULL)
			$res->SetCode(\MvcCore\IResponse::OK);
		if ($terminate) $this->Terminate();
	}

	/**
	 * @inheritDocs
	 * @param  mixed $data
	 * @param  bool  $terminate
	 * @param  int   $jsonEncodeFlags
	 * @throws \Exception JSON encoding error.
	 * @return void
	 */
	public function JsonResponse ($data, $terminate = TRUE, $jsonEncodeFlags = 0) {
		$res = $this->response;
		/** @var \MvcCore\Tool|string $toolClass */ 
		$toolClass = $this->application->GetToolClass();
		$output = $toolClass::JsonEncode($data, $jsonEncodeFlags);
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
	 * @inheritDocs
	 * @param  mixed      $data
	 * @param  string     $callbackParamName
	 * @param  bool       $terminate
	 * @param  int        $jsonEncodeFlags
	 * @throws \Exception JSON encoding error.
	 * @return void
	 */
	public function JsonpResponse ($data, $callbackParamName = 'callback', $terminate = TRUE, $jsonEncodeFlags = 0) {
		$res = $this->response;
		/** @var \MvcCore\Tool|string $toolClass */ 
		$toolClass = $this->application->GetToolClass();
		$output = $toolClass::JsonEncode($data, $jsonEncodeFlags);
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
	 * @inheritDocs
	 * @param  string $exceptionMessage
	 * @return void
	 */
	public function RenderError ($exceptionMessage) {
		if ($this->application->IsErrorDispatched()) return;
		throw new \ErrorException(
			$exceptionMessage 
				? $exceptionMessage :
				"Server error: `" . htmlspecialchars($this->request->GetFullUrl()) . "`.",
			500
		);
	}

	/**
	 * @inheritDocs
	 * @return void
	 */
	public function RenderNotFound () {
		if ($this->application->IsNotFoundDispatched()) return;
		throw new \ErrorException(
			"Page not found: `" . htmlspecialchars($this->request->GetFullUrl()) . "`.", 
			404
		);
	}

	/**
	 * @inheritDocs
	 * @param  string $controllerOrActionNameDashed
	 * @param  string $actionNameDashed
	 * @return string
	 */
	public function GetViewScriptPath ($controllerOrActionNameDashed = NULL, $actionNameDashed = NULL) {
		$currentCtrlIsTopMostParent = $this->parentController === NULL;
		if ($this->viewScriptsPath !== NULL) {
			// subcontrollers views path customization:
			$resultPathItems = [$this->viewScriptsPath];
			if ($controllerOrActionNameDashed !== NULL) 
				$resultPathItems[] = $controllerOrActionNameDashed;
			if ($actionNameDashed !== NULL) {
				$resultPathItems = [
					$this->getViewScriptPathCtrlName($controllerOrActionNameDashed),
					$actionNameDashed
				];
			}
			$viewScriptPath = str_replace(['_', '\\'], '/', implode('/', $resultPathItems));
			$viewScriptPath = preg_replace("#//+#", '/', $viewScriptPath);
			return $viewScriptPath;
		}
		if ($actionNameDashed !== NULL) { // if action defined - take first argument controller
			$controllerNameDashed = $this->getViewScriptPathCtrlName($controllerOrActionNameDashed);
		} else { // if no action defined - we need to complete controller dashed name
			$toolClass = '';
			if ($currentCtrlIsTopMostParent) { // if controller is tom most one - take routed controller name
				$controllerNameDashed = $this->getViewScriptPathCtrlName($this->controllerName);
			} else {
				// if controller is child controller - translate class name
				// without default controllers directory into dashed name
				$ctrlsDefaultNamespace = (
					$this->application->GetAppDir() . '\\' . $this->application->GetControllersDir()
				);
				$currentCtrlClassName = get_class($this);
				if (mb_strpos($currentCtrlClassName, $ctrlsDefaultNamespace) === 0)
					$currentCtrlClassName = mb_substr($currentCtrlClassName, mb_strlen($ctrlsDefaultNamespace) + 1);
				$currentCtrlClassName = str_replace('\\', '/', $currentCtrlClassName);
				$toolClass = $this->application->GetToolClass();
				$controllerNameDashed = $this->getViewScriptPathCtrlName(
					$toolClass::GetDashedFromPascalCase($currentCtrlClassName)
				);
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
	 * Get relative path to view script by controller name
	 * inside `./App`Views/Scripts/` directory without view file extension.
	 * @param string $controllerNameDashed 
	 * @return string
	 */
	protected function getViewScriptPathCtrlName (string $controllerNameDashed): string {
		$currentRoute = $this->router->GetCurrentRoute();
		$ctrlHasAbsNamespace = $currentRoute !== NULL
			? $currentRoute->GetControllerHasAbsoluteNamespace()
			: FALSE;
		if (!$ctrlHasAbsNamespace) {
			return $controllerNameDashed;
		} else {
			// remove substring `app/controllers/`
			$ctrlNameParts = explode('/', $controllerNameDashed);
			return implode('/', array_slice($ctrlNameParts, 2));
		}
	}

	/**
	 * Check controller dispatch state before rendering.
	 * Call `Init()` or `PreDispatch()` method if necessary 
	 * and return `TRUE` if view could be rendered.
	 * @return bool
	 */
	protected function renderCheckDispatchState () {
		if ($this->dispatchState >= \MvcCore\IController::DISPATCH_STATE_RENDERED)
			return FALSE;
		if ($this->dispatchState < \MvcCore\IController::DISPATCH_STATE_INITIALIZED)
			$this->Init();
		if ($this->dispatchState < \MvcCore\IController::DISPATCH_STATE_PRE_DISPATCHED)
			$this->PreDispatch();
		if ($this->dispatchState >= \MvcCore\IController::DISPATCH_STATE_RENDERED || !$this->viewEnabled) 
			return FALSE;
		return TRUE;
	}

	/**
	 * Default rendering mode.
	 * Render action view first into output buffer, then render layout view
	 * wrapped around rendered action view string also into output buffer.
	 * Then set up rendered content from output buffer into response object
	 * and then send HTTP headers and content after all.
	 * @param  string $controllerOrActionNameDashed
	 * @param  string $actionNameDashed
	 * @param  bool   $topMostParentCtrl
	 * @return void
	 */
	protected function renderWithObFromActionToLayout ($controllerOrActionNameDashed, $actionNameDashed, $topMostParentCtrl) {
		// complete paths
		$viewScriptPath = $this->GetViewScriptPath($controllerOrActionNameDashed, $actionNameDashed);
		// render action view into string
		$this->view->SetUpRender(
			$this->renderMode, $controllerOrActionNameDashed, $actionNameDashed
		);
		$actionResult = $this->view->RenderScript($viewScriptPath);
		if (!$topMostParentCtrl) {
			unset($actionResult, $this->view);
			$this->dispatchState = \MvcCore\IController::DISPATCH_STATE_RENDERED;
			return;
		}
		// create top most parent layout view, set up and render to outputResult
		/** @var \MvcCore\View $layout */
		$layout = $this->createView(FALSE)
			->SetUpStore($this->view, TRUE)
			->SetUpRender(
				$this->renderMode, $controllerOrActionNameDashed, $actionNameDashed
			);
		$outputResult = $layout->RenderLayoutAndContent($this->layout, $actionResult);
		unset($layout, $this->view);
		// set up response only
		$this->XmlResponse($outputResult, FALSE);
		unset($outputResult);
		$this->dispatchState = \MvcCore\IController::DISPATCH_STATE_RENDERED;
	}

	/**
	 * Special rendering mode to continuously sent larger data to client.
	 * Render layout view and render action view together inside it without
	 * output buffering. There is not used reponse object body property for
	 * this rendering mode. Http headers are sent before view rendering.
	 * @param  string $controllerOrActionNameDashed
	 * @param  string $actionNameDashed
	 * @param  bool   $topMostParentCtrl
	 * @return void
	 */
	protected function renderWithoutObContinuously ($controllerOrActionNameDashed, $actionNameDashed, $topMostParentCtrl) {
		if ($topMostParentCtrl) {
			// render layout view and action view inside it:
			/** @var \MvcCore\View $layout */
			$layout = $this->createView(FALSE)
				->SetUpStore($this->view, TRUE)
				->SetUpRender(
					$this->renderMode, $controllerOrActionNameDashed, $actionNameDashed
				);
			// render layout continuously with action view inside
			$layout->RenderLayout($this->layout);
			unset($layout, $this->view);
		} else {
			// complete paths
			$viewScriptPath = $this->GetViewScriptPath($controllerOrActionNameDashed, $actionNameDashed);
			// render sub view into output
			$this->view->SetUpRender(
				$this->renderMode, $controllerOrActionNameDashed, $actionNameDashed
			);
			$this->view->RenderScript($viewScriptPath);
			unset($this->view);
		}
		$this->dispatchState = \MvcCore\IController::DISPATCH_STATE_RENDERED;
	}
}
