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

require_once('Controller.php');

/**
 * Core view:
 * - static doctype storage
 * - static storage for dir names with view scripts
 * - possible to use for any controller/subcontroller/control/\MvcCore\Ext\Form
 * - view prerender preparing and rendering
 * - direct code evaluation
 * - view helpers management
 *   - creating by predefined class names bases
 *   - instance storing and calling
 * - views sub scripts relative path solving
 * - Url() proxy method, AssetUrl() proxy method
 * - magic calls:
 *   - __call() - helpers handling
 *   - __set() - to set anything in controller to get it back in view
 *   - __get() - to get anything in view previously completed in controller
 * - no special view language implemented... why to use such stupid things...
 */
class View
{
	/**
	 * View output document type HTML4
	 * @var string
	 */
	const DOCTYPE_HTML4 = 'HTML4';

	/**
	 * View output document type XHTML
	 * @var string
	 */
	const DOCTYPE_XHTML = 'XHTML';

	/**
	 * View output document type HTML5
	 * @var string
	 */
	const DOCTYPE_HTML5 = 'HTML5';

	/**
	 * View script files extenion in Views application directory
	 * @var string
	 */
	const EXTENSION = '.phtml';

	/**
	 * Document type (HTML, XHTML or anything you desire)
	 * @var string
	 */
	public static $Doctype = self::DOCTYPE_HTML5;

	/**
	 * Controller action templates directory placed in '/App/Views' dir. For read & write.
	 * @var string
	 */
	public static $ScriptsDir = 'Scripts';

	/**
	 * views helpers directory placed in '/App/Views' dir. For read & write.
	 * @var string
	 */
	public static $HelpersDir = 'Helpers';

	/**
	 * Layout templates directory placed in '/App/Views' dir. For read & write.
	 * @var string
	 */
	public static $LayoutsDir = 'Layouts';

	/**
	 * Helpers classes - base class names. For read & write.
	 * @var array
	 */
	public static $HelpersClassBases = array(/*'\MvcCore\Ext\View\Helpers\'*/);

	/**
	 * Rendered content
	 * @var \MvcCore\Controller|mixed
	 */
	public $Controller;

	/**
	 * Rendered content
	 * @var string
	 */
	private $_content = '';

	/**
	 * Currently rendered php/html file path
	 * @var array
	 */
	private $_renderedFullPaths = array();

	/**
	 * Originaly declared dynamic properties to protect from __set() magic method
	 * @var string
	 */
	protected static $originalyDeclaredProperties = array(
		'Controller'		=> 1,
		'_content'			=> 1,
		'_renderedFullPaths'=> 1,
	);

	/**
	 * Helpers instances storrage
	 * @var array
	 */
	private static $_helpers = array();

	/**
	 * Static initialization - complete static::$HelpersClassBases by app configuration.
	 * @return void
	 */
	public static function StaticInit () {
		$app = \MvcCore::GetInstance();
		static::$HelpersClassBases = array(
			'\MvcCore\Ext\View\Helpers\\',
			// '\App\Views\Helpers\'
			'\\' . implode('\\', array(
				$app->GetAppDir(),
				$app->GetViewsDir(),
				static::$HelpersDir
			)) . '\\',
		);
	}

	/**
	 * Add view helpers class base name(s),
	 * example: \MvcCore\View::AddHelpersClassBases('\Any\Other\ViewHelpers\Place\', '...');
	 * @param string $helper,... View helper class base name(s)
	 * @return void
	 */
	public static function AddHelpersClassBases (/*...$helper*/) {
		$args = func_get_args();
		foreach ($args as $arg) {
			static::$HelpersClassBases[] = '\\' . trim($arg, '\\') . '\\';
		}
	}

	/**
	 * Get view script full path
	 * @param string $typePath
	 * @param string $corectedRelativePath
	 * @return string
	 */
	public static function GetViewScriptFullPath ($typePath = '', $corectedRelativePath = '') {
		$app = \MvcCore::GetInstance();
		return implode('/', array(
			$app->GetRequest()->AppRoot,
			$app->GetAppDir(),
			$app->GetViewsDir(),
			$typePath,
			$corectedRelativePath . \MvcCore\View::EXTENSION
		));
	}

	/**
	 * Create new view instance.
	 * @param \MvcCore\Controller $controller
	 */
	public function __construct (/*\MvcCore\Controller*/ & $controller) {
		$this->Controller = $controller;
	}

	/**
	 * Set up all keys/fields/properties in given array/stdclass/instance into current view context.
	 * @param mixed $paramsInstance
	 */
	public function SetUp (& $paramsInstance) {
		/*$type = new \ReflectionClass($paramsInstance);
		$props = $type->getProperties(
			\ReflectionProperty::IS_PUBLIC | \ReflectionProperty::IS_PROTECTED
		);
		foreach ($props as $prop) {
			/* * @var $prop \ReflectionProperty * /
			if (!$prop->isPublic()) $prop->setAccessible(TRUE);
			$propName = $prop->getName();
			$this->$propName = $prop->getValue($paramsInstance);
		}*/
		$params = get_object_vars($paramsInstance);
		foreach ($params as $key => $value) {
			if (isset(static::$originalyDeclaredProperties[$key])) continue;
			$this->$key = $value;
		}
	}

