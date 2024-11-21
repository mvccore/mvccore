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

namespace MvcCore\Response;

interface IInstancing {

	/**
	 * No singleton, get every time new instance of configured HTTP response
	 * class in `\MvcCore\Application::GetInstance()->GetResponseClass();`.
	 * @param  int|NULL                                   $code
	 * @param  array<string,string|int|array<string|int>> $headers
	 * @param  string                                     $body
	 * @return \MvcCore\Response
	 */
	public static function CreateInstance (
		$code = NULL,
		$headers = [],
		$body = ''
	);

}
