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

namespace MvcCore\Session;

trait Props {

	/**
	 * Default session namespace name.
	 * @var string
	 */
	protected $__name = \MvcCore\ISession::DEFAULT_NAMESPACE_NAME;

	/**
	 * Static boolean about if session has been already started or not.
	 * @var bool|NULL
	 */
	protected static $started = NULL;

	/**
	 * Metadata array or stdClass with all MvcCore namespaces metadata information:
	 * - `"names"`			=> Array with all namespace records names.
	 * - `"hoops"`			=> Array with all namespace records page requests count to expire.
	 * - `"expirations"`	=> Array with all records expiration times.
	 * This metadata arrays are decoded from `$_SESSION` storage only once at in session start.
	 * @var array|\stdClass
	 */
	protected static $meta = [
		/** @var \string[] Array with all namespace records names. */
		'names'			=> [],
		/** @var \int[] Array with all namespace records page requests count to expire. Keyed by namespace names. */
		'hoops'			=> [],
		/** @var \int[] Array with all records expiration times. Keyed by namespace names. */
		'expirations'	=> [],
	];

	/**
	 * Array of created `\MvcCore\ISession` instances,
	 * keys in this array storage are session namespaces names.
	 * @var \MvcCore\Session[]
	 */
	protected static $instances = [];

	/**
	 * Unix epoch for current request session start moment.
	 * @var int
	 */
	protected static $sessionStartTime = 0;

	/**
	 * The highest expiration in seconds for namespace with
	 * the highest expiration to set expiration for `PHPSESSID` cookie.
	 * @var int
	 */
	protected static $sessionMaxTime = 0;

	/**
	 * Request instance reference.
	 * @var \MvcCore\Request
	 */
	protected static $req = NULL;
}
