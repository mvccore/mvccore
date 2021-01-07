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

namespace MvcCore\Controller;

trait Props {

	/**
	 * Path to all static files - css, js, images and fonts.
	 * @var string
	 */
	protected static $staticPath = '/static';

	/**
	 * Path to temporary directory with generated css and js files.
	 * @var string
	 */
	protected static $tmpPath = '/Var/Tmp';

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
	 * @var boolean
	 */
	protected $ajax = FALSE;

	/**
	 * Class store object for view properties.
	 * Before `\MvcCore\Controller::PreDispatch();` is called
	 * in controller lifecycle, this property will be still `NULL`.
	 * @var \MvcCore\View
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
	 * @var \MvcCore\Model
	 */
	protected $user = NULL;

	/**
	 * If `TRUE`, start session automatically in `Init()` method.
	 * @var bool
	 */
	protected $autoStartSession = TRUE;

	/**
	 * If `TRUE`, automatically initialize properties with `@autoinit` tag.
	 * @var bool
	 */
	protected $autoInitProperties = TRUE;

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
	protected $dispatchState = \MvcCore\IController::DISPATCH_STATE_CREATED;

	/**
	 * Parent controller instance if any.
	 * @var \MvcCore\Controller|NULL
	 */
	protected $parentController = NULL;

	/**
	 * Registered sub-controllers instances.
	 * @var \MvcCore\Controller[]
	 */
	protected $childControllers = [];

	/**
	 * All registered controllers instances.
	 * @var \MvcCore\Controller[]
	 */
	protected static $allControllers = [];

	/**
	 * All asset mime types possibly called through `\MvcCore\Controller::AssetAction();`.
	 * @var string
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
		'woff'	=> 'application/x-font-woff',
	];
}
