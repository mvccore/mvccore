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
trait NamespaceMethods {

	/**
	 * @inheritDoc
	 * @param  string $name Session namespace unique name.
	 * @return \MvcCore\Session
	 */
	public static function GetNamespace (
		$name = \MvcCore\ISession::DEFAULT_NAMESPACE_NAME
	) {
		if (!static::GetStarted())
			static::Start();
		if (!isset(static::$instances[$name])) {
			/** @var \MvcCore\Session $instance */
			$instance = new static(); /** @phpstan-ignore-line */
			$instance->__name = $name;
			static::$meta->names[$name] = 1;
			if (!isset($_SESSION[$name]))
				$_SESSION[$name] = [];
			static::$instances[$name] = $instance;
		}
		return static::$instances[$name];
	}

	/**
	 * @inheritDoc
	 * @param  int $hoopsCount           Requests count.
	 * @param  int $ignoredRequestsFlags Ignored requests flags, 1022 by default.
	 * @return \MvcCore\Session
	 */
	public function SetExpirationHoops ($hoopsCount, $ignoredRequestsFlags = \MvcCore\ISession::EXPIRATION_HOOPS_IGNORE_DEFAULT) {
		static::$meta->hoops[$this->__name] = [$hoopsCount, $ignoredRequestsFlags];
		return $this;
	}

	/**
	 * @inheritDoc
	 * @param  int $seconds
	 * @return \MvcCore\Session
	 */
	public function SetExpirationSeconds ($seconds = 0) {
		if ($seconds > 0)
			static::$meta->expirations[$this->__name] = static::$sessionStartTime + $seconds;
		return $this;
	}

	/**
	 * @inheritDoc
	 * @return void
	 */
	public function Destroy () {
		$name = $this->__name;
		$names = & static::$meta->names;
		$hoops = & static::$meta->hoops;
		$expirations = & static::$meta->expirations;
		$instances = & static::$instances;
		if (isset($names[$name])) unset($names[$name]);
		if (isset($hoops[$name])) unset($hoops[$name]);
		if (isset($expirations[$name])) unset($expirations[$name]);
		if (isset($_SESSION[$name])) unset($_SESSION[$name]);
		if (isset($instances[$name])) unset($instances[$name]);
	}

	/**
	 * @inheritDoc
	 * @return void
	 */
	public static function DestroyAll () {
		session_destroy();
		$_SESSION = NULL;
		static::$started = FALSE;
		$response = \MvcCore\Application::GetInstance()->GetResponse();
		if (!$response->IsSent()) {
			$params = (object) session_get_cookie_params();
			$response->DeleteCookie(
				session_name(),
				$params->path,
				$params->domain,
				$params->secure
			);
		}
	}

	/**
	 * @inheritDoc
	 * @return \MvcCore\Session
	 */
	public static function GetCsrfNamespace () {
		$csrfExpiration = static::GetSessionCsrfMaxTime();
		$namespaceName = "\\" . get_called_class() . "\\Csrf";
		$sessionNamespace = static::GetNamespace($namespaceName);
		$sessionNamespace->SetExpirationSeconds($csrfExpiration);
		return $sessionNamespace;
	}
}
