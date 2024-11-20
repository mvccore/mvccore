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
 * @phpstan-type ConfigEnvSection string|array{"clients":mixed,"paths":mixed,"servers":mixed,"variables":mixed}|object{"clients":mixed,"paths":mixed,"servers":mixed,"variables":mixed}
 */
interface IDetection {

	
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