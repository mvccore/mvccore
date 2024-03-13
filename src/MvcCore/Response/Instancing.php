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

/**
 * @mixin \MvcCore\Response
 */
trait Instancing {

	/**
	 * @inheritDoc
	 * @param  int|NULL                                   $code
	 * @param  array<string,string|int|array<string|int>> $headers
	 * @param  string                                     $body
	 * @return \MvcCore\Response
	 */
	public static function CreateInstance (
		$code = NULL,
		$headers = [],
		$body = ''
	) {
		$code = $code ?: \MvcCore\IResponse::OK;
		$responseClass = \MvcCore\Application::GetInstance()->GetResponseClass();
		return new $responseClass($code, $headers, $body);
	}

	/**
	 * Create new HTTP response instance.
	 * @param  int                                        $code
	 * @param  array<string,string|int|array<string|int>> $headers
	 * @param  string                                     $body
	 * @return void
	 */
	public function __construct (
		$code = NULL,
		$headers = [],
		$body = ''
	) {
		$this->code = $code ?: \MvcCore\IResponse::OK;
		$this->headers = $headers;
		$this->body = $body;
		$this->request = \MvcCore\Application::GetInstance()->GetRequest();
	}
}
