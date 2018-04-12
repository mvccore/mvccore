<?php

/**
 * MvcCore
 *
 * This source file is subject to the BSD 3 License
 * For the full copyright and license information, please view
 * the LICENSE.md file that are distributed with this source code.
 *
 * @copyright	Copyright (c) 2016 Tom Flídr (https://github.com/mvccore/mvccore)
 * @license		https://mvccore.github.io/docs/mvccore/4.0.0/LICENCE.md
 */

namespace MvcCore;

//include_once(__DIR__ . '/Interfaces/IView.php');

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
class View implements Interfaces\IView
{
	/**
	 * View script files extenion. Default value: `".phtml"`.
	 * For read & write.
	 * @var string
	 */
	public static $Extension = '.phtml';

	/**
	 * Document type to send proper optinal headers or anything else.
	 * Possible values:
	 * - `\MvcCore\Interfaces\IView::DOCTYPE_HTML4`
	 * - `\MvcCore\Interfaces\IView::DOCTYPE_XHTML`
	 * - `\MvcCore\Interfaces\IView::DOCTYPE_HTML5`
	 * @var string
	 */
	public static $Doctype = self::DOCTYPE_HTML5;

	/**
	 * Controller/action templates directory placed by default inside `"/App/Views"` directory.
	 * Default value: `"Scripts"`. For read & write.
	 * @var string
	 */
	public static $ScriptsDir = 'Scripts';

	/**
	 * Views helpers directory placed by default inside `"/App/Views"` directory.
	 * Default value: `"Helpers"`. For read & write.
	 * @var string
	 */
	public static $HelpersDir = 'Helpers';

	/**
	 * Layout templates directory placed by default inside `"/App/Views"` directory.
	 * Default value: `"Layouts"`. For read & write.
	 * @var string
	 */
	public static $LayoutsDir = 'Layouts';

	/**
	 * Helpers classes namespaces, where are all configured view helpers placed.
	 * For read & write.
	 * @var array
	 */
	public static $HelpersClassesNamespaces = array(
		/*'\MvcCore\Ext\View\Helpers\'*/
	);

	/**
	 * Controller instance.
	 * @var \MvcCore\Controller
	 */
	private $_controller = NULL;

	/**
	 * Rendered content.
	 * @var string
	 */
	private $_content = '';

	/**
	 * Variables store, setted (always from controller)
	 * throught `__set()` magic function.
	 * @var array
	 */
	private $_store = array();

	/**
	 * Currently rendered php/html file path(s).
	 * @var array
	 */
	private $_renderedFullPaths = array();

	/**
	 * Originaly declared internal view properties list to protect
	 * their possible overwriting by `__set()` magic method.
	 * @var string
	 */
	protected static $originalyDeclaredProperties = array(
		'_controller'		=> 1,
		'_store'			=> 1,
		'_content'			=> 1,
		'_renderedFullPaths'=> 1,
	);

	/**
	 * Helpers instances storrage. Keys in array are helper method names.
	 * @var array
	 */
	private static $_helpers = array();

	/**
	 * Static initialization to complete
	 * `static::$HelpersClassesNamespaces` by application configuration.
	 * @return void
	 */
	public static function StaticInit () {
		$app = \MvcCore\Application::GetInstance();
		static::$HelpersClassesNamespaces = array(
			'\MvcCore\Ext\View\Helpers\\',
			// and '\App\Views\Helpers\' by default:
			'\\' . implode('\\', array(
				$app->GetAppDir(),
				$app->GetViewsDir(),
				static::$HelpersDir
			)) . '\\',
		);
	}

	/**
	 * Add view helpers classes namespace(s),
	 * Example: `\MvcCore\View::AddHelpersClassNamespaces('\Any\Other\ViewHelpers\Place\', '...');`.
	 * @param string $helperNamespace,... View helper classes namespace(s)
	 * @return void
	 */
	public static function AddHelpersClassNamespaces (/*...$helperNamespace*/) {
		$args = func_get_args();
		foreach ($args as $arg) {
			static::$HelpersClassesNamespaces[] = '\\' . trim($arg, '\\') . '\\';
		}
	}

	/**
	 * Get view script full path by internal application configuration,
	 * by `$typePath` param and by `$corectedRelativePath` param.
	 * @param string $typePath Usually `"Layouts"` or `"Scripts"`.
	 * @param string $corectedRelativePath
	 * @return string
	 */
	public static function GetViewScriptFullPath ($typePath = '', $corectedRelativePath = '') {
		$app = \MvcCore\Application::GetInstance();
		return implode('/', array(
			$app->GetRequest()->GetAppRoot(),
			$app->GetAppDir(),
			$app->GetViewsDir(),
			$typePath,
			$corectedRelativePath . static::$Extension
		));
	}

	/**
	 * Set controller instance.
	 * @param \MvcCore\Controller $controller
	 * @return \MvcCore\View
	 */
	public function & SetController (\MvcCore\Interfaces\IController & $controller) {
		$this->_controller = $controller;
		return $this;
	}

	/**
	 * Get controller instance as reference.
	 * @return \MvcCore\Controller
	 */
	public function & GetController () {
		return $this->_controller;
	}

	/**
	 * Set up all from given view object variables store into current store,
	 * if there is any already existing key - overwrite it.
	 * @param \MvcCore\View $view
	 * @return \MvcCore\View
	 */
	public function & SetValues (& $view) {
		foreach ($view->_store as $key => $value) {
			$this->$key = $value;
		}
		return $this;
	}

