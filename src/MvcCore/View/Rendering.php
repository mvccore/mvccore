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
 * @phpstan-type ViewHelper \MvcCore\Ext\Views\Helpers\AbstractHelper|\MvcCore\Ext\Views\Helpers\IHelper|\Closure|mixed
 */
trait Rendering {

	/**
	 * @inheritDoc
	 * @param  string              $relativePath
	 * Relative path from current view script.
	 * @param  array<string,mixed> $variables
	 * Associative array with variables to pass it 
	 * into view script inside view store or as local variables.
	 * @return string
	 */
	public function RenderScript ($relativePath, array $variables = []) {
		if (count($variables) > 0) {
			$currentStore = & $this->__protected['store'];
			// always overvrite existing keys:
			$this->__protected['store'] = array_merge($currentStore, $variables);
		}
		return $this->Render(static::VIEW_TYPE_SCRIPT, $relativePath);
	}

	/**
	 * @inheritDoc
	 * @param  string              $relativePath
	 * Relative path from current view script.
	 * @param  array<string,mixed> $variables
	 * Associative array with variables to pass it 
	 * into view script inside view store or as local variables.
	 * @return string
	 */
	public function RenderLayout ($relativePath, array $variables = []) {
		if (count($variables) > 0) {
			$currentStore = & $this->__protected['store'];
			// always overvrite existing keys:
			$this->__protected['store'] = array_merge($currentStore, $variables);
		}
		return $this->Render(static::VIEW_TYPE_LAYOUT, $relativePath);
	}

	/**
	 * @inheritDoc
	 * @internal
	 * @param  string|NULL $relativePath
	 * @param  string|NULL $content
	 * @return string
	 */
	public function RenderLayoutAndContent ($relativePath, $content = NULL) {
		if ($relativePath === NULL) return $content; // no layout defined
		$this->__protected['content'] = $content;
		return $this->Render(static::VIEW_TYPE_LAYOUT, $relativePath);
	}

