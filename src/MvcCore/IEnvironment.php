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

namespace MvcCore;

/**
 * Responsibility - detecting environment, optionally by in system config.
 * - Environment management and detection by:
 *   - comparing server and client IP, by value or regular expression.
 *   - comparing server hostname or IP, by value or regular expression.
 *   - checking system environment variable existence, value or by regular exp.
 */
interface IEnvironment
{
    /**
	 * Development environment.
	 */
	const DEVELOPMENT = 'dev';

	/**
	 * Common team testing environment.
	 */
	const ALPHA = 'alpha';

	/**
	 * Release testing environment.
	 */
	const BETA = 'beta';

	/**
	 * Release environment.
	 */
	const PRODUCTION = 'production';


	/**
	 * Get all available nevironment names.
	 * @return \string[]
	 */
	public static function GetAllNames ();

	/**
	 * Set `TRUE`, if environment is necessary to detected by loaded system config, `FALSE` otherwise.
	 * @return boolean
	 */
	public static function SetDetectionBySystemConfig ($detectionBySystemConfig = TRUE);

	/**
	 * Get `TRUE`, if environment is necessary to detected by loaded system config, `FALSE` otherwise.
	 * @return boolean
	 */
	public static function GetDetectionBySystemConfig ();

	/**
	 * Create empty environment detection instance.
	 * Detection will be executed ondemand later.
	 * @return \MvcCore\IEnvironment
	 */
	public static function CreateInstance ();

    /**
	 * Return `TRUE` if environment is `"dev"`.
	 * @return bool
	 */
	public function IsDevelopment ();

	/**
	 * Return `TRUE` if environment is `"beta"`.
	 * @return bool
	 */
	public function IsBeta ();

	/**
	 * Return `TRUE` if environment is `"alpha"`.
	 * @return bool
	 */
	public function IsAlpha ();

	/**
	 * Return `TRUE` if environment is `"production"`.
	 * @return bool
	 */
	public function IsProduction ();

	/**
	 * Return `TRUE` if environment has already detected name.
	 * @return bool
	 */
	public function IsDetected ();

	/**
	 * Set environment name as string,
	 * defined by constants: `\MvcCore\IEnvironment::<NAME>`.
	 * @param string $name
	 * @return string
	 */
	public function SetName ($name = \MvcCore\IEnvironment::PRODUCTION);

	/**
	 * Get environment name as string,
	 * defined by constants: `\MvcCore\IEnvironment::<NAME>`.
	 * @return string
	 */
	public function GetName ();

	/**
	 * First environment value setup - by server and client IP address.
	 * @return string Detected environment string.
	 */
	public static function DetectByIps ();

	/**
	 * Second environment value setup - by system config data environment record.
	 * @param array $environmentsSectionData System config environment section data part.
	 * @return string Detected environment string.
	 */
	public static function DetectBySystemConfig (array $environmentsSectionData = []);
}