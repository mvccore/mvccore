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
 */
trait Closing {

	/**
	 * @inheritDoc
	 * @return void
	 */
	public static function Close () {
		if (!static::GetStarted()) return;
		$app = self::$app ?: (self::$app = \MvcCore\Application::GetInstance());
		$req = self::$req ?: (self::$req = $app->GetRequest());
		$res = self::$res ?: (self::$res = $app->GetResponse());
		$resIsRedirect = $res->HasHeader('Location');
		register_shutdown_function(function () use ($req, $resIsRedirect) {
			foreach (static::$instances as & $instance)
				if (count((array) $_SESSION[$instance->__name]) === 0)
					// if there is nothing in namespace - destroy it. It's useless.
					$instance->Destroy();
			$metaKey = static::SESSION_METADATA_KEY;
			if ($resIsRedirect) {
				$hoops = & static::$meta->hoops;
				$reqMethod = $req->GetMethod();
				$reqIsAjax = $req->IsAjax();
				foreach ($hoops as $name => $hoopsItem) {
					list($hoopsCount, $ignoredReqsFlags) = $hoopsItem;
					if(static::isRequestIgnoredForHoops($ignoredReqsFlags, $reqMethod, $reqIsAjax)) 
						continue;
					if (($ignoredReqsFlags & \MvcCore\ISession::EXPIRATION_HOOPS_IGNORE_REDIRECTS) != 0)
						$hoopsCount += 1;
					$hoops[$name] = [$hoopsCount, $ignoredReqsFlags];
				}
			}
			$serializeFn = function_exists('igbinary_serialize') ? 'igbinary_serialize' : 'serialize';
			/** @var string|FALSE $metaStr */
			$metaStr = @call_user_func($serializeFn, static::$meta);
			if ($metaStr !== FALSE) {
				$_SESSION[$metaKey] = $metaStr;
				@session_write_close();
			}
		});
	}

	/**
	 * @inheritDoc
	 * @return void
	 */
	public static function SendSessionIdCookie () {
		if (!static::GetStarted()) return;
		$app = self::$app ?: (self::$app = \MvcCore\Application::GetInstance());
		$res = self::$res ?: self::$res = $app->GetResponse();
		if (!$res->IsSent()) {
			// remove Set-Cookie header from session_start():
			$setCookieHeaderName = 'Set-Cookie';
			$sessionIdName = session_name();
			$sessionIdValue = session_id();
			$setCookies = $res->GetHeader('Set-Cookie');
			if ($setCookies !== NULL) {
				$cookieSubStr = "{$sessionIdName}={$sessionIdValue}";
				$setCookiesNew = [];
				foreach ($setCookies as $setCookie)
					if (mb_strpos($setCookie, $cookieSubStr) === FALSE)
						$setCookiesNew[] = $setCookie;
				$res->SetHeader($setCookieHeaderName, $setCookiesNew);
			}
			// set up new session id cookie:
			$options = static::getOptions($app->GetRequest(), FALSE);
			$res->SetCookie(
				$sessionIdName, $sessionIdValue, 
				$options['cookie_lifetime'],	$options['cookie_path'], 
				$options['cookie_domain'],		$options['cookie_secure'], 
				$options['cookie_httponly'],
				isset($options['cookie_samesite']) 
					? $options['cookie_samesite']
					: \MvcCore\IResponse::COOKIE_SAMESITE_LAX
			);
		}
	}

	/**
	 * @inheritDoc
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
	
	/**
	 * @inheritDoc
	 * @return void
	 */
	public static function SendRefreshedCsrfCookie () {
		if (!static::GetStarted()) return;
		$app = self::$app ?: (self::$app = \MvcCore\Application::GetInstance());
		$res = self::$res ?: self::$res = $app->GetResponse();
		if (
			!$res->IsSent() &&
			($app->GetCsrfProtection() & \MvcCore\IApplication::CSRF_PROTECTION_COOKIE) != 0
		) {
			$sessionNamespace = static::GetCsrfNamespace();
			$toolClass = $app->GetToolClass();
			$csrfSecret = $toolClass::GetRandomHash(64); // generate new value
			$sessionNamespace->secret = $csrfSecret; // @phpstan-ignore-line
			$csrfExpiration = static::GetSessionCsrfMaxTime();
			$params = (object) session_get_cookie_params();
			if ($csrfExpiration > static::$sessionStartTime) {
				$cookieLifeTime = $csrfExpiration - static::$sessionStartTime;
			} else {
				$cookieLifeTime = isset($params->lifetime) ? $params->lifetime : 0;
			}
			$res->SetCookie(
				$res::GetCsrfProtectionCookieName(), $csrfSecret, $cookieLifeTime, $params->path,
				NULL, NULL, TRUE, \MvcCore\IResponse::COOKIE_SAMESITE_STRICT
			);
		}
	}
	
	/**
	 * @inheritDoc
	 * @return int
	 */
	public static function GetSessionCsrfMaxTime () {
		if (static::$sessionCsrfMaxTime !== NULL)
			return static::$sessionCsrfMaxTime;
		static $authClassesFullNames = [
			"\\MvcCore\\Ext\\Auth",
			"\\MvcCore\\Ext\\Auths\\Basic"
		];
		/** @var ?\MvcCore\Ext\Auths\Basic $auth @phpstan-ignore-next-line */
		$auth = NULL;
		foreach ($authClassesFullNames as $authClassFullName) {
			if (class_exists($authClassFullName, TRUE)) {
				$auth = $authClassFullName::GetInstance();
				break;
			}
		}
		// If there is any authentication class, 
		// try to get expiration seconds value:
		if ($auth !== NULL) {
			$sessionCsrfMaxSeconds = $auth->GetExpirationAuthorization();
			if (is_int($sessionCsrfMaxSeconds) && $sessionCsrfMaxSeconds > 0)
				static::$sessionCsrfMaxTime = $sessionCsrfMaxSeconds;
		}
		// If there is nothing like that, set expiration until browser close:
		if (static::$sessionCsrfMaxTime === NULL)
			static::$sessionCsrfMaxTime = 0;
		return static::$sessionCsrfMaxTime;
	}
}
