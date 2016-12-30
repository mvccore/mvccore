<?php

/**
 * MvcCore
 *
 * This source file is subject to the BSD 3 License
 * For the full copyright and license information, please view 
 * the LICENSE.md file that are distributed with this source code.
 *
 * @copyright	Copyright (c) 2016 Tom Flídr (https://github.com/mvccore/mvccore)
 * @license		https://mvccore.github.io/docs/mvccore/1.0.0/LICENCE.md
 */
 
class MvcCore_Controller {	
	/**
	 * Request properties - parsed uri and query params
	 * @var stdClass
	 */
	protected $request;
	/**
	 * Requested controller name - dashed
	 * @var string
	 */
	protected $controller = '';
	/**
	 * Requested action name - dashed
	 * @var string
	 */
	protected $action = '';
	/**
	 * Boolean about ajax request
	 * @var boolean
	 */
	protected $ajax = FALSE;
	/**
	 * Class store object for view properties
	 * @var stdClass
	 */
	protected $view;
	/**
	 * Layout name to render html wrapper around rendered view
	 * @var string
	 */
	protected $layout = 'front';
	/**
	 * Boolean about disabled or enabled view to render at last
	 * @var boolean
	 */
	protected $viewEnabled = TRUE;
	public function __construct ($request = NULL) {
		$this->request = $request;
		$this->controller = $this->request->params['controller'];
		$this->action = $this->request->params['action'];
		$this->Init();
	}
	public function Init () {
		$sessionNotStarted = function_exists('session_status') ? session_status() == PHP_SESSION_NONE : session_id() == '' ;
		if ($sessionNotStarted) session_start();
		if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
			$this->ajax = TRUE;
			$this->DisableView();
		}
	}
	public function PreDispatch () {
		if (!$this->ajax) {
			$this->view = new MvcCore_View($this);
		}
	}
	public function GetParam ($name = "", $pregReplaceAllowedChars = "a-zA-Z0-9_/\-\.\@") {
		$result = '';
		$params = $this->request->params;
		if (isset($params[$name])) {
			$rawValue = trim($params[$name]);
			if (mb_strlen($rawValue) > 0) {
				if (!$pregReplaceAllowedChars || $pregReplaceAllowedChars == ".*") {
					$result = $rawValue;
				} else {
					$pattern = "#[^" . $pregReplaceAllowedChars . "]#";
					$result = preg_replace($pattern, "", $rawValue);
				}
			}
		}
		return $result;
	}
	public function GetRequest () {
		return $this->request;
	}
	public function SetLayout ($layout = '') {
		$this->layout = $layout;
	}
	public function DisableView () {
		$this->viewEnabled = FALSE;
	}
	public function Render ($controllerName = '', $actionName = '') {	
		if ($this->viewEnabled) {
			if (!$controllerName)	$controllerName	= $this->request->params['controller'];
			if (!$actionName)		$actionName		= $this->request->params['action'];
			$controllerPath = str_replace('_', DIRECTORY_SEPARATOR, $controllerName);
			$filename = $actionName . MvcCore_View::EXTENSION;
			$viewScriptPath = implode(DIRECTORY_SEPARATOR, array(
				$controllerPath, $filename
			));
			$actionResult = $this->view->RenderScript($viewScriptPath);
			$layout = new MvcCore_View($this);
			$layout->SetUp($this->view);
			$layoutScriptPath = $this->layout . MvcCore_View::EXTENSION;
			$outputResult = $layout->RenderLayout($layoutScriptPath, $actionResult);
			if (class_exists('Minify_HTML')) $outputResult = Minify_HTML::minify($outputResult);
			$this->HtmlResponse($outputResult);
		}
	}
	public function HtmlResponse ($output = "") {
		header('Content-Type: text/html; charset=utf-8');
		if (class_exists('Debug') && Debug::$productionMode) header('Content-Length: ' . strlen($output));
		self::addTimeAndMemoryHeader();
		echo $output;
		exit;
	}
	public function JsonResponse ($data = array()) {
		$output = json_encode($data, JSON_HEX_TAG | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
		header('Content-Type: text/javascript; charset=utf-8');
		if (class_exists('Debug') && Debug::$productionMode) header('Content-Length: ' . strlen($output));
		self::addTimeAndMemoryHeader();
		echo $output;
		exit;
	}
	public function Url ($controllerAction = '', $params = array()) {
		return MvcCore::GetInstance()->Url($controllerAction, $params);
	}
	protected static function addTimeAndMemoryHeader () {
		$time = number_format((microtime(TRUE) - MvcCore::GetMicrotime()) * 1000, 1, '.', ' ');
		$ram = function_exists('memory_get_peak_usage') ? number_format(memory_get_peak_usage() / 1000000, 2, '.', ' ') : 'n/a';
		header("X-MvcCore-Cpu-Ram: $time ms, $ram MB");
	}
	public static function Redirect ($location = '', $code = 303) {
		$codes = array(
			301	=> 'Moved Permanently',
			303	=> 'See Other',
			404	=> 'Not Found',
		);
		$status = isset($codes[$code]) ? ' ' . $codes[$code] : '';
		header("HTTP/1.0 $code$status");
		header("Location: $location");
		exit;
	}
	protected function redirectToNotFound () {
		if ($this->checkIfDefaultNotFoundControllerActionExists()) {
			self::Redirect(
				$this->url('Default::NotFound'), 
				404
			);
		} else {
			$this->renderNotFoundPlainText();
		}
	}
	protected function renderNotFound () {
		if ($this->checkIfDefaultNotFoundControllerActionExists()) {
			if (!($this->view instanceof MvcCore_View)) $this->view = new MvcCore_View($this);
			$this->Render('default', 'not-found');
		} else {
			$this->renderNotFoundPlainText();
		}
	}
	protected function checkIfDefaultNotFoundControllerActionExists () {
		$controllerName = 'App_Controllers_Default';
		return (bool) class_exists($controllerName) && method_exists($controllerName, 'NotFoundAction');
	}
	protected function renderNotFoundPlainText () {
		header("HTTP/1.0 404");
		header("Content-Type: text/plain");
		echo "Error 404 – Page not found.";
		exit;
	}
}