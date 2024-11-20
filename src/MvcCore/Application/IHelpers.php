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

interface IHelpers {

	/**
	 * Check if default application controller (`\App\Controllers\Index` by default) has specific action.
	 * If default controller has specific action - return default controller full name, else empty string.
	 * @param  string $actionName
	 * @return string
	 */
	public function GetDefaultControllerIfHasAction ($actionName);

	/**
	 * Complete standard MvcCore application controller full name in form:
	 * `\App\Controllers\<$controllerNamePascalCase>`.
	 * @param  string $controllerNamePascalCase
	 * @return string
	 */
	public function CompleteControllerName ($controllerNamePascalCase);

	/**
	 * Return `TRUE` if current request is default controller error action dispatching process.
	 * @return bool
	 */
	public function IsErrorDispatched ();

	/**
	 * Return `TRUE` if current request is default controller not found error action dispatching process.
	 * @return bool
	 */
	public function IsNotFoundDispatched ();

	/**
	 * Get `TRUE` if main application controller
	 * is from any composer vendor package.
	 * Compilled applications doesn't support 
	 * dispatching in vendor packages.
	 * @throws \Exception
	 * @return bool
	 */
	public function GetVendorAppDispatch ();

	/**
	 * Validate CSRF protection by http(s) cookie and session secret value.
	 * If CSRF protection is not enabled in cookie mode, return `NULL`.
	 * If protection validation is enabled and validated successfully,
	 * return `TRUE`, if validation fails, return `FALSE`.
	 * @inheritDoc
	 * @return bool|NULL
	 */
	public function ValidateCsrfProtection ();
}
