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
	 * Environment name. Usual values:
	 * - `"dev"`			- Development environment.
	 * - `"beta"`			- Common team testing environment.
	 * - `"alpha"`			- Release testing environment.
	 * - `"production"`		- Release environment.
	 * @var string|NULL
	 */
	protected static $environment = NULL;

	/**
	 * Return `TRUE` if environment is `"dev"`.
	 * @param bool $autoloadSystemConfig If `TRUE`, environment will be detected by loaded system config.
	 * @return bool
	 */
	public static function IsDevelopment ($autoloadSystemConfig = TRUE) {
		return static::GetEnvironment($autoloadSystemConfig) === static::ENVIRONMENT_DEVELOPMENT;
	}

	/**
	 * Return `TRUE` if environment is `"beta"`.
	 * @param bool $autoloadSystemConfig If `TRUE`, environment will be detected by loaded system config.
	 * @return bool
	 */
	public static function IsBeta ($autoloadSystemConfig = TRUE) {
		return static::GetEnvironment($autoloadSystemConfig) === static::ENVIRONMENT_BETA;
	}

	/**
	 * Return `TRUE` if environment is `"alpha"`.
	 * @param bool $autoloadSystemConfig If `TRUE`, environment will be detected by loaded system config.
	 * @return bool
	 */
	public static function IsAlpha ($autoloadSystemConfig = TRUE) {
		return static::GetEnvironment($autoloadSystemConfig) === static::ENVIRONMENT_ALPHA;
	}

	/**
	 * Return `TRUE` if environment is `"production"`.
	 * @param bool $autoloadSystemConfig If `TRUE`, environment will be detected by loaded system config.
	 * @return bool
	 */
	public static function IsProduction ($autoloadSystemConfig = TRUE) {
		return static::GetEnvironment($autoloadSystemConfig) === static::ENVIRONMENT_PRODUCTION;
	}

	/**
	 * Set environment name as string,
	 * defined by constants: `\MvcCore\IConfig::ENVIRONMENT_<environment>`.
	 * @param string $environment
	 * @return string
	 */
	public static function SetEnvironment ($environment = \MvcCore\IConfig::ENVIRONMENT_PRODUCTION) {
		return static::$environment = $environment;
	}

	/**
	 * Get environment name as string,
	 * defined by constants: `\MvcCore\IConfig::ENVIRONMENT_<environment>`.
	 * @return string
	 */
	public static function GetEnvironment ($autoloadSystemConfig = FALSE) {
		if (static::$environment === NULL) {
			if ($autoloadSystemConfig) {
				if (static::GetSystem() === FALSE) 
					// if there is no sysconfig, recognize environment only by very 
					// simple way - by server and client IP only
					static::environmentDetectByIps();
			} else {
				static::environmentDetectByIps();
			}
		}
		return static::$environment;
	}

	/**
	 * First environment value setup - by server and client ip address.
	 * @return void
	 */
	protected static function environmentDetectByIps () {
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

	/**
	 * Second environment value setup - by system config data environment record.
	 * @param array $rawCfgData
	 * @return string|NULL
	 */
	protected static function environmentDetectBySystemConfig (array & $rawCfgData = []) {
		$environment = NULL;
		if (isset($rawCfgData['environments'])) {
			$environmentsSections = & $rawCfgData['environments'];
			$app = self::$app ?: self::$app = \MvcCore\Application::GetInstance();
			$request = $app->GetRequest();
			$clientIp = NULL;
			$serverHostName = NULL;
			$serverGlobals = NULL;
			foreach ($environmentsSections as $environmentName => $environmentSection) {
				$sectionData = static::environmentDetectParseSysConfigEnvSectionData($environmentSection);
				$detected = static::environmentDetectBySystemConfigEnvSection(
					$sectionData, $request, $clientIp, $serverHostName, $serverGlobals
				);
				if ($detected) {
					$environment = $environmentName;
					break;
				}
			}
		}
		if ($environment && !static::$environment) 
			static::SetEnvironment($environment);
		return static::$environment;
	}

	protected static function environmentDetectParseSysConfigEnvSectionData ($environmentSection) {
		$data = (object) [
			'clientIps' => (object) [
				'check'		=> FALSE,
				'values'	=> [], 
				'regExeps'	=> []
			],
			'serverHostNames' => (object) [
				'check'		=> FALSE,
				'values'	=> [], 
				'regExeps'	=> []
			],
			'serverVariables' => (object) [
				'check'		=> FALSE,
				'existence'	=> [], 
				'values'	=> [], 
				'regExeps'	=> []
			]
		];
		if (is_string($environmentSection) && strlen($environmentSection) > 0) {
			// if there is only string provided, value is probably only
			// about the most and simple way - to describe client IPS:
			static::environmentDetectParseSysConfigClientIps($data, $environmentSection);
		} else if (is_array($environmentSection)) {
			foreach ($environmentSection as $key => $value) {
				if (is_numeric($key) || $key == 'clients') {
					// if key is only numeric key provided, value is probably
					// only one regular expression to match client ip or 
					// the strings list with the most and simple way - to describe client IPS:
					// of if key has `clients` value, there could be list of clients IPs
					// or list of clients IPs regular expressions
					static::environmentDetectParseSysConfigClientIps($data, $value);
				} else if ($key == 'servers') {
					// if key is `servers`, there could be string with single regular
					// expression to match hostname or string with comma separated hostnames
					// or list with hostnames and hostname regular expressions
					static::environmentDetectParseSysConfigServerNames($data, $value);
				} else if ($key == 'variables') {
					// if key is `variables`, there could be string with `$_SERVER` variable
					// names to check if they exists or key => value object with variable
					// name and value, which could be also regular expression to match
					static::environmentDetectParseSysConfigVariables($data, $value);
				}
			}
		}
		return $data;
	}

	protected static function environmentDetectParseSysConfigClientIps (& $data, $rawClientIps) {
		$data->clientIps->check = TRUE;
		if (is_string($rawClientIps)) {
			if (substr($rawClientIps, 0, 1) == '/') {
				$data->clientIps->regExeps[] = $rawClientIps;
			} else {
				$data->clientIps->values = array_merge(
					$data->clientIps->values, 
					explode(',', str_replace(' ', '', $rawClientIps))
				);
			}
		} else if (is_array($rawClientIps)) {
			foreach ($rawClientIps as $rawClientIpsItem) {
				if (substr($rawClientIpsItem, 0, 1) == '/') {
					$data->clientIps->regExeps[] = $rawClientIpsItem;
				} else {
					$data->clientIps->values = array_merge(
						$data->clientIps->values, 
						explode(',', str_replace(' ', '', $rawClientIpsItem))
					);
				}
			}
		}
	}

	protected static function environmentDetectParseSysConfigServerNames (& $data, $rawHostNames) {
		$data->serverHostNames->check = TRUE;
		if (is_string($rawHostNames)) {
			if (substr($rawHostNames, 0, 1) == '/') {
				$data->serverHostNames->regExeps[] = $rawHostNames;
			} else {
				$data->serverHostNames->values = array_merge(
					$data->serverHostNames->values, 
					explode(',', str_replace(' ', '', $rawHostNames))
				);
			}
		} else if (is_array($rawHostNames)) {
			foreach ($rawHostNames as $rawHostNamesItem) {
				if (substr($rawHostNamesItem, 0, 1) == '/') {
					$data->serverHostNames->regExeps[] = $rawHostNamesItem;
				} else {
					$data->serverHostNames->values = array_merge(
						$data->serverHostNames->values, 
						explode(',', str_replace(' ', '', $rawHostNamesItem))
					);
				}
			}
		}
	}
	
	protected static function environmentDetectParseSysConfigVariables (& $data, $rawServerVariable) {
		$data->serverVariables->check = TRUE;
		if (is_string($rawServerVariable)) {
			$data->serverVariables->existence[] = $rawServerVariable;
		} else if (is_array($rawServerVariable)) {
			foreach ($rawServerVariable as $key => $value) {
				if (is_numeric($key)) {
					$data->serverVariables->existence[] = $value;
				} else if (substr($value, 0, 1) == '/') {
					$data->serverVariables->regExeps[$key] = $value;
				} else {
					$data->serverVariables->values[$key] = $value;
				}
			}
		}
	}

	protected static function environmentDetectBySystemConfigEnvSection (& $data, & $req, & $clientIp, & $serverHostName, & $serverGlobals) {
		if ($data->clientIps->check) {
			// try to recognize environment by any configured client IP address value
			$clientIp = $clientIp ?: $req->GetClientIp();
			if ($data->clientIps->values) {
				$clientIpToMatch = ',' . $clientIp . ',';
				$clientIpsToMatch = ',' . implode(',', $data->clientIps->values) . ',';
				if (strpos($clientIpsToMatch, $clientIpToMatch) !== FALSE) 
					return TRUE;
			}
			// try to recognize environment by any configured client IP address regular expression
			if ($data->clientIps->regExeps) 
				foreach ($data->clientIps->regExeps as $regExep) 
					if (preg_match($regExep, $clientIp)) 
						return TRUE;
		}
		if ($data->serverHostNames->check) {
			$serverHostName = $serverHostName ?: gethostname();
			// try to recognize environment by any configured internal server hostname value 
			// (value from `/etc/hostname` or Windows computer name)
			if ($data->serverHostNames->values) {
				$serverHostNamesToMatch = ','.implode(',', $data->serverHostNames->values).',';
				if (strpos($serverHostNamesToMatch, ','.$serverHostName.',') !== FALSE) 
					return TRUE;
			}
			// try to recognize environment by any configured internal server hostname value 
			// regular expression (value from `/etc/hostname` or Windows computer name)
			if ($data->serverHostNames->regExeps) 
				foreach ($data->serverHostNames->regExeps as $regExep) 
					if (preg_match($regExep, $serverHostName)) 
						return TRUE;
		}
		if ($data->serverVariables->check) {
			$serverGlobals = $serverGlobals ?: $req->GetGlobalCollection('server');
			// try to recognize environment by any configured existing record in 
			// super global variable `$_SERVER` by PHP function `array_key_exists()`
			if ($data->serverVariables->existence) 
				foreach ($data->serverVariables->existence as $serverVariableName) 
					if (array_key_exists($serverVariableName, $serverGlobals)) 
						return TRUE;
			// try to recognize environment by configured specific value
			// presented in super global variable `$_SERVER` 
			if ($data->serverVariables->values) 
				foreach ($data->serverVariables->values as $serverVariableName => $serverVariableValue) 
					if (
						isset($serverGlobals[$serverVariableName]) && 
						$serverGlobals[$serverVariableName] === $serverVariableValue
					) return TRUE;
			// try to recognize environment by configured specific value
			// presented in super global variable `$_SERVER` by regular expression
			if ($data->serverVariables->regExeps) 
				foreach ($data->serverVariables->regExeps as $serverVariableName => $serverVariableRegExp) 
					if (
						isset($serverGlobals[$serverVariableName]) && 
						preg_match($serverVariableRegExp, (string) $serverGlobals[$serverVariableName])
					) return TRUE;
		}
		return FALSE;
	}
}
