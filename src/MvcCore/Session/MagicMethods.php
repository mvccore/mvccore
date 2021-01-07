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

trait MagicMethods {

	/** Classic PHP magic methods for object access ***************************/

	/**
	 * @inheritDocs
	 * @param string $key
	 * @return mixed
	 */
	public function __get ($key) {
		/** @var $this \MvcCore\Session */
		$name = $this->__name;
		if (isset($_SESSION[$name][$key])) return $_SESSION[$name][$key];
		return NULL;
	}

	/**
	 * @inheritDocs
	 * @param string $key
	 * @param mixed $value
	 * @return mixed
	 */
	public function __set ($key, $value) {
		/** @var $this \MvcCore\Session */
		return $_SESSION[$this->__name][$key] = $value;
	}

	/**
	 * @inheritDocs
	 * @param string $key
	 * @return bool
	 */
	public function __isset ($key) {
		/** @var $this \MvcCore\Session */
		return isset($_SESSION[$this->__name][$key]);
	}

	/**
	 * @inheritDocs
	 * @param string $key
	 * @return void
	 */
	public function __unset ($key) {
		/** @var $this \MvcCore\Session */
		$name = $this->__name;
		if (array_key_exists($key, $_SESSION[$name])) 
			unset($_SESSION[$name][$key]);
	}

	/**
	 * Print all about current session namespace instance for debug purposes.
	 * @return array
	 */
	public function __debugInfo () {
		/** @var $this \MvcCore\Session */
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


	/** \Countable interface **************************************************/

	/**
	 * Get how many records is in the session namespace.
	 * Example: `count($sessionNamespace);`
	 * @return int
	 */
	public function count () {
		/** @var $this \MvcCore\Session */
		return count((array) $_SESSION[$this->__name]);
	}


	/** \Iterator interface ***************************************************/

	/**
	 * Return the current element.
	 * @return mixed
	 */
	public function current () {
		/** @var $this \MvcCore\Session */
		return current($_SESSION[$this->__name]);
	}

	/**
	 * Return the key of the current element.
	 * @return string|int
	 */
	public function key () {
		/** @var $this \MvcCore\Session */
		return key($_SESSION[$this->__name]);
	}

	/**
	 * Move forward to next element.
	 * @return void
	 */
	public function next () {
		/** @var $this \MvcCore\Session */
		return next($_SESSION[$this->__name]);
	}

	/**
	 * Rewind the Iterator to the first element.
	 * @return void
	 */
	public function rewind () {
		/** @var $this \MvcCore\Session */
		reset($_SESSION[$this->__name]);
	}

	/**
	 * Checks if current position is valid.
	 * @return bool
	 */
	public function valid () {
		/** @var $this \MvcCore\Session */
		return key($_SESSION[$this->__name]) !== NULL;
	}


	/** \ArrayAccess interface ************************************************/

	/**
	 * Set the value at the specified index.
	 * Example: `$sessionNamespace['any'] = 'thing';`
	 * @param mixed $offset
	 * @param mixed $value
	 */
	public function offsetSet ($offset, $value) {
		/** @var $this \MvcCore\Session */
		$data = & $_SESSION[$this->__name];
		if ($offset === NULL) {
			$data[] = $value;
		} else {
			$data[$offset] = $value;
		}
	}

	/**
	 * Get the value at the specified index.
	 * Example: `$thing = $sessionNamespace['any'];`
	 * @param mixed $offset
	 * @param mixed $value
	 */
	public function offsetGet ($offset) {
		/** @var $this \MvcCore\Session */
		$data = & $_SESSION[$this->__name];
		return isset($data[$offset]) ? $data[$offset] : NULL;
	}

	/**
	 * Return whether the requested index exists.
	 * Example: `isset($sessionNamespace['any']);`
	 * @param mixed $offset
	 * @return bool
	 */
	public function offsetExists ($offset) {
		/** @var $this \MvcCore\Session */
		return isset($_SESSION[$this->__name][$offset]);
	}

	/**
	 * Unset the value at the specified index.
	 * Example: `unset($sessionNamespace['any']);`
	 * @param mixed $offset
	 */
	public function offsetUnset ($offset) {
		/** @var $this \MvcCore\Session */
		unset($_SESSION[$this->__name][$offset]);
	}
}
