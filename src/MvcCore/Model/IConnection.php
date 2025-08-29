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

namespace MvcCore\Model;

interface IConnection {
	
	/**
	 * Returns `\PDO` database connection by connection name/index,
	 * usually by system config values (cached by local store)
	 * or create new connection if no connection cached.
	 * @param  string|int|array<string,mixed>|\stdClass|null $connectionNameOrConfig
	 * @param  bool                                          $strict
	 * If `TRUE` and no connection under given name or given
	 * index found, exception is thrown. `TRUE` by default.
	 * If `FALSE`, there could be returned connection by
	 * first available configuration.
	 * @throws \InvalidArgumentException|\PDOException|\Throwable
	 * @return \PDO
	 */
	public static function GetConnection ($connectionNameOrConfig = NULL, $strict = TRUE);

	/**
	 * Set up connection instance into connection store to be available for all other model classes.
	 * @param  string|int $connectionName
	 * @param  \PDO       $connection
	 * @return \PDO
	 */
	public static function SetConnection ($connectionName, $connection);
	
	/**
	 * Return `TRUE` if any database connection exists under given index.
	 * @param  string|int $connectionName
	 * @return bool
	 */
	public static function HasConnection ($connectionName);
	
	/**
	 * Unsets connection from the global connections store and calls `Close()` 
	 * method (if exists) on connection instance to close the connection.
	 * @param  string|int|null $connectionName
	 * @throws \InvalidArgumentException
	 * @return bool
	 */
	public static function CloseConnection ($connectionName = NULL);

}