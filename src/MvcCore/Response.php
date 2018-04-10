<?php

/**
 * MvcCore
 *
 * This source file is subject to the BSD 3 License
 * For the full copyright and license information, please view
 * the LICENSE.md file that are distributed with this source code.
 *
 * @copyright	Copyright (c) 2016 Tom Flídr (https://github.com/mvccore/mvccore)
 * @license		https://mvccore.github.io/docs/mvccore/4.0.0/LICENCE.md
 */

namespace MvcCore;

require_once(__DIR__ . '/Interfaces/IResponse.php');

use \MvcCore\Interfaces\IResponse;

/**
 * - HTTP response wrapper carrying response headers and response body.
 * - Sending response at application terminate process by `\MvcCore\Interfaces\IResponse::Send();` method.
 * - Completing MvcCore performance header at response end.
 */
class Response implements Interfaces\IResponse
{
	public static $CodeMessages = array(
		IResponse::OK						=> 'OK',
		IResponse::MOVED_PERMANENTLY		=> 'Moved Permanently',
		IResponse::SEE_OTHER				=> 'See Other',
		IResponse::NOT_FOUND				=> 'Not Found',
		IResponse::INTERNAL_SERVER_ERROR	=> 'Internal Server Error',
	);

	/**
	 * Response HTTP code.
	 * @var int
	 */
	public $Code = self::OK;

	/**
	 * Response HTTP headers.
	 * @var array
	 */
	public $Headers = array();

	/**
	 * Response HTTP body.
	 * @var string
	 */
	public $Body = '';

	/**
	 * Get everytime calling this function new instance of HTTP response.
	 * @param int		$code
	 * @param array		$headers
	 * @param string	$body
	 * @return \MvcCore\Response
	 */
	public static function GetInstance (
		$code = \MvcCore\Interfaces\IResponse::OK,
		$headers = array(),
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
	 * @return \MvcCore\Interfaces\IResponse
	 */
	public function __construct (
		$code = \MvcCore\Interfaces\IResponse::OK,
		$headers = array(),
		$body = ''
	) {
		$this->Code = $code;
		$this->Headers = $headers;
		$this->Body = $body;
	}

	/**
	 * Set HTTP response code.
	 * @param int $code
	 * @return \MvcCore\Interfaces\IResponse
	 */
	public function & SetCode ($code) {
		$this->Code = $code;
		return $this;
	}

	/**
	 * Set HTTP response header.
	 * @param string $name
	 * @param string $value
	 * @return \MvcCore\Interfaces\IResponse
	 */
	public function & SetHeader ($name, $value) {
		header($name . ": " . $value);
		$this->Headers[$name] = $value;
		return $this;
	}

	/**
	 * Set HTTP response body.
	 * @param string $body
	 * @return \MvcCore\Interfaces\IResponse
	 */
	public function & SetBody ($body) {
		$this->Body = & $body;
		return $this;
	}

	/**
	 * Append HTTP response body.
	 * @param string $body
	 * @return \MvcCore\Interfaces\IResponse
	 */
	public function PrependBody ($body) {
		$this->Body = $body . $this->Body;
		return $this;
	}

	/**
	 * Append HTTP response body.
	 * @param string $body
	 * @return \MvcCore\Interfaces\IResponse
	 */
	public function AppendBody ($body) {
		$this->Body .= $body;
		return $this;
	}

	/**
	 * Consolidate headers array from php response headers array.
	 * @return void
	 */
	public function UpdateHeaders () {
		$rawHeaders = headers_list();
		$name = '';
		$value = '';
		foreach ($rawHeaders as $rawHeader) {
			$doubleDotPos = strpos($rawHeader, ':');
			if ($doubleDotPos !== FALSE) {
				$name = trim(substr($rawHeader, 0, $doubleDotPos));
				$value = trim(substr($rawHeader, $doubleDotPos + 1));
			} else {
				$name = $rawHeader;
				$value = '';
			}
  			$this->Headers[$name] = $value;
		}
	}

	/**
	 * Return if response has any redirect `"Location: ..."` header inside.
	 * @return bool
	 */
	public function IsRedirect () {
		return isset($this->Headers['Location']);
	}

	/**
	 * Return if response has any html/xhtml header inside.
	 * @return bool
	 */
	public function IsHtmlOutput () {
		if (isset($this->Headers['Content-Type'])) {
			$value = $this->Headers['Content-Type'];
			return strpos($value, 'text/html') !== FALSE || strpos($value, 'application/xhtml+xml') !== FALSE;
		}
		return FALSE;
	}

	/**
	 * Send all HTTP headers and send response body.
	 * @return void
	 */
	public function Send () {
		if (!headers_sent()) {
			$code = $this->Code;
			$status = isset(static::$CodeMessages[$code]) ? ' ' . static::$CodeMessages[$code] : '';
			header("HTTP/1.0 $code $status");
			foreach ($this->Headers as $name => $value) {
				header($name . ": " . $value);
			}
			$this->addTimeAndMemoryHeader();
		}
		echo $this->Body;
	}

	/**
	 * Add CPU and RAM usage header at HTML/JSON response end.
	 * @return void
	 */
	protected function addTimeAndMemoryHeader () {
		$mtBegin = \MvcCore\Application::GetInstance()->GetMicrotime();
		$time = number_format((microtime(TRUE) - $mtBegin) * 1000, 1, '.', ' ');
		$ram = function_exists('memory_get_peak_usage') ? number_format(memory_get_peak_usage() / 1000000, 2, '.', ' ') : 'n/a';
		header("X-MvcCore-Cpu-Ram: $time ms, $ram MB");
	}
}
