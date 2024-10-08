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

interface IConstants {

	/**
	 * Metadata key in `$_SESSION` storage.
	 * @var string
	 */
	const SESSION_METADATA_KEY = '__MC';

	/**
	 * Default session namespace name.
	 * @var string
	 */
	const DEFAULT_NAMESPACE_NAME = 'default';


	/**
	 * Number of seconds for 1 minute (60).
	 * @var int
	 */
	const EXPIRATION_SECONDS_MINUTE	= 60;

	/**
	 * Number of seconds for 1 hour (60 * 60 = 3600).
	 * @var int
	 */
	const EXPIRATION_SECONDS_HOUR	= 3600;

	/**
	 * Number of seconds for 1 day (60 * 60 * 24 = 86400).
	 * @var int
	 */
	const EXPIRATION_SECONDS_DAY	= 86400;

	/**
	 * Number of seconds for 1 week (60 * 60 * 24 * 7 = 3600).
	 * @var int
	 */
	const EXPIRATION_SECONDS_WEEK	= 604800;

	/**
	 * Number of seconds for 1 month, 30 days (60 * 60 * 24 * 30 = 3600).
	 * @var int
	 */
	const EXPIRATION_SECONDS_MONTH	= 2592000;

	/**
	 * Number of seconds for 1 year, 365 days (60 * 60 * 24 * 365 = 3600).
	 * @var int
	 */
	const EXPIRATION_SECONDS_YEAR	= 31536000;


	
	/**
	 * Session hoops expiration flag to include all requests.
	 * @var int
	 */
	const EXPIRATION_HOOPS_ALL_REQUESTS		= 0;
	
	/**
	 * Session hoops expiration flag to ignore `GET` requests.
	 * @var int
	 */
	const EXPIRATION_HOOPS_IGNORE_GET		= 1;
	
	/**
	 * Session hoops expiration flag to ignore `POST` requests.
	 * @var int
	 */
	const EXPIRATION_HOOPS_IGNORE_POST		= 2;
	
	/**
	 * Session hoops expiration flag to ignore `HEAD` requests.
	 * @var int
	 */
	const EXPIRATION_HOOPS_IGNORE_HEAD		= 4;
	
	/**
	 * Session hoops expiration flag to ignore `PUT` requests.
	 * @var int
	 */
	const EXPIRATION_HOOPS_IGNORE_PUT		= 8;
	
	/**
	 * Session hoops expiration flag to ignore `DELETE` requests.
	 * @var int
	 */
	const EXPIRATION_HOOPS_IGNORE_DELETE	= 16;
	
	/**
	 * Session hoops expiration flag to ignore `OPTIONS` requests.
	 * @var int
	 */
	const EXPIRATION_HOOPS_IGNORE_OPTIONS	= 32;
	
	/**
	 * Session hoops expiration flag to ignore `PATCH` requests.
	 * @var int
	 */
	const EXPIRATION_HOOPS_IGNORE_PATCH		= 64;
	
	/**
	 * Session hoops expiration flag to ignore `TRACE` requests.
	 * @var int
	 */
	const EXPIRATION_HOOPS_IGNORE_TRACE		= 128;
	
	/**
	 * Session hoops expiration flag to ignore ajax requests.
	 * @var int
	 */
	const EXPIRATION_HOOPS_IGNORE_AJAX		= 256;
	
	/**
	 * Session hoops expiration flag to ignore redirect requests.
	 * @var int
	 */
	const EXPIRATION_HOOPS_IGNORE_REDIRECTS	= 512;
	
	/**
	 * Session hoops expiration flag to ignore all request 
	 * types except `GET` non ajax non redirect requests.
	 * @var int
	 */
	const EXPIRATION_HOOPS_IGNORE_DEFAULT	= 1022; // 512 | 256 | 128 | 64 | 32 | 16 | 8 | 4 | 2

}