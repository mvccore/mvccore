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

/**
 * @mixin \MvcCore\Request
 */
trait Props {

	/**
	 * List of exceptional two-segment top-level domain like
	 * `'co.jp', 'co.uk', 'co.kr', 'co.nf' ...` to parse
	 * domain string correctly.
	 * @var array<string,int|bool>
	 */
	protected static $twoSegmentTlds = ['co.jp'=>1,'ac.uk'=>1,'co.uk'=>1,'co.kr'=>1,'co.nl'=>1,'in.ua'=>1,'co.nf'=>1,'ny.us'=>1,'co.us'=>1];

	/**
	 * List of default ports, not defined in server name by default.
	 * @var array<string|int,int|bool>
	 */
	protected static $defaultPorts = ['80'=>1,'443'=>1];

	/**
	 * Configured router full class name string from core, loaded in `__constructor()`.
	 * @var ?string    
	 */
	protected static $routerClass = NULL;

	/**
	 * Reference to `\MvcCore\Application::GetInstance();`
	 * @var \MvcCore\Application|null
	 */
	protected static $app = NULL;

	/**
	 * String name or resource for request input stream.
	 * Example: `'php://input' | STDIN`
	 * @var string|resource|null
	 */
	protected $inputStream		= NULL;

	/**
	 * `TRUE` if PHP `php_sapi_name()` is `cli` and also
	 * if there is no `$_SERVER['REQUEST_URI']` defined.
	 * @var ?bool    
	 */
	protected $cli				= NULL;

	/**
	 * Language international code, lower case, not used by default.
	 * To use this variable - install  `\MvcCore\Router` extension `\MvcCore\Ext\Router\Lang`
	 * Or use this variable by your own decision.
	 * Example: `"en" | "de"`
	 * @var ?string    
	 */
	protected $lang				= NULL;

	/**
	 * Country/locale code, upper case, not used by default.
	 * To use this variable - install `\MvcCore\Router` extension `\MvcCore\Ext\Router\Lang`
	 * Or use this variable by your own decision.
	 * Example: `"US" | "UK"`
	 * @var ?string    
	 */
	protected $locale			= NULL;

	/**
	 * Media site key - `"full" | "tablet" | "mobile"`.
	 * To use this variable - install `\MvcCore\Router` extension `\MvcCore\Ext\Routers\Media`
	 * Or use this variable by your own decision.
	 * Example: `"full" | "tablet" | "mobile"`
	 * @var ?string    
	 */
	protected $mediaSiteVersion = NULL;

	/**
	 * Http scheme: `"http:" | "https:"`
	 * Example: `"http:"`
	 * @var ?string    
	 */
	protected $scheme			= NULL;

	/**
	 * `TRUE` if http scheme is `"https:"`
	 * @var ?bool    
	 */
	protected $secure			= NULL;

	/**
	 * Application server name - domain without any port.
	 * Example: `"localhost"`
	 * @var ?string    
	 */
	protected $hostName			= NULL;

	/**
	 * Application host with port if there is any.
	 * Example: `"localhost:88"`
	 * @var ?string    
	 */
	protected $host				= NULL;

	/**
	 * Http port defined in requested URI if any.
	 * Empty string if there is no port number in requested address.`.
	 * Example: `"88" | ""`
	 * @var ?string    
	 */
	protected $port				= NULL;

	/**
	 * Parsed server name (domain without port) parts.
	 * Example: `['any.content', 'example', 'co.uk'] | [NULL, NULL, 'localhost']`
	 * @var ?array<string>
	 */
	protected $domainParts		= NULL;

	/**
	 * `TRUE` if http port defined in requested URI.
	 * @var bool
	 */
	protected $portDefined		= FALSE;

	/**
	 * Requested path in from application root (if `mod_rewrite` enabled), never with query string.
	 * Example: `"/products/page/2"`
	 * @var ?string    
	 */
	protected $path				= NULL;

	/**
	 * URL query string without question mark.
	 * Example: `"param-1=value-1&param-2=value-2&param-3[]=value-3-a&param-3[]=value-3-b"`
	 * @var ?string    
	 */
	protected $query			= NULL;

	/**
	 * URL fragment including hash.
	 * Example: `"#any-sublink-path"`
	 * @var ?string    
	 */
	protected $fragment			= NULL;

	/**
	 * `TRUE` if request is requested from browser by `XmlHttpRequest` object
	 * with http header: `X-Requested-With: AnyJavascriptFrameworkName`, `FALSE` otherwise.
	 * @var ?bool    
	 */
	protected $ajax				= NULL;

	/**
	 * Php requested script name path from application root.
	 * Example: `"/index.php"`
	 * @var ?string    
	 */
	protected $scriptName		= NULL;

	/**
	 * Application root path.
	 * This value is always the same with webserver document root for single file projects.
	 * Example: `"C:/www/my/development/project"`
	 * @var ?string    
	 */
	protected $appRoot			= NULL;

	/**
	 * Webserver document root path.
	 * This value is always the same with application root for single file projects.
	 * Example: `"C:/www/my/development/project/www"`
	 * @var ?string    
	 */
	protected $documentRoot		= NULL;

