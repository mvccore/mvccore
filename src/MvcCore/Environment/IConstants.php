<?php

/**
 * MvcCore
 *
 * This source file is subject to the BSD 3 License
 * For the full copyright and license information, please view
 * the LICENSE.md file that are distributed with this source code.
 *
 * @copyright	Copyright (c) 2016 Tom Flidr (https://github.com/mvccore)
 * @license  https://mvccore.github.io/docs/mvccore/5.0.0/LICENCE.md
 */

namespace MvcCore\Environment;

interface IConstants {

	/**
	 * Release environment.
	 */
	const PRODUCTION = 'production';

	/**
	 * Common team testing environment.
	 */
	const ALPHA = 'alpha';

	/**
	 * Release testing environment.
	 */
	const BETA = 'beta';

	/**
	 * Development environment.
	 */
	const DEVELOPMENT = 'dev';
}