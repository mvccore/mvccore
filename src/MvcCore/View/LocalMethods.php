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
	 * @param  int    $typePath
	 * @param  string $relativePath
	 * @return string full path
	 */
	protected function correctRelativePath ($typePath, $relativePath) {
		$result = str_replace('\\', '/', $relativePath);
		// if relative path starts with dot:
		if (mb_substr($relativePath, 0, 1) === '.') {
			$typedViewDirFullPath = $this->GetTypedViewsDirFullPath($typePath, FALSE);
			// get current view script full path:
			$renderedFullPaths = & $this->__protected['renderedFullPaths'];
			$lastRenderedFullPath = $renderedFullPaths[count($renderedFullPaths) - 1];
			// create `$renderedRelPath` by cutting directory with typed view scripts:
			if (mb_strpos($lastRenderedFullPath, $typedViewDirFullPath) === 0) {
				// get last rendered relative path from typed views dir full path
				$renderedRelPath = mb_substr($lastRenderedFullPath, mb_strlen($typedViewDirFullPath));
			} else {
				$defaultViewDirFullPath = $this->GetTypedViewsDirFullPath($typePath, TRUE);
				if (mb_strpos($lastRenderedFullPath, $defaultViewDirFullPath) === 0) {
					// get last rendered relative path from default views dir full path
					$renderedRelPath = mb_substr($lastRenderedFullPath, mb_strlen($defaultViewDirFullPath));
				} else {
					// get last rendered relative path from beginning to last slash
					// TODO: Is this case possible? How?
					$lastSlashPos = mb_strrpos($lastRenderedFullPath, '/');
					$renderedRelPath = $lastSlashPos !== FALSE
						? mb_substr($lastRenderedFullPath, $lastSlashPos + 1)
						: '';
				}
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
	 * Static initialization to complete
	 * `static::$helpersNamespaces`
	 * by application configuration once.
	 * @return void
	 */
	protected static function initHelpersNamespaces () {
		static::$helpersNamespaces = [];
		$app = \MvcCore\Application::GetInstance();
		$appRoot = $app->GetPathAppRoot();
		$helpersDir = $app->GetPathViewHelpers(TRUE);
		if (mb_strpos($helpersDir, $appRoot) === 0) {
			$viewHelpersPath = mb_substr($helpersDir, mb_strlen($appRoot));
			$appViewHelpersNamespaceBase = str_replace('/', '\\', $viewHelpersPath) . '\\';
			static::$helpersNamespaces[] = $appViewHelpersNamespaceBase;
		}
		static::$helpersNamespaces[] = '\\MvcCore\\Ext\\Views\Helpers\\';
	}
	
	/**
	 * Get application relative or absolute path by view type.
	 * @param  \MvcCore\Application $app
	 * @param  int                  $viewType 
	 * @param  bool                 $absolute
	 * @return string
	 */
	protected static function getViewPathByType (
		\MvcCore\IApplication $app, $viewType, $absolute
	) {
		return $app->GetPath(self::$viewTypes2AppPaths[$viewType], $absolute, FALSE);
	}
	
}