	/**
	 * @inheritDoc
	 * @param  int $renderMode
	 * @return \MvcCore\View
	 */
	public function SetUpRender ($renderMode = \MvcCore\IView::RENDER_WITH_OB_FROM_ACTION_TO_LAYOUT, $controllerOrActionNameDashed = NULL, $actionNameDashed = NULL) {
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
	 * @inheritDoc
	 * @param  int    $viewType
	 * @param  string $relativePath
	 * @throws \InvalidArgumentException Template not found in path: `$viewScriptFullPath`.
	 * @return string
	 */
	public function Render ($viewType, $relativePath) {
		$relativePath = $this->correctRelativePath(
			$viewType, $relativePath
		);
		// 1. try to find template in vendor package view dir (if vendor dispatching) or in default view dir
		$viewScriptFullPath = static::GetViewScriptFullPath(
			$this->GetTypedViewsDirFullPath($viewType, FALSE), 
			$relativePath
		);
		if (!file_exists($viewScriptFullPath)) {
			// 2. try to find template always in default view dir:
			$viewScriptFullPathDefault = static::GetViewScriptFullPath(
				$this->GetTypedViewsDirFullPath($viewType, TRUE), 
				$relativePath
			);
			if (file_exists($viewScriptFullPathDefault)) {
				$viewScriptFullPath = $viewScriptFullPathDefault;
			} else {
				throw new \InvalidArgumentException(
					"[".get_class($this)."] Template not found in path: `{$viewScriptFullPath}`."
				);
			}
		}
		return $this->RenderByFullPath($viewScriptFullPath);
	}

	/**
	 * @inheritDoc
	 * @internal
	 * @param  string $viewScriptFullPath 
	 * @return string
	 */
	public function RenderByFullPath ($viewScriptFullPath) {
		// add currently rendered full path
		$renderedFullPaths = & $this->__protected['renderedFullPaths'];
		$renderedFullPaths[] = $viewScriptFullPath;
		// get render mode
		list($renderMode) = $this->__protected['renderArgs'];
		$renderModeWithOb = ($renderMode & \MvcCore\IView::RENDER_WITH_OB_FROM_ACTION_TO_LAYOUT) != 0;
		// if render mode is default - start output buffering
		if ($renderModeWithOb)
			ob_start();
		// render the template with local variables from the store
		call_user_func(function ($viewPath, $controller, $helpers) {
			extract($helpers, EXTR_SKIP);
			unset($helpers);
			extract($this->__protected['store'], EXTR_SKIP);
			extract([
				'application'	=> $controller->GetApplication(),
				'request'		=> $controller->GetRequest(),
				'response'		=> $controller->GetResponse(),
				'environment'	=> $controller->GetRouter(),
			], EXTR_SKIP);
			include($viewPath);
		}, $viewScriptFullPath, $this->controller, $this->__protected['helpers']);
		// if render mode is default - get result from output buffer and return the result,
		// if render mode is continuous - result is sent to client already, so return empty string only.
		if ($renderModeWithOb) {
			$result = ob_get_clean();
			\array_pop($renderedFullPaths); // unset last
			return $result;
		} else {
			\array_pop($renderedFullPaths); // unset last
			return '';
		}
	}
	
	/**
	 * @inheritDoc
	 * @param  string $typedViewsDirFullPath Example: `/abs/doc/root/App/Views/{Layouts,Forms,Scripts}`.
	 * @param  string $scriptRelativePath    Example: `ctrl-name/action-name`.
	 * @return string
	 */
	public static function GetViewScriptFullPath ($typedViewsDirFullPath, $scriptRelativePath) {
		return implode('/', [
			$typedViewsDirFullPath,
			$scriptRelativePath . static::$extension
		]);
	}
	
	/**
	 * @inhertDocs
	 * @param  int  $viewType
	 * @param  bool $default
	 * @return string
	 */
	public function GetTypedViewsDirFullPath ($viewType, $default = FALSE) {
		$resultType = $default 
			? static::VIEW_TYPE_DEFAULT 
			: $viewType;

		$viewsDirsFullPaths = [];
		if (isset($this->__protected['viewsDirsFullPaths'])) {
			$viewsDirsFullPaths = & $this->__protected['viewsDirsFullPaths'];
			if (isset($viewsDirsFullPaths[$resultType]))
				return $viewsDirsFullPaths[$resultType];
		}
		$app = $this->controller->GetApplication();
		
		$defaultViewsDirFullPath = static::getViewPathByType($app, $viewType, TRUE);

		if ($this->controller->GetParentController() !== NULL) {
			// child controller dispatching
			$typedFullPath = static::GetExtViewsDirFullPath(
				$app, get_class($this->controller), $viewType, TRUE
			);
		} else {
			// main controller dispatching
			$typedFullPath = $app->GetVendorAppDispatch()
				? static::GetExtViewsDirFullPath(
					$app, get_class($this->controller), $viewType, FALSE
				)
				: $defaultViewsDirFullPath; // tady by se zavolalo nove ziskavani podle typu jako komplet cesta
		}

		$viewsDirsFullPaths[$viewType] = $typedFullPath;
		$viewsDirsFullPaths[static::VIEW_TYPE_DEFAULT] = $defaultViewsDirFullPath;
		$this->__protected['viewsDirsFullPaths'] = $viewsDirsFullPaths;

		return $viewsDirsFullPaths[$resultType];
	}

	/**
	 * @inheritDoc
	 * @param  \MvcCore\Application $app 
	 * @param  string               $ctrlClassFullName
	 * @param  int                  $viewType
	 * @param  bool                 $useReflection
	 * @return string
	 */
	public static function GetExtViewsDirFullPath (
		\MvcCore\IApplication $app, $ctrlClassFullName, $viewType, $useReflection = TRUE
	) {
		// compilled applications doesn't support dispatching in vendor directories
		$extensionRoot = NULL;
		if ($useReflection) {
			// child controller rendering
			$ctrlType = new \ReflectionClass($ctrlClassFullName);
			$ctrlFileFullPath = str_replace('\\', '/', $ctrlType->getFileName());
			$extensionRoot = mb_substr(
				$ctrlFileFullPath, 0, mb_strlen($ctrlFileFullPath) - (mb_strlen($ctrlClassFullName) + 5)
			);
		} else {
			// main controller rendering
			$extensionRoot = $app->GetPathAppRootVendor();
		}

		$pathViewsRel = static::getViewPathByType($app, $viewType, FALSE);

		if (mb_strpos($pathViewsRel, '~/') === 0) {
			$extViewsDirFullPath = $extensionRoot . mb_substr($pathViewsRel, 1);
		} else {
			$extViewsDirFullPath = $extensionRoot . '/' . ltrim($pathViewsRel, '/');
		}
		
		$toolClass = $app->GetToolClass();
		$extViewsDirFullPath = $toolClass::RealPathVirtual($extViewsDirFullPath);

		return $extViewsDirFullPath;
	}

	/**
	 * @inheritDoc
	 * @param  \MvcCore\View $view                  View object to get store from, to set up all it's variables to current view store.
	 * @param  bool          $overwriteExistingKeys If any property name already exist in view store, overwrite it by given value by default.
	 * @return \MvcCore\View
	 */
	public function SetUpStore (\MvcCore\IView $view, $overwriteExistingKeys = TRUE) {
		$currentStore = & $this->__protected['store'];
		$viewStore = & $view->__protected['store'];
		$currentStore['view'] = $view;
		if ($overwriteExistingKeys) {
			$this->__protected['store'] = array_merge($currentStore, $viewStore);
		} else {
			foreach ($viewStore as $key => $value)
				if (!array_key_exists($key, $currentStore))
					$currentStore[$key] = $value;
		}
		return $this;
	}
	
	/**
	 * @inheritDoc
	 * @param  array<string, mixed> $data                  View data store to add into view store.
	 * @param  bool                 $overwriteExistingKeys If any property name already exist in view store, overwrite it by given value by default.
	 * @return \MvcCore\View
	 */
	public function AddData (array $data, $overwriteExistingKeys = TRUE) {
		$currentStore = & $this->__protected['store'];
		if ($overwriteExistingKeys) {
			$this->__protected['store'] = array_merge($currentStore, $data);
		} else {
			foreach ($data as $key => $value)
				if (!array_key_exists($key, $currentStore))
					$currentStore[$key] = $value;
		}
		return $this;
	}
	
	/**
	 * @inheritDoc
	 * @return array<string, mixed>
	 */
	public function & GetData () {
		return $this->__protected['store'];
	}

	/**
	 * @inheritDoc
	 * @return string
	 */
	public function GetContent () {
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
			$viewScriptPath = $this->controller->GetViewScriptPath(
				$controllerOrActionNameDashed, $actionNameDashed
			);
			// render action view into string
			/** @var \MvcCore\View $actionView */
			$actionView = $this->view;
			$actionView
				->SetUpStore($this, TRUE)
				->SetUpRender($renderMode, $controllerOrActionNameDashed, $actionNameDashed);
			$actionView->RenderScript($viewScriptPath);
			$this->SetUpStore($actionView, TRUE);
			return '';
		}
	}

