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
	 * @var int
	 */
	const DISPATCH_STATE_CREATED			= 0;

	/**
	 * Value after executing the `Init()` method.
	 * @var int
	 */
	const DISPATCH_STATE_INITIALIZED		= 1;

	/**
	 * Value after executing the `Init()` method.
	 * @var int
	 */
	const DISPATCH_STATE_ACTION_INITIALIZED	= 2;
	
	/**
	 * Value after executing the `PreDispatch()` method.
	 * @var int
	 */
	const DISPATCH_STATE_PRE_DISPATCHED		= 3;
	
	/**
	 * Value after executing the action method.
	 * @var int
	 */
	const DISPATCH_STATE_ACTION_EXECUTED	= 4;
	
	/**
	 * Value after executing the `Render ()` method.
	 * @var int
	 */
	const DISPATCH_STATE_RENDERED			= 5;
	
	/**
	 * Value after executing the `Terminate()` or `Redirect()` method.
	 * @var int
	 */
	const DISPATCH_STATE_TERMINATED			= 100;



	/**
	 * Flash message option flag to define `success` message.
	 * @var int
	 */
	const FLASH_MESSAGE_TYPE_SUCCESS		= 1;

	/**
	 * Flash message option flag to define `help` message.
	 * @var int
	 */
	const FLASH_MESSAGE_TYPE_HELP			= 2;

	/**
	 * Flash message option flag to define `info` message.
	 * @var int
	 */
	const FLASH_MESSAGE_TYPE_INFO			= 4;

	/**
	 * Flash message option flag to define `warning` message.
	 * @var int
	 */
	const FLASH_MESSAGE_TYPE_WARN			= 8;

	/**
	 * Flash message option flag to define `error` message.
	 * @var int
	 */
	const FLASH_MESSAGE_TYPE_ERROR			= 16;

	/**
	 * Flash message option flag to define `critical` message.
	 * @var int
	 */
	const FLASH_MESSAGE_TYPE_CRITICAL		= 32;
	
	/*
	 * More flash message types are limited only by values:
	 * 64,128,256,512,1024,2048,4096,8192,16384,32768,65536,131072,
	 * 262144,524288,1048576,2097152,4194304,8388608,16777216.
	 */
	

	/**
	 * Flash message option flag to define `info` message type with auto hide
	 * (`\MvcCore\IController::FLASH_MESSAGE_TYPE_INFO | \MvcCore\IController::FLASH_MESSAGE_AUTOHIDE`).
	 * @var int
	 */
	const FLASH_MESSAGE_TYPE_DEFAULT		= 33554436;
	
	
	/**
	 * Flash message options key to define message auto hiding.
	 * Could be used as integer flag or options array key with boolean.
	 * @var int
	 */
	const FLASH_MESSAGE_AUTOHIDE			= 33554432;
	
	/**
	 * Flash message options key to define closeable message.
	 * Could be used as integer flag or options array key with boolean.
	 * @var int
	 */
	const FLASH_MESSAGE_CLOSEABLE			= 67108864;
	
	/**
	 * Flash message options array key to define message type.
	 * @var int
	 */
	const FLASH_MESSAGE_TYPE				= 134217728;

	/**
	 * Flash message options array key to define miliseconds 
	 * message timeout before auto hide.
	 * @var int
	 */
	const FLASH_MESSAGE_TIMEOUT				= 268435456;

	/**
	 * Flash message options array key to define custom hoops count.
	 * @var int
	 */
	const FLASH_MESSAGE_HOOPS				= 536870912;

	/**
	 * Flash message options array key to define custom expiration datetime.
	 * This flag creates persistent flash message that will exist 
	 * in the session until the expiration date is reached.
	 * @var int
	 */
	const FLASH_MESSAGE_EXPIRATION			= 1073741824;


	/**
	 * Flash messages session namespace name.
	 * @var string
	 */
	const FLASH_MESSAGES_SESSION_NAMESPACE = '\\MvcCore\\Controller\\FlashMessages';
	
}