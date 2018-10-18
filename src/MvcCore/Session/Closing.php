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

trait Closing
{
	/**
	 * Write and close session in `\MvcCore::Terminate();`.
	 * Serialize all metadata and call php function to write session into php session storrage.
	 * (HDD, Redis, database, etc., depends on php configuration).
	 * @return void
	 */
	public static function Close () {
		register_shutdown_function(function () {
			foreach (static::$instances as & $instance)
				if (count((array) $_SESSION[$instance->__name]) === 0)
					// if there is nothing in namespace - destroy it. It's useless.
					$instance->Destroy();
			$metaKey = static::SESSION_METADATA_KEY;
			$_SESSION[$metaKey] = serialize(static::$meta);
			@session_write_close();
		});
	}

	/**
	 * Send `PHPSESSID` http cookie with session id hash before response body is sent.
	 * This function is always called by `\MvcCore\Application::Terminate();` at the request end.
	 * @return void
	 */
	public static function SendCookie () {
		$maxExpiration = static::GetSessionMaxTime();
		$response = & \MvcCore\Application::GetInstance()->GetResponse();
		if (!$response->IsSent()) {
			$params = (object) session_get_cookie_params();
			$response->SetCookie(
				session_name(),
				session_id(),
				($maxExpiration > static::$sessionStartTime
					? (static::$sessionMaxTime - static::$sessionStartTime)
					: (isset($params->lifetime) ? $params->lifetime : 0)),
				$params->path,
				$params->domain,
				$params->secure,
				TRUE
			);
		}
	}

	/**
	 * Get the highest expiration in seconds for namespace with
	 * the highest expiration to set expiration for `PHPSESSID` cookie.
	 * @return int
	 */
	public static function GetSessionMaxTime () {
		static::$sessionMaxTime = static::$sessionStartTime;
		foreach (static::$meta->expirations as /*$sessionNamespaceName => */$expiration) {
			if ($expiration > static::$sessionMaxTime)
				static::$sessionMaxTime = $expiration;
		}
		return static::$sessionMaxTime;
	}
}