	/**
	 * Return rendered controller/action template content as reference.
	 * @return string
	 */
	public function & GetContent () {
		return $this->_content;
	}

	/**
	 * Render controller/action template script and return it's result as reference.
	 * @param string $relativePath
	 * @return string
	 */
	public function & RenderScript ($relativePath = '') {
		return $this->Render(static::$ScriptsDir, $relativePath);
	}

	/**
	 * Render layout template script and return it's result as reference.
	 * @param string $relativePath
	 * @return string
	 */
	public function & RenderLayout ($relativePath = '') {
		return $this->Render(static::$LayoutsDir, $relativePath);
	}

	/**
	 * Render layout template script and return it's result
	 * as reference with inner rendered content.
	 * @param string $relativePatht.
	 * @param string $content
	 * @return string
	 */
	public function & RenderLayoutAndContent ($relativePath = '', $content = '') {
		$this->_content = $content;
		return $this->Render(static::$LayoutsDir, $relativePath);
	}

	/**
	 * Render controller template and all necessary layout
	 * templates and return rendered result as reference.
	 * @param string $typePath
	 * @param string $relativePath
	 * @throws \Exception
	 * @return string
	 */
	public function & Render ($typePath = '', $relativePath = '') {
		if (!$typePath) $typePath = static::$ScriptsDir;
		$result = '';
		$relativePath = $this->_correctRelativePath($this->Controller->GetRequest()->GetAppRoot(), $typePath, $relativePath);
		$viewScriptFullPath = static::GetViewScriptFullPath($typePath, $relativePath);
		if (!file_exists($viewScriptFullPath)) {
			throw new \InvalidArgumentException('['.__CLASS__."] Template not found in path: '$viewScriptFullPath'.");
		}
		$this->_renderedFullPaths[] = $viewScriptFullPath;
		ob_start();
		include($viewScriptFullPath);
		$result = ob_get_clean();
		array_pop($this->_renderedFullPaths); // unset last
		return $result;
	}

	/**
	 * Evaluate given code as PHP code by `eval()` in current view context,
	 * any `$this` keyword will be used as current view context.
	 * Returned result is content from output buffer as reference.
	 * @param string $content
	 * @return string
	 */
	public function & Evaluate ($content = '') {
		ob_start();
		try {
			eval(' ?'.'>'.$content.'<'.'?php ');
		} catch (\Exception $e) {
			throw $e;
		}
		return ob_get_clean();
	}

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
	public function Url ($controllerActionOrRouteName = 'Index:Index', $params = array()) {
		return $this->_controller->GetRouter()->Url($controllerActionOrRouteName, $params);
	}

	/**
	 * Return asset path or single file mode url for small assets
	 * handled by internal controller action `"Controller:Asset"`.
	 * @param string $path
	 * @return string
	 */
	public function AssetUrl ($path = '') {
		return $this->Controller->AssetUrl($path);
	}

	/**
	 * Set any value into view context internal store
	 * except system keys declared in `static::$originalyDeclaredProperties`.
	 * @param string $name
	 * @param mixed $value
	 * @throws \Exception
	 */
	public function __set ($name, $value) {
		if (isset(static::$originalyDeclaredProperties[$name])) {
			throw new \InvalidArgumentException(
				'['.__CLASS__."] It's not possible to change property: '$name' originaly declared in class ".__CLASS__.'.'
			);
		}
		$this->_store[$name] = & $value;
	}

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
	public function __call ($method, $arguments) {
		$result = '';
		$helperFound = FALSE;
		foreach (static::$HelpersClassesNamespaces as $helperClassBase) {
			$className = $helperClassBase . ucfirst($method);
			if (class_exists($className)) {
				$helperFound = TRUE;
				if (isset(self::$_helpers[$method]) && get_class(self::$_helpers[$method]) == $className) {
					$instance = self::$_helpers[$method];
					$result = call_user_func_array(array($instance, $method), $arguments);
				} else {
					$instance = new $className($this);
					$result = call_user_func_array(array($instance, $method), $arguments);
				}
				break;
			}
		}
		if (!$helperFound) throw new \InvalidArgumentException(
			"[".__CLASS__."] View helper method '$method' is not possible to handle by any configured view helper "
			." (View helper namespaces: '".implode("', '", static::$HelpersClassesNamespaces)."')."
		);
		return $result;
	}

	/**
	 * If relative path declared in view starts with `"./anything/else.phtml"`,
	 * then change relative path to correct `"./"` context and return full path.
	 * @param string $appRoot
	 * @param string $typePath
	 * @param string $relativePath
	 * @return string full path
	 */
	private function _correctRelativePath ($appRoot, $typePath, $relativePath) {
		$result = str_replace('\\', '/', $relativePath);
		if (substr($relativePath, 0, 2) == './') {
			$app = \MvcCore\Application::GetInstance();
			$typedViewDirFullPath = implode('/', array(
				$appRoot, $app->GetAppDir(), $app->GetViewsDir(), $typePath
			));
			$lastRenderedFullPath = $this->_renderedFullPaths[count($this->_renderedFullPaths) - 1];
			$renderedRelPath = substr($lastRenderedFullPath, strlen($typedViewDirFullPath));
			$renderedRelPathLastSlashPos = strrpos($renderedRelPath, '/');
			if ($renderedRelPathLastSlashPos !== FALSE) {
				$result = substr($renderedRelPath, 0, $renderedRelPathLastSlashPos + 1).substr($relativePath, 2);
				$result = ltrim($result, '/');
			}
		}
		return $result;
	}
}
View::StaticInit();
