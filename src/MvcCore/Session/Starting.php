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
 * @phpstan-type SessionOptions array{"cookie_lifetime":int,"cookie_secure":bool,"cookie_httponly":true,"cookie_domain":string,"cookie_path":string,"cookie_samesite":?string}
 */
trait Starting {

	/**
	 * @inheritDoc
	 * @return void
	 */
	public static function Start () {
		if (static::GetStarted()) return;
		$app = self::$app ?: (self::$app = \MvcCore\Application::GetInstance()); // @phpstan-ignore-line
		$req = self::$req ?: (self::$req = $app->GetRequest());
		if ($req->IsInternalRequest() === TRUE) return;
		$res = self::$res ?: (self::$res = $app->GetResponse());
		static::setUpSessionId($req, $res);
		// no magic in `__get()`, just get what we know it is there:
		$preSessionStartHandlers = $app->__get('preSessionStartHandlers');
		if (count($preSessionStartHandlers) > 0 && !$app->ProcessCustomHandlers($preSessionStartHandlers)) {
			$app->Terminate();
			return;
		}
		static::$started = session_start(static::getOptions($req, TRUE));
		static::$sessionStartTime = time();
		static::$sessionMaxTime = static::$sessionStartTime;
		static::setUpMeta();
		static::setUpData($req);
		// no magic in `__get()`, just get what we know it is there:
		$postSessionStartHandlers = $app->__get('postSessionStartHandlers');
		if (count($postSessionStartHandlers) > 0 && !$app->ProcessCustomHandlers($postSessionStartHandlers))
			$app->Terminate();
	}

	/**
	 * @inheritDoc
	 * @return int
	 */
	public static function GetSessionStartTime () {
		return static::$sessionStartTime;
	}

	/**
	 * @inheritDoc
	 * @return bool
	 */
	public static function GetStarted () {
		if (static::$started === NULL) {
			$req = self::$req ?: (self::$req = \MvcCore\Application::GetInstance()->GetRequest());
			if (!$req->IsCli()) {
				$alreadyStarted = session_status() === PHP_SESSION_ACTIVE && session_id() !== '';
				if ($alreadyStarted) {
					// if already started but `static::$started` property is `NULL`:
					static::$sessionStartTime = time();
					static::$sessionMaxTime = static::$sessionStartTime;
					static::setUpMeta();
					static::setUpData($req);
				}
				static::$started = $alreadyStarted;
			}
		}
		return static::$started;
	}

	/**
	 * Get session if from request header. If there is no session id
	 * or if there are more session ids, generate new session id.
	 * @param  \MvcCore\Request  $req
	 * @param  \MvcCore\Response $res
	 * @return void
	 */
	protected static function setUpSessionId (\MvcCore\IRequest $req, \MvcCore\IResponse $res) {
		$mcSessionCookieName = $res->GetSessionIdCookieName();
		@ini_set('session.name', $mcSessionCookieName);
		$sessionCookieName = session_name($mcSessionCookieName);
		$rawCookieHeader = ';' . trim($req->GetHeader('Cookie', '-_,=;a-zA-Z0-9') ?: '', ';') . ';';
		$sessionId = NULL;
		if (preg_match_all("#;\s?{$sessionCookieName}\s?\=([^;]+)#", $rawCookieHeader, $matches)) {
			$rawSessionIds = isset($matches[1]) ? $matches[1] : [];
			if (count($rawSessionIds) === 1)
				$sessionId = $rawSessionIds[0];
			// if count is higher than 1, it's session fixation atack request, 
			// then generate new session id for response.
		}
		if ($sessionId === NULL) 
			$sessionId = static::createId();
		$_COOKIE[$sessionCookieName] = $sessionId;
		session_id($sessionId);
	}

	/**
	 * Complete `session_start()` options and session id cookie for response.
	 * @param  \MvcCore\Request $req
	 * @param  bool             $starting Get options for session start, otherwise get options for session id cookie.
	 * @return SessionOptions
	 */
	protected static function getOptions (\MvcCore\IRequest $req, $starting = TRUE) {
		$sessionCookieParamsDefault = (object) session_get_cookie_params();
		$cookieLifeTime = isset($sessionCookieParamsDefault->lifetime) 
			? $sessionCookieParamsDefault->lifetime 
			: 0;
		if (!$starting) {
			$sessionMaxTime = static::GetSessionMaxTime();
			if ($sessionMaxTime > static::$sessionStartTime)
				$cookieLifeTime = $sessionMaxTime - static::$sessionStartTime;
		}
		$sessionStartOptions = [
			// $sentSessionId
			'cookie_lifetime'	=> $cookieLifeTime,
			'cookie_secure'		=> $req->IsSecure(),
			'cookie_httponly'	=> TRUE,
			'cookie_domain'		=> $sessionCookieParamsDefault->domain,
			'cookie_path'		=> $sessionCookieParamsDefault->path,
		];
		if (PHP_VERSION_ID >= 70300) {
			/** @var ?string $sameSite */
			$sameSite = $sessionCookieParamsDefault->samesite;
			if ($sameSite === '' || $sameSite === NULL)
				$sameSite = \MvcCore\IResponse::COOKIE_SAMESITE_LAX;
			$sessionStartOptions['cookie_samesite'] = $sameSite;
		}
		return $sessionStartOptions;
	}

	/**
	 * Create new session id. It's used to create new session id 
	 * for the current session. It returns collision free session id.
	 * If session is not active, collision check is omitted.
	 * Session ID is created according to php.ini settings.
	 * It is important to use the same user ID of your web server for 
	 * GC task script. Otherwise, you may have permission problems 
	 * especially with files save handler.
	 * @see https://www.php.net/manual/en/function.session-create-id.php#121945
	 * @see https://www.php.net/manual/en/function.random-bytes.php#118932
	 * @param  ?string $prefix
	 * @param  int     $outputLen 
	 * @return string
	 */
	protected static function createId ($prefix = NULL, $outputLen = 26) {
		if (PHP_VERSION_ID > 70100)
			return $prefix === NULL ? session_create_id() : session_create_id($prefix);
		if ($prefix !== NULL && !preg_match("#^([a-zA-Z0-9\-,]+)$#", $prefix))
			trigger_error(
				"Prefix cannot contain special characters. Only the `A-Z`, `a-z`, `0-9`, ".
				"`-`, and `,` characters are allowed.", E_USER_WARNING
			);
		$toolClass = \MvcCore\Application::GetInstance()->GetToolClass();
		$randomBytes = $toolClass::GetRandomHash($outputLen);
		return ($prefix ?: '') . $randomBytes;
	}

}
