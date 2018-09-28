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

//include_once(__DIR__ . '/IResponse.php');

use \MvcCore\IResponse;

/**
 * Responsibility - completing all information for response - headers (cookies) and content.
 * - HTTP response wrapper carrying response headers and response body.
 * - PHP `setcookie` function wrapper to complete default values such domain or http only etc.
 * - Sending response at application terminate process by `\MvcCore\IResponse::Send();`.
 * - Completing MvcCore performance header at response end.
 */
class Response implements IResponse
{
	public static $CodeMessages = [
		IResponse::OK						=> 'OK',
		IResponse::MOVED_PERMANENTLY		=> 'Moved Permanently',
		IResponse::SEE_OTHER				=> 'See Other',
		IResponse::NOT_FOUND				=> 'Not Found',
		IResponse::INTERNAL_SERVER_ERROR	=> 'Internal Server Error',
	];

	/**
	 * Response HTTP code.
	 * Example: `200 | 301 | 404`
	 * @var int|NULL
	 */
	protected $code = NULL;

	/**
	 * Response HTTP headers as `key => value` array.
	 * Example:
	 *	`array(
	 *		'Content-Type'		=> 'text/html',
	 *		'Content-Encoding'	=> 'utf-8'
	 *	);`
	 * @var \string[]
	 */
	protected $headers = [];

	/**
	 * Response content encoding.
	 * Example: `"utf-8" | "windows-1250" | "ISO-8859-2"`
	 * @var \string|NULL
	 */
	protected $encoding = NULL;

	/**
	 * Response HTTP body.
	 * Example: `"<!DOCTYPE html><html lang="en"><head><meta ..."`
	 * @var \string|NULL
	 */
	protected $body = NULL;

	/**
	 * `TRUE` if headers or body has been sent.
	 * @var bool
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
	 * @return \MvcCore\Response
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

	/**
	 * Set HTTP response code.
	 * @param int $code
	 * @return \MvcCore\Response
	 */
	public function & SetCode ($code) {
		$this->code = $code;
		http_response_code($code);
		return $this;
	}

	/**
	 * Get HTTP response code.
	 * @return int
	 */
	public function GetCode () {
		if ($this->code === NULL) {
			$phpCode = http_response_code();
			$this->code = $phpCode === FALSE ? static::OK : $phpCode;
		}
		return $this->code;
	}

	/**
	 * Set multiple HTTP response headers as `key => value` array.
	 * All given headers are automaticly merged with previously setted headers.
	 * If you change second argument to true, all previous request object and PHP
	 * headers are removed and given headers will be only headers for output.
	 * There is automaticly set response encoding from value for
	 * `Content-Type` header, if contains any `charset=...`.
	 * There is automaticly set response encoding from value for
	 * `Content-Encoding` header.
	 * Example: `$request->SetHeader(array('Content-Type' => 'text/plain; charset=utf-8'));`
	 * @param array $headers
	 * @param bool $cleanAllPrevious `FALSE` by default. If `TRUE`, all previous headers
	 *								 set by PHP `header()` or by this object will be removed.
	 * @return \MvcCore\Response
	 */
	public function & SetHeaders (array $headers = [], $cleanAllPrevious = FALSE) {
		if ($cleanAllPrevious) {
			$this->UpdateHeaders();
			foreach ($this->headers as $name => $value) header_remove($name);
			$this->headers = [];
		}
		foreach ($headers as $name => $value) {
			$this->SetHeader($name, $value);
		}
		return $this;
	}

	/**
	 * Set HTTP response header.
	 * There is automaticly set response encoding from value for
	 * `Content-Type` header, if contains any `charset=...`.
	 * There is automaticly set response encoding from value for
	 * `Content-Encoding` header.
	 * Example: `$request->SetHeader('Content-Type', 'text/plain; charset=utf-8');`
	 * @param string $name
	 * @param string $value
	 * @return \MvcCore\Response
	 */
	public function & SetHeader ($name, $value) {
		header($name . ": " . $value);
		$this->headers[$name] = $value;
		if ($name === 'Content-Type') {
			$charsetPos = strpos($value, 'charset');
			if ($charsetPos !== FALSE) {
				$equalPos = strpos($value, '=', $charsetPos);
				if ($equalPos !== FALSE) $this->SetEncoding(
					trim(substr($value, $equalPos + 1))
				);
			}
		}
		if ($name === 'Content-Encoding') $this->encoding = $value;
		return $this;
	}

	/**
	 * Get HTTP response header by name. If header dowsn't exists, null is returned.
	 * Example: `$request->GetHeader('Content-Type'); // returns 'text/plain; charset=utf-8'`
	 * @param string $name
	 * @return string|NULL
	 */
	public function GetHeader ($name) {
		return isset($this->headers[$name]) ? $this->headers[$name] : NULL;
	}

	/**
	 * Get if response has any HTTP response header by given `$name`.
	 * Example:
	 *	`$request->GetHeader('Content-Type'); // returns TRUE if there is header 'Content-Type'
	 *	`$request->GetHeader('content-type'); // returns FALSE if there is header 'Content-Type'
	 * @param string $name
	 * @return bool
	 */
	public function HasHeader ($name) {
		return isset($this->headers[$name]);
	}

