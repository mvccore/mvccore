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

use \MvcCore\Response\IConstants as ResConsts;

/**
 * @mixin \MvcCore\Session
 * @phpstan-type SessionSecret array{"0":string,"1":int}
 */
trait Security {

	/**
	 * @inheritDoc
	 * @return bool
	 */
	public static function ValidateSecurityToken () {
		$app = self::$app ?: (self::$app = \MvcCore\Application::GetInstance()); // @phpstan-ignore-line
		$sessionNamespace = static::GetSecurityNamespace();
		$req = self::$req ?: (self::$req = $app->GetRequest());
		$res = self::$res ?: (self::$res = $app->GetResponse());
		$securityCookieToken = $req->GetCookie($res::GetSecurityCookieName());
		/** @var array<SessionSecret> $secrets */
		$secrets = $sessionNamespace->secrets;
		$matched = FALSE;
		for ($i = 0, $l = count($secrets); $i < $l; $i++) {
			list($secret) = $secrets[$i];
			if ($secret === $securityCookieToken) {
				$matched = TRUE;
				break;
			}
		}
		return $matched;
	}

	/**
	 * @inheritDoc
	 * @param  ?bool $immediately
	 * @param  bool  $keepOlder
	 * @return bool
	 */
	public static function RegenerateSecurityToken ($immediately = NULL, $keepOlder = FALSE) {
		if (!$immediately && !static::securityTokenExpired($immediately)) 
			return FALSE;
		/** @var \MvcCore\Application $app */
		$app = self::$app ?: (self::$app = \MvcCore\Application::GetInstance()); // @phpstan-ignore-line
		$toolClass = $app->GetToolClass();
		$newSecret = $toolClass::GetRandomHash(64); // generates new value
		$securityNamespace = static::GetSecurityNamespace();
		if (!isset($securityNamespace->secrets))
			$securityNamespace->secrets = [];
		/** @var array<SessionSecret> $secrets */
		$secrets = $securityNamespace->secrets;
		$reqStartTime = intval($app->GetRequest()->GetStartTime());
		if (!$keepOlder) {
			$secrets = [[$newSecret, $reqStartTime]];
		} else {
			array_unshift($secrets, [$newSecret, $reqStartTime]);
			if (count($secrets) > 1)
				$secrets[1][1] = $reqStartTime + static::SECURITY_TOKEN_EXPIRATION_LAST_TIME;
		}
		$securityNamespace->secrets = $secrets;
		return TRUE;
	}

	/**
	 * Process all secret records and remove records older 
	 * than request start time + 1 minute (configurable). 
	 * Always keep first current secret record.
	 * @return void
	 */
	protected static function setUpSecurity () {
		$securityNamespace = static::GetSecurityNamespace();
		if (!isset($securityNamespace->secrets))
			$securityNamespace->secrets = [];
		// process all secrets and remove all timeouted
		/** @var array<SessionSecret> $secretsOld */
		$secretsOld = $securityNamespace->secrets;
		$secretsOldCnt = count($secretsOld);
		if ($secretsOldCnt > 1) {
			/** @var \MvcCore\Application $app */
			$app = self::$app ?: (self::$app = \MvcCore\Application::GetInstance()); // @phpstan-ignore-line
			/** @var array<SessionSecret> $secretsNew */
			$secretsNew = [reset($secretsOld)];
			$reqStartTime = intval($app->GetRequest()->GetStartTime());
			for ($i = 1, $l = count($secretsOld); $i < $l; $i++) {
				$secretOld = $secretsOld[$i];
				list(, $secretTimeout) = $secretOld;
				if ($secretTimeout > $reqStartTime)
					$secretsNew[] = $secretOld;
			}
			$securityNamespace->secrets = $secretsNew;
		}
		// check if there is necessary to regenerate token
		static::RegenerateSecurityToken(NULL, TRUE);
	}

	/**
	 * Check if security token is possible to regenerate.
	 * If first argument is:
	 * - `FALSE` - form submit - if minimum has been loaded, regenerate,
	 * - `NULL`  - any request begin - if maximum has been loaded, regenerate.
	 * `TRUE` value is never here, it's used for previous function to keep code simplier.
	 * @param  false|null $immediately 
	 * @return bool
	 */
	protected static function securityTokenExpired ($immediately = NULL) {
		$securityNamespace = static::GetSecurityNamespace();
		if (!isset($securityNamespace->secrets) || count($securityNamespace->secrets) === 0)
			return TRUE;
		$secrets = $securityNamespace->secrets;
		list(, $currentTokenCreatedTime) = reset($secrets);
		$app = self::$app ?: (self::$app = \MvcCore\Application::GetInstance()); // @phpstan-ignore-line
		$reqStartTime = intval($app->GetRequest()->GetStartTime());
		if ($immediately === FALSE) {
			if ($currentTokenCreatedTime + static::SECURITY_TOKEN_EXPIRATION_MIN_TIME < $reqStartTime)
				return TRUE;
		} else /* if ($immediately === NULL)*/ {
			if ($currentTokenCreatedTime + static::SECURITY_TOKEN_EXPIRATION_MAX_TIME < $reqStartTime)
				return TRUE;
		}
		return FALSE;
	}

}
