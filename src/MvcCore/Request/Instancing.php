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

namespace MvcCore\Request;

trait Instancing
{
	/**
	 * Static factory to get every time new instance of http request object.
	 * Global variables for constructor arguments (`$_SERVER`, `$_GET`, `$_POST`...)
	 * should be changed to any arrays with any values and injected here to get
	 * different request object then currently called real request object.
	 * For example to create fake request object for testing purposes
	 * or for non-real request rendering into request output cache.
	 * @param array $server
	 * @param array $get
	 * @param array $post
	 * @param array $cookie
	 * @param array $files
	 * @return \MvcCore\Request|\MvcCore\IRequest
	 */
	public static function CreateInstance (
		array & $server = [],
		array & $get = [],
		array & $post = [],
		array & $cookie = [],
		array & $files = []
	) {
		if (!func_get_args()) 
			list($server, $get, $post, $cookie, $files) = [& $_SERVER, & $_GET, & $_POST, & $_COOKIE, & $_FILES];
		$requestClass = \MvcCore\Application::GetInstance()->GetRequestClass();
		return new $requestClass($server, $get, $post, $cookie, $files);
	}

	/**
	 * Create new instance of http request object.
	 * Global variables for constructor arguments (`$_SERVER`, `$_GET`, `$_POST`...)
	 * should be changed to any arrays with any values and injected here to get
	 * different request object then currently called real request object.
	 * For example to create fake request object for testing purposes
	 * or for non-real request rendering into request output cache.
	 * @param array $server
	 * @param array $get
	 * @param array $post
	 * @param array $cookie
	 * @param array $files
	 * @return void
	 */
	public function __construct (
		array & $server = [],
		array & $get = [],
		array & $post = [],
		array & $cookie = [],
		array & $files = []
	) {
		self::$routerClass = self::$routerClass ?: \MvcCore\Application::GetInstance()->GetRouterClass();
		$this->globalServer = & $server;
		$this->globalGet = & $get;
		$this->globalPost = & $post;
		$this->globalCookies = & $cookie;
		$this->globalFiles = & $files;
		$this->initCli();
	}

	/**
	 * Initialize all possible protected values from all global variables,
	 * including all http headers, all params and application inputs.
	 * This method is not recommended to use in production mode, it's
	 * designed mostly for development purposes, to see in one moment,
	 * what could be inside request after calling any getter method.
	 * @return \MvcCore\Request|\MvcCore\IRequest
	 */
	public function & InitAll () {
		/** @var $this \MvcCore\Request */
		$this->GetScriptName();
		$this->GetAppRoot();
		$this->GetMethod();
		$this->GetBasePath();
		$this->GetScheme();
		$this->IsSecure();
		$this->GetHostName();
		$this->GetHost();
		$this->GetRequestPath();
		$this->GetFullUrl();
		$this->GetReferer();
		$this->GetMicrotime();
		$this->IsAjax();
		if ($this->port === NULL) $this->initUrlSegments();
		if ($this->headers === NULL) $this->initHeaders();
		if ($this->params === NULL) $this->initParams();
		$this->GetServerIp();
		$this->GetClientIp();
		$this->GetContentLength();
		return $this;
	}
}
