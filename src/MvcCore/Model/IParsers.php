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

/**
 * @phpstan-type RawValue int|float|string|bool|\DateTime|\DateTimeImmutable|array<mixed,mixed>|object
 */
interface IParsers {
	
	/**
	 * Try to convert raw database value into first type in target types.
	 * @param  RawValue      $rawValue
	 * @param  array<string> $typesString
	 * @param  array<mixed>  $parserArgs
	 * This argument is used in extended model only.
	 * @return RawValue Converted result.
	 */
	public static function ParseToTypes ($rawValue, $typesString, $parserArgs = []);
	
}