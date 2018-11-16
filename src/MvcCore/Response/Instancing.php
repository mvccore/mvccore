<?php

/**
 * MvcCore
 *
 * This source file is subject to the BSD 3 License
 * For the full copyright and license information, please view
 * the LICENSE.md file that are distributed with this source code.
 *
 * @copyright	Copyright (c) 2016 Tom Flídr (https://github.com/mvccore/mvccore)
 * @license  https://mvccore.github.io/docs/mvccore/5.0.0/LICENCE.md
 */

namespace MvcCore\Response;

trait Instancing
{
	/**
	 * No singleton, get every time new instance of configured HTTP response
	 * class in `\MvcCore\Application::GetInstance()->GetResponseClass();`.
	 * @param int		$code
	 * @param array		$headers
	 * @param string	$body
	 * @return \MvcCore\Response|\MvcCore\IResponse
	 */
	public static function CreateInstance (
		$code = \MvcCore\IResponse::OK,
		$headers = [],
		$body = ''
	) {
		$responseClass = \MvcCore\Application::GetInstance()->GetResponseClass();
		return new $responseClass($code, $headers, $body);
	}

	/**
	 * Create new HTTP response instance.
	 * @param int		$code
	 * @param array		$headers
	 * @param string	$body
	 * @return void
	 */
	public function __construct (
		$code = \MvcCore\IResponse::OK,
		$headers = [],
		$body = ''
	) {
		$this->code = $code;
		$this->headers = $headers;
		$this->body = $body;
	}
}
