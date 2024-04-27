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

namespace MvcCore;

/**
 * Responsibility - detecting environment, optionally by in system config.
 * - Environment management and detection by:
 *   - comparing server and client IP, by value or regular expression.
 *   - comparing server hostname or IP, by value or regular expression.
 *   - checking system environment variable existence, value or by regular exp.
 * @phpstan-type ConfigEnvSection string|array{"clients":mixed,"paths":mixed,"servers":mixed,"variables":mixed}|object{"clients":mixed,"paths":mixed,"servers":mixed,"variables":mixed}
 */
interface IEnvironment extends \MvcCore\Environment\IConstants {

	/**
	 * Get all available nevironment names.
	 * @return array<string>
	 */
	public static function GetAllNames ();

	/**
	 * Set `TRUE`, if environment is necessary to detected by loaded system config, `FALSE` otherwise.
	 * @param  boolean $detectionBySystemConfig `TRUE` by default.
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
	 * @return \MvcCore\Environment
	 */
	public static function CreateInstance ();

	/**
	 * Return `TRUE` if environment is `"dev"`.
	 * @return bool
	 */
	public function IsDevelopment ();

	/**
	 * Return `TRUE` if environment is `"alpha"`.
	 * @return bool
	 */
	public function IsAlpha ();

	/**
	 * Return `TRUE` if environment is `"beta"`.
	 * @return bool
	 */
	public function IsBeta ();

	/**
	 * Return `TRUE` if environment is `"gamma"`.
	 * @return bool
	 */
	public function IsGamma ();

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
	 * @param  string $name
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
	 * @param  array<string,ConfigEnvSection> $environmentsSectionData System config environment section data part.
	 * @return string Detected environment string.
	 */
	public static function DetectBySystemConfig (array $environmentsSectionData = []);
}