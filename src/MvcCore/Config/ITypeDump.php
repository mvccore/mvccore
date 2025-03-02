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

interface ITypeDump {
	
	/**
	 * Dump configuration data (for all environments) into INI configuration
	 * syntax with environment specific sections and data.
	 * @return string
	 */
	public function Dump ();

}