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

namespace MvcCore\Application;

/**
 * Trait as partial class for `\MvcCore\Application`:
 * - Helper methods for normal requests and error requests dispatching.
 * - Helper methods for core classes configuration.
 * @mixin \MvcCore\Application
 */
trait Helpers {

	/***********************************************************************************
	 *                     `\MvcCore\Application` - Helper Methods                     *
	 ***********************************************************************************/

	/**
	 * @inheritDoc
	 * @param  string $actionName
	 * @return string
	 */
	public function GetDefaultControllerIfHasAction ($actionName) {
		$defaultControllerName = $this->CompleteControllerName($this->defaultControllerName);
		if (
			class_exists($defaultControllerName) && 
			method_exists($defaultControllerName, $actionName . 'Action')
		) {
			return $defaultControllerName;
		}
		return '';
	}

	/**
	 * @inheritDoc
	 * @param  string $controllerNamePascalCase
	 * @return string
	 */
	public function CompleteControllerName ($controllerNamePascalCase) {
		if (mb_strpos($controllerNamePascalCase, '//') === 0) 
			return '\\' . ltrim($controllerNamePascalCase, '/');
		return '\\' . implode('\\', [
			$this->appDir,
			$this->controllersDir,
			ltrim($controllerNamePascalCase, '\\')
		]);
	}

	/**
	 * @inheritDoc
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
	 * @inheritDoc
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
	 * @inheritDoc
	 * @throws \Exception
	 * @return bool
	 */
	public function GetVendorAppDispatch () {
		if ($this->vendorAppDispatch !== NULL)
			return $this->vendorAppDispatch;
		$this->initVendorProps();
		return $this->vendorAppDispatch;
	}

	/**
	 * @inheritDoc
	 * @throws \Exception
	 * @return string|NULL
	 */
	public function GetVendorAppRoot () {
		if ($this->vendorAppDispatch !== NULL)
			return $this->vendorAppRoot;
		$this->initVendorProps();
		return $this->vendorAppRoot;
	}

	/**
	 * @inheritDoc
	 * @return bool|NULL
	 */
	public function ValidateCsrfProtection () {
		if (($this->csrfProtection & \MvcCore\IApplication::CSRF_PROTECTION_COOKIE) == 0) {
			return NULL;
		} else {
			$sessionClass = $this->sessionClass;
			$sessionNamespace = $sessionClass::GetCsrfNamespace();
			$res = $this->GetResponse();
			$csrfCookie = $this->GetRequest()->GetCookie($res::GetCsrfProtectionCookieName());
			if ($sessionNamespace->secret === $csrfCookie) {
				return TRUE;
			} else {
				$this->ProcessCustomHandlers($this->csrfErrorHandlers);
				return FALSE;
			}
		}
	}

	/**
	 * 
	 * @throws \Exception
	 * @return void
	 */
	protected function initVendorProps () {
		if ($this->controller === NULL) throw new \Exception(
			"[".__CLASS__."] There was not possible to detect vendor app"
			." dispatching, because controller still doesn't exists."
		);
		if ($this->GetCompiled()) {
			$this->vendorAppDispatch = FALSE;
			return;
		}
		$ctrlClassFullName = get_class($this->controller);
		$ctrlType = new \ReflectionClass($ctrlClassFullName);
		$ctrlFileFullPath = str_replace('\\', '/', $ctrlType->getFileName());
		$this->vendorAppRoot = mb_substr(
			$ctrlFileFullPath, 0, mb_strlen($ctrlFileFullPath) - (mb_strlen($ctrlClassFullName) + 5)
		);
		$appRoot = $this->GetRequest()->GetAppRoot();
		$this->vendorAppDispatch = $appRoot !== $this->vendorAppRoot;
		if (!$this->vendorAppDispatch) 
			$this->vendorAppRoot = NULL;
	}


	/**
	 * Set core class name only if given class string implements
	 * given core interface, else thrown an exception.
	 * @param  string $newCoreClassName
	 * @param  string $coreClassVar
	 * @param  string $coreClassInterface
	 * @throws \Exception
	 * @return \MvcCore\Application
	 */
	protected function setCoreClass ($newCoreClassName, $coreClassVar, $coreClassInterface) {
		if (call_user_func(
			[$this->toolClass, 'CheckClassInterface'], 
			$newCoreClassName, $coreClassInterface, TRUE, TRUE // check static methods and throw an exception if false
		)) $this->$coreClassVar = $newCoreClassName;
		return $this;
	}

	/**
	 * Set pre-route, pre-dispatch or post-dispatch handler under specific priority index.
	 * @param  array    $handlers      Application handlers collection reference.
	 * @param  callable $handler
	 * @param  int|NULL $priorityIndex
	 * @return \MvcCore\Application
	 */
	protected function setHandler (array & $handlers, callable $handler, $priorityIndex = NULL) {
		// there is possible to call any `callable` as closure function in variable
		// except forms like `'ClassName::methodName'` and `['childClassName', 'parent::methodName']`
		// and `[$childInstance, 'parent::methodName']`.
		$closureCalling = (
			$handler instanceof \Closure || !(
			(is_string($handler) && strpos($handler, '::') !== FALSE) ||
			(is_array($handler) && strpos($handler[1], '::') !== FALSE)
		));
		
		if ($priorityIndex !== NULL) {
			if (isset($handlers[$priorityIndex])) {
				$handlers[$priorityIndex][] = [$closureCalling, $handler];
			} else {
				$handlers[$priorityIndex] = [[$closureCalling, $handler]];
			}
		} else {
			// check if there could be an array overflow
			ksort($handlers);
			if (PHP_VERSION_ID >= 70300) {
				$lastHandlerKey = array_key_last($handlers);
			} else {
				$handlersKeys = array_keys($handlers);
				$handlersKeysCount = count($handlersKeys);
				$lastHandlerKey = ($handlersKeysCount > 0)
					? $handlersKeys[count($handlersKeys) - 1]
					: NULL;
			}
			if ($lastHandlerKey === PHP_INT_MAX) {
				$handlers[PHP_INT_MAX][] = [$closureCalling, $handler];
			} else {
				$handlers[] = [[$closureCalling, $handler]];
			}
		}
		return $this;
	}
}