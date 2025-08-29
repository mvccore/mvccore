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
 * @phpstan-type ViewHelper \MvcCore\Ext\Views\Helpers\AbstractHelper|\MvcCore\Ext\Views\Helpers\IHelper|\Closure|mixed
 */
interface IRendering {
	
	/**
	 * Render action template script or any include script and return it's result as reference.
	 * Do not use this method in layout sub-templates, use method `RenderLayout()` instead.
	 * @param  string              $relativePath
	 * Relative path from current view script.
	 * @param  array<string,mixed> $variables
	 * Associative array with variables to pass it 
	 * into view script inside view store or as local variables.
	 * @return string
	 */
	public function RenderScript ($relativePath, array $variables = []);

	/**
	 * Render layout template script or any include script and return it's result as reference.
	 * Do not use this method in action sub-templates, use method `RenderScript()` instead.
	 * @param  string              $relativePath
	 * Relative path from current view script.
	 * @param  array<string,mixed> $variables
	 * Associative array with variables to pass it 
	 * into view script inside view store or as local variables.
	 * @return string
	 */
	public function RenderLayout ($relativePath, array $variables = []);
	
	/**
	 * This method is INTERNAL, always called from `\MvcCore\Controller::Render();`.
	 * Do not use this method in templates!
	 * Method renders whole configured layout template and return it's result
	 * as string reference with inner rendered action template content.
	 * @internal
	 * @param  ?string     $relativePath
	 * @param  ?string     $content
	 * @return string
	 */
	public function RenderLayoutAndContent ($relativePath, $content = NULL);
	
	/**
	 * Set up view rendering arguments  to render layout and action view in both modes properly.
	 * Set up view instance helpers before rendering.
	 * @param  int    $renderMode
	 * @param  string $controllerOrActionNameDashed
	 * @param  string $actionNameDashed
	 * @return \MvcCore\View
	 */
	public function SetUpRender ($renderMode = \MvcCore\IView::RENDER_WITH_OB_FROM_ACTION_TO_LAYOUT, $controllerOrActionNameDashed = NULL, $actionNameDashed = NULL);
	
	/**
	 * Render controller template and return rendered 
	 * result as string or render it into output buffer.
	 * Try to find template by dispatched controller in 
	 * app composer package or in main app module.
	 * @param  int    $viewType
	 * @param  string $relativePath
	 * @throws \InvalidArgumentException Template not found in path: `$viewScriptFullPath`.
	 * @return string
	 */
	public function Render ($viewType, $relativePath);

	/**
	 * Render template by previously configured view object by given full path.
	 * @internal
	 * @param  string $viewScriptFullPath 
	 * @return string
	 */
	public function RenderByFullPath ($viewScriptFullPath);
	
	/**
	 * Get view script full path including view file extension 
	 * by typed views absolute directory and relative path without `./` and `../`.
	 * Example: 
	 * ```php
	 * $viewScriptFp = \MvcCore\View::GetViewScriptFullPath(
	 *    '/abs/doc/root/App/Views/Scripts', // could end with: `/Layouts` | `/Forms` | `/Scripts`
	 *    'my-module/ctrl-name/action-name'
	 * );
	 * // `/abs/doc/root/App/Views/Scripts/my-module/ctrl-name/action-name.phtml`
	 * ```
	 * @param  string $typedViewsDirFullPath Example: `/abs/doc/root/App/Views/{Layouts,Forms,Scripts}`.
	 * @param  string $corectedRelativePath  Example: `ctrl-name/action-name`.
	 * @return string
	 */
	public static function GetViewScriptFullPath ($typedViewsDirFullPath, $corectedRelativePath);
	
	/**
	 * Get typed views directory full path, cached in current view instance.
	 * Layouts directory could be only inside application, not in vendor packages.
	 * @param  int  $viewType
	 * @param  bool $default
	 * @return string
	 */
	public function GetTypedViewsDirFullPath ($viewType, $default = FALSE);
	
	/**
	 * Get application views directory full path by controller name used inside view instance.
	 * Controller class file could be placed inside application or in any vendor package:
	 *  - in application:    `/abs/doc/root/App/Controllers/MyModule/MyCtrl.php`
	 *  - in vendor package: `/abs/doc/root/vendor/package/extension/src/App/Controllers/MyModule/MyCtrl.php`
	 * The result views directory absolute path could be:
	 *  - for controller in application:    `/abs/doc/root/App/Views`
	 *  - for controller in vendor package: `/abs/doc/root/vendor/package/extension/src/App/Views`.
	 * @param  \MvcCore\Application $app 
	 * @param  string               $ctrlClassFullName
	 * @param  int                  $viewType
	 * @param  bool                 $useReflection
	 * @return string
	 */
	public static function GetExtViewsDirFullPath (\MvcCore\IApplication $app, $ctrlClassFullName, $viewType, $useReflection = TRUE);
	
	/**
	 * This is INTERNAL method, do not use it in templates.
	 * Method is always called in the most parent controller
	 * `\MvcCore\Controller:Render()` moment when view is rendered.
	 * Set up all from given view object variables store into current store,
	 * if there exists any key already - overwrite it by default.
	 * @param  \MvcCore\View $view                  View object to get store from, to set up all it's variables to current view store.
	 * @param  bool          $overwriteExistingKeys If any property name already exist in view store, overwrite it by given value by default.
	 * @return \MvcCore\View
	 */
	public function SetUpStore (\MvcCore\IView $view, $overwriteExistingKeys = TRUE);
	
	/**
	 * Add variables from given array store into current store,
	 * if there exists any key already - overwrite it by default.
	 * @param  array<string, mixed> $data                  View data store to add into view store.
	 * @param  bool                 $overwriteExistingKeys If any property name already exist in view store, overwrite it by given value by default.
	 * @return \MvcCore\View
	 */
	public function AddData (array $data, $overwriteExistingKeys = TRUE);

	/**
	 * Get view store data (as array reference).
	 * @return array<string, mixed>
	 */
	public function & GetData ();
	
	/**
	 * Return rendered action template content as string reference.
	 * You need to use this method always somewhere in layout template to
	 * render rendered action result content.
	 * If render mode is continuous, this method renders action view.
	 * @return string
	 */
	public function GetContent ();
	
	/**
	 * Evaluate given template code as PHP code by `eval()` in current view
	 * context, any `$this` keyword will be used as current view context.
	 * Returned result is content from output buffer as string reference.
	 * Evaluated code is wrapped into `try/catch` automatically.
	 * USE THIS METHOD ONLY IF YOU TRUST THE INPUT!
	 * @deprecated
	 * @param  ?string     $content
	 * @return string
	 */
	public function Evaluate ($content);

}