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

namespace MvcCore\Session;

/**
 * @phpstan-type SessionMetaData array{"names":array<string,array<string,int>>,"hoops":array<string,array{0:int,1:int}>,"expirations":array<string,int>}|object{"names":array<string,array<string,int>>,"hoops":array<string,array{0:int,1:int}>,"expirations":array<string,int>}
 */
interface IMetaData {
	
	/**
	 * Get session metadata about session namespaces.
	 * This method is used for debugging purposes.
	 * @return \stdClass
	 */
	public static function GetSessionMetadata ();

}
