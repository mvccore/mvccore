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

use MvcCore\Tool\Helpers;

/**
 * @mixin \MvcCore\View
 * @phpstan-type ViewHelper \MvcCore\Ext\Views\Helpers\AbstractHelper|\MvcCore\Ext\Views\Helpers\IHelper|\Closure|mixed
 * @phpstan-type ViewHelperCacheRecord array{0:ViewHelper,1:bool,2:bool}
 */
trait ViewHelpers {

	/**
	 * @inheritDoc
	 * @return string
	 */
	public static function GetHelpersDir () {
		return static::$helpersDir;
	}

	/**
	 * @inheritDoc
	 * @param  string $helpersDir
	 * @return string
	 */
	public static function SetHelpersDir ($helpersDir = 'Helpers') {
		return static::$helpersDir = $helpersDir;
	}

	/**
	 * @inheritDoc
	 * @param  string $helperNamespaces,... View helper classes namespace(s).
	 * @return void
	 */
	public static function PrependHelpersNamespaces ($helperNamespaces) {
		if (!static::$helpersNamespaces) self::initHelpersNamespaces();
		$args = func_get_args();
		if (count($args) === 1 && is_array($args[0])) $args = $args[0];
		$helpersNamespaces = array_map(function ($arg) { return '\\' . trim($arg, '\\') . '\\'; }, $args);
		static::$helpersNamespaces = array_merge($helpersNamespaces, static::$helpersNamespaces);
	}
	
	/**
	 * @inheritDoc
	 * @param  string $helperNamespaces,... View helper classes namespace(s).
	 * @return void
	 */
	public static function AppendHelpersNamespaces ($helperNamespaces) {
		if (!static::$helpersNamespaces) self::initHelpersNamespaces();
		$args = func_get_args();
		if (count($args) === 1 && is_array($args[0])) $args = $args[0];
		foreach ($args as $arg)
			static::$helpersNamespaces[] = '\\' . trim($arg, '\\') . '\\';
	}

	/**
	 * @inheritDoc
	 * @param  string $helperNamespaces,... View helper classes namespace(s).
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
	 * @inheritDoc
	 * @param  string $method            View helper method name in pascal case.
	 * @param  mixed  $arguments         View helper method arguments.
	 * @throws \InvalidArgumentException If view doesn't exist in configured namespaces.
	 * @return string|mixed              View helper string result or any other view helper result type or view helper instance, always as `\MvcCore\Ext\Views\Helpers\AbstractHelper|\MvcCore\Ext\Views\Helpers\IHelper` instance.
	 */
	public function __call ($method, $arguments) {
		$result = '';
		$methodCamelCase = lcfirst($method);
		$instance = & $this->GetHelper($methodCamelCase, TRUE);
		$isObject = is_object($instance);
		if ($instance instanceof \Closure || ($isObject && method_exists($instance, '__invoke'))) {
			$result = call_user_func_array($instance, $arguments);
		} else if ($isObject && method_exists($instance, $method)) {
			$result = call_user_func_array([$instance, $method], $arguments);
		} else {
			throw new \InvalidArgumentException(
				"[".get_class($this)."] View class instance has no method '{$method}', no view helper found."
			);
		}
		$this->__protected['helpers'][$methodCamelCase] = & $instance;
		return $result;
	}

	/**
	 * @inheritDoc
	 * @param  string $helperNameCamelCase View helper method name in camel case.
	 * @param  bool   $asClosure           Get View helper prepared as closure function, `FALSE` by default.
	 * @throws \InvalidArgumentException   If view doesn't exist in configured namespaces.
	 * @return ViewHelper View helper instance.
	 */
	public function & GetHelper ($helperNameCamelCase, $asClosure = FALSE) {
		$setUpView = FALSE;
		$needsClosureFn = FALSE;
		$instance = NULL;
		$helpers = & $this->__protected['helpers'];
		$helperNamePascalCase = ucfirst($helperNameCamelCase);
		if (isset($helpers[$helperNameCamelCase]) && $asClosure) {
			$instance = & $helpers[$helperNameCamelCase];
		} else {
			if (!isset(self::$globalHelpers[$helperNamePascalCase])) 
				$this->setUpHelper($helperNamePascalCase);
			/** @var ViewHelperCacheRecord $globalHelpersRecord */
			$globalHelpersRecord = & self::$globalHelpers[$helperNamePascalCase];
			$instance = & $globalHelpersRecord[0];
			$setUpView = $globalHelpersRecord[1];
			$needsClosureFn = $globalHelpersRecord[2];
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
		if ($asClosure) {
			return $result;
		} else {
			return $instance;
		}
	}

	/**
	 * @inheritDoc
	 * @param  string     $helperNameCamelCase
	 * View helper method name in camel case.
	 * @param  ViewHelper $instance
	 * View helper instance.
	 * @param  bool       $forAllTemplates
	 * Register this helper instance for all rendered views in the future.
	 * @return \MvcCore\View
	 */
	public function SetHelper ($helperNameCamelCase, $instance, $forAllTemplates = TRUE) {
		$implementsIHelper = FALSE;
		$toolClass = self::$toolClass ?: self::$toolClass = \MvcCore\Application::GetInstance()->GetToolClass();
		$helpersInterface = self::HELPERS_INTERFACE_CLASS_NAME;
		$className = get_class($instance);
		$implementsIHelper = $toolClass::CheckClassInterface($className, $helpersInterface, FALSE, FALSE);
		$helperNamePascalCase = ucfirst($helperNameCamelCase);
		$needsClosureFn = (
			!($instance instanceof \Closure) &&
			!method_exists($className, '__invoke')
		);
		if ($forAllTemplates)
			self::$globalHelpers[ucfirst($helperNameCamelCase)] = [
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

	/**
	 * Set up view hwlper in global static helpers array.
	 * @param  string $helperNamePascalCase 
	 * @throws \InvalidArgumentException 
	 * @return void
	 */
	protected function setUpHelper ($helperNamePascalCase) {
		$setUpView = FALSE;
		$helperFound = FALSE;
		$toolClass = self::$toolClass ?: self::$toolClass = \MvcCore\Application::GetInstance()->GetToolClass();
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
			self::$globalHelpers[$helperNamePascalCase] = [& $instance, $setUpView, $needsClosureFn];
			break;
		}
		if (!$helperFound) {
			if (method_exists($this, $helperNamePascalCase)) {
				$helperFound = TRUE;
				$instance = function () use ($helperNamePascalCase) {
					return call_user_func_array([$this, $helperNamePascalCase], func_get_args());
				};
				$setUpView = FALSE;
				$needsClosureFn = FALSE;
				self::$globalHelpers[$helperNamePascalCase] = [& $instance, $setUpView, $needsClosureFn];
			} else if (method_exists($this->controller, $helperNamePascalCase)) {
				$helperFound = TRUE;
				$instance = function () use ($helperNamePascalCase) {
					return call_user_func_array([$this->controller, $helperNamePascalCase], func_get_args());
				};
				$setUpView = FALSE;
				$needsClosureFn = FALSE;
				self::$globalHelpers[$helperNamePascalCase] = [& $instance, $setUpView, $needsClosureFn];
			}
		}
		if (!$helperFound)
			throw new \InvalidArgumentException(
				"[".get_class($this)."] View helper method '{$helperNamePascalCase}' is not"
				." possible to handle by any configured view helper (View"
				." helper namespaces: '".implode("', '", static::$helpersNamespaces)."')."
			);
	}
}
