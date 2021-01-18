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

namespace MvcCore\Controller;

interface IConstants {

	/**
	 * Initial value after controller has been instantiated.
	 */
	const DISPATCH_STATE_CREATED			= 0;

	/**
	 * Value after executing the `Init()` method.
	 */
	const DISPATCH_STATE_INITIALIZED		= 1;
	
	/**
	 * Value after executing the `PreDispatch()` method.
	 */
	const DISPATCH_STATE_PRE_DISPATCHED		= 2;
	
	/**
	 * Value after executing the action method.
	 */
	const DISPATCH_STATE_ACTION_EXECUTED	= 3;
	
	/**
	 * Value after executing the `Render ()` method.
	 */
	const DISPATCH_STATE_RENDERED			= 4;
	
	/**
	 * Value after executing the `Terminate()` or `Redirect()` method.
	 */
	const DISPATCH_STATE_TERMINATED			= 5;
}