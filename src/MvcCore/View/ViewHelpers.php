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
		if (!static::$helpersNamespaces) self::_initHelpersNamespaces();
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
		$instance = & $this->GetHelper($method);
		if (method_exists($instance, $method)) {
			$result = call_user_func_array([$instance, $method], $arguments);
		} else {
			$selfClass = version_compare(PHP_VERSION, '5.5', '>') ? self::class : __CLASS__;
			throw new \InvalidArgumentException(
				"[".$selfClass."] View class instance has no method '$method', no view helper found."
			);
		}
		return $result;
	}

	/**
	 * Try to get view helper.
	 * If view helper doesn't exist in global helpers store - create new helper instance.
	 * If helper already exists in global helpers store - do not create it again - use instance from the store.
	 * Example: `echo $this->GetHelper('Facebook')->RenderSomeSpecialWidgetMethod();`
	 * @param string $helperName View helper method name in pascal case.
	 * @throws \InvalidArgumentException If view doesn't exist in configured namespaces.
	 * @return mixed View helper instance, always as `\MvcCore\Ext\Views\Helpers\AbstractHelper|\MvcCore\Ext\Views\Helpers\IHelper` instance.
	 */
	public function & GetHelper ($helperName) {
		$setUpViewAgain = FALSE;
		$implementsIHelper = FALSE;
		$instance = NULL;
		$helpers = & $this->__protected['helpers'];
		if (isset($helpers[$helperName])) {
			$instance = & $helpers[$helperName];
		} else if (isset(self::$_globalHelpers[$helperName])) {
			$globalHelpersRecord = & self::$_globalHelpers[$helperName];
			$instance = & $globalHelpersRecord[0];
			$implementsIHelper = $globalHelpersRecord[1];
			$setUpViewAgain = TRUE;
		} else {
			$helperFound = FALSE;
			if (self::$_toolClass === NULL)
				self::$_toolClass = \MvcCore\Application::GetInstance()->GetToolClass();
			$toolClass = self::$_toolClass;
			$helpersInterface = self::HELPERS_INTERFACE_CLASS_NAME;
			if (!static::$helpersNamespaces) self::_initHelpersNamespaces();
			foreach (static::$helpersNamespaces as $helperClassBase) {
				$className = $helperClassBase . ucfirst($helperName) . 'Helper';
				if (class_exists($className)) {
					$helperFound = TRUE;
					$setUpViewAgain = TRUE;
					if ($toolClass::CheckClassInterface($className, $helpersInterface, TRUE, FALSE)) {
						$implementsIHelper = TRUE;
						$instance = & $className::GetInstance();
					} else {
						$instance = new $className();
					}
					self::$_globalHelpers[$helperName] = [$instance, $implementsIHelper];
					break;
				}
			}
			if (!$helperFound) {
				$selfClass = version_compare(PHP_VERSION, '5.5', '>') ? self::class : __CLASS__;
				throw new \InvalidArgumentException(
					"[".$selfClass."] View helper method '$helperName' is not"
					." possible to handle by any configured view helper (View"
					." helper namespaces: '".implode("', '", static::$helpersNamespaces)."')."
				);
			}
		}
		if ($setUpViewAgain) {
			if ($implementsIHelper) $instance->SetView($this);
			$helpers[$helperName] = & $instance;
		}
		return $instance;
	}

	/**
	 * Set view helper for current template or for all templates globally by default.
	 * If view helper already exist in global helpers store - it's overwritten.
	 * @param string $helperName View helper method name in pascal case.
	 * @param mixed $instance View helper instance, always as `\MvcCore\Ext\Views\Helpers\AbstractHelper|\MvcCore\Ext\Views\Helpers\IHelper` instance.
	 * @param bool $forAllTemplates register this helper instance for all rendered views in the future.
	 * @return \MvcCore\View|\MvcCore\IView
	 */
	public function & SetHelper ($helperName, & $instance, $forAllTemplates = TRUE) {
		/** @var $this \MvcCore\View */
		$implementsIHelper = FALSE;
		if ($forAllTemplates) {
			if (self::$_toolClass === NULL)
				self::$_toolClass = \MvcCore\Application::GetInstance()->GetToolClass();
			$toolClass = self::$_toolClass;
			$helpersInterface = self::HELPERS_INTERFACE_CLASS_NAME;
			$className = get_class($instance);
			$implementsIHelper = $toolClass::CheckClassInterface($className, $helpersInterface, FALSE, FALSE);
			self::$_globalHelpers[$helperName] = [& $instance, $implementsIHelper];
		}
		$this->__protected['helpers'][$helperName] = & $instance;
		if ($implementsIHelper) $instance->SetView($this);
		return $this;
	}
}
