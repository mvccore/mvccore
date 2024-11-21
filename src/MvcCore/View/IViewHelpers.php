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
 * @phpstan-type ViewHelper \MvcCore\Ext\Views\Helpers\AbstractHelper|\MvcCore\Ext\Views\Helpers\IHelper|\Closure|mixed
 * @phpstan-type ViewHelperCacheRecord array{0:ViewHelper,1:bool,2:bool}
 */
interface IViewHelpers {
	
	/**
	 * Prepend view helpers classes namespace(s),
	 * Example: `\MvcCore\View::PrependHelpersNamespaces('Any\Other\ViewHelpers\Place', '...');`.
	 * @param  string $helperNamespaces,... View helper classes namespace(s).
	 * @return void
	 */
	public static function PrependHelpersNamespaces ($helperNamespaces);

	/**
	 * Append view helpers classes namespace(s),
	 * Example: `\MvcCore\View::AppendHelpersNamespaces('Any\Other\ViewHelpers\Place', '...');`.
	 * @param  string $helperNamespaces,... View helper classes namespace(s).
	 * @return void
	 */
	public static function AppendHelpersNamespaces ($helperNamespaces);

	/**
	 * Set view helpers classes namespace(s). This method replace all previously configured namespaces.
	 * If you want only to add namespace, use `\MvcCore\View::AppendHelpersNamespaces();` instead.
	 * Example: `\MvcCore\View::SetHelpersClassNamespaces('Any\Other\ViewHelpers\Place', '...');`.
	 * @param  string $helperNamespaces,... View helper classes namespace(s).
	 * @return void
	 */
	public static function SetHelpersNamespaces ($helperNamespaces);
	
	/**
	 * Try to get view helper.
	 * If view helper doesn't exist in global helpers store - create new helper instance.
	 * If helper already exists in global helpers store - do not create it again - use instance from the store.
	 * Example: `echo $this->GetHelper('facebook')->RenderSomeSpecialWidgetMethod();`
	 * @param  string $helperNameCamelCase View helper method name in camel case.
	 * @param  bool   $asClosure           Get View helper prepared as closure function, `FALSE` by default.
	 * @throws \InvalidArgumentException   If view doesn't exist in configured namespaces.
	 * @return \MvcCore\Ext\Views\Helpers\AbstractHelper|\MvcCore\Ext\Views\Helpers\IHelper|\Closure|mixed View helper instance.
	 */
	public function & GetHelper ($helperNameCamelCase, $asClosure = FALSE);
	
	/**
	 * Set view helper for current template or for all templates globally by default.
	 * If view helper already exist in global helpers store - it's overwritten.
	 * @param  string     $helperNameCamelCase
	 * View helper method name in camel case.
	 * @param  ViewHelper $instance
	 * View helper instance.
	 * @param  bool       $forAllTemplates
	 * Register this helper instance for all rendered views in the future.
	 * @return \MvcCore\View
	 */
	public function SetHelper ($helperNameCamelCase, $instance, $forAllTemplates = TRUE);

}
