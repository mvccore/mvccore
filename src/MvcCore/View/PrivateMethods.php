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

trait PrivateMethods
{
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
			$typedViewDirFullPath = implode('/', [
				self::$_viewScriptsFullPathBase, $typePath
			]);
			$renderedFullPaths = & $this->__protected['renderedFullPaths'];
			$lastRenderedFullPath = $renderedFullPaths[count($renderedFullPaths) - 1];
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
		self::$_viewScriptsFullPathBase = implode('/', [
			$app->GetRequest()->GetAppRoot(),
			$app->GetAppDir(),
			$app->GetViewsDir()
		]);
	}

	/**
	 * Static initialization to complete
	 * `static::$helpersNamespaces`
	 * by application configuration once.
	 * @return void
	 */
	private static function _initHelpersNamespaces () {
		$app = & \MvcCore\Application::GetInstance();
		static::$helpersNamespaces = [
			'\\MvcCore\\Ext\\Views\Helpers\\',
			// and '\App\Views\Helpers\' by default:
			'\\' . implode('\\', [
				$app->GetAppDir(),
				$app->GetViewsDir(),
				static::$helpersDir
			]) . '\\',
		];
	}
}
