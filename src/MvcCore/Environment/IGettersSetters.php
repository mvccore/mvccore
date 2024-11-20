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

interface IGettersSetters {

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

}