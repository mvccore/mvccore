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

trait Instancing {

	/**
	 * @inheritDocs
	 * @param int|NULL	$code
	 * @param array		$headers
	 * @param string	$body
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
	 * @param int		$code
	 * @param array		$headers
	 * @param string	$body
	 * @return void
	 */
	public function __construct (
		$code = NULL,
		$headers = [],
		$body = ''
	) {
		/** @var $this \MvcCore\Response */
		$this->code = $code ?: \MvcCore\IResponse::OK;
		$this->headers = $headers;
		$this->body = $body;
		$this->request = \MvcCore\Application::GetInstance()->GetRequest();
	}
}
