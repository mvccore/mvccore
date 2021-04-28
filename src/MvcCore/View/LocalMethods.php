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
 */
trait LocalMethods {

	/**
	 * If relative path declared in view starts with `"./anything/else.phtml"`,
	 * then change relative path to correct `"./"` context and return full path.
	 * @param  string $typePath
	 * @param  string $relativePath
	 * @return string full path
	 */
	protected function correctRelativePath ($typePath, $relativePath) {
		$result = str_replace('\\', '/', $relativePath);
		// if relative path starts with dot:
		if (mb_substr($relativePath, 0, 1) === '.') {
			if (static::$viewScriptsFullPathBase === NULL)
				static::initViewScriptsFullPathBase();
			$typedViewDirFullPath = implode('/', [
				static::$viewScriptsFullPathBase, $typePath
			]);
			// get current view script full path:
			$renderedFullPaths = & $this->__protected['renderedFullPaths'];
			$lastRenderedFullPath = $renderedFullPaths[count($renderedFullPaths) - 1];
			// create `$renderedRelPath` by cutting directory with typed view scripts:
			if (mb_strpos($lastRenderedFullPath, $typedViewDirFullPath) === 0) {
				$renderedRelPath = mb_substr($lastRenderedFullPath, mb_strlen($typedViewDirFullPath));
			} else if (mb_strpos($lastRenderedFullPath, static::$viewScriptsFullPathBase) === 0) {
				$renderedRelPath = mb_substr($lastRenderedFullPath, mb_strlen(static::$viewScriptsFullPathBase));
			} else {
				$lastSlashPos = mb_strrpos($lastRenderedFullPath, '/');
				$renderedRelPath = $lastSlashPos !== FALSE
					? mb_substr($lastRenderedFullPath, 0, $lastSlashPos)
					: '';
			}
			// set how many dots is at `$relativePath` string start:
			$startingDotsCount = mb_substr($relativePath, 1, 1) === '.' ? 2 : 1;
			// cut so many slash steps from `$renderedRelPath` start, 
			// how many dots is at the `$relativePath` string start:
			$slashSteps = 0;
			while ($slashSteps++ < $startingDotsCount) {
				$renderedRelPathLastSlashPos = mb_strrpos($renderedRelPath, '/');
				if ($renderedRelPathLastSlashPos !== FALSE) 
					$renderedRelPath = mb_substr($renderedRelPath, 0, $renderedRelPathLastSlashPos);
			}
			// trim relative path for starting dots:
			$relativePath = mb_substr($relativePath, $startingDotsCount);
			// complete result from corected relative path and given path:
			$result = ltrim($renderedRelPath . $relativePath, '/');
		}
		return $result;
	}

	/**
	 * Init view scripts full class string for methods:
	 * - `\MvcCore\View::GetViewScriptFullPath();`
	 * - `\MvcCore\View::correctRelativePath();`
	 * @return void
	 */
	protected static function initViewScriptsFullPathBase () {
		$app = \MvcCore\Application::GetInstance();
		static::$viewScriptsFullPathBase = implode('/', [
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
	protected static function initHelpersNamespaces () {
		$app = \MvcCore\Application::GetInstance();
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