	/**
	 * Return rendered controller/action template content.
	 * @return string
	 */
	public function GetContent () {
		return $this->_content;
	}

	/**
	 * Return controller instance.
	 * @return \MvcCore\Controller
	 */
	public function GetController () {
		return $this->Controller;
	}

	/**
	 * Render controller/action template script and return it's result.
	 * @param string $relativePath
	 * @return string
	 */
	public function RenderScript ($relativePath = '') {
		return $this->Render(static::$ScriptsDir, $relativePath);
	}

	/**
	 * Render layout template script and return it's result.
	 * @param string $relativePath
	 * @return string
	 */
	public function RenderLayout ($relativePath = '') {
		return $this->Render(static::$LayoutsDir, $relativePath);
	}

	/**
	 * Render layout template script and return it's result with inner rendered content.
	 * @param string $relativePatht.
	 * @param string $content
	 * @return string
	 */
	public function RenderLayoutAndContent ($relativePath = '', $content = '') {
		$this->_content = $content;
		return $this->Render(static::$LayoutsDir, $relativePath);
	}

	/**
	 * Render controller template and all necessary layout templates and return rendered result.
	 * @param string $typePath
	 * @param string $relativePath
	 * @throws \Exception
	 * @return string
	 */
	public function Render ($typePath = '', $relativePath = '') {
		if (!$typePath) $typePath = self::$ScriptsDir;
		$result = '';
		$relativePath = $this->_correctRelativePath($this->Controller->GetRequest()->AppRoot, $typePath, $relativePath);
		$viewScriptFullPath = static::GetViewScriptFullPath($typePath, $relativePath);
		if (!file_exists($viewScriptFullPath)) {
			throw new \Exception('['.__CLASS__."] Template not found in path: '$viewScriptFullPath'.");
		}
		$this->_renderedFullPaths[] = $viewScriptFullPath;
		ob_start();
		include($viewScriptFullPath);
		$result = ob_get_clean();
		array_pop($this->_renderedFullPaths); // unset last
		return $result;
	}

	/**
	 * Evaluate given code as PHP in current view context,
	 * any $this keyword will be used as view context.
	 * @param string $content
	 * @return string
	 */
	public function Evaluate ($content = '') {
		ob_start();
		try {
			eval(' ?'.'>'.$content.'<'.'?php ');
		} catch (\Exception $e) {
			throw $e;
		}
		return ob_get_clean();
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
	 * Get asset url - proxy method into \MvcCore\Controller::AssetUrl();
	 * @param string $path
	 * @return string
	 */
	public function AssetUrl ($path = '') {
		return $this->Controller->AssetUrl($path);
	}

	/**
	 * Set any value into view context except system keys declared in static::$originalyDeclaredProperties.
	 * @param string $name
	 * @param mixed $value
	 * @throws \Exception
	 */
	public function __set ($name, $value) {
		if (isset(static::$originalyDeclaredProperties[$name])) {
			throw new \Exception(
				'['.__CLASS__."] It's not possible to change property: '$name' originaly declared in class ".__CLASS__.'.'
			);
		}
		$this->$name = $value;
	}

	/**
	 * Try to call view helper.
	 * If view helper doesn't exist in global helpers store - create new helper instance.
	 * If helper already exists in global helpers store - do not create it again - use instance from store.
	 * Then call it's public method named in the same way as helper and return result
	 * as it is without any conversion! So then there could be called any other helper method if helper instance was returned.
	 * @param string $method
	 * @param mixed $arguments
	 * @return string|mixed
	 */
	public function __call ($method, $arguments) {
		$result = '';
		foreach (static::$HelpersClassBases as $helperClassBase) {
			$className = $helperClassBase . ucfirst($method);
			if (class_exists($className)) {
				if (isset(self::$_helpers[$method]) && get_class(self::$_helpers[$method]) == $className) {
					$instance = self::$_helpers[$method];
					$result = call_user_func_array(array($instance, $method), $arguments);
				} else {
					$instance = new $className($this);
					$result = call_user_func_array(array($instance, $method), $arguments);
				}
				break;
			}
		}
		return $result;
	}

	/**
	 * If relative path declared in view starts with "./anything-else.phtml",
	 * then change relative path to correct "./" context and return full path.
	 * @param string $appRoot
	 * @param string $typePath
	 * @param string $relativePath
	 * @return string full path
	 */
	private function _correctRelativePath ($appRoot, $typePath, $relativePath) {
		$result = str_replace('\\', '/', $relativePath);
		if (substr($relativePath, 0, 2) == './') {
			$app = \MvcCore::GetInstance();
			$typedViewDirFullPath = implode('/', array(
				$appRoot, $app->GetAppDir(), $app->GetViewsDir(), $typePath
			));
			$lastRenderedFullPath = $this->_renderedFullPaths[count($this->_renderedFullPaths) - 1];
			$renderedRelPath = substr($lastRenderedFullPath, strlen($typedViewDirFullPath));
			$renderedRelPathLastSlashPos = strrpos($renderedRelPath, '/');
			if ($renderedRelPathLastSlashPos !== FALSE) {
				$result = substr($renderedRelPath, 0, $renderedRelPathLastSlashPos + 1).substr($relativePath, 2);
				$result = ltrim($result, '/');
			}
		}
		return $result;
	}
}
View::StaticInit();