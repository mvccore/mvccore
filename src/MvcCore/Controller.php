<?php

/**
 * MvcCore
 *
 * This source file is subject to the BSD 3 License
 * For the full copyright and license information, please view 
 * the LICENSE.md file that are distributed with this source code.
 *
 * @copyright	Copyright (c) 2016 Tom Flídr (https://github.com/mvccore/mvccore)
 * @license		https://mvccore.github.io/docs/mvccore/2.0.0/LICENCE.md
 */
	
class MvcCore_Controller
{
	/**
	 * Request properties - parsed uri and query params
	 *
	 * @var stdClass
	 */
	protected $request;
	
	/**
	 * Requested controller name - dashed
	 *
	 * @var string
	 */
	protected $controller = '';
	
	/**
	 * Requested action name - dashed
	 *
	 * @var string
	 */
	protected $action = '';

	/**
	 * Boolean about ajax request
	 *
	 * @var boolean
	 */
	protected $ajax = FALSE;
	
	/**
	 * Class store object for view properties
	 *
	 * @var stdClass
	 */
	protected $view;
	
	/**
	 * Layout name to render html wrapper around rendered view
	 *
	 * @var string
	 */
	protected $layout = 'front';

	/**
	 * Boolean about disabled or enabled view to render at last
	 *
	 * @var boolean
	 */
	protected $viewEnabled = TRUE;

	/**
	 * Boolean about output HTML minification.
	 * To minify, change this to TRUE and place library into /Libs/Minify/HTML.php
	 *
	 * @var boolean
	 */
	protected $minifyHtml = FALSE;

	/**
	 * Path to all static files - css, js, imgs and fonts
	 *
	 * @var string
	 */
	protected static $staticPath = '/static';

	/**
	 * Path to temporary directory with generated css and js files
	 *
	 * @var string
	 */
	protected static $tmpPath = '/Var/Tmp';
	
	/**
	 * All asset mime types possibly called throught Asset action
	 *
	 * @var string
	 */
	private static $_assetsMimeTypes = array(
		'js'	=> 'text/javascript',
		'css'	=> 'text/css',
		'ico'	=> 'image/x-icon',
		'gif'	=> 'image/gif',
		'png'	=> 'image/png',
		'jpg'	=> 'image/jpg',
		'jpeg'	=> 'image/jpeg',
		'bmp'	=> 'image/bmp',
		'svg'	=> 'image/svg+xml',
		'eot'	=> 'application/vnd.ms-fontobject',
		'ttf'	=> 'font/truetype',
		'otf'	=> 'font/opentype',
		'woff'	=> 'application/x-font-woff',
		'woff2'	=> 'application/x-font-woff',
	);
	
	public function __construct (& $request = NULL) {
		$this->request = $request;
		$this->controller = $this->request->params['controller'];
		$this->action = $this->request->params['action'];
		$this->Init();
	}
	public function Init () {
		MvcCore::SessionStart();
		if (
			isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
			strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'
		) {
			$this->ajax = TRUE;
			$this->DisableView();
		}
		if (get_class($this) == 'MvcCore_Controller') {
			$this->DisableView();
		}
	}
	public function PreDispatch () {
		if (!$this->ajax) $this->view = new MvcCore_View($this);
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
	public function & GetRequest () {
		return $this->request;
	}
	public function & GetView () {
		return $this->view;
	}
	public function SetLayout ($layout = '') {
		$this->layout = $layout;
	}
	public function DisableView () {
		$this->viewEnabled = FALSE;
	}
	public function AssetAction () {
		$ext = '';
		$path = $this->GetParam('path');
		$path = '/' . ltrim(str_replace('..', '', $path), '/');
		if (
			strpos($path, self::$staticPath) !== 0 &&
			strpos($path, self::$tmpPath) !== 0
		) {
			throw new Exception("[MvcCore_Controller] File path: '$path' is not allowed.");
		}
		$path = $this->request->appRoot . $path;
		if (!file_exists($path)) {
			throw new Exception("[MvcCore_Controller] File not found: '$path'.");
		}
		$lastDotPos = strrpos($path, '.');
		if ($lastDotPos !== FALSE) {
			$ext = substr($path, $lastDotPos + 1);
		}
		if (isset(self::$_assetsMimeTypes[$ext])) {
			header('Content-Type: ' . self::$_assetsMimeTypes[$ext]);
		}
		readfile($path);
	}
	public function Render ($controllerName = '', $actionName = '') {
		if ($this->viewEnabled) {
			if (!$controllerName)	$controllerName	= $this->request->params['controller'];
			if (!$actionName)		$actionName		= $this->request->params['action'];
			// complete paths
			$controllerPath = str_replace('_', DIRECTORY_SEPARATOR, $controllerName);
			$viewScriptPath = implode(DIRECTORY_SEPARATOR, array(
				$controllerPath, $actionName
			));
			// render content string
			$actionResult = $this->view->RenderScript($viewScriptPath);
			// create parent layout view, set up and render to outputResult
			$layout = new MvcCore_View($this);
			$layout->SetUp($this->view);
			$outputResult = $layout->RenderLayout($this->layout, $actionResult);
			unset($layout, $this->view);
			// minify if class exists
			if ($this->minifyHtml && class_exists('Minify_HTML')) $outputResult = Minify_HTML::minify($outputResult);
			// send response and exit
			$this->HtmlResponse($outputResult);
		}
	}
	public function HtmlResponse ($output = "") {
		header('Content-Type: text/html; charset=utf-8');
		if (class_exists('Debug') && Debug::$productionMode) header('Content-Length: ' . strlen($output));
		self::addTimeAndMemoryHeader();
		echo $output;
		$this->Terminate();
	}
	public function JsonResponse ($data = array()) {
		if (!defined('JSON_UNESCAPED_SLASHES')) define('JSON_UNESCAPED_SLASHES', 64);
		if (!defined('JSON_UNESCAPED_UNICODE')) define('JSON_UNESCAPED_UNICODE', 256);
		$output = json_encode($data, JSON_HEX_TAG | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
		header('Content-Type: text/javascript; charset=utf-8');
		if (class_exists('Debug') && Debug::$productionMode) header('Content-Length: ' . strlen($output));
		self::addTimeAndMemoryHeader();
		echo $output;
		$this->Terminate();
	}
	public function Url ($controllerAction = '', $params = array()) {
		return MvcCore::GetInstance()->Url($controllerAction, $params);
	}
	public function AssetUrl ($path = '') {
		return MvcCore::GetInstance()->Url('Controller::Asset', array('path' => $path));
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
		header("HTTP/1.0 $code $status");
		header("Location: $location");
		MvcCore::Terminate();
	}
	public function Terminate () {
		MvcCore::Terminate();
	}
	protected function redirectToNotFound () {
		if ($this->checkIfDefaultNotFoundControllerActionExists()) {
			self::Redirect(
				$this->url('Default::NotFound'), 404
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
		header('HTTP/1.0 404 Not Found');
		header('Content-Type: text/plain');
		echo 'Error 404 – Page Not Found.';
		$this->Terminate();
	}
}