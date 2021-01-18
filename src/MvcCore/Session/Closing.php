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

trait Closing {

	/**
	 * @inheritDocs
	 * @return void
	 */
	public static function Close () {
		if (!static::GetStarted()) return;
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
	 * @inheritDocs
	 * @return void
	 */
	public static function SendCookie () {
		if (!static::GetStarted()) return;
		$maxExpiration = static::GetSessionMaxTime();
		$response = \MvcCore\Application::GetInstance()->GetResponse();
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
	 * @inheritDocs
	 * @return int
	 */
	public static function GetSessionMaxTime () {
		static::$sessionMaxTime = static::$sessionStartTime;
		if (static::$meta->expirations) {
			foreach (static::$meta->expirations as /*$sessionNamespaceName => */$expiration) {
				if ($expiration > static::$sessionMaxTime)
					static::$sessionMaxTime = $expiration;
			}
		}
		return static::$sessionMaxTime;
	}
}
