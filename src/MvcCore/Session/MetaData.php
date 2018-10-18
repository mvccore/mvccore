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

trait MetaData
{
	/**
	 * Set up MvcCore session namespaces metadata
	 * about namespaces names, hoops and expirations.
	 * Called only once at session start by `\MvcCore\ISession::Start();`.
	 * @return void
	 */
	protected static function setUpMeta () {
		$metaKey = static::SESSION_METADATA_KEY;
		$meta = [];
		if (isset($_SESSION[$metaKey]))
			$meta = @unserialize($_SESSION[$metaKey]);
		if (!$meta)
			$meta = [
				'names'			=> [],
				'hoops'			=> [],
				'expirations'	=> [],
			];
		$meta = (object) $meta;
		static::$meta = & $meta;
	}

	/**
	 * Set up namespaces data - only if data has not been expired yet,
	 * if data has been expired, unset data from
	 * `\MvcCore\ISession::$meta` and `$_SESSION` storrage.
	 * Called only once at session start by `\MvcCore\ISession::Start();`.
	 * @return void
	 */
	protected static function setUpData () {
		$hoops = & static::$meta->hoops;
		$names = & static::$meta->names;
		$expirations = & static::$meta->expirations;
		foreach ($hoops as $name => $hoop) {
			$hoops[$name] -= 1;
		}
		foreach ($names as $name => $one) {
			$unset = [];
			if (isset($hoops[$name])) {
				if ($hoops[$name] < 0) $unset[] = 'hoops';
			}
			if (isset($expirations[$name])) {
				$expiration = $expirations[$name];
				if ($expiration < static::$sessionStartTime) {
					$unset[] = 'expirations';
				} else if ($expiration > static::$sessionMaxTime) {
					static::$sessionMaxTime = $expiration;
				}
			}
			if ($unset) {
				$currentErrRepLevels = error_reporting();
				error_reporting(0);
				foreach ($unset as $unsetKey) {
					if (isset(static::$meta->{$unsetKey}) && isset(static::$meta->{$unsetKey}[$name])) {
						unset(static::$meta->{$unsetKey}[$name]);
					}
				}
				error_reporting($currentErrRepLevels);
				unset($names[$name]);
				unset($_SESSION[$name]);
			}
		}
	}

	/**
	 * Get session metadata about session namespaces.
	 * This method is used for debuging purposses.
	 * @return \stdClass
	 */
	public static function GetSessionMetadata () {
		return static::$meta;
	}
}
