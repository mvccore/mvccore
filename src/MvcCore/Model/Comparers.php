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

trait Comparers {

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
	protected static function isEqual ($value1, $value2) {
		$valuasAreTheSame = FALSE;
		$value1IsNull = $value1 === NULL;
		$value2IsNull = $value2 === NULL;
		if ($value1IsNull && $value2IsNull) {
			$valuasAreTheSame = TRUE;
		} else if (!$value1IsNull && !$value2IsNull) {
			if (is_float($value1) && is_float($value2)) {
				$valuasAreTheSame = abs($value1 - $value2) < PHP_FLOAT_EPSILON;
				
			} else if (
				(is_scalar($value1) && is_scalar($value2)) ||
				(is_array($value1) && is_array($value2)) ||
				($value1 instanceof \stdClass && $value2 instanceof \stdClass)
			) {
				$valuasAreTheSame = $value1 === $value2;
				
			} else if ($value1 instanceof \DateTimeInterface && $value2 instanceof \DateTimeInterface) {
				$valuasAreTheSame = $value1 == $value2;
				
			} else if ($value1 instanceof \DateInterval && $value2 instanceof \DateInterval) {
				$valuasAreTheSame = abs(
					static::convertIntervalToFloat($value1) - 
					static::convertIntervalToFloat($value2)
				) < PHP_FLOAT_EPSILON;

			} else if ($value1 instanceof \DateTimeZone && $value2 instanceof \DateTimeZone) {
				$now = new \DateTime('now');
				$valuasAreTheSame = $value1->getOffset($now) === $value2->getOffset($now);

			} else if ($value1 instanceof \DatePeriod && $value2 instanceof \DatePeriod) {
				$valuasAreTheSame = (
					$value1->getStartDate() == $value2->getStartDate() && 
					$value1->getEndDate() == $value2->getEndDate() && 
					abs(
						static::convertIntervalToFloat($value1->getDateInterval()) - 
						static::convertIntervalToFloat($value2->getDateInterval())
					) < PHP_FLOAT_EPSILON
				);

			} else if (is_resource($value1) && is_resource($value2)) {
				$valuasAreTheSame = intval($value1) == intval($value2);

			} else {
				// compare if object instances are the same (do not process any reflection comparison):
				$valuasAreTheSame = $value1 === $value2;

			}
		}
		return $valuasAreTheSame;
	}
}