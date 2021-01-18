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

namespace MvcCore;

/**
 * Core view:
 * - Static storage for
 *   - commonly used document type
 *   - common views extension
 *   - common directories names containing view scripts
 *   - common views helpers namespaces
 * - It's possible to use this class for any controller, sub controller or form.
 * - View pre render preparing and rendering.
 * - View helpers management on demand:
 *   - Creating by predefined class namespaces.
 *   - global static helpers instances storage and repeatable calling.
 * - Views sub scripts relative path solving in:
 *   `<?php $this->RenderScript('./any-subdirectory/script-to-render.php'); ?>`
 * - `Url()` - proxy method from `\MvcCore\Router` targeting to configured router.
 * - `AssetUrl()` - proxy method from `\MvcCore\Controller`.
 * - Magic calls:
 *   - __call() - To handler any view helper, if no helper found - exception thrown.
 *   - __set() - To set anything from controller to get it back in view.
 *   - __get() - To get anything in view previously initialized from controller.
 * - Optional direct code evaluation.
 * - No special view language implemented, use `short_open_tags` (`<?=...?>`) allowed by default.
 *
 * MvcCore view properties and helpers:
 * @property-read \MvcCore\Controller $controller Currently dispatched controller instance.
 * @method \MvcCore\Ext\Views\Helpers\CssHelper Css(string $groupName = self::GROUP_NAME_DEFAULT) Get css helper instance by group name. To use this method, you need to install extension `mvccore/ext-view-helper-assets`.
 * @method \MvcCore\Ext\Views\Helpers\JsHelper Js(string $groupName = self::GROUP_NAME_DEFAULT) Get js helper instance by group name. To use this method, you need to install extension `mvccore/ext-view-helper-assets`.
 * @method string FormatDate(\DateTime|\IntlCalendar|int $dateTimeOrTimestamp = NULL, int|string $dateTypeOrFormatMask = NULL, int $timeType = NULL, string|\IntlTimeZone|\DateTimeZone $timeZone = NULL, int $calendar = NULL) Format given date time by `Intl` extension or by `strftime()` as fallback. To use this method, you need to install extension `mvccore/ext-view-helper-formatdatetime`.
 * @method string FormatNumber(float|int $number = 0.0, int $decimals = 0, string $dec_point = NULL , string $thousands_sep = NULL) To use this method, you need to install extension `mvccore/ext-view-helper-formatnumber`.
 * @method string FormatMoney(float|int$number = 0.0, int $decimals = 0, string $dec_point = NULL , string $thousands_sep = NULL) To use this method, you need to install extension `mvccore/ext-view-helper-formatmoney`.
 * @method string LineBreaks(string $text, string $lang = '') Prevent breaking line inside numbers, after week words, shortcuts, numbers and units and much more, very configurable. To use this method, you need to install extension `mvccore/ext-view-helper-linebreaks`.
 * @method string DataUrl(string $relativeOrAbsolutePath) Return any file content by given relative or absolute path in data URL like `data:image/png;base64,iVBOR..`. Path could be relative from currently rendered view, relative from application root or absolute path to file. To use this method, you need to install extension `mvccore/ext-view-helper-dataurl`.
 * @method string WriteByJS(string $string) Return any given HTML code as code rendered in javascript: `<script>document.write(String.fromCharCode(...));</script>`. To use this method, you need to install extension `mvccore/ext-view-helper-writebyjs`.
 * @method string Truncate(string $text, int $maxChars = 200, bool $isHtml = NULL) Truncate plain text or text with html tags by given max. characters number and add three dots at the end. To use this method, you need to install extension `mvccore/ext-view-helper-truncate`.
 */
interface IView extends \MvcCore\View\IConstants {

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
	 * @param string $extension An extension with or without leading dot char.
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
	 * @param string $doctype
	 * @return string
	 */
	public static function SetDoctype ($doctype = \MvcCore\IView::DOCTYPE_HTML5);

	/**
	 * Get layout templates directory placed by default
	 * inside `"/App/Views"` directory. Default value
	 * is `"Layouts"`, so layouts app path
	 * is `"/App/Views/Layouts"`.
	 * @return string
	 */
	public static function GetLayoutsDir ();

	/**
	 * Set layout templates directory placed by default
	 * inside `"/App/Views"` directory. Default value
	 * is `"Layouts"`, so layouts app path
	 * is `"/App/Views/Layouts"`.
	 * @param string $layoutsDir
	 * @return string
	 */
	public static function SetLayoutsDir ($layoutsDir = 'Layouts');

