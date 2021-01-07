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

trait Starting
{
	/**
	 * @inheritDocs
	 * @return void
	 */
	public static function Start (& $session = []) {
		if (static::GetStarted()) return;
		$req = self::$req ?: self::$req = \MvcCore\Application::GetInstance()->GetRequest();
		if ($req->IsInternalRequest() === TRUE) return;

		static::preventSessionFixation($req);

		$sessionStartOptions = [
			// $sentSessionId
			'cookie_secure'		=> $req->IsSecure(),
			'cookie_httponly'	=> TRUE,
		];
		if (PHP_VERSION_ID >= 70300)
			$sessionStartOptions['cookie_samesite'] = TRUE;

		static::$started = session_start($sessionStartOptions);
		static::$sessionStartTime = time();
		static::$sessionMaxTime = static::$sessionStartTime;
		static::setUpMeta();
		static::setUpData();
	}

	/**
	 * @inheritDocs
	 * @return int
	 */
	public static function GetSessionStartTime () {
		return static::$sessionStartTime;
	}

	/**
	 * @inheritDocs
	 * @return bool
	 */
	public static function GetStarted () {
		if (static::$started === NULL) {
			$req = self::$req ?: self::$req = \MvcCore\Application::GetInstance()->GetRequest();
			if (!$req->IsCli()) {
				$alreadyStarted = session_status() === PHP_SESSION_ACTIVE && session_id() !== '';
				if ($alreadyStarted) {
					// if already started but `static::$started` property is `NULL`:
					static::$sessionStartTime = time();
					static::$sessionMaxTime = static::$sessionStartTime;
					static::setUpMeta();
					static::setUpData();
				}
				static::$started = $alreadyStarted;
			}
		}
		return static::$started;
	}

	/**
	 * When there is a situation in client browser, when there is executed
	 * some XSS session fixation script manipulation with HTTP only session id,
	 * then there could be schizofrenic situation in browser local storrage.
	 *
	 * The script could look like this:
	 * `document.cookie="PHPSESSID=evil_value";`
	 *
	 * This creates in browser two cookies with the same name.
	 * First cookie is from server side with HTTP ONLY
	 * flag and the second cookie exists for javascript environment.
	 *
	 * Then user could continue to next document and browser always sent both cookies.
	 * But the HTTP only cookie is always send as second in Cookie header value cookies list.
	 * But PHP engine takes always the first cookie value to start session.
	 * To prevent atacks like that, take always the last session id value 
	 * in Cookie header list by fixing session id before session has been started.
	 * @param \MvcCore\Request $req
	 * @return void
	 */
	protected static function preventSessionFixation (\MvcCore\IRequest $req) {
		$sessionCookieName = session_name();
		$rawCookieHeader = ';' . trim($req->GetHeader('Cookie', '-,=;a-zA-Z0-9'), ';') . ';';
		$sessionCookieNameExtended = ';' . $sessionCookieName . '=';
		// check if there has been executed any potentional client XSS for session fixation:
		if (substr_count($rawCookieHeader, $sessionCookieNameExtended) > 1) {
			$sentSessionId = '';
			$lastPoss = mb_strrpos($rawCookieHeader, $sessionCookieNameExtended);
			if ($lastPoss !== FALSE) {
				$rawSentSessionId = mb_substr($rawCookieHeader, $lastPoss + mb_strlen($sessionCookieNameExtended));
				$valueEndPos = mb_strpos($rawSentSessionId, ';');
				if ($valueEndPos === FALSE) {
					$sentSessionId = $rawSentSessionId;
				} else {
					$sentSessionId = mb_substr($rawSentSessionId, 0, $valueEndPos);
				}
				if (mb_strlen($sentSessionId) > 128)
					$sentSessionId = mb_substr($sentSessionId, 0, 128);
				$sentSessionId = str_replace('=', '', $sentSessionId);
				// Use the last session id in Cookies header potentional list.
				// The last one is always the right with htttp only flag.
				$_COOKIE[$sessionCookieName] = $sentSessionId;
				session_id($sentSessionId);
			}
		}
	}
}
