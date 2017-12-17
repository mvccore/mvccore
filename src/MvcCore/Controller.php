<?php

/**
 * MvcCore
 *
 * This source file is subject to the BSD 3 License
 * For the full copyright and license information, please view
 * the LICENSE.md file that are distributed with this source code.
 *
 * @copyright	Copyright (c) 2016 Tom Flídr (https://github.com/mvccore/mvccore)
 * @license		https://mvccore.github.io/docs/mvccore/4.0.0/LICENCE.md
 */

namespace MvcCore;

require_once(__DIR__.'/../MvcCore.php');
require_once('Request.php');
require_once('Response.php');
require_once('View.php');

/**
 * Application controller:
 * - template methods:
 *	 (necessary to call parent at method begin)
 *   - Init()
 *		- called after controller is created
 *		- session start
 *		- all internal variables initialized except view
 *   - PreDispatch()
 *		- called after Init, before every controller action
 *		- view initialization
 * - internal actions:
 *	 - AssetAction()
 *	   - handling internal MvcCore http request
 *	     to get assets from packed package
 * - url proxy method, reading request param proxy method
 * - view rendering or no-rendering management
 * - http responses and redirects management
 * - basic error responses rendering
 * - request termination (to write and close session)
 */
class Controller
{
	/**
	 * Request object - parsed uri, query params, app paths...
	 * @var \MvcCore\Request
	 */
	protected $request;

	/**
	 * Response object - headers and rendered body
	 * @var \MvcCore\Response
	 */
	protected $response;

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
	 * @var \MvcCore\View
	 */
	protected $view = NULL;

	/**
	 * Registered controls instances.
	 * @var \MvcCore\Ext\Control[]|array
	 */
	protected $controls = array();

	/**
	 * Layout name to render html wrapper around rendered view
	 * @var string
	 */
	protected $layout = 'layout';

	/**
	 * Boolean about disabled or enabled view to render at last
	 * @var boolean
	 */
	protected $viewEnabled = TRUE;

	/**
	 * Path to all static files - css, js, imgs and fonts
	 * @var string
	 */
	protected static $staticPath = '/static';

	/**
	 * Path to temporary directory with generated css and js files
	 * @var string
	 */
	protected static $tmpPath = '/Var/Tmp';

