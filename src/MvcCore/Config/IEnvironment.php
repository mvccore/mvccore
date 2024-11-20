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

namespace MvcCore\Config;

interface IEnvironment {

	/**
	 * Return environment configuration data from system config. Environment
	 * configuration data are always stored under root level section `[environments]`.
	 * If second param is `TRUE`, there is returned whole config content.
	 * @param  \MvcCore\Config $config
	 * @return array<mixed,mixed>|\stdClass
	 */
	public static function & GetEnvironmentDetectionData (\MvcCore\IConfig $config);

	/**
	 * Set up config with current environment data immediately after
	 * environment name is detected. This method is used INTERNALLY!
	 * @param  \MvcCore\Config $config
	 * @param  string          $environmentName
	 * @return void
	 */
	public static function SetUpEnvironmentData (\MvcCore\IConfig $config, $environmentName);

	/**
	 * Get internal array store as reference.
	 * @param  string|NULL $environmentName
	 * Return configuration data only for specific
	 * environment name. If `NULL`, there are
	 * returned data for current environment.
	 * @return array<mixed,mixed>
	 */
	public function & GetData ($environmentName = NULL);

	/**
	 * Set whole internal array store.
	 * @param  array<mixed,mixed> $data
	 * Data to set into configuration store(s). If second
	 * param is `NULL`, there are set data for current envirnment.
	 * @param  string|NULL        $environmentName
	 * Set configuration data for specific
	 * environment name. If `NULL`, there are
	 * set data for current environment.
	 * @return \MvcCore\Config
	 */
	public function SetData (array $data = [], $environmentName = NULL);

}