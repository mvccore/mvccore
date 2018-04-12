<?php

/**
 * MvcCore
 *
 * This source file is subject to the BSD 3 License
 * For the full copyright and license information, please view
 * the LICENSE.md file that are distributed with this source code.
 *
 * @copyright	Copyright (c) 2016 Tom Flídr (https://github.com/mvccore/mvccore)
 * @license		https://mvccore.github.io/docs/mvccore/4.0.0/LICENCE.md
 */

namespace MvcCore\Application;

//include_once(__DIR__.'/../Tools.php');

/**
 * Trait as partial class for `\MvcCore\Application`:
 * - Helper methods for normal requests and error requests dispatching.
 * - Helper methods for core classes configuration.
 */
trait Helpers
{
	/***********************************************************************************
	 *                     `\MvcCore\Application` - Helper Methods                     *
	 ***********************************************************************************/

	/**
	 * Check if default application controller (`\App\Controllers\Index` by default) has specific action.
	 * If default controller has specific action - return default controller full name, else empty string.
	 * @param string $actionName
	 * @return string
	 */
	public function GetDefaultControllerIfHasAction ($actionName) {
		$defaultControllerName = $this->CompleteControllerName($this->defaultControllerName);
		if (class_exists($defaultControllerName) && method_exists($defaultControllerName, $actionName.'Action')) {
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
		$firstChar = substr($controllerNamePascalCase, 0, 1);
		if ($firstChar == '\\') return $controllerNamePascalCase;
		return '\\' . implode('\\', array(
			$this->appDir,
			$this->controllersDir,
			$controllerNamePascalCase
		));
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
	protected function setCoreClass ($newCoreClassName, $coreClassVar, $coreClassInterface) {
		if (call_user_func(array($this->toolClass, 'CheckClassInterface'), $newCoreClassName, $coreClassInterface))
			$this->$coreClassVar = $newCoreClassName;
		return $this;
	}
}