	/**
	 * @inheritDoc
	 * @param  string|NULL $content
	 * @return string
	 */
	public function Evaluate ($content) {
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

	/**
	 * Set up build in view instance helpers before rendering.
	 * @param  array<string,ViewHelper> $helpers 
	 * @return void
	 */
	protected function setUpRenderBuildInHelpers (& $helpers) {
		$router = $this->controller->GetRouter();
		$helpers += [
			'url' => function ($controllerActionOrRouteName = 'Index:Index', array $params = []) use (& $router) {
				/** @var \MvcCore\Router $router */
				return $router->Url($controllerActionOrRouteName, $params);
			},
			'assetUrl' => function ($path) use (& $router) {
				/** @var \MvcCore\Router $router */
				return $router->Url('Controller:Asset', ['path' => $path]);
			},
			'escape' => function ($str, $flags = ENT_QUOTES, $encoding = NULL, $double = FALSE, $jsTemplate = FALSE) {
				return $this->Escape($str, $flags, $encoding, $double, $jsTemplate);
			},
			'escapeHtml' => function ($str, $encoding = NULL, $double = FALSE) {
				return $this->EscapeHtml($str, $encoding, $double);
			},
			'escapeAttr' => function ($str, $flags = ENT_QUOTES, $encoding = NULL, $double = FALSE) {
				return $this->EscapeAttr($str, $flags, $encoding, $double);
			},
			'escapeXml' => function ($str, $encoding = NULL, $double = FALSE) {
				return $this->EscapeXml($str, $encoding, $double);
			},
			'escapeJs' => function ($obj, $flags = 0, $depth = 512) {
				return $this->EscapeJs($obj, $flags, $depth);
			},
			'escapeAttrJs' => function ($obj, $flags = 0, $depth = 512) {
				return $this->EscapeJs($obj, $flags, $depth);
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