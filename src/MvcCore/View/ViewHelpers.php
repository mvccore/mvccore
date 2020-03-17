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

trait ViewHelpers
{
	/**
	 * Get views helpers directory placed by default
	 * inside `"/App/Views"` directory.
	 * Default value is `"Helpers"`, so scripts app path
	 * is `"/App/Views/Helpers"`.
	 * @return string
	 */
	public static function GetHelpersDir () {
		return static::$helpersDir;
	}

	/**
	 * Set views helpers directory placed by default
	 * inside `"/App/Views"` directory.
	 * Default value is `"Helpers"`, so scripts app path
	 * is `"/App/Views/Helpers"`.
	 * @param string $helpersDir
	 * @return string
	 */
	public static function SetHelpersDir ($helpersDir = 'Helpers') {
		return static::$helpersDir = $helpersDir;
	}

	/**
	 * Add view helpers classes namespace(s),
	 * Example: `\MvcCore\View::AddHelpersNamespaces('Any\Other\ViewHelpers\Place', '...');`.
	 * @param string $helperNamespaces,... View helper classes namespace(s).
	 * @return void
	 */
	public static function AddHelpersNamespaces ($helperNamespaces) {
		if (!static::$helpersNamespaces) self::initHelpersNamespaces();
		$args = func_get_args();
		if (count($args) === 1 && is_array($args[0])) $args = $args[0];
		foreach ($args as $arg)
			static::$helpersNamespaces[] = '\\' . trim($arg, '\\') . '\\';
	}

	/**
	 * Set view helpers classes namespace(s). This method replace all previously configured namespaces.
	 * If you want only to add namespace, use `\MvcCore\View::AddHelpersNamespaces();` instead.
	 * Example: `\MvcCore\View::SetHelpersClassNamespaces('Any\Other\ViewHelpers\Place', '...');`.
	 * @param string $helperNamespaces,... View helper classes namespace(s).
	 * @return void
	 */
	public static function SetHelpersNamespaces ($helperNamespaces) {
		static::$helpersNamespaces = [];
		$args = func_get_args();
		if (count($args) === 1 && is_array($args[0])) $args = $args[0];
		foreach ($args as $arg)
			static::$helpersNamespaces[] = '\\' . trim($arg, '\\') . '\\';
	}

	/**
	 * Try to call view helper.
	 * If view helper doesn't exist in global helpers store - create new helper instance.
	 * If helper already exists in global helpers store - do not create it again - use instance from the store.
	 * Then call it's public method named in the same way as helper and return result
	 * as it is, without any conversion. So then there could be called any other helper method if whole helper instance is returned.
	 * @param string $method View helper method name in pascal case.
	 * @param mixed $arguments View helper method arguments.
	 * @throws \InvalidArgumentException If view doesn't exist in configured namespaces.
	 * @return string|mixed View helper string result or any other view helper result type or view helper instance, always as `\MvcCore\Ext\Views\Helpers\AbstractHelper|\MvcCore\Ext\Views\Helpers\IHelper` instance.
	 */
	public function __call ($method, $arguments) {
		$result = '';
		$methodCamelCase = lcfirst($method);
		$instance = & $this->GetHelper($methodCamelCase);
		$isObject = is_object($instance);
		if ($instance instanceof \Closure || ($isObject && method_exists($instance, '__invoke'))) {
			$result = call_user_func_array($instance, $arguments);
		} else if ($isObject && method_exists($instance, $method)) {
			$result = call_user_func_array([$instance, $method], $arguments);
		} else {
			throw new \InvalidArgumentException(
				"[".get_class()."] View class instance has no method '{$method}', no view helper found."
			);
		}
		$this->__protected['helpers'][$methodCamelCase] = & $instance;
		return $result;
	}

