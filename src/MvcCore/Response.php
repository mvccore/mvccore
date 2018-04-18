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

namespace MvcCore;

//include_once(__DIR__ . '/Interfaces/IResponse.php');

use \MvcCore\Interfaces\IResponse;

/**
 * Responsibility - completing all information for response - headers (cookies) and content.
 * - HTTP response wrapper carrying response headers and response body.
 * - PHP `setcookie` function wrapper to complete default values such domain or http only etc.
 * - Sending response at application terminate process by `\MvcCore\Interfaces\IResponse::Send();`.
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
	 * `TRUE` if headers or body has been sent.
	 * @var string
	 */
	protected $sent = FALSE;


	/**
	 * No singleton, get everytime new instance of configured HTTP response
	 * class in `\MvcCore\Application::GetInstance()->GetResponseClass();`.
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
	 * @return \MvcCore\Response
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
	 * @return \MvcCore\Response
	 */
	public function & SetCode ($code) {
		$this->Code = $code;
		return $this;
	}

	/**
	 * Set HTTP response header.
	 * @param string $name
	 * @param string $value
	 * @return \MvcCore\Response
	 */
	public function & SetHeader ($name, $value) {
		header($name . ": " . $value);
		$this->Headers[$name] = $value;
		return $this;
	}

	/**
	 * Set multiple HTTP response headers as `key => value` array.
	 * @param array $headers
	 * @return \MvcCore\Response
	 */
	public function & SetHeaders (array $headers = array()) {
		foreach ($headers as $name => $value) {
			$this->Headers[$name] = $value;
		}
		return $this;
	}

	/**
	 * Set HTTP response body.
	 * @param string $body
	 * @return \MvcCore\Response
	 */
	public function & SetBody ($body) {
		$this->Body = & $body;
		return $this;
	}

	/**
	 * Prepend HTTP response body.
	 * @param string $body
	 * @return \MvcCore\Response
	 */
	public function PrependBody ($body) {
		$this->Body = $body . $this->Body;
		return $this;
	}

	/**
	 * Append HTTP response body.
	 * @param string $body
	 * @return \MvcCore\Response
	 */
	public function AppendBody ($body) {
		$this->Body .= $body;
		return $this;
	}

	/**
	 * Consolidate headers array from PHP response headers array by calling `headers_list()`.
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
	 * `TRUE` if headers or body has been sent.
	 * @return bool
	 */
	public function IsSent () {
		return $this->sent || headers_sent();
	}

	/**
	 * Send all HTTP headers and send response body.
	 * @return void
	 */
	public function Send () {
		if ($this->IsSent()) return;
		$code = $this->Code;
		$status = isset(static::$CodeMessages[$code]) ? ' ' . static::$CodeMessages[$code] : '';
		header("HTTP/1.0 $code $status");
		foreach ($this->Headers as $name => $value) {
			header($name . ": " . $value);
		}
		$this->addTimeAndMemoryHeader();
		echo $this->Body;
		$this->sent = TRUE;
	}

	/**
	 * Send a cookie.
	 * @param string $name        Cookie name. Assuming the name is `cookiename`, this value is retrieved through `$_COOKIE['cookiename']`.
	 * @param string $value       The value of the cookie. This value is stored on the clients computer; do not store sensitive information.
	 * @param int    $lifetime    Life time in seconds to expire. 0 means "until the browser is closed".
	 * @param string $path        The path on the server in which the cookie will be available on. If set to '/', the cookie will be available within the entire domain.
	 * @param string $domain      If not set, value is completed by `\MvcCore\Application::GetInstance()->GetRequest()->GetServerName();` .
	 * @param bool   $secure      If not set, value is completed by `\MvcCore\Application::GetInstance()->GetRequest()->IsSecure();`.
	 * @param bool   $httpOnly    HTTP only cookie, `TRUE` by default.
	 * @throws \RuntimeException  If HTTP headers have been sent.
	 * @return bool               True if cookie has been set.
	 */
	public function SetCookie (
		$name, $value,
		$lifetime = 0, $path = '/',
		$domain = NULL, $secure = NULL, $httpOnly = TRUE
	) {
		if ($this->IsSent()) throw new \RuntimeException(
			"[".__CLASS__."] Cannot set cookie after HTTP headers have been sent."
		);
		$request = \MvcCore\Application::GetInstance()->GetRequest();
		return setcookie(
			$name, $value,
			$lifetime === 0 ? 0 : time() + $lifetime,
			$path,
			$domain === NULL ? $request->GetServerName() : $domain,
			$secure === NULL ? $request->IsSecure() : $secure,
			$httpOnly
		);
	}

	/**
	 * Delete cookie - set value to empty string and
	 * set expiration to "until the browser is closed".
	 * @param string $name        Cookie name. Assuming the name is `cookiename`, this value is retrieved through `$_COOKIE['cookiename']`.
	 * @param string $path        The path on the server in which the cookie will be available on. If set to '/', the cookie will be available within the entire domain.
	 * @param string $domain      If not set, value is completed by `\MvcCore\Application::GetInstance()->GetRequest()->GetServerName();` .
	 * @param bool   $secure      If not set, value is completed by `\MvcCore\Application::GetInstance()->GetRequest()->IsSecure();`.
	 * @throws \RuntimeException  If HTTP headers have been sent.
	 * @return bool               True if cookie has been set.
	 */
	public function DeleteCookie ($name, $path = '/', $domain = NULL, $secure = NULL) {
		return $this->SetCookie($name, '', 0, $path, $domain, $secure);
	}

	/**
	 * Add CPU and RAM usage header at HTML/JSON response end.
	 * @return void
	 */
	protected function addTimeAndMemoryHeader () {
		$mtBegin = \MvcCore\Application::GetInstance()->GetRequest()->GetMicrotime();
		$time = number_format((microtime(TRUE) - $mtBegin) * 1000, 1, '.', ' ');
		$ram = function_exists('memory_get_peak_usage') ? number_format(memory_get_peak_usage() / 1000000, 2, '.', ' ') : 'n/a';
		header("X-MvcCore-Cpu-Ram: $time ms, $ram MB");
	}
}