	/**
	 * Base app directory path after domain, if application is placed in domain subdirectory
	 * Example:
	 * - full URI:  `"http://localhost:88/my/development/directory/www/requested/path/after/domain?with=possible&query=string"`
	 * - base path: `"/my/development/directory/www"`
	 * @var ?string    
	 */
	protected $basePath			= NULL;

	/**
	 * Request path after domain with possible query string
	 * Example: `"/requested/path/after/app/root?with=possible&query=string"`
	 * @var ?string    
	 */
	protected $requestPath		= NULL;

	/**
	 * Url to requested domain and possible port.
	 * Example: `"https://domain.com" | "http://domain:88"` if any port.
	 * @var ?string    
	 */
	protected $domainUrl		= NULL;

	/**
	 * Base URI to application root.
	 * Example: `"http://domain:88/my/development/directory/www"`
	 * @var ?string    
	 */
	protected $baseUrl			= NULL;

	/**
	 * Request URI including scheme, domain, port, path, without any query string
	 * Example: "`http://localhost:88/my/development/directory/www/requested/path/after/domain"`
	 * @var ?string    
	 */
	protected $requestUrl		= NULL;

	/**
	 * Request URI including scheme, domain, port, path and with query string
	 * Example: `"http://localhost:88/my/development/directory/www/requested/path/after/domain?with=possible&query=string"`
	 * @var ?string    
	 */
	protected $fullUrl			= NULL;

	/**
	 * Http method (upper case) - `GET`, `POST`, `PUT`, `HEAD`...
	 * Example: `"GET"`
	 * @var ?string    
	 */
	protected $method			= NULL;

	/**
	 * Referer URL if any.
	 * Example: `"http://foreing.domain.com/path/where/is/link/to/?my=app"`
	 * @var ?string    
	 */
	protected $referer			= NULL;

	/**
	 * Server IP address string.
	 * Example: `"127.0.0.1" | "111.222.111.222"`
	 * @var ?string    
	 */
	protected $serverIp			= NULL;

	/**
	 * Client IP address string.
	 * Example: `"127.0.0.1" | "222.111.222.111"`
	 * @var ?string    
	 */
	protected $clientIp			= NULL;

	/**
	 * Integer value from global `$_SERVER['CONTENT_LENGTH']`,
	 * `NULL` if no value presented in global `$_SERVER` array.
	 * Example: `123456 | NULL`
	 * @var ?int    
	 */
	protected $contentLength	= NULL;

	/**
	 * Timestamp of the start of the request, with microsecond precision.
	 * @var ?float
	 */
	protected $microtime		= NULL;

	/**
	 * All raw http headers without any conversion, initialized by
	 * `getallheaders()` or from `$_SERVER['HTTP_...']`.
	 * Headers are `key => value` array, headers keys are
	 * in standard format like: `"Content-Type" | "Content-Length" | "X-Requested-With" ...`.
	 * @var ?array<string,mixed>
	 */
	protected $headers			= NULL;

	/**
	 * Raw request body, usually from `file_get_contents('php://input');`.
	 * @var ?string    
	 */
	protected $body				= NULL;

	/**
	 * Raw request params array, with keys defined in route or by query string,
	 * always with controller and action keys completed by router.
	 * Do not read this `$params` array directly, read it's values by:
	 * `\MvcCore\Request::GetParam($paramName, $allowedChars, $defaultValueIfNull, $targetType);`.
	 * Example:
	 * ````
	 *   \MvcCore\Request:$params = [
	 *       "controller" => "default",
	 *       "action"     => "default",
	 *       "username"   => "' OR 1=1;-- ", // be careful for this content with raw (danger) value!
	 *   ];
	 *   // Do not read `$params` array directly,
	 *   // to get safe param value use:
	 *   \MvcCore\Request::GetParam("username", "a-zA-Z0-9_");
	 *   // returns `OR` string without danger chars.
	 * ````
	 * @var ?array<string,mixed>
	 */
	protected $params			= NULL;

	/**
	 * Array with colections defining params collection sources.
	 * @var array<int,array<string,bool>>
	 */
	protected $paramsSources	= [];

	/**
	 * Request flag if request targets internal package asset or not,
	 * - 0 => Means request is `Controller:Asset` call for internal package asset.
	 * - 1 => Means request is classic application request.
	 * @var ?bool    
	 */
	protected $appRequest		= NULL;

	/**
	 * Cleaned input param `"controller"`, containing only chars: `"a-zA-Z0-9\-_/"`.
	 * @var ?string    
	 */
	protected $controllerName	= NULL;

	/**
	 * Cleaned input param `"action"`, containing only chars: `"a-zA-Z0-9\-_/"`.
	 * @var ?string    
	 */
	protected $actionName		= NULL;

	/**
	 * Content of referenced `$_SERVER` global variable.
	 * @var array<string,mixed>
	 */
	protected $globalServer	= [];

	/**
	 * Content of referenced `$_GET` global variable.
	 * @var array<string,mixed>
	 */
	protected $globalGet		= [];

	/**
	 * Content of referenced `$_POST` global variable.
	 * @var array<int|string,mixed>
	 */
	protected $globalPost		= [];

	/**
	 * Content of referenced `$_COOKIE` global variable.
	 * @var array<string,string>
	 */
	protected $globalCookies	= [];

	/**
	 * Content of referenced `$_FILES` global variable.
	 * @var array<string,array<string,mixed>>
	 */
	protected $globalFiles		= [];
}
