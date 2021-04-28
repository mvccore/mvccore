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

/**
 * @mixin \MvcCore\Config
 */
trait MagicMethods {

	/**
	 * Serialize only given properties:
	 * @return \string[]
	 */
	public function __sleep () {
		if (!$this->mergedData) {
			$app = self::$app ?: self::$app = \MvcCore\Application::GetInstance();
			$envName = $app->GetEnvironment()->GetName();
			if (!$envName) {
				$cfgClass = $app->GetConfigClass();
				$envDetectionData = & $cfgClass::GetEnvironmentDetectionData($this);
				$envClass = $app->GetEnvironmentClass();
				$envName = $envClass::DetectBySystemConfig((array) $envDetectionData);
			}
			$cfgClass::SetUpEnvironmentData($this, $envName);
		}
		return [
			'system',
			'mergedData',
			'fullPath',
			'lastChanged',
		];
	}

	/** Classic PHP magic methods for object access ***************************/

	/**
	 * Get not defined property from `$this->currentData` array store,
	 * if there is nothing, return `NULL`.
	 * @param  string $key
	 * @return mixed
	 */
	public function __get ($key) {
		if (array_key_exists($key, $this->currentData))
			return $this->currentData[$key];
		return NULL;
	}

	/**
	 * Store not defined property inside `$this->currentData` array store.
	 * @param  string $key
	 * @param  mixed  $value
	 * @return mixed
	 */
	public function __set ($key, $value) {
		return $this->currentData[$key] = $value;
	}

	/**
	 * Magic function triggered by: `isset($cfg->key);`.
	 * @param  string $key
	 * @return bool
	 */
	public function __isset ($key) {
		return isset($this->currentData[$key]);
	}

	/**
	 * Magic function triggered by: `unset($cfg->key);`.
	 * @param  string $key
	 * @return void
	 */
	public function __unset ($key) {
		if (isset($this->currentData[$key])) unset($this->currentData[$key]);
	}


	/** \Iterator interface ***************************************************/

	/**
	 * Return the current element.
	 * @return mixed
	 */
	public function current () {
		return current($this->currentData);
	}

	/**
	 * Return the key of the current element.
	 * @return string|int
	 */
	public function key () {
		return key($this->currentData);
	}

	/**
	 * Move forward to next element.
	 * @return void
	 */
	public function next () {
		return next($this->currentData);
	}

	/**
	 * Rewind the Iterator to the first element.
	 * @return void
	 */
	public function rewind () {
		reset($this->currentData);
	}

	/**
	 * Checks if current position is valid.
	 * @return bool
	 */
	public function valid () {
		return key($this->currentData) !== NULL;
	}


	/** \ArrayAccess interface ************************************************/

	/**
	 * Return whether the requested index exists in the internal store.
	 * Example: `isset($cfg['any']);`
	 * @param  mixed $offset
	 * @return bool
	 */
	public function offsetExists ($offset) {
		return isset($this->currentData[$offset]);
	}

	/**
	 * Get the value at the specified index from the internal store.
	 * Example: `$thing = $cfg['any'];`
	 * @param  mixed $offset
	 * @return mixed
	 */
	public function offsetGet ($offset) {
		return isset($this->currentData[$offset]) ? $this->currentData[$offset] : NULL;
	}

	/**
	 * Set the value at the specified index in the internal store.
	 * Example: `$cfg['any'] = 'thing';`
	 * @param  mixed $offset
	 * @param  mixed $value
	 * @return void
	 */
	public function offsetSet ($offset, $value) {
		if ($offset === NULL) {
			$this->currentData[] = $value;
		} else {
			$this->currentData[$offset] = $value;
		}
	}

	/**
	 * Unset the value at the specified index in the internal store.
	 * Example: `unset($cfg['any']);`
	 * @param  mixed $offset
	 * @return void
	 */
	public function offsetUnset ($offset) {
		unset($this->currentData[$offset]);
	}


	/** \Countable interface **************************************************/

	/**
	 * Get how many records is in the config internal store.
	 * Example: `count($cfg);`
	 * @return int
	 */
	public function count () {
		return count($this->currentData);
	}

}
