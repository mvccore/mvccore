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

class MvcCore_View
{
	/**
	 * View script files extenion in Views application directory
	 *
	 * @var string
	 */
	const EXTENSION = '.phtml';
	
	/**
	 * Rendered content
	 *
	 * @var MvcCore_Controller
	 */
	public $Controller;
	
	/**
	 * Rendered content
	 *
	 * @var string
	 */
	private $_content = '';
	
	/**
	 * Currently rendered php/html file path
	 *
	 * @var array
	 */
	private $_renderedFullPaths = array();
	
	/**
	 * Helpers classes base class name
	 *
	 * @var string
	 */
	private static $_helpersClassBase = 'App_Views_Helpers_';
	
	/**
	 * Originaly declared dynamic properties to protect from __set() magic method
	 *
	 * @var string
	 */
	private static $_originalyDeclaredProperties = array(
		'Controller'		=> 1, 
		'_content'			=> 1, 
		'_renderedFullPaths'=> 1,
	);

	/**
	 * Helpers instances storrage
	 *
	 * @var array
	 */
	private static $_helpers = array();
	
	public function __construct (MvcCore_Controller & $controller) {
		$this->Controller = $controller;
	}
	public function SetUp (& $paramsInstance) {
		$params = get_object_vars($paramsInstance);
		foreach ($params as $key => $value) {
			$this->$key = $value;	
		}
	}
	public function GetContent () {
		return $this->_content;
	}
	public function GetController () {
		return $this->Controller;
	}
	public function RenderLayout ($relativePath = '', $content = '') {
		$this->_content = $content;
		return $this->Render('Layouts', $relativePath);
	}
	public function RenderScript ($relativePath = '') {
		return $this->Render('Scripts', $relativePath);
	}
	public function Render ($typePath = 'Scripts', $relativePath = '') {
		$result = '';
		$appRoot = $this->Controller->GetRequest()->appRoot;
		$relativePath = $this->_correctRelativePath($appRoot, $typePath, $relativePath);
		$viewScriptFullPath = implode('/', array(
			$appRoot, 'App', 'Views', $typePath, $relativePath . MvcCore_View::EXTENSION
		));
		if (!file_exists($viewScriptFullPath)) {
			throw new Exception("[MvcCore_View] Template not found in path: '$viewScriptFullPath'.");
		}
		$this->_renderedFullPaths[] = $viewScriptFullPath;
		ob_start();
		include($viewScriptFullPath);
		$result = ob_get_clean();
		array_pop($this->_renderedFullPaths); // unset last
		return $result;
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
		return $this->Controller->Url($controllerAction, $params);
	}
	public function AssetUrl ($path = '') {
		return $this->Controller->AssetUrl($path);
	}
	public function __set ($name, $value) {
		if (isset(self::$_originalyDeclaredProperties[$name])) {
			throw new Exception ("[MvcCore_View] It's not possible to change property: '$name' originaly declared in class MvcCore_View.");
		}
		$this->$name = $value;
	}
	public function __call ($method, $arguments) {
		$result = '';
		$className = self::$_helpersClassBase . ucfirst($method);
		if (isset(self::$_helpers[$method]) && get_class(self::$_helpers[$method]) == $className) {
			$instance = self::$_helpers[$method];
			$result = call_user_func_array(array($instance, $method), $arguments);
		} else {
			$instance = new $className($this);
			$result = call_user_func_array(array($instance, $method), $arguments);
		}
		return $result;
	}
	private function _correctRelativePath ($appRoot, $typePath, $relativePath) {
		// if relative path declared in view starts with "./anything-else.phtml",
		// then change relative path to correct "./" context
		$result = str_replace('\\', '/', $relativePath);
		if (substr($relativePath, 0, 2) == './') {
			$typedViewDirFullPath = implode('/', array(
				$appRoot, 'App', 'Views', $typePath
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