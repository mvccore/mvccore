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
class Environment implements IEnvironment
{
	use \MvcCore\Environment\Instancing;
	use \MvcCore\Environment\PropsGettersSetters;
	use \MvcCore\Environment\Detection;
}
