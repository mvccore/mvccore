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
	 * - If controller has no other parent controller, render layout view aroud action view.
	 * - For top most parent controller - store rendered action and layout view in response object and return empty string.
	 * - For child controller - return rendered action view as string.
	 * @param string $controllerOrActionNameDashed
	 * @param string $actionNameDashed
	 * @return string
	 */
	public function Render ($controllerOrActionNameDashed = NULL, $actionNameDashed = NULL) {
		if ($this->dispatchState == 0) $this->Init();
		if ($this->dispatchState == 1) $this->PreDispatch();
		if ($this->dispatchState < 4 && $this->viewEnabled) {
			$currentCtrlIsTopMostParent = $this->parentController === NULL;
			if (!$currentCtrlIsTopMostParent) {
				$this->view->SetUpStore($this->parentController->GetView(), FALSE);
			}
			foreach ($this->childControllers as $ctrlKey => $childCtrl) {
				if (!is_numeric($ctrlKey) && !isset($this->view->$ctrlKey))
					$this->view->$ctrlKey = $childCtrl;
			}
			// complete paths
			$viewScriptPath = $this->renderGetViewScriptPath($controllerOrActionNameDashed, $actionNameDashed);
			// render content string
			$actionResult = $this->view->RenderScript($viewScriptPath);
			if ($currentCtrlIsTopMostParent) {
				// create top most parent layout view, set up and render to outputResult
				$viewClass = $this->application->GetViewClass();
				/** @var $layout \MvcCore\View */
				$layout = $viewClass::CreateInstance()
					->SetController($this)
					->SetUpStore($this->view, TRUE);
				$outputResult = $layout->RenderLayoutAndContent($this->layout, $actionResult);
				unset($layout, $this->view);
				// set up response only
				$this->XmlResponse($outputResult);
			} else {
				// return response
				$this->dispatchState = 4;
				return $actionResult;
			}
		}
		$this->dispatchState = 4;
		return '';
	}

	/**
	 * Store rendered HTML output inside `\MvcCore\Controller::$response`
	 * to send into client browser later in `MvcCore::Terminate();`.
	 * @param string $output
	 * @param bool $terminate
	 * @return void
	 */
	public function HtmlResponse ($output = '', $terminate = FALSE) {
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
	 * to send into client browser later in `MvcCore::Terminate();`.
	 * @param string $output
	 * @param bool $terminate
	 * @return void
	 */
	public function XmlResponse ($output = '', $terminate = FALSE) {
		if (!$this->response->HasHeader('Content-Type'))
			$this->response->SetHeader('Content-Type', 'application/xml');
		$this->response
			->SetCode(\MvcCore\IResponse::OK)
			->SetBody($output);
		if ($terminate) $this->Terminate();
	}

	/**
	 * Serialize any PHP value into `JSON string` and store
	 * it inside `\MvcCore\Controller::$response` to send it
	 * into client browser later in `MvcCore::Terminate();`.
	 * @param mixed $data
	 * @param bool  $terminate
	 * @return void
	 */
	public function JsonResponse ($data = NULL, $terminate = FALSE) {
		$toolClass = $this->application->GetToolClass();
		$output = $toolClass::EncodeJson($data);
		if (!$this->response->HasHeader('Content-Type'))
			$this->response->SetHeader('Content-Type', 'text/javascript');
		$this->response
			->SetCode(\MvcCore\IResponse::OK)
			->SetHeader('Content-Length', strlen($output))
			->SetBody($output);
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
	protected function renderGetViewScriptPath ($controllerOrActionNameDashed = NULL, $actionNameDashed = NULL) {
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
				// if controller is child controller - translate classs name
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
}