	/**
	 * Get controller/action templates directory
	 * placed by default inside `"/App/Views"` directory.
	 * Default value is `"Scripts"`, so scripts app path
	 * is `"/App/Views/Scripts"`.
	 * @return string
	 */
	public static function GetScriptsDir ();

	/**
	 * Get controller/action templates directory
	 * placed by default inside `"/App/Views"` directory.
	 * Default value is `"Scripts"`, so scripts app path
	 * is `"/App/Views/Scripts"`.
	 * @param string $scriptsDir
	 * @return string
	 */
	public static function SetScriptsDir ($scriptsDir = 'Scripts');

	/**
	 * Get views helpers directory placed by default
	 * inside `"/App/Views"` directory.
	 * Default value is `"Helpers"`, so scripts app path
	 * is `"/App/Views/Helpers"`.
	 * @return string
	 */
	public static function GetHelpersDir ();

	/**
	 * Set views helpers directory placed by default
	 * inside `"/App/Views"` directory.
	 * Default value is `"Helpers"`, so scripts app path
	 * is `"/App/Views/Helpers"`.
	 * @param string $helpersDir
	 * @return string
	 */
	public static function SetHelpersDir ($helpersDir = 'Helpers');

	/**
	 * Add view helpers classes namespace(s),
	 * Example: `\MvcCore\View::AddHelpersNamespaces('Any\Other\ViewHelpers\Place', '...');`.
	 * @param string $helperNamespaces,... View helper classes namespace(s).
	 * @return void
	 */
	public static function AddHelpersNamespaces ($helperNamespaces);

	/**
	 * Set view helpers classes namespace(s). This method replace all previously configured namespaces.
	 * If you want only to add namespace, use `\MvcCore\View::AddHelpersNamespaces();` instead.
	 * Example: `\MvcCore\View::SetHelpersClassNamespaces('Any\Other\ViewHelpers\Place', '...');`.
	 * @param string $helperNamespaces,... View helper classes namespace(s).
	 * @return void
	 */
	public static function SetHelpersNamespaces ($helperNamespaces);

	/**
	 * Get view script full path by internal application configuration,
	 * by `$typePath` param and by `$corectedRelativePath` param.
	 * @param string $typePath Usually `"Layouts"` or `"Scripts"`.
	 * @param string $corectedRelativePath
	 * @return string
	 */
	public static function GetViewScriptFullPath ($typePath = '', $corectedRelativePath = '');

	/**
	 * This is INTERNAL method, do not use it in templates.
	 * Method is always called in the most parent controller
	 * `\MvcCore\Controller:PreDispatch()` moment when view instance is created.
	 * Method sets controller instance into view.
	 * @param \MvcCore\Controller $controller
	 * @return \MvcCore\View
	 */
	public function SetController (\MvcCore\IController $controller);

	/**
	 * Get controller instance.
	 * @return \MvcCore\Controller
	 */
	public function GetController ();

	/**
	 * Set up view rendering arguments  to render layout and action view in both modes properly.
	 * Set up view instance helpers before rendering.
	 * @param int $renderMode
	 * @param string $controllerOrActionNameDashed
	 * @param string $actionNameDashed
	 * @return \MvcCore\View
	 */
	public function SetUpRender ($renderMode = \MvcCore\IView::RENDER_WITH_OB_FROM_ACTION_TO_LAYOUT, $controllerOrActionNameDashed = NULL, $actionNameDashed = NULL);

	/**
	 * This is INTERNAL method, do not use it in templates.
	 * Method is always called in the most parent controller
	 * `\MvcCore\Controller:Render()` moment when view is rendered.
	 * Set up all from given view object variables store into current store,
	 * if there is any already existing key - overwrite it.
	 * @param \MvcCore\View $view
	 * @param bool $overwriteExistingKeys If any property name already exist in view store, overwrite it by given value by default.
	 * @return \MvcCore\View
	 */
	public function SetUpStore (\MvcCore\IView $view, $overwriteExistingKeys = TRUE);

	/**
	 * Return rendered action template content as string reference.
	 * You need to use this method always somewhere in layout template to
	 * render rendered action result content.
	 * If render mode is continuous, this method renders action view.
	 * @return string
	 */
	public function & GetContent ();

	/**
	 * Get currently rendered view file full path.
	 * If this method is called outside of rendering process, `NULL` is returned.
	 * @return string|NULL
	 */
	public function GetCurrentViewFullPath ();

