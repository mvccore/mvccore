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

namespace MvcCore\Config;

trait Environment
{
	/**
	 * Set environment name as string,
	 * defined by constants: `\MvcCore\IConfig::ENVIRONMENT_<environment>`.
	 * @param string $environment
	 * @return string
	 */
	public static function SetEnvironment ($environment = \MvcCore\IConfig::ENVIRONMENT_PRODUCTION) {
		static::$environment = $environment;
	}

	/**
	 * Get environment name as string,
	 * defined by constants: `\MvcCore\IConfig::ENVIRONMENT_<environment>`.
	 * @return string
	 */
	public static function GetEnvironment ($autoloadSystemConfig = FALSE) {
		if ($autoloadSystemConfig)
			if (static::GetSystem() === FALSE) static::initEnvironmentByIps();
		else
			static::initEnvironmentByIps();
		return static::$environment;
	}

	/**
	 * First environment value setup - by server and client ip address.
	 * @return void
	 */
	protected static function initEnvironmentByIps () {
		if (static::$environment === NULL) {
			$request = & \MvcCore\Application::GetInstance()->GetRequest();
			$serverAddress = $request->GetServerIp();
			$remoteAddress = $request->GetClientIp();
			if ($serverAddress == $remoteAddress) {
				static::$environment = static::ENVIRONMENT_DEVELOPMENT;
			} else {
				static::$environment = static::ENVIRONMENT_PRODUCTION;
			}
		}
	}
}
