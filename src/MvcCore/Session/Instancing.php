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

namespace MvcCore\Session;

trait Instancing
{
	/**
	 * Get new or existing MvcCore session namespace instance.
	 * @param string $name
	 * @return void
	 */
	public function __construct ($name = \MvcCore\ISession::DEFAULT_NAMESPACE_NAME) {
		if (static::$started !== TRUE) static::Start();
		$this->__name = $name;
		static::$meta->names[$name] = 1;
		if (!isset($_SESSION[$name])) $_SESSION[$name] = [];
		static::$instances[$name] = $this;
	}
}