	/**
	 * All asset mime types possibly called throught Asset action
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
	);

	/**
	 * Create new controller instance - always called from \MvcCore app instance before controller is dispatched.
	 * Never used in application controllers.
	 * @param \MvcCore\Request $request
	 */
	public function __construct (\MvcCore\Request & $request = NULL, \MvcCore\Response & $response = NULL) {
		$this->request = & $request;
		$this->controller = $request->Params['controller'];
		$this->action = $request->Params['action'];
		if ($this->controller == 'controller' && $this->action == 'asset') {
			$this->DisableView();
			return;
		}
		$this->response = & $response;
		if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strlen($_SERVER['HTTP_X_REQUESTED_WITH']) > 0) {
			$this->ajax = TRUE;
			$this->DisableView();
		}
	}

	/**
	 * Dispatching controller life cycle by given action,
	 * call this imediatelly after calling controller __construct()
	 * with request and response params. This function automaticly
	 * complete response object with content, which you can send
	 * to browser by $ctrl->Terminate() method.
	 * @param string $actionName php code action name in PascalCase (this value is used to call your desired function in controller without any change)
	 * @return void
	 */
	public function Run ($actionName = "Index") {
		// \MvcCore\Debug::Timer('dispatch');
		$this->Init();
		// \MvcCore\Debug::Timer('dispatch');
		$this->PreDispatch();
		// \MvcCore\Debug::Timer('dispatch');
		if (method_exists($this, $actionName)) $this->$actionName();
		// \MvcCore\Debug::Timer('dispatch');
		$this->Render(
			$this->request->Params['controller'],	// dashed ctrl name
			$this->request->Params['action']		// dashed action name
		);
		// \MvcCore\Debug::Timer('dispatch');
	}

	/**
	 * Application controllers initialization.
	 * This is best time to initialize language, locale or session.
	 * @return void
	 */
	public function Init () {
		\MvcCore::SessionStart();
		foreach ($this->controls as $control) $control->Init();
	}

	/**
	 * Application pre render common action - always used in application controllers.
	 * This is best time to define any common properties or common view properties.
	 * @return void
	 */
	public function PreDispatch () {
		if ($this->viewEnabled) {
			$viewClass = \MvcCore::GetInstance()->GetViewClass();
			$this->view = new $viewClass($this);
		}
		foreach ($this->controls as $control) $control->PreDispatch();
	}

	/**
	 * Get param value, filtered for characters defined as second argument to use them in preg_replace().
	 * Shortcut for $this->request->GetParam();
	 * @param string $name
	 * @param string $pregReplaceAllowedChars
	 * @return string
	 */
	public function GetParam ($name = "", $pregReplaceAllowedChars = "a-zA-Z0-9_/\-\.\@") {
		return $this->request->GetParam($name, $pregReplaceAllowedChars);
	}

	/**
	 * Get current application request object as reference.
	 * @return \MvcCore\Request
	 */
	public function & GetRequest () {
		return $this->request;
	}

	/**
	 * Get current application request object, rarely used.
	 * @param \MvcCore\Request $request
	 * @return \MvcCore\Controller
	 */
	public function SetRequest (\MvcCore\Request & $request) {
		$this->request = $request;
		return $this;
	}

	/**
	 * Get current application response object as reference.
	 * @return \MvcCore\Response
	 */
	public function & GetResponse () {
		return $this->response;
	}

	/**
	 * Get current application response object, rarely used.
	 * @param \MvcCore\Response $response
	 * @return \MvcCore\Controller
	 */
	public function SetResponse (\MvcCore\Response & $response) {
		$this->response = $response;
		return $this;
	}

	/**
	 * Boolean about ajax request
	 * @return bool
	 */
	public function IsAjax () {
		return $this->ajax;
	}

	/**
	 * Boolean about disabled or enabled view to render at last
	 * @return bool
	 */
	public function IsViewEnabled () {
		return $this->viewEnabled;
	}

	/**
	 * Return current controller view object if any.
	 * Before PreDispatch() should be still NULL.
	 * @return \MvcCore\View|NULL
	 */
	public function & GetView () {
		return $this->view;
	}

	/**
	 * Set current controller view object, rarely used.
	 * @param \MvcCore\View $view
	 * @return \MvcCore\Controller
	 */
	public function SetView (\MvcCore\View & $view) {
		$this->view = $view;
		return $this;
	}

	/**
	 * Get layout name: 'front' | 'admin' | 'account' ...
	 * @return string
	 */
	public function GetLayout () {
		return $this->layout;
	}

	/**
	 * Set layout name
	 * @param string $layout
	 * @return \MvcCore\Controller
	 */
	public function SetLayout ($layout = '') {
		$this->layout = $layout;
		return $this;
	}

	/**
	 * Disable view rendering - always called in text or ajax responses.
	 * @return void
	 */
	public function DisableView () {
		$this->viewEnabled = FALSE;
	}

	/**
	 * Register control to process dispatching on it
	 * @return \MvcCore\Controller
	 */
	public function AddControl (/*\MvcCore\Ext\Control*/ & $control) {
		$this->controls[] = & $control;
		return $this;
	}

	/**
	 * Return small assets content with proper headers in single file application mode
	 * @throws \Exception
	 * @return void
	 */
	public function AssetAction () {
		$ext = '';
		$path = $this->GetParam('path');
		$path = '/' . ltrim(str_replace('..', '', $path), '/');
		if (
			strpos($path, static::$staticPath) !== 0 &&
			strpos($path, static::$tmpPath) !== 0
		) {
			throw new \Exception("[".__CLASS__."] File path: '$path' is not allowed.", 500);
		}
		$path = $this->request->AppRoot . $path;
		if (!file_exists($path)) {
			throw new \Exception("[".__CLASS__."] File not found: '$path'.", 404);
		}
		$lastDotPos = strrpos($path, '.');
		if ($lastDotPos !== FALSE) {
			$ext = substr($path, $lastDotPos + 1);
		}
		if (isset(self::$_assetsMimeTypes[$ext])) {
			header('Content-Type: ' . self::$_assetsMimeTypes[$ext]);
		}
		header_remove('X-Powered-By');
		header('Vary: Accept-Encoding');
		$assetMTime = @filemtime($path);
		if ($assetMTime) header('Last-Modified: ' . gmdate('D, d M Y H:i:s T', $assetMTime));
		readfile($path);
		exit;
	}

	/**
	 * Render and send prepared controller view, all sub views and controller layout view.
	 * @param mixed $controllerName
	 * @param mixed $actionName
	 * @return void
	 */
	public function Render ($controllerName = '', $actionName = '') {
		if ($this->viewEnabled) {
			if (!$controllerName)	$controllerName	= $this->request->params['controller'];
			if (!$actionName)		$actionName		= $this->request->params['action'];
			// complete paths
			$controllerPath = str_replace(array('_', '\\'), '/', $controllerName);
			$viewScriptPath = implode('/', array(
				$controllerPath, $actionName
			));
			// render content string
			$actionResult = $this->view->RenderScript($viewScriptPath);
			// create parent layout view, set up and render to outputResult
			$viewClass = \MvcCore::GetInstance()->GetViewClass();
			/** @var $layout \MvcCore\View */
			$layout = new $viewClass($this);
			$layout->SetUp($this->view);
			$outputResult = $layout->RenderLayoutAndContent($this->layout, $actionResult);
			unset($layout, $this->view);
			// send response and exit
			$this->HtmlResponse($outputResult);
			$this->DisableView(); // disable to not render it again
		}
	}

	/**
	 * Send rendered html output to user.
	 * @param mixed $output
	 * @return void
	 */
	public function HtmlResponse ($output = "") {
		$contentTypeHeaderValue = strpos(\MvcCore\View::$Doctype, \MvcCore\View::DOCTYPE_XHTML) !== FALSE ? 'application/xhtml+xml' : 'text/html' ;
		$this->response
			->SetHeader('Content-Type', $contentTypeHeaderValue . '; charset=utf-8')
			->SetBody($output);
	}

	/**
	 * Send any php value serialized in json to user.
	 * @param mixed $data
	 * @return void
	 */
	public function JsonResponse ($data = array()) {
		$output = \MvcCore\Tool::EncodeJson($data);
		$this->response
			->SetHeader('Content-Type', 'text/javascript; charset=utf-8')
			->SetHeader('Content-Length', strlen($output))
			->SetBody($output);
	}

	/**
	 * Generates url by:
	 * - 'Controller:Action' name and params array
	 *   (for routes configuration when routes array has keys with 'Controller:Action' strings
	 *   and routes has not controller name and action name defined inside)
	 * - route name and params array
	 *	 (route name is key in routes configuration array, should be any string
	 *	 but routes must have information about controller name and action name inside)
	 * Result address should have two forms:
	 * - nice rewrited url by routes configuration
	 *   (for apps with .htaccess supporting url_rewrite and when first param is key in routes configuration array)
	 * - for all other cases is url form: index.php?controller=ctrlName&action=actionName
	 *	 (when first param is not founded in routes configuration array)
	 * @param string $controllerActionOrRouteName	Should be 'Controller:Action' combination or just any route name as custom specific string
	 * @param array  $params						optional
	 * @return string
	 */
	public function Url ($controllerActionOrRouteName = 'Index:Index', $params = array()) {
		return \MvcCore\Router::GetInstance()->Url($controllerActionOrRouteName, $params);
	}

	/**
	 * Return asset path or single file mode url
	 * @param string $path
	 * @return string
	 */
	public function AssetUrl ($path = '') {
		return \MvcCore::GetInstance()->Url('Controller:Asset', array('path' => $path));
	}

	/**
	 * Render controller action for error or error plain text response.
	 * @param string $exceptionMessage
	 * @return void
	 */
	public function RenderError ($exceptionMessage = '') {
		if (\MvcCore::GetInstance()->IsErrorDispatched()) return;
		throw new \ErrorException(
			$exceptionMessage ? $exceptionMessage :
			"Server error: \n'" . $this->request->FullUrl . "'",
			500
		);
	}

	/**
	 * Render not found controller action or not found plain text response.
	 * @return void
	 */
	public function RenderNotFound () {
		if (\MvcCore::GetInstance()->IsNotFoundDispatched()) return;
		throw new \ErrorException(
			"Page not found: \n'" . $this->request->FullUrl . "'", 404
		);
	}

	/**
	 * Terminate request. Write session, send headers if possible and echo response body.
	 * @return void
	 */
	public function Terminate () {
		\MvcCore::GetInstance()->Terminate();
	}

	/**
	 * Redirect user browser to another location.
	 * @param string $location
	 * @param int    $code
	 * @return void
	 */
	public static function Redirect ($location = '', $code = \MvcCore\Response::SEE_OTHER) {
		\MvcCore::GetInstance()->GetResponse()
			->SetCode($code)
			->SetHeader('Location', $location);
		\MvcCore::GetInstance()->Terminate();
	}
}