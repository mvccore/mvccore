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

namespace MvcCore;

include_once(__DIR__ . '/Application.php'); // because of static init
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
	 * View scripts files extension with leading dot char.
	 * Default value: `".phtml"`.
	 * @var string
	 */
	protected static $extension = '.phtml';

	/**
	 * Output document type (to automaticly and optionaly send proper
	 * HTTP header `Content-Type`, if there is no `Content-Type` HTTP
	 * header in response object yet).
	 * This value could be used also for any other custom purposses.
	 * Possible values:
	 * - `HTML4` - `\MvcCore\Interfaces\IView::DOCTYPE_HTML4`
	 * - `XHTML` - `\MvcCore\Interfaces\IView::DOCTYPE_XHTML`
	 * - `HTML5` - `\MvcCore\Interfaces\IView::DOCTYPE_HTML5`
	 * - `XML`   - `\MvcCore\Interfaces\IView::DOCTYPE_XML`
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
	protected static $helpersNamespaces = array(
		/*'\MvcCore\Ext\Views\Helpers\'*/
	);

	/**
	 * Originaly declared internal view properties to protect their
	 * possible overwriting by `__set()` or `__get()` magic methods.
	 * @var array
	 */
	protected static $protectedProperties = array(
		'_controller'		=> 1,
		'_store'			=> 1,
		'_helpers'			=> 1,
		'_content'			=> 1,
		'_renderedFullPaths'=> 1,
	);

	/**
	 * Global helpers instances storrage.
	 * Keys in array are helper method names.
	 * These helpers instances are used for all views.
	 * @var array
	 */
	private static $_globalHelpers = array();

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

	/**
	 * Controller instance.
	 * @var \MvcCore\Controller|\MvcCore\Interfaces\IController
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
	 * Helpers instances storrage for current view instance.
	 * Keys in array are helper method names.
	 * Every view has it's own helpers storrage to recognize
	 * if helper has been already used inside current view or not.
	 * @var array
	 */
	private $_helpers = array();

	/**
	 * Currently rendered php/html file path(s).
	 * @var array
	 */
	private $_renderedFullPaths = array();

	/**
	 * Return always new instance of staticly called class, no singleton.
	 * Always called from `\MvcCore\Controller::PreDispatch()` and
	 * `\MvcCore\Controller::Render()` to create layout view.
	 * This is place where to customize any view creation process,
	 * before it's created by MvcCore framework to fill and render it.
	 * @return \MvcCore\View
	 */
	public static function CreateInstance () {
		return new static();
	}

	/**
	 * Get view scripts files extension with leading dot char.
	 * Default value: `".phtml"`.
	 * @return string
	 */
	public static function GetExtension () {
		return static::$extension;
	}

	/**
	 * Set view scripts files extension.
	 * given value could be with or without leading dot char.
	 * @param string $extension Extension with or without leading dot char.
	 * @return void
	 */
	public static function SetExtension ($extension = '.phtml') {
		static::$extension = $extension;
	}

	/**
	 * Get output document type (to automaticly and optionaly send proper
	 * HTTP header `Content-Type`, if there is no `Content-Type` HTTP
	 * header in response object yet).
	 * This value could be used also for any other custom purposses.
	 * Possible values:
	 * - `HTML4` - `\MvcCore\Interfaces\IView::DOCTYPE_HTML4`
	 * - `XHTML` - `\MvcCore\Interfaces\IView::DOCTYPE_XHTML`
	 * - `HTML5` - `\MvcCore\Interfaces\IView::DOCTYPE_HTML5`
	 * - `XML`   - `\MvcCore\Interfaces\IView::DOCTYPE_XML`
	 * Default value: `HTML5`.
	 * @return string
	 */
	public static function GetDoctype () {
		return static::$doctype;
	}

	/**
	 * Set output document type (to automaticly and optionaly send proper
	 * HTTP header `Content-Type`, if there is no `Content-Type` HTTP
	 * header in response object yet).
	 * This value could be used also for any other custom purposses.
	 * Possible values:
	 * - `HTML4` - `\MvcCore\Interfaces\IView::DOCTYPE_HTML4`
	 * - `XHTML` - `\MvcCore\Interfaces\IView::DOCTYPE_XHTML`
	 * - `HTML5` - `\MvcCore\Interfaces\IView::DOCTYPE_HTML5`
	 * - `XML`   - `\MvcCore\Interfaces\IView::DOCTYPE_XML`
	 * Default value: `HTML5`.
	 * @param string $doctype
	 * @return void
	 */
	public static function SetDoctype ($doctype = \MvcCore\Interfaces\IView::DOCTYPE_HTML5) {
		static::$doctype = $doctype;
	}

	/**
	 * Get layout templates directory placed by default
	 * inside `"/App/Views"` directory. Default value
	 * is `"Layouts"`, so layouts app path
	 * is `"/App/Views/Layouts"`.
	 * @return string
	 */
	public static function GetLayoutsDir () {
		return static::$layoutsDir;
	}

	/**
	 * Set layout templates directory placed by default
	 * inside `"/App/Views"` directory. Default value
	 * is `"Layouts"`, so layouts app path
	 * is `"/App/Views/Layouts"`.
	 * @param string $layoutsDir
	 * @return void
	 */
	public static function SetLayoutsDir ($layoutsDir = 'Layouts') {
		static::$layoutsDir = $layoutsDir;
	}

	/**
	 * Get controller/action templates directory
	 * placed by default inside `"/App/Views"` directory.
	 * Default value is `"Scripts"`, so scripts app path
	 * is `"/App/Views/Scripts"`.
	 * @return string
	 */
	public static function GetScriptsDir () {
		return static::$scriptsDir;
	}

	/**
	 * Set controller/action templates directory
	 * placed by default inside `"/App/Views"` directory.
	 * Default value is `"Scripts"`, so scripts app path
	 * is `"/App/Views/Scripts"`.
	 * @param string $scriptsDir
	 * @return void
	 */
	public static function SetScriptsDir ($scriptsDir = 'Scripts') {
		static::$scriptsDir = $scriptsDir;
	}

	/**
	 * Get views helpers directory placed by default
	 * inside `"/App/Views"` directory.
	 * Default value is `"Helpers"`, so scripts app path
	 * is `"/App/Views/Helpers"`.
	 * @return string
	 */
	public static function GetHelpersDir () {
		return static::$helpersDir;
	}

	/**
	 * Set views helpers directory placed by default
	 * inside `"/App/Views"` directory.
	 * Default value is `"Helpers"`, so scripts app path
	 * is `"/App/Views/Helpers"`.
	 * @param string $helpersDir
	 * @return void
	 */
	public static function SetHelpersDir ($helpersDir = 'Helpers') {
		static::$helpersDir = $helpersDir;
	}

	/**
	 * Add view helpers classes namespace(s),
	 * Example: `\MvcCore\View::AddHelpersNamespaces('Any\Other\ViewHelpers\Place', '...');`.
	 * @param string $helperNamespace,... View helper classes namespace(s).
	 * @return void
	 */
	public static function AddHelpersNamespaces (/* ...$helperNamespace */) {
		if (!static::$helpersNamespaces) self::_initHelpersNamespaces();
		foreach (func_get_args() as $arg)
			static::$helpersNamespaces[] = '\\' . trim($arg, '\\') . '\\';
	}

	/**
	 * Set view helpers classes namespace(s). This method replace all previously configured namespaces.
	 * If you want only to add namespace, use `\Mvccore\View::AddHelpersNamespaces();` instead.
	 * Example: `\MvcCore\View::SetHelpersClassNamespaces('Any\Other\ViewHelpers\Place', '...');`.
	 * @param string $helperNamespace,... View helper classes namespace(s).
	 * @return void
	 */
	public static function SetHelpersNamespaces (/* ...$helperNamespace */) {
		static::$helpersNamespaces = array();
		foreach (func_get_args() as $arg)
			static::$helpersNamespaces[] = '\\' . trim($arg, '\\') . '\\';
	}

	/**
	 * Get view script full path by internal application configuration,
	 * by `$typePath` param and by `$corectedRelativePath` param.
	 * @param string $typePath Usually `"Layouts"` or `"Scripts"`.
	 * @param string $corectedRelativePath
	 * @return string
	 */
	public static function GetViewScriptFullPath ($typePath = '', $corectedRelativePath = '') {
		if (self::$_viewScriptsFullPathBase === NULL) 
			self::_initViewScriptsFullPathBase();
		return implode('/', array(
			self::$_viewScriptsFullPathBase,
			$typePath,
			$corectedRelativePath . static::$extension
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
	 * Set up all instance public and instance protected properties from given controller
	 * instance into current store by reflection class. If there is any already existing 
	 * key in current store - overwrite it.
	 * @param \MvcCore\Controller|\MvcCore\Interfaces\IController $controller
	 * @param bool $overwriteExistingKeys If any property name already exist in view store, overwrite it by given value by default.
	 * @return \MvcCore\View
	 */
	public function & SetUpValuesFromController (\MvcCore\Interfaces\IController & $controller, $overwriteExistingKeys = TRUE) {
		$type = new \ReflectionClass($controller);
		/** @var $props \ReflectionProperty[] */
		$props = $type->getProperties(\ReflectionProperty::IS_PUBLIC | \ReflectionProperty::IS_PROTECTED);
		foreach ($props as $prop) {
			if (!$overwriteExistingKeys && isset($this->_store[$prop->name])) continue;
			if ($prop->isProtected()) $prop->setAccessible(TRUE);
			$this->_store[$prop->name] = $prop->getValue($controller);
		}
		return $this;
	}

	/**
	 * Set up all from given view object variables store into current store,
	 * if there is any already existing key - overwrite it.
	 * @param \MvcCore\View $view
	 * @param bool $overwriteExistingKeys If any property name already exist in view store, overwrite it by given value by default.
	 * @return \MvcCore\View
	 */
	public function & SetUpValuesFromView (\MvcCore\Interfaces\IView & $view, $overwriteExistingKeys = TRUE) {
		if ($overwriteExistingKeys) {
			$this->_store = array_merge($this->_store, $view->_store);
		} else {
			foreach ($view->_store as $key => & $value)
				if (!isset($view->_store))
					$view->_store[$key] = & $value;
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
	 * Get currently rendered view file full path.
	 * If this method is called outside of rendering process, `NULL` is returned.
	 * @return string|NULL
	 */
	public function GetCurrentViewFullPath () {
		$result = NULL;
		$count = count($this->_renderedFullPaths);
		if ($count > 0)
			$result = $this->_renderedFullPaths[$count - 1];
		return $result;
	}

	/**
	 * Get currently rendered view file directory full path.
	 * If this method is called outside of rendering process, `NULL` is returned.
	 * @return string|NULL
	 */
	public function GetCurrentViewDirectory () {
		$result = $this->GetCurrentViewFullPath();
		$lastSlashPos = mb_strrpos($result, '/');
		if ($lastSlashPos !== FALSE) {
			$result = mb_substr($result, 0, $lastSlashPos);
		}
		return $result;
	}

	/**
	 * Get currently rendered parent view file full path.
	 * Parent view file could be any view file, where is called `$this->RenderScript(...);`
	 * method to render sub-view file (actual view file) or it could be any view file
	 * from parent controller or if current controller has no parent controller,
	 * it could be layout view script full path.
	 * If this method is called outside of rendering process, `NULL` is returned.
	 * @return string|NULL
	 */
	public function GetParentViewFullPath () {
		$result = NULL;
		$count = count($this->_renderedFullPaths);
		if ($count > 1) {
			$result = $this->_renderedFullPaths[$count - 2];
		} else {
			$controller = $this->_controller;
			$parentCtrl = $controller->GetParentController();
			if ($parentCtrl !== NULL) {
				while (TRUE) {
					$parentCtrlView = $parentCtrl->GetView();
					if ($parentCtrlView === NULL) {
						$parentCtrl->GetParentController();
						if ($parentCtrl === NULL) break;
					}
					$result = $parentCtrlView->GetCurrentViewFullPath();
					if ($result !== NULL) break;
				}
			}
			if ($result === NULL) {
				$relativePath = $this->_correctRelativePath(static::$layoutsDir, $controller->GetLayout());
				return static::GetViewScriptFullPath(static::$layoutsDir, $relativePath);
			}
		}
		return $result;
	}

	/**
	 * Get currently rendered parent view file directory full path.
	 * Parent view file could be any view file, where is called `$this->RenderScript(...);`
	 * method to render sub-view file (actual view file) or it could be any view file
	 * from parent controller or if current controller has no parent controller,
	 * it could be layout view script full path.
	 * If this method is called outside of rendering process, `NULL` is returned.
	 * @return string|NULL
	 */
	public function GetParentViewDirectory () {
		$result = $this->GetParentViewFullPath();
		$lastSlashPos = mb_strrpos($result, '/');
		if ($lastSlashPos !== FALSE) {
			$result = mb_substr($result, 0, $lastSlashPos);
		}
		return $result;
	}

	/**
	 * Render controller/action template script and return it's result as reference.
	 * @param string $relativePath
	 * @return string
	 */
	public function & RenderScript ($relativePath = '') {
		return $this->Render(static::$scriptsDir, $relativePath);
	}

	/**
	 * Render layout template script and return it's result as reference.
	 * @param string $relativePath
	 * @return string
	 */
	public function & RenderLayout ($relativePath = '') {
		return $this->Render(static::$layoutsDir, $relativePath);
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
		return $this->Render(static::$layoutsDir, $relativePath);
	}

	/**
	 * Render controller template and all necessary layout
	 * templates and return rendered result as reference.
	 * @param string $typePath By default: `"Layouts" | "Scripts"`. It could be `"Forms" | "Forms/Fields"` etc...
	 * @param string $relativePath 
	 * @throws \Exception
	 * @return string
	 */
	public function & Render ($typePath = '', $relativePath = '') {
		if (!$typePath) $typePath = static::$scriptsDir;
		$result = '';
		$relativePath = $this->_correctRelativePath(
			$typePath, $relativePath
		);
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
		return $this->_controller->AssetUrl($path);
	}

	/**
	 * Try to get view helper.
	 * If view helper doesn't exist in global helpers store - create new helper instance.
	 * If helper already exists in global helpers store - do not create it again - use instance from the store.
	 * @param string $helperName View helper method name in pascal case.
	 * @throws \InvalidArgumentException If view doesn't exist in configured namespaces.
	 * @return \MvcCore\Ext\Views\Helpers\AbstractHelper|\MvcCore\Ext\Views\Helpers\IHelper|mixed View helper instance.
	 */
	public function & GetHelper ($helperName) {
		$setUpViewAgain = FALSE;
		$implementsIHelper = FALSE;
		$instance = NULL;
		if (isset($this->_helpers[$helperName])) {
			$instance = & $this->_helpers[$helperName];
		} else if (isset(self::$_globalHelpers[$helperName])) {
			$globalHelpersRecord = & self::$_globalHelpers[$helperName];
			$instance = & $globalHelpersRecord[0];
			$implementsIHelper = $globalHelpersRecord[1];
			$setUpViewAgain = TRUE;
		} else {
			$helperFound = FALSE;
			if (self::$_toolClass === NULL) 
				self::$_toolClass = \MvcCore\Application::GetInstance()->GetToolClass();
			$toolClass = self::$_toolClass;
			$helpersInterface = self::HELPERS_INTERFACE_CLASS_NAME;
			if (!static::$helpersNamespaces) self::_initHelpersNamespaces();
			foreach (static::$helpersNamespaces as $helperClassBase) {
				$className = $helperClassBase . ucfirst($helperName);
				if (class_exists($className)) {
					$helperFound = TRUE;
					$setUpViewAgain = TRUE;
					if ($toolClass::CheckClassInterface($className, $helpersInterface, TRUE, FALSE)) {
						$implementsIHelper = TRUE;
						$instance = & $className::GetInstance();
					} else {
						$instance = new $className();
					}
					self::$_globalHelpers[$helperName] = array(& $instance, $implementsIHelper);
					break;
				}
			}
			if (!$helperFound) throw new \InvalidArgumentException(
				"[".__CLASS__."] View helper method '$helperName' is not possible to handle by any configured view helper "
				." (View helper namespaces: '".implode("', '", static::$helpersNamespaces)."')."
			);
		}
		if ($setUpViewAgain) {
			if ($implementsIHelper) $instance->SetView($this);
			$this->_helpers[$helperName] = & $instance;
		}
		return $instance;
	}

	/**
	 * Set view helper for current template or for all templates globaly by default.
	 * If view helper already exist in global helpers store - it's overwritten.
	 * @param string $helperName View helper method name in pascal case.
	 * @param \MvcCore\Ext\Views\Helpers\AbstractHelper|\MvcCore\Ext\Views\Helpers\IHelper|mixed $instance View helper instance.
	 * @param bool $forAllTemplates register this helper instance for all rendered views in the future.
	 * @return \MvcCore\View|\MvcCore\Interfaces\IView
	 */
	public function & SetHelper ($helperName, & $instance, $forAllTemplates = TRUE) {
		$implementsIHelper = FALSE;
		if ($forAllTemplates) {
			if (self::$_toolClass === NULL) 
				self::$_toolClass = \MvcCore\Application::GetInstance()->GetToolClass();
			$toolClass = self::$_toolClass;
			$helpersInterface = self::HELPERS_INTERFACE_CLASS_NAME;
			$className = get_class($instance);
			$implementsIHelper = $toolClass::CheckClassInterface($className, $helpersInterface, FALSE, FALSE);
			self::$_globalHelpers[$helperName] = array(& $instance, $implementsIHelper);
		}
		$this->_helpers[$helperName] = & $instance;
		if ($implementsIHelper) $instance->SetView($this);
		return $this;
	}

	/**
	 * Set any value into view context internal store
	 * except system keys declared in `static::$protectedProperties`.
	 * @param string $name
	 * @param mixed $value
	 * @throws \Exception
	 * @return bool
	 */
	public function __set ($name, $value) {
		if (isset(static::$protectedProperties[$name])) {
			throw new \InvalidArgumentException(
				'['.__CLASS__."] It's not possible to change property: '$name' originaly declared in class ".__CLASS__.'.'
			);
		}
		return $this->_store[$name] = & $value;
	}

	/**
	 * Get any value from view context internal store
	 * except system keys declared in `static::$protectedProperties`.
	 * @param string $name
	 * @throws \Exception
	 * @return mixed
	 */
	public function __get ($name) {
		if (isset(static::$protectedProperties[$name])) {
			throw new \InvalidArgumentException(
				'['.__CLASS__."] It's not possible to get internal private property: '$name' in class ".__CLASS__.'.'
			);
		}
		return isset($this->_store[$name]) ? $this->_store[$name] : NULL;
	}

	/**
	 * Get if any value from view context internal store exists
	 * except system keys declared in `static::$protectedProperties`.
	 * @param string $name
	 * @return bool
	 */
	public function __isset ($name) {
		if (isset(static::$protectedProperties[$name])) return TRUE;
		return isset($this->_store[$name]);
	}

	/**
	 * Unset any value from view context internal store
	 * except system keys declared in `static::$protectedProperties`.
	 * @param string $name
	 * @return void
	 */
	public function __unset ($name) {
		if (isset(static::$protectedProperties[$name])) {
			throw new \InvalidArgumentException(
				'['.__CLASS__."] It's not possible to unset internal private property: '$name' in class ".__CLASS__.'.'
			);
		}
		if (isset($this->_store[$name]))
			unset($this->_store[$name]);
	}

	/**
	 * Try to call view helper.
	 * If view helper doesn't exist in global helpers store - create new helper instance.
	 * If helper already exists in global helpers store - do not create it again - use instance from the store.
	 * Then call it's public method named in the same way as helper and return result
	 * as it is, without any conversion. So then there could be called any other helper method if whole helper instance is returned.
	 * @param string $method View helper method name in pascal case.
	 * @param mixed $arguments View helper method arguments.
	 * @throws \InvalidArgumentException If view doesn't exist in configured namespaces.
	 * @return \MvcCore\Ext\Views\Helpers\AbstractHelper|\MvcCore\Ext\Views\Helpers\IHelper|string|mixed View helper string result or view helper instance or any other view helper result type.
	 */
	public function __call ($method, $arguments) {
		$result = '';
		$instance = & $this->GetHelper($method);
		if (method_exists($instance, $method)) {
			$result = call_user_func_array(array($instance, $method), $arguments);
		} else {
			throw new \InvalidArgumentException(
				"[".__CLASS__."] View helper instance '".get_class($instance)."' has no method '$method'."
			);
		}
		return $result;
	}

	/**
	 * If relative path declared in view starts with `"./anything/else.phtml"`,
	 * then change relative path to correct `"./"` context and return full path.
	 * @param string $typePath
	 * @param string $relativePath
	 * @return string full path
	 */
	private function _correctRelativePath ($typePath, $relativePath) {
		$result = str_replace('\\', '/', $relativePath);
		if (substr($relativePath, 0, 2) == './') {
			if (self::$_viewScriptsFullPathBase === NULL) 
				self::_initViewScriptsFullPathBase();
			$typedViewDirFullPath = implode('/', array(
				self::$_viewScriptsFullPathBase, $typePath
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

	/**
	 * Init view scripts full class string for methods:
	 * - `\MvcCore\View::GetViewScriptFullPath();`
	 * - `\MvcCore\View::_correctRelativePath();`
	 * @return void
	 */
	private static function _initViewScriptsFullPathBase () {
		$app = & \MvcCore\Application::GetInstance();
		self::$_viewScriptsFullPathBase = implode('/', array(
			$app->GetRequest()->GetAppRoot(),
			$app->GetAppDir(),
			$app->GetViewsDir()
		));
	}

	/**
	 * Static initialization to complete
	 * `static::$helpersNamespaces` 
	 * by application configuration once.
	 * @return void
	 */
	private static function _initHelpersNamespaces () {
		$app = & \MvcCore\Application::GetInstance();
		static::$helpersNamespaces = array(
			'\\MvcCore\\Ext\\Views\Helpers\\',
			// and '\App\Views\Helpers\' by default:
			'\\' . implode('\\', array(
				$app->GetAppDir(),
				$app->GetViewsDir(),
				static::$helpersDir
			)) . '\\',
		);
	}
}
