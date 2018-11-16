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

namespace MvcCore\View;

trait GettersSetters
{
	/**
	 * Return always new instance of statically called class, no singleton.
	 * Always called from `\MvcCore\Controller::PreDispatch()` and
	 * `\MvcCore\Controller::Render()` to create layout view.
	 * This is place where to customize any view creation process,
	 * before it's created by MvcCore framework to fill and render it.
	 * @return \MvcCore\View|\MvcCore\IView
	 */
	public static function CreateInstance () {
		/** @var $result \MvcCore\View */
		$result = new static();
		return $result;
	}

	/**
	 * Get view scripts files extension with leading dot char.
	 * Default value: `".phtml"`.
	 * @return string
	 */
	public static function GetExtension () {
		return static::$extension;
	}

	/**
	 * Set view scripts files extension.
	 * given value could be with or without leading dot char.
	 * @param string $extension An extension with or without leading dot char.
	 * @return string
	 */
	public static function SetExtension ($extension = '.phtml') {
		return static::$extension = $extension;
	}

	/**
	 * Get output document type (to automatically and optionally send proper
	 * HTTP header `Content-Type`, if there is no `Content-Type` HTTP
	 * header in response object yet).
	 * This value could be used also for any other custom purposes.
	 * Possible values:
	 * - `HTML4` - `\MvcCore\IView::DOCTYPE_HTML4`
	 * - `XHTML` - `\MvcCore\IView::DOCTYPE_XHTML`
	 * - `HTML5` - `\MvcCore\IView::DOCTYPE_HTML5`
	 * - `XML`   - `\MvcCore\IView::DOCTYPE_XML`
	 * Default value: `HTML5`.
	 * @return string
	 */
	public static function GetDoctype () {
		return static::$doctype;
	}

	/**
	 * Set output document type (to automatically and optionally send proper
	 * HTTP header `Content-Type`, if there is no `Content-Type` HTTP
	 * header in response object yet).
	 * This value could be used also for any other custom purposes.
	 * Possible values:
	 * - `HTML4` - `\MvcCore\IView::DOCTYPE_HTML4`
	 * - `XHTML` - `\MvcCore\IView::DOCTYPE_XHTML`
	 * - `HTML5` - `\MvcCore\IView::DOCTYPE_HTML5`
	 * - `XML`   - `\MvcCore\IView::DOCTYPE_XML`
	 * Default value: `HTML5`.
	 * @param string $doctype
	 * @return string
	 */
	public static function SetDoctype ($doctype = \MvcCore\IView::DOCTYPE_HTML5) {
		return static::$doctype = $doctype;
	}

	/**
	 * Get layout templates directory placed by default
	 * inside `"/App/Views"` directory. Default value
	 * is `"Layouts"`, so layouts app path
	 * is `"/App/Views/Layouts"`.
	 * @return string
	 */
	public static function GetLayoutsDir () {
		return static::$layoutsDir;
	}

	/**
	 * Set layout templates directory placed by default
	 * inside `"/App/Views"` directory. Default value
	 * is `"Layouts"`, so layouts app path
	 * is `"/App/Views/Layouts"`.
	 * @param string $layoutsDir
	 * @return string
	 */
	public static function SetLayoutsDir ($layoutsDir = 'Layouts') {
		return static::$layoutsDir = $layoutsDir;
	}

	/**
	 * Get controller/action templates directory
	 * placed by default inside `"/App/Views"` directory.
	 * Default value is `"Scripts"`, so scripts app path
	 * is `"/App/Views/Scripts"`.
	 * @return string
	 */
	public static function GetScriptsDir () {
		return static::$scriptsDir;
	}

	/**
	 * Set controller/action templates directory
	 * placed by default inside `"/App/Views"` directory.
	 * Default value is `"Scripts"`, so scripts app path
	 * is `"/App/Views/Scripts"`.
	 * @param string $scriptsDir
	 * @return string
	 */
	public static function SetScriptsDir ($scriptsDir = 'Scripts') {
		return static::$scriptsDir = $scriptsDir;
	}

	/**
	 * This is INTERNAL method, do not use it in templates.
	 * Method is always called in the most parent controller 
	 * `\MvcCore\Controller:PreDispatch()` moment when view instance is created.
	 * Method sets controller instance into view.
	 * @param \MvcCore\Controller $controller
	 * @return \MvcCore\View
	 */
	public function & SetController (\MvcCore\IController & $controller) {
		/** @var $this \MvcCore\View */
		$this->controller = $controller;
		return $this;
	}

	/**
	 * Get controller instance as reference.
	 * @return \MvcCore\Controller
	 */
	public function & GetController () {
		/** @var $this \MvcCore\View */
		return $this->controller;
	}
}
