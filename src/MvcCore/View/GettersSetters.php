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
	 * @inheritDoc
	 * @return \MvcCore\View
	 */
	public static function CreateInstance () {
		/** @var \MvcCore\View $result */
		$result = new static();
		return $result;
	}

	/**
	 * @inheritDoc
	 * @return string
	 */
	public static function GetExtension () {
		return static::$extension;
	}

	/**
	 * @inheritDoc
	 * @param  string $extension An extension with or without leading dot char.
	 * @return string
	 */
	public static function SetExtension ($extension = '.phtml') {
		return static::$extension = $extension;
	}

	/**
	 * @inheritDoc
	 * @return string
	 */
	public static function GetDoctype () {
		return static::$doctype;
	}

	/**
	 * @inheritDoc
	 * @param  string $doctype
	 * @return string
	 */
	public static function SetDoctype ($doctype = \MvcCore\IView::DOCTYPE_HTML5) {
		return static::$doctype = $doctype;
	}

	/**
	 * @inheritDoc
	 * @return string
	 */
	public static function GetLayoutsDir () {
		return static::$layoutsDir;
	}

	/**
	 * @inheritDoc
	 * @param  string $layoutsDir
	 * @return string
	 */
	public static function SetLayoutsDir ($layoutsDir = 'Layouts') {
		return static::$layoutsDir = $layoutsDir;
	}

	/**
	 * @inheritDoc
	 * @return string
	 */
	public static function GetScriptsDir () {
		return static::$scriptsDir;
	}

	/**
	 * @inheritDoc
	 * @param  string $scriptsDir
	 * @return string
	 */
	public static function SetScriptsDir ($scriptsDir = 'Scripts') {
		return static::$scriptsDir = $scriptsDir;
	}

	/**
	 * @inheritDoc
	 * @param  \MvcCore\Controller $controller
	 * @return \MvcCore\View
	 */
	public function SetController (\MvcCore\IController $controller) {
		$this->controller = $controller;
		return $this;
	}

	/**
	 * @inheritDoc
	 * @return \MvcCore\Controller
	 */
	public function GetController () {
		return $this->controller;
	}

	/**
	 * @inheritDoc
	 * @param  string $controller
	 * @return \MvcCore\View
	 */
	public function SetEncoding ($encoding) {
		$this->__protected['encoding'] = strtoupper($encoding);
		return $this;
	}

	/**
	 * @inheritDoc
	 * @return string
	 */
	public function GetEncoding () {
		return $this->__protected['encoding'];
	}
}
