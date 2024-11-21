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

namespace MvcCore\Route;

interface IMatching {
	
	/**
	 * Return array of matched params if incoming request match this route
	 * or `NULL` if doesn't. Returned array must contain all matched reverse 
	 * params with matched controller and action names by route and by matched 
	 * params. Route is matched usually if request property `path` matches by 
	 * PHP `preg_match_all()` route `match` pattern. Sometimes, matching subject 
	 * could be different if route specifies it - if route `pattern` (or `match`) 
	 * property contains domain (or base path part) - it means if it is absolute 
	 * or if `pattern` (or `match`) property contains a query string part.
	 * This method is usually called in core request routing process
	 * from `\MvcCore\Router::Route();` method and it's sub-methods.
	 * @param  \MvcCore\Request $request The request object instance.
	 * @throws \LogicException           Route configuration property is missing.
	 * @throws \InvalidArgumentException Wrong route pattern format.
	 * @return array<string,mixed>       Matched and params array, keys are matched
	 *                                   params or controller and action params.
	 */
	public function Matches (\MvcCore\IRequest $request);

}
