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

namespace MvcCore\Request;

interface IInternalInits {
	
	/**
	 * Parse list of comma separated language tags and sort it by the
	 * quality value from `$this->globalServer['HTTP_ACCEPT_LANGUAGE']`.
	 * @param  string $languagesList
	 * @return array<int,array<array{0:string,1:?string}>>
	 */
	public static function ParseHttpAcceptLang ($languagesList);

}