	/**
	 * Try to get view helper.
	 * If view helper doesn't exist in global helpers store - create new helper instance.
	 * If helper already exists in global helpers store - do not create it again - use instance from the store.
	 * Example: `echo $this->GetHelper('facebook')->RenderSomeSpecialWidgetMethod();`
	 * @param string $helperNameCamelCase View helper method name in camel case.
	 * @throws \InvalidArgumentException If view doesn't exist in configured namespaces.
	 * @return mixed View helper instance, always as `\MvcCore\Ext\Views\Helpers\AbstractHelper|\MvcCore\Ext\Views\Helpers\IHelper` instance.
	 */
	public function & GetHelper ($helperNameCamelCase) {
		$setUpView = FALSE;
		$needsClosureFn = FALSE;
		$instance = NULL;
		$helpers = & $this->__protected['helpers'];
		$helperNamePascalCase = ucfirst($helperNameCamelCase);
		if (isset($helpers[$helperNameCamelCase])) {
			$instance = & $helpers[$helperNameCamelCase];
		} else if (isset(self::$_globalHelpers[$helperNamePascalCase])) {
			$globalHelpersRecord = & self::$_globalHelpers[$helperNamePascalCase];
			$instance = & $globalHelpersRecord[0];
			//$result = & $instance;
			$setUpView = $globalHelpersRecord[1];
			$needsClosureFn = $globalHelpersRecord[2];
		} else {
			$helperFound = FALSE;
			$toolClass = self::$_toolClass ?: self::$_toolClass = \MvcCore\Application::GetInstance()->GetToolClass();
			$helpersInterface = self::HELPERS_INTERFACE_CLASS_NAME;
			if (!static::$helpersNamespaces)
				self::initHelpersNamespaces();
			foreach (static::$helpersNamespaces as $helperClassBase) {
				$className = $helperClassBase . $helperNamePascalCase . 'Helper';
				if (!class_exists($className))
					continue;
				$helperFound = TRUE;
				if ($toolClass::CheckClassInterface($className, $helpersInterface, TRUE, FALSE)) {
					$setUpView = TRUE;
					$instance = $className::GetInstance();
				} else {
					$instance = new $className();
				}
				$needsClosureFn = (
					!($instance instanceof \Closure) &&
					!method_exists($className, '__invoke')
				);
				self::$_globalHelpers[$helperNamePascalCase] = [& $instance, $setUpView, $needsClosureFn];
				break;
			}
			if (!$helperFound)
				throw new \InvalidArgumentException(
					"[".get_class()."] View helper method '{$helperNamePascalCase}' is not"
					." possible to handle by any configured view helper (View"
					." helper namespaces: '".implode("', '", static::$helpersNamespaces)."')."
				);
		}
		if ($setUpView)
			$instance->SetView($this);
		if ($needsClosureFn) {
			$result = function () use (& $instance, $helperNamePascalCase) {
				return call_user_func_array([$instance, $helperNamePascalCase], func_get_args());
			};
			$helpers[$helperNameCamelCase] = & $result;
		} else {
			$helpers[$helperNameCamelCase] = & $instance;
			$result = & $instance;
		}
		return $result;
	}

	/**
	 * Set view helper for current template or for all templates globally by default.
	 * If view helper already exist in global helpers store - it's overwritten.
	 * @param string $helperNameCamelCase View helper method name in camel case.
	 * @param mixed $instance View helper instance, always as `\MvcCore\Ext\Views\Helpers\AbstractHelper|\MvcCore\Ext\Views\Helpers\IHelper` instance or `\Closure`.
	 * @param bool $forAllTemplates register this helper instance for all rendered views in the future.
	 * @return \MvcCore\View|\MvcCore\IView
	 */
	public function SetHelper ($helperNameCamelCase, $instance, $forAllTemplates = TRUE) {
		/** @var $this \MvcCore\View */
		$implementsIHelper = FALSE;
		if (self::$_toolClass === NULL)
			self::$_toolClass = \MvcCore\Application::GetInstance()->GetToolClass();
		$toolClass = self::$_toolClass;
		$helpersInterface = self::HELPERS_INTERFACE_CLASS_NAME;
		$className = get_class($instance);
		$implementsIHelper = $toolClass::CheckClassInterface($className, $helpersInterface, FALSE, FALSE);
		$helperNamePascalCase = ucfirst($helperNameCamelCase);
		$needsClosureFn = (
			!($instance instanceof \Closure) &&
			!method_exists($className, '__invoke')
		);
		if ($forAllTemplates)
			self::$_globalHelpers[ucfirst($helperNameCamelCase)] = [
				& $instance, $implementsIHelper, $needsClosureFn
			];
		$helpers = & $this->__protected['helpers'];
		if ($needsClosureFn) {
			$helpers[$helperNameCamelCase] = function () use (& $instance, $helperNamePascalCase) {
				return call_user_func_array([$instance, $helperNamePascalCase], func_get_args());
			};
		} else {
			$helpers[$helperNameCamelCase] = & $instance;
		}
		return $this;
	}
}
