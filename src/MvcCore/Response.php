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

/**
 * Core response:
 * - http response wrapper carrying response headers and response body
 * - sending response at application terminate process by Send(); method
 * - completing MvcCore performance custom header at response sending
 */
class Response
{
	const OK = 200;
	const MOVED_PERMANENTLY = 301;
	const SEE_OTHER = 303;
	const NOT_FOUND = 404;
	const INTERNAL_SERVER_ERROR = 500;

	public static $CodeMessages = array(
		self::OK					=> 'OK',
		self::MOVED_PERMANENTLY		=> 'Moved Permanently',
		self::SEE_OTHER				=> 'See Other',
		self::NOT_FOUND				=> 'Not Found',
		self::INTERNAL_SERVER_ERROR	=> 'Internal Server Error',
	);

	/**
	 * Response http code
	 * @var int
	 */
	public $Code = self::OK;

	/**
	 * Response http headers
	 * @var array
	 */
	public $Headers = array();

	/**
	 * Response http body
	 * @var string
	 */
	public $Body = '';

	/**
	 * Get everytime new instance of http response.
	 * @param int		$code 
	 * @param array		$headers
	 * @param string	$body
	 * @return \MvcCore\Response
	 */
	public static function GetInstance ($code = self::OK, $headers = array(), $body = '') {
		$responseClass = \MvcCore::GetInstance()->GetResponseClass();
		return new $responseClass($code, $headers, $body);
	}

	/**
	 * Create new http response instance.
	 * @param int		$code
	 * @param array		$headers
	 * @param string	$body
	 */
	public function __construct ($code = self::OK, $headers = array(), $body = '') {
		$this->Code = $code;
		$this->Headers = $headers;
		$this->Body = $body;
	}

	/**
	 * Set http response code.
	 * @param int $code 
	 * @return \MvcCore\Response
	 */
	public function SetCode ($code) {
		$this->Code = $code;
		return $this;
	}

	/**
	 * Set http response header.
	 * @param string $name
	 * @param string $value 
	 * @return \MvcCore\Response
	 */
	public function SetHeader ($name, $value) {
		header($name . ": " . $value);
		$this->Headers[$name] = $value;
		return $this;
	}

	/**
	 * Set http response body.
	 * @param string $body 
	 * @return \MvcCore\Response
	 */
	public function SetBody ($body) {
		$this->Body = & $body;
		return $this;
	}

	/**
	 * Append http response body.
	 * @param string $body
	 * @return \MvcCore\Response
	 */
	public function PrependBody ($body) {
		$this->Body = $body . $this->Body;
		return $this;
	}

	/**
	 * Append http response body.
	 * @param string $body
	 * @return \MvcCore\Response
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
	 * Return if response has any Location header inside.
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
	 * Send all http headers and send response body.
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
	 * Add CPU and RAM usage header at HTML/JSON response end
	 */
	protected function addTimeAndMemoryHeader () {
		$mtBegin = \MvcCore::GetInstance()->GetMicrotime();
		$time = number_format((microtime(TRUE) - $mtBegin) * 1000, 1, '.', ' ');
		$ram = function_exists('memory_get_peak_usage') ? number_format(memory_get_peak_usage() / 1000000, 2, '.', ' ') : 'n/a';
		header("X-MvcCore-Cpu-Ram: $time ms, $ram MB");
	}
}