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
trait MetaData {
	
	/**
	 * @inheritDocs
	 * @return \stdClass
	 */
	public static function GetSessionMetadata () {
		return static::$meta;
	}

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
	 * `\MvcCore\Session::$meta` and `$_SESSION` storage.
	 * Called only once at session start by `\MvcCore\Session::Start();`.
	 * @param  \MvcCore\Request $req
	 * @return void
	 */
	protected static function setUpData (\MvcCore\IRequest $req) {
		$hoops = & static::$meta->hoops;
		$names = & static::$meta->names;
		$expirations = & static::$meta->expirations;
		$reqMethod = $req->GetMethod();
		$reqIsAjax = $req->IsAjax();
		foreach ($hoops as $name => $hoopsItem) {
			list($hoopsCount, $ignoredReqsFlags) = $hoopsItem;
			$ignored = static::isRequestIgnoredForHoops($ignoredReqsFlags, $reqMethod, $reqIsAjax);
			if (!$ignored)
				$hoopsCount -= 1;
			$hoops[$name] = [$hoopsCount, $ignoredReqsFlags];
		}
		foreach ($names as $name => $one) {
			$unset = [];
			if (isset($hoops[$name])) {
				list($hoopsCount) = $hoops[$name];
				if ($hoopsCount < 0) $unset[] = 'hoops';
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
	 * Return `TRUE` if request is ignored to count down session expiration hoops.
	 * @param  int    $ignoredReqsFlags 
	 * @param  string $reqMethod 
	 * @param  bool   $reqIsAjax 
	 * @return bool
	 */
	protected static function isRequestIgnoredForHoops ($ignoredReqsFlags, $reqMethod, $reqIsAjax) {
		static $methodsAndFlags = [
			\MvcCore\IRequest::METHOD_GET		=> \MvcCore\ISession::EXPIRATION_HOOPS_IGNORE_GET,
			\MvcCore\IRequest::METHOD_POST		=> \MvcCore\ISession::EXPIRATION_HOOPS_IGNORE_POST,
			\MvcCore\IRequest::METHOD_HEAD		=> \MvcCore\ISession::EXPIRATION_HOOPS_IGNORE_HEAD,
			\MvcCore\IRequest::METHOD_PUT		=> \MvcCore\ISession::EXPIRATION_HOOPS_IGNORE_PUT,
			\MvcCore\IRequest::METHOD_DELETE	=> \MvcCore\ISession::EXPIRATION_HOOPS_IGNORE_DELETE,
			\MvcCore\IRequest::METHOD_OPTIONS	=> \MvcCore\ISession::EXPIRATION_HOOPS_IGNORE_OPTIONS,
			\MvcCore\IRequest::METHOD_PATCH		=> \MvcCore\ISession::EXPIRATION_HOOPS_IGNORE_PATCH,
			\MvcCore\IRequest::METHOD_TRACE		=> \MvcCore\ISession::EXPIRATION_HOOPS_IGNORE_TRACE,
		];
		$ignoredMethods = [];
		foreach ($methodsAndFlags as $httpMethod => $ignoreReqFlag) 
			$ignoredMethods[$httpMethod] = ($ignoredReqsFlags & $ignoreReqFlag) != 0;
		if (isset($ignoredMethods[$reqMethod]) && $ignoredMethods[$reqMethod]) 
			return TRUE;
		if ($reqIsAjax && ($ignoredReqsFlags & \MvcCore\ISession::EXPIRATION_HOOPS_IGNORE_AJAX) != 0)
			return TRUE;
		return FALSE;
	}
}