	/**
	 * Get currently rendered view file directory full path.
	 * If this method is called outside of rendering process, `NULL` is returned.
	 * @return string|NULL
	 */
	public function GetCurrentViewDirectory ();

	/**
	 * Get currently rendered parent view file full path.
	 * Parent view file could be any view file, where is called `$this->RenderScript(...);`
	 * method to render sub-view file (actual view file) or it could be any view file
	 * from parent controller or if current controller has no parent controller,
	 * it could be layout view script full path.
	 * If this method is called outside of rendering process, `NULL` is returned.
	 * @return string|NULL
	 */
	public function GetParentViewFullPath ();

	/**
	 * Get currently rendered parent view file directory full path.
	 * Parent view file could be any view file, where is called `$this->RenderScript(...);`
	 * method to render sub-view file (actual view file) or it could be any view file
	 * from parent controller or if current controller has no parent controller,
	 * it could be layout view script full path.
	 * If this method is called outside of rendering process, `NULL` is returned.
	 * @return string|NULL
	 */
	public function GetParentViewDirectory ();

	/**
	 * Render action template script or any include script and return it's result as reference.
	 * Do not use this method in layout sub-templates, use method `RenderLayout()` instead.
	 * @param string $relativePath
	 * @return string
	 */
	public function & RenderScript ($relativePath = '');

	/**
	 * Render layout template script or any include script and return it's result as reference.
	 * Do not use this method in action sub-templates, use method `RenderScript()` instead.
	 * @param string $relativePath
	 * @return string
	 */
	public function & RenderLayout ($relativePath = '');

	/**
	 * This method is INTERNAL, always called from `\MvcCore\Controller::Render();`.
	 * Do not use this method in templates!
	 * Method renders whole configured layout template and return it's result
	 * as string reference with inner rendered action template content.
	 * @param string $relativePatht.
	 * @param string $content
	 * @return string
	 */
	public function & RenderLayoutAndContent ($relativePath = '', & $content = NULL);

	/**
	 * Render controller template and all necessary layout
	 * templates and return rendered result as string reference.
	 * @param string $typePath By default: `"Layouts" | "Scripts"`. It could be `"Forms" | "Forms/Fields"` etc...
	 * @param string $relativePath
	 * @throws \InvalidArgumentException Template not found in path: `$viewScriptFullPath`.
	 * @return string
	 */
	public function & Render ($typePath = '', $relativePath = '');

	/**
	 * Evaluate given template code as PHP code by `eval()` in current view
	 * context, any `$this` keyword will be used as current view context.
	 * Returned result is content from output buffer as string reference.
	 * Evaluated code is wrapped into `try/catch` automatically.
	 * @param string $content
	 * @return string
	 */
	public function Evaluate ($content);

	/**
	 * Generates url:
	 * - By `"Controller:Action"` name and params array
	 *   (for routes configuration when routes array has keys with `"Controller:Action"` strings
	 *   and routes has not controller name and action name defined inside).
	 * - By route name and params array
	 *	 (route name is key in routes configuration array, should be any string
	 *	 but routes must have information about controller name and action name inside).
	 * Result address (url string) should have two forms:
	 * - Nice rewritten URL by routes configuration
	 *   (for apps with URL rewrite support (Apache `.htaccess` or IIS URL rewrite module)
	 *   and when first param is key in routes configuration array).
	 * - For all other cases is URL form like: `"index.php?controller=ctrlName&amp;action=actionName"`
	 *	 (when first param is not founded in routes configuration array).
	 * @param string $controllerActionOrRouteName	Should be `"Controller:Action"` combination or just any route name as custom specific string.
	 * @param array  $params						Optional, array with params, key is param name, value is param value.
	 * @return string
	 */
	public function Url ($controllerActionOrRouteName = 'Index:Index', array $params = []);

	/**
	 * Return asset path or single file mode URL for small assets
	 * handled by internal controller action `"Controller:Asset"`.
	 * Example: `echo $this->AssetUrl('/static/img/favicon.ico');`
	 * @param string $path
	 * @return string
	 */
	public function AssetUrl ($path = '');

	/**
	 * Escape string for use inside HTML/XHTML/HTML5 
	 * node as text content.
	 * @param string	$str 
	 * @param bool		$double 
	 * @param string	$encoding 
	 * @return string
	 */
	public function Escape ($str, $encoding = 'UTF-8');
	
	/**
	 * Escape string for use inside HTML/XHTML/HTML5 
	 * node between `<` and `>` for attributes definitions.
	 * @param string	$str 
	 * @param bool		$double 
	 * @param string	$encoding 
	 * @return string
	 */
	public function EscapeHtml ($str, $encoding = 'UTF-8');
	
