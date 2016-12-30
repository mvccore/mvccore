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

class MvcCore_View {
	/**
	 * View script files extenion in Views application directory
	 * @var string
	 */
	const EXTENSION = '.phtml';
	/**
	 * Rendered content
	 * @var string
	 */
	private $_content = '';
	/**
	 * Rendered content
	 * @var MvcCore_Controller
	 */
	private $_controller;
	/**
	 * Helpers classes base class name
	 * @var string
	 */
	private static $_helpersClassBase = 'App_Views_Helpers_';
	/**
	 * Helpers instances storrage
	 * @var array
	 */
	private static $_helpers = array();
	public function __construct (MvcCore_Controller $controller) {
		$this->_controller = $controller;
	}
	public function SetUp () {
		$args = func_get_args();
		foreach ($args as $arg) {
			$argArr = (array) $arg;
			foreach ($argArr as $key => $value) {
				if (mb_substr($key, 0, 1) === "\x00") continue;
				$this->$key = $value;	
			}
		}
	}
	public function GetContent () {
		return $this->_content;
	}
	public function GetController () {
		return $this->_controller;
	}
	public function RenderLayout ($relativePath = '', $content = '') {
		$this->_content = $content;
		return $this->Render('Layouts', $relativePath);
	}
	public function RenderScript ($relativePath = '') {
		return $this->Render('Scripts', $relativePath);
	}
	public function Render ($typePath = 'Scripts', $relativePath = '') {
		$appRoot = $this->_controller->GetRequest()->appRoot;
		$viewScriptFullPath = implode(DIRECTORY_SEPARATOR, array(
			$appRoot, 'App', 'Views', $typePath, $relativePath
		));
		if (!file_exists($viewScriptFullPath)) {
			throw new Exception("[MvcCore_View] Template not found in path: '$viewScriptFullPath'.");
		}
		if (MvcCore::GetCompiled()) {
			$content = file_get_contents($viewScriptFullPath);
			$this->_content = $this->Evaluate($content);
		} else {
			ob_start();
			include $viewScriptFullPath;
			$this->_content = ob_get_clean();
		}
		return $this->_content;
	}
	public function Evaluate ($content = '') {
		ob_start();
		try {
			eval(' ?'.'>'.$content.'<'.'?php ');
		} catch (Exception $e) {
			throw $e;
		}
		return ob_get_clean();
	}
	public function Url ($controllerAction = '', $params = array()) {
		return $this->_controller->Url($controllerAction, $params);
	}
	public function __set ($name, $value) {
		$this->$name = $value;
	}
	public function __call ($method, $arguments) {
		$result = '';
		$className = self::$_helpersClassBase . ucfirst($method);
		if (isset(self::$_helpers[$method]) && get_class(self::$_helpers[$method]) == $className) {
			$instance = self::$_helpers[$method];
			call_user_func_array(array($instance, $method), $arguments);
		} else {
			$instance = new $className($this);
			call_user_func_array(array($instance, $method), $arguments);
		}
		return $instance;
	}
}