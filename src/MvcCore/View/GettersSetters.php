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

namespace MvcCore\View;

/**
 * @mixin \MvcCore\View
 */
trait GettersSetters {

	/**
	 * @inheritDocs
	 * @return \MvcCore\View
	 */
	public static function CreateInstance () {
		/** @var \MvcCore\View $result */
		$result = new static();
		return $result;
	}

	/**
	 * @inheritDocs
	 * @return string
	 */
	public static function GetExtension () {
		return static::$extension;
	}

	/**
	 * @inheritDocs
	 * @param  string $extension An extension with or without leading dot char.
	 * @return string
	 */
	public static function SetExtension ($extension = '.phtml') {
		return static::$extension = $extension;
	}

	/**
	 * @inheritDocs
	 * @return string
	 */
	public static function GetDoctype () {
		return static::$doctype;
	}

	/**
	 * @inheritDocs
	 * @param  string $doctype
	 * @return string
	 */
	public static function SetDoctype ($doctype = \MvcCore\IView::DOCTYPE_HTML5) {
		return static::$doctype = $doctype;
	}

	/**
	 * @inheritDocs
	 * @return string
	 */
	public static function GetLayoutsDir () {
		return static::$layoutsDir;
	}

	/**
	 * @inheritDocs
	 * @param  string $layoutsDir
	 * @return string
	 */
	public static function SetLayoutsDir ($layoutsDir = 'Layouts') {
		return static::$layoutsDir = $layoutsDir;
	}

	/**
	 * @inheritDocs
	 * @return string
	 */
	public static function GetScriptsDir () {
		return static::$scriptsDir;
	}

	/**
	 * @inheritDocs
	 * @param  string $scriptsDir
	 * @return string
	 */
	public static function SetScriptsDir ($scriptsDir = 'Scripts') {
		return static::$scriptsDir = $scriptsDir;
	}

	/**
	 * @inheritDocs
	 * @param  \MvcCore\Controller $controller
	 * @return \MvcCore\View
	 */
	public function SetController (\MvcCore\IController $controller) {
		$this->controller = $controller;
		return $this;
	}

	/**
	 * @inheritDocs
	 * @return \MvcCore\Controller
	 */
	public function GetController () {
		return $this->controller;
	}

	/**
	 * @inheritDocs
	 * @param  string $controller
	 * @return \MvcCore\View
	 */
	public function SetEncoding ($encoding) {
		$this->__protected['encoding'] = strtoupper($encoding);
		return $this;
	}

	/**
	 * @inheritDocs
	 * @return string
	 */
	public function GetEncoding () {
		return $this->__protected['encoding'];
	}
}
