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

trait DirectoryMethods {

	/**
	 * @inheritDocs
	 * @return string|NULL
	 */
	public function GetCurrentViewFullPath () {
		$result = NULL;
		$renderedFullPaths = & $this->__protected['renderedFullPaths'];
		$count = count($renderedFullPaths);
		if ($count > 0)
			$result = $renderedFullPaths[$count - 1];
		return $result;
	}

	/**
	 * @inheritDocs
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
	 * @inheritDocs
	 * @return string|NULL
	 */
	public function GetParentViewFullPath () {
		$result = NULL;
		$renderedFullPaths = & $this->__protected['renderedFullPaths'];
		$count = count($renderedFullPaths);
		if ($count > 1) {
			$result = $renderedFullPaths[$count - 2];
		} else {
			$controller = $this->controller;
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
				$relativePath = $this->correctRelativePath(static::$layoutsDir, $controller->GetLayout());
				return static::GetViewScriptFullPath(static::$layoutsDir, $relativePath);
			}
		}
		return $result;
	}

	/**
	 * @inheritDocs
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
}
