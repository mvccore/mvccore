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

interface IGettersSetters {
	
	/**
	 * Return always new instance of statically called class, no singleton.
	 * Always called from `\MvcCore\Controller::PreDispatch()` and
	 * `\MvcCore\Controller::Render()` to create layout view.
	 * This is place where to customize any view creation process,
	 * before it's created by MvcCore framework to fill and render it.
	 * @return \MvcCore\View
	 */
	public static function CreateInstance ();

	/**
	 * Get view scripts files extension with leading dot char.
	 * Default value: `".phtml"`.
	 * @return string
	 */
	public static function GetExtension ();

	/**
	 * Set view scripts files extension.
	 * given value could be with or without leading dot char.
	 * @param  string $extension An extension with or without leading dot char.
	 * @return string
	 */
	public static function SetExtension ($extension = '.phtml');

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
	public static function GetDoctype ();

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
	 * @param  string $doctype
	 * @return string
	 */
	public static function SetDoctype ($doctype = \MvcCore\IView::DOCTYPE_HTML5);
	
	/**
	 * This is INTERNAL method, do not use it in templates.
	 * Method is always called in the most parent controller
	 * `\MvcCore\Controller:PreDispatch()` moment when view instance is created.
	 * Method sets controller instance into view.
	 * @param  \MvcCore\Controller $controller
	 * @return \MvcCore\View
	 */
	public function SetController (\MvcCore\IController $controller);

	/**
	 * Get controller instance.
	 * @return \MvcCore\Controller
	 */
	public function GetController ();
	
	/**
	 * Set default template encoding, used mostly as default 
	 * encoding param in escaping methods, initialized
	 * from controller response.
	 * @param  string $encoding
	 * @return \MvcCore\View
	 */
	public function SetEncoding ($encoding);

	/**
	 * Get default template encoding, used mostly as default 
	 * encoding param in escaping methods, initialized
	 * from controller response.
	 * @return string
	 */
	public function GetEncoding ();

}
