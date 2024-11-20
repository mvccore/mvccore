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

namespace MvcCore\Controller;

/**
 * @mixin \MvcCore\Controller
 */
trait Props {

	/**
	 * All registered controllers instances and it's types.
	 * @var array<string,array{0:\MvcCore\Controller,1:\ReflectionClass<\MvcCore\IController>}>
	 */
	protected static $allControllers = [];

	/**
	 * Flash messages static definition for type flags and type strings.
	 * @var array<int,string>
	 */
	protected static $flashMessagesTypes = [
		self::FLASH_MESSAGE_TYPE_SUCCESS	=> 'success',
		self::FLASH_MESSAGE_TYPE_HELP		=> 'help',
		self::FLASH_MESSAGE_TYPE_INFO		=> 'info',
		self::FLASH_MESSAGE_TYPE_WARN		=> 'warn',
		self::FLASH_MESSAGE_TYPE_ERROR		=> 'error',
		self::FLASH_MESSAGE_TYPE_CRITICAL	=> 'critical',
	];

	/**
	 * Flash messages reading speed configuration to automatically
	 * calculate messages autohide timeout.
	 * @var array<string,int>
	 */
	protected static $flashMessagesReadingSpeedCfg = [
		'averageWordsPerMinute'	=> 100,		// 100 words per minute
		'minimalMessageTimeout'	=> 5000,	// ms
	];
	

	/**
	 * Reference to `\MvcCore\Application` singleton object.
	 * @var \MvcCore\Application
	 */
	protected $application;

	/**
	 * Environment object to detect and manage environment name.
	 * @var \MvcCore\Environment
	 */
	protected $environment;

	/**
	 * Request object - parsed URI, query params, app paths...
	 * @var \MvcCore\Request
	 */
	protected $request;

	/**
	 * Response object - storage for response headers and rendered body.
	 * @var \MvcCore\Response
	 */
	protected $response;

	/**
	 * Application router object - reference storage for application router to crate URL addresses.
	 * @var \MvcCore\Router
	 */
	protected $router;

	/**
	 * Requested controller name - `"dashed-controller-name"`.
	 * @var string
	 */
	protected $controllerName = '';

	/**
	 * Requested action name - `"dashed-action-name"`.
	 * @var string
	 */
	protected $actionName = '';

	/**
	 * Boolean about AJAX request.
	 * `TRUE` if request is requested from browser by `XmlHttpRequest` object
	 * with http header: `X-Requested-With: AnyJavascriptFrameworkName`, `FALSE` otherwise.
	 * @var bool
	 */
	protected $ajax = FALSE;

	/**
	 * Class store object for view properties.
	 * Before `\MvcCore\Controller::PreDispatch();` is called
	 * in controller lifecycle, this property will be still `NULL`.
	 * @var \MvcCore\View|NULL
	 */
	protected $view = NULL;

	/**
	 * Rendering mode switch to render views in two ways:
	 * `\MvcCore\IView::RENDER_WITH_OB_FROM_ACTION_TO_LAYOUT`:
	 *   - Render action view first into output buffer, then render layout view
	 *     wrapped around rendered action view string also into output buffer.
	 *     Then set up rendered content from output buffer into response object
	 *     and then send HTTP headers and content after all.
	 * `\MvcCore\IView::RENDER_WITHOUT_OB_CONTINUOUSLY`:
	 *   - Special rendering mode to continuously sent larger data to client.
	 *     Render layout view and render action view together inside it without
	 *     output buffering. There is not used reponse object body property for
	 *     this rendering mode. Http headers are sent before view rendering.
	 * @var int
	 */
	protected $renderMode = \MvcCore\IView::RENDER_WITH_OB_FROM_ACTION_TO_LAYOUT;

	/**
	 * Layout name to render html wrapper around rendered action view.
	 * @var string
	 */
	protected $layout = 'layout';

	/**
	 * This property is to customize sub-controllers template path. `NULL` by default.
	 * You need to set into this property any custom string as relative path to
	 * your template file placed somewhere in `/App/Views/Scripts/`.
	 * For example if you want to render template file placed in:
	 * `/App/Views/Scripts/something/completely/custom.phtml`, you need to set
	 * up this property to value `something/completely` and then there is
	 * necessary to render your template only by calling controller rendering by:
	 * `$subcontrollerInstance->Render('custom');`
	 * @var string|NULL
	 */
	protected $viewScriptsPath = NULL;

	/**
	 * If `TRUE`, view object is automatically created in base controller
	 * `PreDispatch()` method and view is automatically rendered with wrapping
	 * layout view around after controller action is called. Default value is
	 * `TRUE` for all non-ajax requests.
	 * @var boolean
	 */
	protected $viewEnabled = TRUE;

	/**
	 * User model instance. Template property.
	 * @var \MvcCore\Model|NULL
	 */
	protected $user = NULL;

	/**
	 * If `TRUE`, start session automatically in `Init()` method. `FALSE` by default.
	 * @var bool
	 */
	protected $autoStartSession = FALSE;

	/**
	 * If `TRUE`, automatically initialize properties with PHP docs tag
	 * with tha same name as this property. `FALSE` by default.
	 * @var bool
	 */
	protected $autoInitProperties = FALSE;

	/**
	 * Controller lifecycle state:
	 * - 0 => Controller has been created.
	 * - 1 => Controller has been initialized.
	 * - 2 => Controller has been pre-dispatched.
	 * - 3 => controller has been action executed.
	 * - 4 => Controller has been rendered.
	 * - 5 => Controller has been redirected.
	 * @var int
	 */
	protected $dispatchState = self::DISPATCH_STATE_CREATED;

	/**
	 * Dispatching state checking recursive semaphore.
	 * @internal
	 * @var bool
	 */
	protected $dispatchStateSemaphore = FALSE;

	/**
	 * Parent controller instance if any.
	 * @var \MvcCore\Controller|NULL
	 */
	protected $parentController = NULL;

	/**
	 * Registered sub-controllers instances.
	 * @var array<string|int,\MvcCore\Controller>
	 */
	protected $childControllers = [];

	/**
	 * Flash messages store to complete messages for next request.
	 * This store is always saved before application terminates
	 * and it's used only the most parent application controller.
	 * @var \stdClass[]|NULL
	 */
	protected $flashMessages = NULL;


	/**
	 * All asset mime types possibly called through `\MvcCore\Controller::AssetAction();`.
	 * @var array<string, string>
	 */
	private static $_assetsMimeTypes = [
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
		'woff'	=> 'font/woff',
		'woff2'	=> 'font/woff2',
	];
}
