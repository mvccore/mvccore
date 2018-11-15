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

namespace MvcCore\Application;

/**
 * Trait as partial class for `\MvcCore\Application`:
 * - Helper methods for normal requests and error requests dispatching.
 * - Helper methods for core classes configuration.
 */
trait Helpers
{
	/***********************************************************************************
	 *					 `\MvcCore\Application` - Helper Methods					 *
	 ***********************************************************************************/

	/**
	 * Check if default application controller (`\App\Controllers\Index` by default) has specific action.
	 * If default controller has specific action - return default controller full name, else empty string.
	 * @param string $actionName
	 * @return string
	 */
	public function GetDefaultControllerIfHasAction ($actionName) {
		$defaultControllerName = $this->CompleteControllerName($this->defaultControllerName);
		if (class_exists($defaultControllerName) && method_exists($defaultControllerName, $actionName . 'Action')) {
			return $defaultControllerName;
		}
		return '';
	}

	/**
	 * Complete standard MvcCore application controller full name in form:
	 * `\App\Controllers\<$controllerNamePascalCase>`.
	 * @param string $controllerNamePascalCase
	 * @return string
	 */
	public function CompleteControllerName ($controllerNamePascalCase) {
		if (substr($controllerNamePascalCase, 0, 2) == '//') 
			return '\\' . ltrim($controllerNamePascalCase, '/');
		return '\\' . implode('\\', [
			$this->appDir,
			$this->controllersDir,
			ltrim($controllerNamePascalCase, '\\')
		]);
	}

	/**
	 * Return `TRUE` if current request is default controller error action dispatching process.
	 * @return bool
	 */
	public function IsErrorDispatched () {
		$toolClass = $this->toolClass;
		$defaultCtrlName = $toolClass::GetDashedFromPascalCase($this->defaultControllerName);
		$errorActionName = $toolClass::GetDashedFromPascalCase($this->defaultControllerErrorActionName);
		return $this->request->GetControllerName() == $defaultCtrlName &&
			$this->request->GetActionName() == $errorActionName;
	}

	/**
	 * Return `TRUE` if current request is default controller not found error action dispatching process.
	 * @return bool
	 */
	public function IsNotFoundDispatched () {
		$toolClass = $this->toolClass;
		$defaultCtrlName = $toolClass::GetDashedFromPascalCase($this->defaultControllerName);
		$errorActionName = $toolClass::GetDashedFromPascalCase($this->defaultControllerNotFoundActionName);
		return $this->request->GetControllerName() == $defaultCtrlName &&
			$this->request->GetActionName() == $errorActionName;
	}

	/**
	 * Set core class name only if given class string implements
	 * given core interface, else thrown an exception.
	 * @param string $newCoreClassName
	 * @param string $coreClassVar
	 * @param string $coreClassInterface
	 * @throws \Exception
	 * @return \MvcCore\Application
	 */
	protected function & setCoreClass ($newCoreClassName, $coreClassVar, $coreClassInterface) {
		if (call_user_func(
			[$this->toolClass, 'CheckClassInterface'], 
			$newCoreClassName, $coreClassInterface, TRUE, TRUE // check static methods and throw an exception if false
		)) $this->$coreClassVar = $newCoreClassName;
		return $this;
	}

	/**
	 * Set pre-route, pre-dispatch or post-dispatch handler under specific priority index.
	 * @param array $handlers Application handlers collection reference.
	 * @param callable $handler
	 * @param int|NULL $priorityIndex
	 * @return \MvcCore\Application
	 */
	protected function & setHandler (array & $handlers, callable $handler, $priorityIndex = NULL) {
		// there is possible to call any `callable` as closure function in variable
		// except forms like `'ClassName::methodName'` and `['childClassName', 'parent::methodName']`
		// and `[$childInstance, 'parent::methodName']`.
		$closureCalling = (
			(is_string($handler) && strpos($handler, '::') !== FALSE) ||
			(is_array($handler) && strpos($handler[1], '::') !== FALSE)
		) ? FALSE : TRUE;
		if ($priorityIndex === NULL) {
			$handlers[] = [$closureCalling, $handler];
		} else {
			if (isset($handlers[$priorityIndex])) {
				array_splice($handlers, $priorityIndex, 0, [$closureCalling, $handler]);
			} else {
				$handlers[$priorityIndex] = [$closureCalling, $handler];
			}
		}
		return $this;
	}
}
