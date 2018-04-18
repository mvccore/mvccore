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

namespace MvcCore\Interfaces;

//include_once('IController.php');

/**
 * Core view:
 * - Static storage for
 *   - commonly used doctype
 *   - common views extension
 *   - common directories names containing view scripts
 *   - common views helpers namespaces
 * - It's possible to use this class for any controller, subcontroller or form.
 * - View prerender preparing and rendering.
 * - View helpers management on demand:
 *   - Creating by predefined class namespaces.
 *   - global static helpers instances storage and repeatable calling.
 * - Views sub scripts relative path solving in:
 *   `<?php $this->renderScript('./any-subdirectory/script-to-render.php'); ?>`
 * - `Url()` - proxy method from `\MvcCore\Router` targeting to configured router.
 * - `AssetUrl()` - proxy method from `\MvcCore\Controller`.
 * - Magic calls:
 *   - __call() - To handler any view helper, if no helper found - exception thrown.
 *   - __set() - To set anything from controller to get it back in view.
 *   - __get() - To get anything in view previously initialized from controller.
 * - Optional direct code evaluation.
 * - No special view language implemented.
 *   - Why to use such stupid things, if we have configured `short_open_tags` by default? `<?=...?>`
 */
interface IView
{
	/**
	 * View output document type HTML4.
	 * @var string
	 */
	const DOCTYPE_HTML4 = 'HTML4';

	/**
	 * View output document type XHTML.
	 * @var string
	 */
	const DOCTYPE_XHTML = 'XHTML';

	/**
	 * View output document type HTML5.
	 * @var string
	 */
	const DOCTYPE_HTML5 = 'HTML5';

	/**
	 * Static initialization to complete
	 * `static::$HelpersClassesNamespaces` by application configuration.
	 * @return void
	 */
	public static function StaticInit ();

	/**
	 * Return always new instance of staticly called class, no singleton.
	 * Always called from `\MvcCore\Controller::PreDispatch()`.
	 * This is place where to customize any view creation process,
	 * before it's created by MvcCore framework to fill and render it.
	 * @return \MvcCore\Interfaces\IView
	 */
	public static function GetInstance ();

	/**
	 * Add view helpers classes namespace(s),
	 * Example: `\MvcCore\View::AddHelpersClassNamespaces('\Any\Other\ViewHelpers\Place\', '...');`.
	 * @param string $helperNamespace,... View helper classes namespace(s)
	 * @return void
	 */
	public static function AddHelpersClassNamespaces (/*...$helperNamespace*/);

	/**
	 * Get view script full path by internal application configuration,
	 * by `$typePath` param and by `$corectedRelativePath` param.
	 * @param string $typePath Usually `"Layouts"` or `"Scripts"`.
	 * @param string $corectedRelativePath
	 * @return string
	 */
	public static function GetViewScriptFullPath ($typePath = '', $corectedRelativePath = '');

	/**
	 * Set controller instance.
	 * @param \MvcCore\Interfaces\IController $controller
	 * @return \MvcCore\Interfaces\IView
	 */
	public function & SetController (\MvcCore\Interfaces\IController & $controller);

	/**
	 * Get controller instance as reference.
	 * @return \MvcCore\Interfaces\IController
	 */
	public function & GetController ();

	/**
	 * Set up all from given view object variables store into current store,
	 * if there is any already existing key - overwrite it.
	 * @param \MvcCore\Interfaces\IView $view
	 * @return \MvcCore\Interfaces\IView
	 */
	public function & SetValues (\MvcCore\Interfaces\IView & $view);

	/**
	 * Return rendered controller/action template content as reference.
	 * @return string
	 */
	public function & GetContent ();

	/**
	 * Render controller/action template script and return it's result as reference.
	 * @param string $relativePath
	 * @return string
	 */
	public function & RenderScript ($relativePath = '');

	/**
	 * Render layout template script and return it's result as reference.
	 * @param string $relativePath
	 * @return string
	 */
	public function & RenderLayout ($relativePath = '');

	/**
	 * Render layout template script and return it's result
	 * as reference with inner rendered content.
	 * @param string $relativePatht.
	 * @param string $content
	 * @return string
	 */
	public function & RenderLayoutAndContent ($relativePath = '', $content = '');

	/**
	 * Render controller template and all necessary layout
	 * templates and return rendered result as reference.
	 * @param string $typePath
	 * @param string $relativePath
	 * @throws \Exception
	 * @return string
	 */
	public function & Render ($typePath = '', $relativePath = '');

	/**
	 * Evaluate given code as PHP code by `eval()` in current view context,
	 * any `$this` keyword will be used as current view context.
	 * Returned result is content from output buffer as reference.
	 * @param string $content
	 * @return string
	 */
	public function Evaluate ($content = '');

	/**
	 * Generates url:
	 * - By `"Controller:Action"` name and params array
	 *   (for routes configuration when routes array has keys with `"Controller:Action"` strings
	 *   and routes has not controller name and action name defined inside).
	 * - By route name and params array
	 *	 (route name is key in routes configuration array, should be any string
	 *	 but routes must have information about controller name and action name inside).
	 * Result address (url string) should have two forms:
	 * - Nice rewrited url by routes configuration
	 *   (for apps with URL rewrite support (Apache `.htaccess` or IIS URL rewrite module)
	 *   and when first param is key in routes configuration array).
	 * - For all other cases is url form like: `"index.php?controller=ctrlName&amp;action=actionName"`
	 *	 (when first param is not founded in routes configuration array).
	 * @param string $controllerActionOrRouteName	Should be `"Controller:Action"` combination or just any route name as custom specific string.
	 * @param array  $params						Optional, array with params, key is param name, value is param value.
	 * @return string
	 */
	public function Url ($controllerActionOrRouteName = 'Index:Index', $params = array());

	/**
	 * Return asset path or single file mode url for small assets
	 * handled by internal controller action `"Controller:Asset"`.
	 * @param string $path
	 * @return string
	 */
	public function AssetUrl ($path = '');

	/**
	 * Set any value into view context internal store
	 * except system keys declared in `static::$originalyDeclaredProperties`.
	 * @param string $name
	 * @param mixed $value
	 * @throws \Exception
	 */
	public function __set ($name, $value);

	/**
	 * Get any value from view context internal store
	 * except system keys declared in `static::$originalyDeclaredProperties`.
	 * @param string $name
	 * @throws \Exception
	 */
	public function & __get ($name);

	/**
	 * Try to call view helper.
	 * If view helper doesn't exist in global helpers store - create new helper instance.
	 * If helper already exists in global helpers store - do not create it again - use instance from the store.
	 * Then call it's public method named in the same way as helper and return result
	 * as it is, without any conversion. So then there could be called any other helper method if whole helper instance is returned.
	 * @param string $method
	 * @param mixed $arguments
	 * @return string|mixed
	 */
	public function & __call ($method, $arguments);
}
