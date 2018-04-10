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

namespace MvcCore\Interfaces;

require_once(__DIR__.'/../MvcCore.php');

/**
 * - Linear request url parsing from `$_SERVER` global variable
 *   (as constructor argument) into local properties describing url sections.
 * - Params reading from `$_GET` and `$_POST` global variables
 *   (as constructor arguments) or readed from direct PHP input: `"php://input"` (in JSON or in query string).
 * - Params recursive cleaning by called developer rules.
 */
interface IRequest
{
	/**
	 * Non-secured HTTP protocol (http:).
	 */
	const PROTOCOL_HTTP = 'http:';

	/**
	 * Secured HTTP(s) protocol (https:).
	 */
	const PROTOCOL_HTTPS = 'https:';

	/**
	 * Retrieves the information or entity that is identified by the URI of the request.
	 */
	const METHOD_GET = 'GET';

	/**
	 * Posts a new entity as an addition to a URI.
	 */
	const METHOD_POST = 'POST';

	/**
	 * Replaces an entity that is identified by a URI.
	 */
	const METHOD_PUT = 'PUT';

	/**
	 * Requests that a specified URI be deleted.
	 */
	const METHOD_DELETE = 'DELETE';

	/**
	 * Retrieves the message headers for the information or entity that is identified by the URI of the request.
	 */
	const METHOD_HEAD = 'HEAD';

	/**
	 * Represents a request for information about the communication options available on the request/response chain identified by the Request-URI.
	 */
	const METHOD_OPTIONS = 'OPTIONS';

	/**
	 * Requests that a set of changes described in the request entity be applied to the resource identified by the Request- URI.
	 */
	const METHOD_PATCH = 'PATCH';


	/**
	 * Static factory to get everytime new instance of http request object.
	 * Global variables for testing or non-real request rendering should be changed
	 * and injected here to get different request object from currently called real request.
	 * @param array $server
	 * @param array $get
	 * @param array $post
	 * @return \MvcCore\Interfaces\IRequest
	 */
	public static function GetInstance (array & $server, array & $get, array & $post);

	/**
	 * Return `TRUE` boolean flag if request target
	 * is anything different than `Controller:Asset`.
	 * @return bool
	 */
	public function IsAppRequest ();

	/**
	 * Set directly all raw parameters without any conversion at once.
	 * @param array $params
	 * @return \MvcCore\Interfaces\IRequest
	 */
	public function & SetParams (& $params = array());

	/**
	 * Set directly raw parameter value without any conversion.
	 * @param string $name
	 * @param string|string[] $value
	 * @return \MvcCore\Interfaces\IRequest
	 */
	public function & SetParam ($name = "", $value = "");

	/**
	 * Get param value from `$_GET` or `$_POST` or `php://input`,
	 * filtered by characters defined in second argument throught `preg_replace()`.
	 * Place into second argument only char groups you want to keep.
	 * @param string $name Parametter string name.
	 * @param string $pregReplaceAllowedChars List of regular expression characters to only keep.
	 * @param mixed $ifNullValue Default value returned if given param name is null.
	 * @param string $targetType Target type to retype param value or default if-null value. If param is an array, every param item will be retyped into given target type.
	 * @return string|string[]
	 */
	public function GetParam (
		$name = "",
		$pregReplaceAllowedChars = "a-zA-Z0-9_/\-\.\@",
		$ifNullValue = NULL,
		$targetType = NULL
	);

	/**
	 * Return cleaned requested controller name from `\MvcCore\Request::$Params['controller'];`.
	 * @return string
	 */
	public function GetControllerName ();

	/**
	 * Return cleaned requested action name from `\MvcCore\Request::$Params['action'];`.
	 * @return string
	 */
	public function GetActionName ();

	/**
	 * Sets any custom property `"PropertyName"` by `\MvcCore\Request::SetPropertyName("value")`,
	 * which is not necessary to define previously or gets previously defined
	 * property `"PropertyName"` by `\MvcCore\Request::GetPropertyName();`.
	 * Throws exception if no property defined by get call or if virtual call
	 * begins with anything different from 'Set' or 'Get'.
	 * This method returns custom value for get and `\MvcCore\Request` instance for set.
	 * @param string $rawName
	 * @param array  $arguments
	 * @throws \Exception
	 * @return mixed|\MvcCore\Interfaces\IRequest
	 */
	public function __call ($rawName, $arguments = array());

	/**
	 * Universal getter, if property not defined, `NULL` is returned.
	 * @param string $name
	 * @return mixed
	 */
	public function __get ($name);

	/**
	 * Universal setter, if property not defined, it's automaticly declarated.
	 * @param string $name
	 * @param mixed	 $value
	 * @return \MvcCore\Interfaces\IRequest
	 */
	public function __set ($name, $value);
}
