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

namespace MvcCore;

/**
 * Responsibility - describing request(s) to match and reversely build URL addresses.
 * - Describing request to match and target it (read more about properties).
 * - Matching request by given request object, see `\MvcCore\Route::Matches()`.
 * - Completing URL address by given params array, see `\MvcCore\Route::Url()`.
 */
interface	IRoute
extends		\MvcCore\Route\IConstants,
			\MvcCore\Route\IInstancing,
			\MvcCore\Route\IGettersSetters,
			\MvcCore\Route\IInternalInits,
			\MvcCore\Route\IMatching,
			\MvcCore\Route\IUrlBuilding {
}
