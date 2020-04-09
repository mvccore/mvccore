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

namespace MvcCore\Environment;

trait Detection
{
    /**
	 * First environment value setup - by server and client IP address.
	 * @return string Detected environment string.
	 */
	public static function DetectByIps () {
		$request = \MvcCore\Application::GetInstance()->GetRequest();
		$serverAddress = $request->GetServerIp();
		$remoteAddress = $request->GetClientIp();
		if ($serverAddress == $remoteAddress) {
			$name = static::DEVELOPMENT;
		} else {
			$name = static::PRODUCTION;
		}
		return $name;
	}

	/**
	 * Environment value detection by system config `[environments]` section record.
	 * @param array $environmentsSectionData System config environment section data part.
	 * @return string Detected environment string.
	 */
	public static function DetectBySystemConfig (array $environmentsSectionData = []) {
		$name = NULL;
		$app = self::$app ?: self::$app = \MvcCore\Application::GetInstance();
		$request = $app->GetRequest();
		$clientIp = NULL;
		$serverHostName = NULL;
		$serverGlobals = NULL;
		foreach ($environmentsSectionData as $environmentName => $environmentSection) {
			$sectionData = static::detectByConfigSectionData($environmentSection);
			$detected = static::detectByConfigSection(
				$sectionData, $request, $clientIp, $serverHostName, $serverGlobals
			);
			if ($detected) {
				$name = $environmentName;
				break;
			}
		}
		if ($name == NULL)
			$name = \MvcCore\IEnvironment::PRODUCTION;
		return $name;
	}

	/**
	 * Parse system config environment section data from various declarations
	 * into specific detection structure.
	 * @param mixed $environmentSection
	 * @return \stdClass
	 */
	protected static function detectByConfigSectionData ($environmentSection) {
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
			static::detectByConfigClientIps($data, $environmentSection);
		} else if (is_array($environmentSection) || $environmentSection instanceof \stdClass) {
			foreach ((array) $environmentSection as $key => $value) {
				if (is_numeric($key) || $key == 'clients') {
					// if key is only numeric key provided, value is probably
					// only one regular expression to match client IP or
					// the strings list with the most and simple way - to describe client IPS:
					// of if key has `clients` value, there could be list of clients IPs
					// or list of clients IPs regular expressions
					static::detectByConfigClientIps($data, $value);
				} else if ($key == 'servers') {
					// if key is `servers`, there could be string with single regular
					// expression to match hostname or string with comma separated hostnames
					// or list with hostnames and hostname regular expressions
					static::detectByConfigServerNames($data, $value);
				} else if ($key == 'variables') {
					// if key is `variables`, there could be string with `$_SERVER` variable
					// names to check if they exists or key => value object with variable
					// name and value, which could be also regular expression to match
					static::detectByConfigEnvVariables($data, $value);
				}
			}
		}
		return $data;
	}

	/**
	 * Parse system config environment section data from various declarations
	 * about client IP addresses into specific detection structure.
	 * @param \stdClass $data
	 * @param mixed $rawClientIps
	 * @return void
	 */
	protected static function detectByConfigClientIps (& $data, $rawClientIps) {
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
		} else if (is_array($rawClientIps) || $rawClientIps instanceof \stdClass) {
			foreach ((array) $rawClientIps as $rawClientIpsItem) {
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

	/**
	 * Parse system config environment section data from various declarations
	 * about server host names into specific detection structure.
	 * @param \stdClass $data
	 * @param mixed $rawHostNames
	 * @return void
	 */
	protected static function detectByConfigServerNames (& $data, $rawHostNames) {
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
		} else if (is_array($rawHostNames) || $rawHostNames instanceof \stdClass) {
			foreach ((array) $rawHostNames as $rawHostNamesItem) {
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

	/**
	 * Parse system config environment section data from various declarations
	 * about server environment variables into specific detection structure.
	 * @param \stdClass $data
	 * @param mixed $rawServerVariable
	 * @return void
	 */
	protected static function detectByConfigEnvVariables (& $data, $rawServerVariable) {
		$data->serverVariables->check = TRUE;
		if (is_string($rawServerVariable)) {
			$data->serverVariables->existence[] = $rawServerVariable;
		} else if (is_array($rawServerVariable) || $rawServerVariable instanceof \stdClass) {
			foreach ((array) $rawServerVariable as $key => $value) {
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

	/**
	 * Detect environment by specifically parsed environment configuration data.
	 * This method is called for all founded environments in system config in
	 * order by system config and it tries to detect environment by following
	 * order:
	 *	- by client IP address (if defined)
	 *		- by IPs list (if defined)
	 *		- by regular expression (if defined)
	 *	- by server hostname (if defined)
	 *		- by host names list (if defined)
	 *		- by regular expression (if defined)
	 *	- by server variable(s) (if defined)
	 *		- by existence (if defined)
	 *		- by value (if defined)
	 *		- by regular expression (if defined)
	 * Method returns `TRUE` to stop environment detection or `FALSE`, if
	 * environment was not detected by given data.
	 * @param \stdClass			$data
	 * @param \MvcCore\IRequest	$req
	 * @param string|NULL		$clientIp
	 * @param string|NULL		$serverHostName
	 * @param array|NULL		$serverGlobals
	 * @return bool If `TRUE`, environment has been detected and detection procedure could stop.
	 */
	protected static function detectByConfigSection (& $data, $req, & $clientIp, & $serverHostName, & $serverGlobals) {
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