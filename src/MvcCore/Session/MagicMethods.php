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

trait MagicMethods
{
	/**
	 * Magic function triggered by: `isset(\MvcCore\ISession->key);`.
	 * @param string $key
	 * @return bool
	 */
	public function __isset ($key) {
		return isset($_SESSION[$this->__name][$key]);
	}

	/**
	 * Magic function triggered by: `unset(\MvcCore\ISession->key);`.
	 * @param string $key
	 * @return void
	 */
	public function __unset ($key) {
		$name = $this->__name;
		if (isset($_SESSION[$name][$key])) unset($_SESSION[$name][$key]);
	}

	/**
	 * Magic function triggered by: `$value = \MvcCore\ISession->key;`.
	 * @param string $key
	 * @return mixed
	 */
	public function __get ($key) {
		$name = $this->__name;
		if (isset($_SESSION[$name][$key])) return $_SESSION[$name][$key];
		return NULL;
	}

	/**
	 * Magic function triggered by: `\MvcCore\ISession->key = "value";`.
	 * @param string $key
	 * @param mixed $value
	 * @return void
	 */
	public function __set ($key, $value) {
		$_SESSION[$this->__name][$key] = $value;
	}

	/**
	 * Print all about current session namespace instance for debug purposses.
	 * @return array
	 */
	public function __debugInfo () {
		$hoops = isset(static::$meta->hoops[$this->__name])
			? static::$meta->hoops[$this->__name]
			: NULL;
		$expiration = isset(static::$meta->expirations[$this->__name])
			? static::$meta->expirations[$this->__name]
			: NULL;
		return [
			'name'					=> $this->__name,
			'expirationSeconds'		=> date('D, d M Y H:i:s', $expiration),
			'expirationHoops'		=> $hoops,
			'values'				=> $_SESSION[$this->__name],
		];
	}

	/**
	 * Magic `\ArrayObject` function triggered by: `count(\MvcCore\ISession);`.
	 * @return int
	 */
	public function count () {
		return count((array) $_SESSION[$this->__name]);
	}
}
