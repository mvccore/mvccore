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

namespace MvcCore\Environment;

/**
 * @mixin \MvcCore\Environment
 * @phpstan-type ConfigEnvSection string|array{"clients":mixed,"paths":mixed,"servers":mixed,"variables":mixed}|object{"clients":mixed,"paths":mixed,"servers":mixed,"variables":mixed}
 */
trait Detection {

	/**
	 * @inheritDoc
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
	 * @inheritDoc
	 * @param  array<string,ConfigEnvSection> $environmentsSectionData System config environment section data part.
	 * @return string Detected environment string.
	 */
	public static function DetectBySystemConfig (array $environmentsSectionData = []) {
		$name = NULL;
		$app = self::$app ?: (self::$app = \MvcCore\Application::GetInstance()); // @phpstan-ignore-line
		$request = $app->GetRequest();
		$clientIp = $request->GetClientIp();
		$appRoot = NULL;
		$serverHostName = NULL;
		$serverGlobals = NULL;
		foreach ($environmentsSectionData as $environmentName => $environmentSection) {
			$sectionData = static::detectByConfigSectionData($environmentSection, $clientIp);
			$detected = static::detectByConfigSection(
				$sectionData, $request, $clientIp, $appRoot, $serverHostName, $serverGlobals
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
	 * @param  ConfigEnvSection $environmentSection
	 * @param  string|NULL      $clientIp
	 * @return \stdClass
	 */
	protected static function detectByConfigSectionData ($environmentSection, $clientIp) {
		$data = (object) [
			'clientIps'			=> (object) [
				'check'			=> FALSE,
				'values'		=> [],
				'regExeps'		=> []
			],
			'paths'				=> (object) [
				'check'			=> FALSE,
				'values'		=> [],
				'regExeps'		=> []
			],
			'serverHostNames'	=> (object) [
				'check'			=> FALSE,
				'values'		=> [],
				'regExeps'		=> []
			],
			'serverVariables'	=> (object) [
				'check'			=> FALSE,
				'existence'		=> [],
				'values'		=> [],
				'regExeps'		=> []
			]
		];
		if (is_string($environmentSection) && strlen($environmentSection) > 0) {
			// if there is only string provided, value is probably only
			// about the most and simple way - to describe client IPS:
			if ($clientIp !== NULL)
				static::detectByConfigClientIps($data, $environmentSection);
		} else if (is_array($environmentSection) || $environmentSection instanceof \stdClass) { // @phpstan-ignore-line
			foreach ((array) $environmentSection as $key => $value) {
				if (is_numeric($key) || $key == 'clients') {
					// if key is only numeric key provided, value is probably
					// only one regular expression to match client IP or
					// the strings list with the most and simple way - to describe client IPS:
					// of if key has `clients` value, there could be list of clients IPs
					// or list of clients IPs regular expressions
					if ($clientIp !== NULL)
						static::detectByConfigClientIps($data, $value);
				} else if ($key == 'paths') {
					// if key is `paths`, there could be string(s) or regular
					// expression(s) to match application document root
					static::detectByConfigPaths($data, $value);
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
	 * @param  \stdClass $data
	 * @param  mixed     $rawClientIps
	 * @return void
	 */
	protected static function detectByConfigClientIps (& $data, $rawClientIps) {
		$data->clientIps->check = TRUE;
		if (is_string($rawClientIps)) {
			if (static::detectRegExpCheck($rawClientIps)) {
				$data->clientIps->regExeps[] = $rawClientIps;
			} else {
				$data->clientIps->values = array_merge(
					$data->clientIps->values,
					explode(',', str_replace(' ', '', $rawClientIps))
				);
			}
		} else if (is_array($rawClientIps) || $rawClientIps instanceof \stdClass) {
			foreach ((array) $rawClientIps as $rawClientIpsItem) {
				if (static::detectRegExpCheck($rawClientIpsItem)) {
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
	 * about application document root path into specific detection structure.
	 * @param  \stdClass $data
	 * @param  mixed     $rawPaths
	 * @return void
	 */
	protected static function detectByConfigPaths (& $data, $rawPaths) {
		$data->paths->check = TRUE;
		if (is_string($rawPaths)) {
			if (static::detectRegExpCheck($rawPaths)) {
				$data->paths->regExeps[] = $rawPaths;
			} else {
				$data->paths->values = array_merge(
					$data->paths->values,
					explode(',', str_replace(' ', '', $rawPaths))
				);
			}
		} else if (is_array($rawPaths) || $rawPaths instanceof \stdClass) {
			foreach ((array) $rawPaths as $rawPathsItem) {
				if (static::detectRegExpCheck($rawPathsItem)) {
					$data->paths->regExeps[] = $rawPathsItem;
				} else {
					$data->paths->values = array_merge(
						$data->paths->values,
						explode(',', str_replace(' ', '', $rawPathsItem))
					);
				}
			}
		}
	}

	/**
	 * Parse system config environment section data from various declarations
	 * about server host names into specific detection structure.
	 * @param  \stdClass $data
	 * @param  mixed     $rawHostNames
	 * @return void
	 */
	protected static function detectByConfigServerNames (& $data, $rawHostNames) {
		$data->serverHostNames->check = TRUE;
		if (is_string($rawHostNames)) {
			if (static::detectRegExpCheck($rawHostNames)) {
				$data->serverHostNames->regExeps[] = $rawHostNames;
			} else {
				$data->serverHostNames->values = array_merge(
					$data->serverHostNames->values,
					explode(',', str_replace(' ', '', $rawHostNames))
				);
			}
		} else if (is_array($rawHostNames) || $rawHostNames instanceof \stdClass) {
			foreach ((array) $rawHostNames as $rawHostNamesItem) {
				if (static::detectRegExpCheck($rawHostNamesItem)) {
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
	 * @param  \stdClass $data
	 * @param  mixed     $rawServerVariable
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
				} else if (static::detectRegExpCheck($value)) {
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
	 * - by client IP address (if defined)
	 *   - by IPs list (if defined)
	 *   - by regular expression (if defined)
	 * - by server hostname (if defined)
	 *   - by host names list (if defined)
	 *   - by regular expression (if defined)
	 * - by server variable(s) (if defined)
	 *   - by existence (if defined)
	 *   - by value (if defined)
	 *   - by regular expression (if defined)
	 * Method returns `TRUE` to stop environment detection or `FALSE`, if
	 * environment was not detected by given data.
	 * @param  \stdClass                $data
	 * @param  \MvcCore\Request         $req
	 * @param  string|NULL              $clientIp
	 * @param  string|NULL              $appRoot
	 * @param  string|NULL              $serverHostName
	 * @param  array<string,mixed>|NULL $serverGlobals
	 * @return bool If `TRUE`, environment has been detected and detection procedure could stop.
	 */
	protected static function detectByConfigSection (& $data, $req, & $clientIp, & $appRoot, & $serverHostName, & $serverGlobals) {
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
			if ($data->clientIps->regExeps) {
				foreach ($data->clientIps->regExeps as $rawRegExep) {
					$regExep = static::detectRegExpConvert($rawRegExep);
					if (preg_match($regExep, $clientIp))
						return TRUE;
				}
			}
		}
		if ($data->paths->check) {
			// try to recognize environment by any configured application document root path match
			if ($appRoot === NULL) {
				$app = self::$app ?: (self::$app = \MvcCore\Application::GetInstance()); // @phpstan-ignore-line
				$appRoot = $app->GetPathAppRoot();
			}
			if ($data->paths->values)
				foreach ($data->paths->values as $pathToMatch)
					if (mb_strpos($appRoot, $pathToMatch) !== FALSE)
						return TRUE;
			// try to recognize environment by any configured application document root path regular expression match
			if ($data->paths->regExeps) {
				foreach ($data->paths->regExeps as $rawRegExep) {
					$regExep = static::detectRegExpConvert($rawRegExep);
					if (preg_match($regExep, $appRoot))
						return TRUE;
				}
			}
		}
		if ($data->serverHostNames->check) {
			$serverHostName = $serverHostName ?: $req->GetHostName();
			// try to recognize environment by any configured internal server hostname value
			// (value from `/etc/hostname` or Windows computer name)
			if ($data->serverHostNames->values) {
				$serverHostNamesToMatch = ','.implode(',', $data->serverHostNames->values).',';
				if (strpos($serverHostNamesToMatch, ','.$serverHostName.',') !== FALSE)
					return TRUE;
			}
			// try to recognize environment by any configured internal server hostname value
			// regular expression (value from `/etc/hostname` or Windows computer name)
			if ($data->serverHostNames->regExeps) {
				foreach ($data->serverHostNames->regExeps as $rawRegExep) {
					$regExep = static::detectRegExpConvert($rawRegExep);
					if (preg_match($regExep, $serverHostName))
						return TRUE;
				}
			}
		}
		if ($data->serverVariables->check) {
			$serverGlobals = $serverGlobals ?: array_merge(getenv(), $req->GetGlobalCollection('server'));
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
						$serverGlobals[$serverVariableName] == $serverVariableValue
					) return TRUE;
			// try to recognize environment by configured specific value
			// presented in super global variable `$_SERVER` by regular expression
			if ($data->serverVariables->regExeps) {
				foreach ($data->serverVariables->regExeps as $serverVariableName => $rawRegExep) {
					$serverVariableRegExp = static::detectRegExpConvert($rawRegExep);
					if (
						isset($serverGlobals[$serverVariableName]) &&
						preg_match($serverVariableRegExp, (string) $serverGlobals[$serverVariableName])
					) return TRUE;
				}
			}
		}
		return FALSE;
	}

	/**
	 * Check if given string is PHP regular expression syntax.
	 * @param  string $rawValue
	 * @return bool
	 */
	protected static function detectRegExpCheck ($rawValue) {
		return (bool) preg_match("#^/(.+)/([imsxADSUXJu]*)$#", $rawValue);
	}

	/**
	 * Convert given regular expression into hash trailing characters form.
	 * @param  string $regExpWithTrailingSlashes
	 * @return string
	 */
	protected static function detectRegExpConvert ($regExpWithTrailingSlashes) {
		return preg_replace("#^/(.+)/([imsxADSUXJu]*)$#", "#$1#$2", $regExpWithTrailingSlashes);
	}
}