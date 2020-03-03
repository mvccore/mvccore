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

trait Props
{
	/**
	 * Currently dispatched controller instance.
	 * @var \MvcCore\Controller|\MvcCore\IController
	 */
	protected $controller = NULL;

	/**
	 * All other private properties.
	 * @var array
	 */
	protected $__protected = [
		/**
		  * Rendered content.
		  * @var string
		  */
		'content'			=> '',
		/**
		  * Variables store, setted (always from controller)
		  * through `__set()` magic function.
		  * @var array
		  */
		'store'				=> [],
		/**
		  * Helpers instances storage for current view instance.
		  * Keys in array are helper method names.
		  * Every view has it's own helpers storage to recognize
		  * if helper has been already used inside current view or not.
		  * @var array
		  */
		'helpers'			=> [],
		/**
		  * `0` - Rendering mode switch to render views in two ways:
		  *     `\MvcCore\IView::RENDER_WITH_OB_FROM_ACTION_TO_LAYOUT`:
		  *       - Render action view first into output buffer, then render layout view
		  *         wrapped around rendered action view string also into output buffer.
		  *         Then set up rendered content from output buffer into response object
		  *         and then send HTTP headers and content after all.
		  *     `\MvcCore\IView::RENDER_WITHOUT_OB_CONTINUOUSLY`:
		  *       - Special rendering mode to continuously sent larger data to client.
		  *         Render layout view and render action view together inside it without
		  *         output buffering. There is not used reponse object body property for
		  *         this rendering mode. Http headers are sent before view rendering.
		  * `1` - `string` - controller name dashed (or action name dashed).
		  * `2` - `string` - action name dashed.
		  * @var int
		  */
		'renderArgs'		=> [\MvcCore\IView::RENDER_WITH_OB_FROM_ACTION_TO_LAYOUT, NULL, NULL],
		/**
		  * Currently rendered php/phtml file path(s).
		  * @var \string[]
		  */
		'renderedFullPaths'	=> [],
		/**
		  * `\ReflectionClass` instances to get additional values
		  * from controller or form or any other parent instance
		  * by `__get()` method.
		  * @var array
		  */
		'reflectionTypes'	=> [],
		/**
		  * Currently searched reflection class property name.
		  * @var string|NULL
		  */
		'reflectionName'	=> NULL,
	];

	/**
	 * View scripts files extension with leading dot char.
	 * Default value: `".phtml"`.
	 * @var string
	 */
	protected static $extension = '.phtml';

	/**
	 * Output document type (to automatically and optionally send proper
	 * HTTP header `Content-Type`, if there is no `Content-Type` HTTP
	 * header in response object yet).
	 * This value could be used also for any other custom purposes.
	 * Possible values:
	 * - `HTML4` - `\MvcCore\IView::DOCTYPE_HTML4`
	 * - `XHTML` - `\MvcCore\IView::DOCTYPE_XHTML`
	 * - `HTML5` - `\MvcCore\IView::DOCTYPE_HTML5`
	 * - `XML`   - `\MvcCore\IView::DOCTYPE_XML`
	 * Default value: `HTML5`.
	 * @var string
	 */
	protected static $doctype = self::DOCTYPE_HTML5;

	/**
	 * Layout templates directory placed by default
	 * inside `"/App/Views"` directory. Default value
	 * is `"Layouts"`, so layouts app path
	 * is `"/App/Views/Layouts"`.
	 * @var string
	 */
	protected static $layoutsDir = 'Layouts';

	/**
	 * Controller/action templates directory
	 * placed by default inside `"/App/Views"` directory.
	 * Default value is `"Scripts"`, so scripts app path
	 * is `"/App/Views/Scripts"`.
	 * @var string
	 */
	protected static $scriptsDir = 'Scripts';

	/**
	 * Views helpers directory placed by default
	 * inside `"/App/Views"` directory.
	 * Default value is `"Helpers"`, so scripts app path
	 * is `"/App/Views/Helpers"`.
	 * @var string
	 */
	protected static $helpersDir = 'Helpers';

	/**
	 * Helpers classes namespaces, where are all configured view helpers placed.
	 * For read & write.
	 * @var array
	 */
	protected static $helpersNamespaces = [
		/*'\MvcCore\Ext\Views\Helpers\'*/
	];

	/**
	 * Global helpers instances storage.
	 * Keys in array are helper method names.
	 * These helpers instances are used for all views.
	 * @var array
	 */
	private static $_globalHelpers = [];

	/**
	 * Cached base full path for repeat method calls `\MvcCore\View::GetViewScriptFullPath();`.
	 * @var string
	 */
	private static $_viewScriptsFullPathBase = NULL;

	/**
	 * Reference to `\MvcCore\Application::GetInstance()->GetToolClass();`.
	 * @var string|NULL
	 */
	private static $_toolClass = NULL;
}
