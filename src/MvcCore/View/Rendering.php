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

trait Rendering {

	/**
	 * @inheritDocs
	 * @param  string $relativePath
	 * @return string
	 */
	public function & RenderScript ($relativePath = '') {
		/** @var $this \MvcCore\View */
		return $this->Render(static::$scriptsDir, $relativePath);
	}

	/**
	 * @inheritDocs
	 * @param  string $relativePath
	 * @return string
	 */
	public function & RenderLayout ($relativePath = '') {
		/** @var $this \MvcCore\View */
		return $this->Render(static::$layoutsDir, $relativePath);
	}

	/**
	 * @inheritDocs
	 * @param  string      $relativePath
	 * @param  string|NULL $content
	 * @return string
	 */
	public function & RenderLayoutAndContent ($relativePath = '', & $content = NULL) {
		/** @var $this \MvcCore\View */
		if ($relativePath === NULL) return $content; // no layout defined
		$this->__protected['content'] = & $content;
		return $this->Render(static::$layoutsDir, $relativePath);
	}

	/**
	 * @inheritDocs
	 * @param  int    $renderMode
	 * @param  string $controllerOrActionNameDashed
	 * @param  string $actionNameDashed
	 * @return \MvcCore\View
	 */
	public function SetUpRender ($renderMode = \MvcCore\IView::RENDER_WITH_OB_FROM_ACTION_TO_LAYOUT, $controllerOrActionNameDashed = NULL, $actionNameDashed = NULL) {
		/** @var $this \MvcCore\View */
		$this->__protected['renderArgs'] = func_get_args();
		// initialize helpers before rendering:
		$helpers = & $this->__protected['helpers'];
		$buildInHelpersInit = & $this->__protected['buildInHelpersInit'];
		if (!$buildInHelpersInit) {
			$buildInHelpersInit = TRUE;
			$this->setUpRenderBuildInHelpers($helpers);
		}
		foreach (self::$globalHelpers as $helperNamePascalCase => $helperRecord) {
			$helperNameCamelCase = lcfirst($helperNamePascalCase);
			if (isset($helpers[$helperNameCamelCase])) continue;
			//list($instance, $implementsIHelper, $needsClosureFn) = $helperRecord;
			$instance = & $helperRecord[0];
			$implementsIHelper = $helperRecord[1];
			$needsClosureFn = $helperRecord[2];
			if ($implementsIHelper)
				$instance->SetView($this);
			if ($needsClosureFn) {
				$helpers[$helperNameCamelCase] = function () use (& $instance, $helperNamePascalCase) {
					return call_user_func_array([$instance, $helperNamePascalCase], func_get_args());
				};
			} else {
				$helpers[$helperNameCamelCase] = & $instance;
			}
		}
		return $this;
	}

	/**
	 * @inheritDocs
	 * @param  string      $typePath     By default: `"Layouts" | "Scripts"`. It could be `"Forms" | "Forms/Fields"` etc...
	 * @param  string      $relativePath
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
	 * @inheritDocs
	 * @param  string $typePath Usually `"Layouts"` or `"Scripts"`.
	 * @param  string $corectedRelativePath
	 * @return string
	 */
	public static function GetViewScriptFullPath ($typePath = '', $corectedRelativePath = '') {
		if (self::$viewScriptsFullPathBase === NULL)
			self::initViewScriptsFullPathBase();
		return implode('/', [
			self::$viewScriptsFullPathBase,
			$typePath,
			$corectedRelativePath . static::$extension
		]);
	}

	/**
	 * @inheritDocs
	 * @param  \MvcCore\View $view
	 * @param  bool          $overwriteExistingKeys If any property name already exist in view store, overwrite it by given value by default.
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
	 * @inheritDocs
	 * @return string
	 */
	public function & GetContent () {
		/** @var $this \MvcCore\View */
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
	 * @inheritDocs
	 * @param  string $content
	 * @return string
	 */
	public function & Evaluate ($content) {
		/** @var $this \MvcCore\View */
		if ($content === NULL || mb_strlen(strval($content)) === 0)
			return '';
		ob_start();
		try {
			eval(' ?'.'>'.$content.'<'.'?php ');
		} catch (\Exception $e) { // backward compatibility
			throw $e;
		} catch (\Throwable $e) {
			throw $e;
		}
		$content = ob_get_clean();
		return $content;
	}

	/**
	 * Set up build in view instance helpers before rendering.
	 * @param  array $helpers 
	 * @return void
	 */
	protected function setUpRenderBuildInHelpers (& $helpers) {
		/** @var $this \MvcCore\View */
		$router = $this->controller->GetRouter();
		$helpers += [
			'url' => function ($controllerActionOrRouteName = 'Index:Index', array $params = []) use (& $router) {
				return $router->Url($controllerActionOrRouteName, $params);
			},
			'assetUrl' => function ($path = '') use (& $router) {
				return $router->Url('Controller:Asset', ['path' => $path]);
			},
			'escapeHtml' => function ($str, $encoding = 'UTF-8') {
				return $this->EscapeHtml($str, $encoding);
			},
			'escapeHtmlText' => function ($str, $encoding = 'UTF-8') {
				return $this->EscapeHtmlText($str, $encoding);
			},
			'escapeHtmlAttr' => function ($str, $double = TRUE, $encoding = 'UTF-8') {
				return $this->EscapeHtmlAttr($str, $double, $encoding);
			},
			'escapeXml' => function ($str, $encoding = 'UTF-8') {
				return $this->EscapeXml($str, $encoding);
			},
			'escapeJs' => function ($str, $flags = 0, $depth = 512) {
				return $this->EscapeJs($str, $flags, $depth);
			},
			'escapeCss' => function ($str) {
				return $this->EscapeCss($str);
			},
			'escapeICal' => function ($str) {
				return $this->EscapeICal($str);
			},
		];
	}
}