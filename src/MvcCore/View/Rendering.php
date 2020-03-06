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

trait Rendering
{
	/**
	 * Render action template script or any include script and return it's result as reference.
	 * Do not use this method in layout sub-templates, use method `RenderLayout()` instead.
	 * @param string $relativePath
	 * @return string
	 */
	public function & RenderScript ($relativePath = '') {
		return $this->Render(static::$scriptsDir, $relativePath);
	}

	/**
	 * Render layout template script or any include script and return it's result as reference.
	 * Do not use this method in action sub-templates, use method `RenderScript()` instead.
	 * @param string $relativePath
	 * @return string
	 */
	public function & RenderLayout ($relativePath = '') {
		return $this->Render(static::$layoutsDir, $relativePath);
	}

	/**
	 * This method is INTERNAL, always called from `\MvcCore\Controller::Render();`.
	 * Do not use this method in templates!
	 * Method renders whole configured layout template and return it's result
	 * as string reference with inner rendered action template content.
	 * @param string $relativePatht.
	 * @param string $content
	 * @return string
	 */
	public function & RenderLayoutAndContent ($relativePath = '', & $content = NULL) {
		if ($relativePath === NULL) return $content; // no layout defined
		$this->__protected['content'] = & $content;
		return $this->Render(static::$layoutsDir, $relativePath);
	}

	/**
	 * Render controller template and all necessary layout
	 * templates and return rendered result as string reference.
	 * @param string $typePath By default: `"Layouts" | "Scripts"`. It could be `"Forms" | "Forms/Fields"` etc...
	 * @param string $relativePath
	 * @throws \InvalidArgumentException Template not found in path: `$viewScriptFullPath`.
	 * @return string
	 */
	public function & Render ($typePath = '', $relativePath = '') {
		/** @var $this \MvcCore\View */
		if (!$typePath)
			$typePath = static::$scriptsDir;
		$result = '';
		$relativePath = $this->correctRelativePath(
			$typePath, $relativePath
		);
		$viewScriptFullPath = static::GetViewScriptFullPath($typePath, $relativePath);
		if (!file_exists($viewScriptFullPath)) {
			throw new \InvalidArgumentException(
				"[".get_class()."] Template not found in path: `{$viewScriptFullPath}`."
			);
		}
		$renderedFullPaths = & $this->__protected['renderedFullPaths'];
		$renderedFullPaths[] = $viewScriptFullPath;
		// get render mode
		list($renderMode) = $this->__protected['renderArgs'];
		$renderModeWithOb = ($renderMode & \MvcCore\IView::RENDER_WITH_OB_FROM_ACTION_TO_LAYOUT) != 0;
		// if render mode is default - start output buffering
		if ($renderModeWithOb)
			ob_start();
		// render the template with local variables from the store
		$result = call_user_func(function ($viewPath, $controller, $helpers) {
			extract($helpers, EXTR_SKIP);
			unset($helpers);
			extract($this->__protected['store'], EXTR_SKIP);
			include($viewPath);
		}, $viewScriptFullPath, $this->controller, $this->__protected['helpers']);
		// if render mode is default - get result from output buffer and return the result,
		// if render mode is continuous - result is sent to client already, so return empty string only.
		if ($renderModeWithOb) {
			$result = ob_get_clean();
			\array_pop($renderedFullPaths); // unset last
			return $result;
		} else {
			$result = '';
			\array_pop($renderedFullPaths); // unset last
			return $result;
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
		if (self::$_viewScriptsFullPathBase === NULL)
			self::initViewScriptsFullPathBase();
		return implode('/', [
			self::$_viewScriptsFullPathBase,
			$typePath,
			$corectedRelativePath . static::$extension
		]);
	}

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
	public function SetUpStore (\MvcCore\IView $view, $overwriteExistingKeys = TRUE) {
		/** @var $this \MvcCore\View */
		$currentStore = & $this->__protected['store'];
		$viewStore = & $view->__protected['store'];
		if ($overwriteExistingKeys) {
			$this->__protected['store'] = array_merge($currentStore, $viewStore);
		} else {
			foreach ($viewStore as $key => & $value)
				if (!array_key_exists($key, $currentStore))
					$currentStore[$key] = & $value;
		}
		return $this;
	}

	/**
	 * Return rendered action template content as string reference.
	 * You need to use this method always somewhere in layout template to
	 * render rendered action result content.
	 * If render mode is continuous, this method renders action view.
	 * @return string
	 */
	public function & GetContent () {
		list(
			$renderMode,
			$controllerOrActionNameDashed,
			$actionNameDashed
		) = $this->__protected['renderArgs'];
		$renderModeWithOb = ($renderMode & \MvcCore\IView::RENDER_WITH_OB_FROM_ACTION_TO_LAYOUT) != 0;
		if ($renderModeWithOb) {
			return $this->__protected['content'];
		} else {
			// complete paths
			$viewScriptPath = $this->controller->GetViewScriptPath($controllerOrActionNameDashed, $actionNameDashed);
			// render action view into string
			$viewClass = $this->controller->GetApplication()->GetViewClass();
			/** @var $layout \MvcCore\View */
			$actionView = $viewClass::CreateInstance()
				->SetController($this->controller)
				->SetUpStore($this, TRUE)
				->SetUpRender(
					$renderMode, $controllerOrActionNameDashed, $actionNameDashed
				);
			$actionView->RenderScript($viewScriptPath);
			$result = '';
			return $result;
		}
	}

	/**
	 * Evaluate given template code as PHP code by `eval()` in current view
	 * context, any `$this` keyword will be used as current view context.
	 * Returned result is content from output buffer as string reference.
	 * Evaluated code is wrapped into `try/catch` automatically.
	 * @param string $content
	 * @return string
	 */
	public function & Evaluate ($content) {
		if ($content === NULL || mb_strlen(strval($content)) === 0)
			return '';
		ob_start();
		try {
			eval(' ?'.'>'.$content.'<'.'?php ');
		} catch (\Throwable $e) {
			throw $e;
		}
		$content = ob_get_clean();
		return $content;
	}
}