	/**
	 * Set HTTP response content encoding.
	 * Example: `$response->SetEncoding('utf-8');`
	 * @param string $encoding
	 * @return \MvcCore\Response
	 */
	public function & SetEncoding ($encoding = 'utf-8') {
		$this->encoding = $encoding;
		$this->headers['Content-Encoding'] = $encoding;
		return $this;
	}

	/**
	 * Get HTTP response content encoding.
	 * Example: `$response->GetEncoding(); // returns 'utf-8'`
	 * @return string|NULL
	 */
	public function GetEncoding () {
		return $this->encoding;
	}

	/**
	 * Set HTTP response body.
	 * @param string $body
	 * @return \MvcCore\Response
	 */
	public function & SetBody ($body) {
		$this->body = & $body;
		return $this;
	}

	/**
	 * Prepend HTTP response body.
	 * @param string $body
	 * @return \MvcCore\Response
	 */
	public function & PrependBody ($body) {
		$this->body = $body . $this->body;
		return $this;
	}

	/**
	 * Append HTTP response body.
	 * @param string $body
	 * @return \MvcCore\Response
	 */
	public function & AppendBody ($body) {
		$this->body .= $body;
		return $this;
	}

	/**
	 * Get HTTP response body.
	 * @return string|NULL
	 */
	public function & GetBody () {
		return $this->body;
	}

	/**
	 * Consolidate all headers from PHP response
	 * by calling `headers_list()` into local headers list.
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
  			$this->headers[$name] = $value;
		}
	}

	/**
	 * Return if response has any redirect `"Location: ..."` header inside.
	 * @return bool
	 */
	public function IsRedirect () {
		return isset($this->headers['Location']);
	}

	/**
	 * Returns if response has any `text/html` or `application/xhtml+xml`
	 * substring in `Content-Type` header.
	 * @return bool
	 */
	public function IsHtmlOutput () {
		if (isset($this->headers['Content-Type'])) {
			$value = $this->headers['Content-Type'];
			return strpos($value, 'text/html') !== FALSE || strpos($value, 'application/xhtml+xml') !== FALSE;
		}
		return FALSE;
	}

	/**
	 * Returns if response has any `xml` substring in `Content-Type` header.
	 * @return bool
	 */
	public function IsXmlOutput () {
		if (isset($this->headers['Content-Type'])) {
			$value = $this->headers['Content-Type'];
			return strpos($value, 'xml') !== FALSE;
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
		$code = $this->GetCode();
		$status = isset(static::$CodeMessages[$code]) ? ' ' . static::$CodeMessages[$code] : '';
		if (!isset($this->headers['Content-Encoding'])) {
			if (!$this->encoding) $this->encoding = 'utf-8';
			$this->headers['Content-Encoding'] = $this->encoding;
		}
		header("HTTP/1.0 $code $status");
		foreach ($this->headers as $name => $value) {
			if ($name == 'Content-Type') {
				$charsetMatched = FALSE;
				$charsetPos = strpos($value, 'charset');
				if ($charsetPos !== FALSE) {
					$equalPos = strpos($value, '=', $charsetPos);
					if ($equalPos !== FALSE) $charsetMatched = TRUE;
				}
				if (!$charsetMatched) $value .= ';charset=' . $this->encoding;
			}
			header($name . ": " . $value);
		}
		$this->addTimeAndMemoryHeader();
		echo $this->body;
		if (ob_get_level()) echo ob_get_clean();
		$this->sent = TRUE;
	}

	/**
	 * Send a cookie.
	 * @param string $name			Cookie name. Assuming the name is `cookiename`, this value is retrieved through `$_COOKIE['cookiename']`.
	 * @param string $value			The value of the cookie. This value is stored on the clients computer; do not store sensitive information.
	 * @param int    $lifetime		Life time in seconds to expire. 0 means "until the browser is closed".
	 * @param string $path			The path on the server in which the cookie will be available on. If set to '/', the cookie will be available within the entire domain.
	 * @param string $domain		If not set, value is completed by `\MvcCore\Application::GetInstance()->GetRequest()->GetServerName();` .
	 * @param bool   $secure		If not set, value is completed by `\MvcCore\Application::GetInstance()->GetRequest()->IsSecure();`.
	 * @param bool   $httpOnly		HTTP only cookie, `TRUE` by default.
	 * @throws \RuntimeException	If HTTP headers have been sent.
	 * @return bool					True if cookie has been set.
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
		return \setcookie(
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
	 * @param string $name			Cookie name. Assuming the name is `cookiename`, this value is retrieved through `$_COOKIE['cookiename']`.
	 * @param string $path			The path on the server in which the cookie will be available on. If set to '/', the cookie will be available within the entire domain.
	 * @param string $domain		If not set, value is completed by `\MvcCore\Application::GetInstance()->GetRequest()->GetServerName();` .
	 * @param bool   $secure		If not set, value is completed by `\MvcCore\Application::GetInstance()->GetRequest()->IsSecure();`.
	 * @throws \RuntimeException	If HTTP headers have been sent.
	 * @return bool					True if cookie has been set.
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
