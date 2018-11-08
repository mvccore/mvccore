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

namespace MvcCore\Config;

trait MagicMethods
{
	/**
	 * Get internal array store as reference.
	 * @return array
	 */
	public function & GetData () {
		return $this->data;
	}


	/** Classic PHP magic methods for object access ***************************/

	/**
	 * Get not defined property from `$this->data` array store, 
	 * if there is nothing, return `NULL`.
	 * @param string $key 
	 * @return mixed
	 */
	public function __get ($key) {
		if (array_key_exists($key, $this->data))
			return $this->data[$key];
		return NULL;
	}

	/**
	 * Store not defined property inside `$this->data` array store.
	 * @param string $key 
	 * @return mixed
	 */
	public function __set ($key, $value) {
		return $this->data[$key] = $value;
	}
	
	/**
	 * Magic function triggered by: `isset($cfg->key);`.
	 * @param string $key
	 * @return bool
	 */
	public function __isset ($key) {
		return isset($this->data[$key]);
	}

	/**
	 * Magic function triggered by: `unset($cfg->key);`.
	 * @param string $key
	 * @return void
	 */
	public function __unset ($key) {
		if (isset($this->data[$key])) unset($this->data[$key]);
	}

	
	/** \Countable interface **************************************************/
	
	/**
	 * Get how many records is in the config internal store.
	 * Example: `count($cfg);`
	 * @return int
	 */
	public function count () {
		return count($this->data);
	}

	
	/** \IteratorAggregate interface ******************************************/

	/**
	 * Return new iterator from the internal data store 
	 * to use config instance in for each cycle.
	 * Example: `foreach ($cfg as $key => $value) { var_dump([$key, $value]); }`
	 * @return \ArrayIterator|\Traversable
	 */
	public function getIterator () {
        return new \ArrayIterator($this->data);
    }


	/** \ArrayAccess interface ************************************************/

	/**
	 * Set the value at the specified index in the internal store.
	 * Example: `$cfg['any'] = 'thing';`
	 * @param mixed $offset 
	 * @param mixed $value 
	 */
	public function offsetSet ($offset, $value) {
        if ($offset === NULL) {
            $this->data[] = $value;
        } else {
            $this->data[$offset] = $value;
        }
    }

	/**
	 * Get the value at the specified index from the internal store.
	 * Example: `$thing = $cfg['any'];`
	 * @param mixed $offset 
	 * @param mixed $value 
	 */
    public function offsetGet ($offset) {
        return isset($this->data[$offset]) ? $this->data[$offset] : NULL;
    }

    /**
     * Return whether the requested index exists in the internal store.
	 * Example: `isset($cfg['any']);`
     * @param mixed $offset 
     * @return bool
     */
    public function offsetExists ($offset) {
        return isset($this->data[$offset]);
    }

    /**
     * Unset the value at the specified index in the internal store.
	 * Example: `unset($cfg['any']);`
     * @param mixed $offset 
     */
    public function offsetUnset ($offset) {
        unset($this->data[$offset]);
    }
}
