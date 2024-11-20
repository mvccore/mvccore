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
 * @phpstan-type ProtetedSpace array{
 *    "content":string,
 *    "store":array<string,mixed>,
 *    "helpers":array<string,ViewHelper>,
 *    "buildInHelpersInit":bool,
 *    "renderArgs":array{0:int,1:?string,2:?string},
 *    "renderedFullPaths":array<string>,
 *    "reflectionTypes":array<string,\ReflectionClass>,
 *    "reflectionName":string|NULL,
 *    "encoding":string|NULL,
 *    "viewsDirsFullPaths":array<string>|NULL
 * }
 * @phpstan-type ViewHelper \MvcCore\Ext\Views\Helpers\AbstractHelper|\MvcCore\Ext\Views\Helpers\IHelper|\Closure|mixed
 * @phpstan-type ViewHelperCacheRecord array{0:ViewHelper,1:bool,2:bool}
 */
trait Props {

	/**
	 * Currently dispatched controller instance.
	 * @var \MvcCore\Controller|NULL
	 */
	protected $controller = NULL;

	/**
	 * All other private properties.
	 * @var ProtetedSpace
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
		  * @var array<string,mixed>
		  */
		'store'				=> [],
		/**
		  * Helpers instances storage for current view instance.
		  * Keys in array are helper method names.
		  * Every view has it's own helpers storage to recognize
		  * if helper has been already used inside current view or not.
		  * @var array<string,ViewHelper>
		  */
		'helpers'			=> [],
		/**
		  * Boolean about if build in helpers are initialized.
		  * @var bool
		  */
		'buildInHelpersInit'=> FALSE,
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
		  * `1` - `?string` - controller name dashed (or action name dashed).
		  * `2` - `?string` - action name dashed.
		  * @var array{0:int,1:?string,2:?string}
		  */
		'renderArgs'		=> [\MvcCore\IView::RENDER_WITH_OB_FROM_ACTION_TO_LAYOUT, NULL, NULL],
		/**
		  * Currently rendered php/phtml file path(s).
		  * @var array<string>
		  */
		'renderedFullPaths'	=> [],
		/**
		  * `\ReflectionClass` instances to get additional values
		  * from controller or form or any other parent instance
		  * by `__get()` method.
		  * @var array<string,\ReflectionClass>
		  */
		'reflectionTypes'	=> [],
		/**
		  * Currently searched reflection class property name.
		  * @var string|NULL
		  */
		'reflectionName'	=> NULL,
		/**
		  * Default template encoding, used mostly as default 
		  * encoding param in escaping methods, initialized
		  * from controller response.
		  * @var string|NULL
		  */
		'encoding'			=> 'UTF-8',
		/**
		 * Cached typed views directories full paths for method calls:
		 * `\MvcCore\View::GetParentViewFullPath();`,
		 * `\MvcCore\View::correctRelativePath();`,
		 * `\MvcCore\View::Render($typePath, $relativePath);`,
		 * `\MvcCore\Ext\Controllers\DataGrids::Render($typePath, $relativePath, $internalTemplate = FALSE);`;
		 * @var array<string>|NULL
		 */
		'viewsDirsFullPaths'=> NULL,
	];

	/**
	 * Definition of view types to application paths, where to find them.
	 * This internal definition is used in method `\MvcCore\View::getViewPathByType();`.
	 * @var array<int,string>
	 */
	protected static $viewTypes2AppPaths = [
		self::VIEW_TYPE_DEFAULT		=> 'pathView',
		self::VIEW_TYPE_LAYOUT		=> 'pathViewLayouts',
		self::VIEW_TYPE_SCRIPT		=> 'pathViewScripts',
		self::VIEW_TYPE_FORM		=> 'pathViewForms',
		self::VIEW_TYPE_FORM_FIELD	=> 'pathViewFormsFields',
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
	 * Helpers classes namespaces, where are all configured view helpers placed.
	 * For read & write.
	 * @var array<int,string>
	 */
	protected static $helpersNamespaces = [
		/*'\App\Views\Helpers\',*/
		/*'\MvcCore\Ext\Views\Helpers\'*/
	];

	/**
	 * Global helpers instances storage.
	 * Keys in array are helper method names.
	 * These helpers instances are used for all views.
	 * @var array<string,ViewHelperCacheRecord>
	 */
	protected static $globalHelpers = [];

	/**
	 * Reference to `\MvcCore\Application::GetInstance()->GetToolClass();`.
	 * @var string|NULL
	 */
	protected static $toolClass = NULL;
}