	/**
	 * Escape string for use inside HTML/XHTML/HTML5 attribute.
	 * @param string	$str 
	 * @param bool		$double 
	 * @param string	$encoding 
	 * @return string
	 */
	public function EscapeAttr ($str, $double = TRUE, $encoding = 'UTF-8');
	
	/**
	 * Escape string for use inside XML template.
	 * XML 1.0:	\x09 \x0A \x0D and C1 allowed directly, C0 forbidden
	 * XML 1.1:	\x00 forbidden directly and as a character reference,
	 * 		\x09 \x0A \x0D \x85 allowed directly, C0, C1 and \x7F allowed as character references
	 * @param string $str 
	 * @param string $encoding 
	 * @return string
	 */
	public function EscapeXml ($str, $encoding = 'UTF-8');
	
	/**
	 * Escape string for use inside JS context, including trailing double quotes.
	 * @param string	$str 
	 * @param int		$flags 
	 * @param int		$depth 
	 * @return string
	 */
	public function EscapeJs ($str, $flags = 0, $depth = 512);
	
	/**
	 * Escape string for use inside CSS context.
	 * @see http://www.w3.org/TR/2006/WD-CSS21-20060411/syndata.html#q6
	 * @param string $str 
	 * @return string
	 */
	public function EscapeCss ($str);
	
	/**
	 * Escape string for use inside iCal template.
	 * @see https://www.ietf.org/rfc/rfc5545.txt
	 * @param string $str 
	 * @return string
	 */
	public function EscapeICal ($str);

	/**
	 * Try to get view helper.
	 * If view helper doesn't exist in global helpers store - create new helper instance.
	 * If helper already exists in global helpers store - do not create it again - use instance from the store.
	 * Example: `echo $this->GetHelper('facebook')->RenderSomeSpecialWidgetMethod();`
	 * @param string $helperNameCamelCase View helper method name in camel case.
	 * @param bool $asClosure Get View helper prepared as closure function, `FALSE` by default.
	 * @throws \InvalidArgumentException If view doesn't exist in configured namespaces.
	 * @return mixed View helper instance, always as `\MvcCore\Ext\Views\Helpers\AbstractHelper|\MvcCore\Ext\Views\Helpers\IHelper` instance.
	 */
	public function & GetHelper ($helperNameCamelCase, $asClosure = FALSE);

	/**
	 * Set view helper for current template or for all templates globally by default.
	 * If view helper already exist in global helpers store - it's overwritten.
	 * @param string $helperNameCamelCase View helper method name in camel case.
	 * @param mixed $instance View helper instance, always as `\MvcCore\Ext\Views\Helpers\AbstractHelper|\MvcCore\Ext\Views\Helpers\IHelper` instance or `\Closure`.
	 * @param bool $forAllTemplates register this helper instance for all rendered views in the future.
	 * @return \MvcCore\View
	 */
	public function SetHelper ($helperNameCamelCase, $instance, $forAllTemplates = TRUE);

	/**
	 * Set any value into view context internal store.
	 * @param string $name
	 * @param mixed $value
	 * @return bool
	 */
	public function __set ($name, $value);

	/**
	 * Get any value by given name existing in local store. If there is no value
	 * in local store by given name, try to get result value into store by
	 * controller reflection class from controller instance property.
	 * @param string $name
	 * @return mixed
	 */
	public function __get ($name);

	/**
	 * Get `TRUE` if any value by given name exists in
	 * local view store or in local controller instance.
	 * @param string $name
	 * @return bool
	 */
	public function __isset ($name);

	/**
	 * Unset any value from view context internal store.
	 * @param string $name
	 * @return void
	 */
	public function __unset ($name);

	/**
	 * Try to call view helper.
	 * If view helper doesn't exist in global helpers store - create new helper instance.
	 * If helper already exists in global helpers store - do not create it again - use instance from the store.
	 * Then call it's public method named in the same way as helper and return result
	 * as it is, without any conversion. So then there could be called any other helper method if whole helper instance is returned.
	 * @param string $method View helper method name in pascal case.
	 * @param mixed $arguments View helper method arguments.
	 * @throws \InvalidArgumentException If view doesn't exist in configured namespaces.
	 * @return string|mixed View helper string result or any other view helper result type or view helper instance, always as `\MvcCore\Ext\Views\Helpers\AbstractHelper|\MvcCore\Ext\Views\Helpers\IHelper` instance.
	 */
	public function __call ($method, $arguments);
}
