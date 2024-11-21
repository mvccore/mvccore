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

interface IComparers {

	/**
	 * Compare two values. Supported types are:
	 *  - NULL
	 *  - scalar (int, float, string, bool)
	 *  - array
	 *  - \stdClass
	 *  - \DateTimeInterface, \DateInterval, \DateTimeZone, \DatePeriod
	 *  - resource (only by `intval($value1) == intval($value2)`)
	 *  - object instances (only by `===` comparison)
	 * @param  mixed $value1 
	 * @param  mixed $value2 
	 * @return bool
	 */
	public static function IsEqual ($value1, $value2);

}