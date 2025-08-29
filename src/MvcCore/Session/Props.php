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
 * @phpstan-type SessionMetaData array{"names":array<string,array<string,int>>,"hoops":array<string,array{0:int,1:int}>,"expirations":array<string,int>}|object{"names":array<string,array<string,int>>,"hoops":array<string,array{0:int,1:int}>,"expirations":array<string,int>}
 */
trait Props {

	/**
	 * Default session namespace name.
	 * @var string
	 */
	protected $__name = \MvcCore\ISession::DEFAULT_NAMESPACE_NAME;

	/**
	 * Static boolean about if session has been already started or not.
	 * @var ?bool
	 */
	protected static $started = NULL;

	/**
	 * Metadata array or stdClass with all MvcCore namespaces metadata information:
	 * - `"names"`			=> Array with all namespace records names.
	 * - `"hoops"`			=> Array with all namespace records page requests count to expire.
	 * - `"expirations"`	=> Array with all records expiration times.
	 * This metadata arrays are decoded from `$_SESSION` storage only once at in session start.
	 * @var SessionMetaData
	 */
	protected static $meta = [
		/**
		 * Array with all namespace records names.
		 * @var array<string,array<string,int>>
		 */
		'names'			=> [],
		/**
		 * Array with all namespace records page requests count to expire. 
		 * Keys are namespace names, values are arrays with first item to be
		 * hoops count and second item to be ignoring requests flags to ignore
		 * specific request to be counted.
		 * @var array<string,array{0:int,1:int}>
		 */
		'hoops'			=> [],
		/**
		 * Array with all records expiration times. Keyed by namespace names.
		 * @var array<string,int>
		 */
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
	 * Session expiration for CSRF protection in seconds. 
	 * Default value is zero seconds (`0`).
	 * Zero value (`0`) means "until the browser is closed".
	 * If there is found any autorization service,
	 * value is set by authorization expiration time.
	 * @var ?int
	 */
	protected static $sessionSecurityMaxTime = NULL;

	/**
	 * `TRUE` if session has been regenerated in login/logout submit.
	 * All session data are always transfered into new session.
	 * @var mixed
	 */
	protected static $regenerated = FALSE;

	/**
	 * Application instance reference.
	 * @var ?\MvcCore\Application
	 */
	protected static $app = NULL;

	/**
	 * Request instance reference.
	 * @var ?\MvcCore\Request
	 */
	protected static $req = NULL;

	/**
	 * Response instance reference.
	 * @var ?\MvcCore\Response
	 */
	protected static $res = NULL;
}
