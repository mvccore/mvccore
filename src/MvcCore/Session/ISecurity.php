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

namespace MvcCore\Session;

/**
 * @mixin \MvcCore\Session
 */
interface ISecurity {

	/**
	 * Validate security cookie token with tokens in session.
	 * There is always only one token, but sometimes after token change, 
	 * there could be one more last token for requests started
	 * with old token before new token has been set on client side.
	 * @inheritDoc
	 * @return bool
	 */
	public static function ValidateSecurityToken ();

	/**
	 * Regenerate security token, if application security mode is configured 
	 * with security cookie. 
	 * If First argument is 
	 * - `TRUE` (usually login/logout form submit action), token is regenerated 
	 *   immediatelly. 
	 * - `FALSE` (usually any other form submit action), token is regenerated 
	 *   only if the minimal token time has been spent.
	 * - `NULL` (any request at request begin), token is regenerated only 
	 *   if the maximum token time has been spent.
	 * If second argument is `TRUE`, there is kept also older token in session
	 * for some configured time for delayed request started from client with 
	 * old token before new token has been saved in browser. Older tokens
	 * are not kept always in login/logout submit, any other token change is 
	 * processed with `TRUE` value.
	 * @inheritDoc
	 * @param  ?bool $immediately
	 * @param  bool  $keepOlder
	 * @return bool
	 */
	public static function RegenerateSecurityToken ($immediately = NULL, $keepOlder = FALSE);

}
