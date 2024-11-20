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
trait Instancing {

	/**
	 * @inheritDoc
	 * @param  array<string,mixed>               $server
	 * @param  array<string,mixed>               $get
	 * @param  array<int|string,mixed>           $post
	 * @param  array<string,string>              $cookie
	 * @param  array<string,array<string,mixed>> $files
	 * @param  string|NULL                       $inputStream
	 * @return \MvcCore\Request
	 */
	public static function CreateInstance (
		array & $server = [],
		array & $get = [],
		array & $post = [],
		array & $cookie = [],
		array & $files = [],
		$inputStream = NULL
	) {
		if (!func_get_args())
			list($server, $get, $post, $cookie, $files) = [& $_SERVER, & $_GET, & $_POST, & $_COOKIE, & $_FILES];
		$app = self::$app ?: (self::$app = \MvcCore\Application::GetInstance());
		$requestClass = $app->GetRequestClass();
		return new $requestClass($server, $get, $post, $cookie, $files, $inputStream);
	}

	/**
	 * Create new instance of http request object.
	 * Global variables for constructor arguments (`$_SERVER`, `$_GET`, `$_POST`...)
	 * should be changed to any arrays with any values and injected here to get
	 * different request object then currently called real request object.
	 * For example to create fake request object for testing purposes
	 * or for non-real request rendering into request output cache.
	 * @param  array<string,mixed>               $server
	 * @param  array<string,mixed>               $get
	 * @param  array<int|string,mixed>           $post
	 * @param  array<string,string>              $cookie
	 * @param  array<string,array<string,mixed>> $files
	 * @param  string|NULL                       $inputStream
	 * @return void
	 */
	public function __construct (
		array & $server = [],
		array & $get = [],
		array & $post = [],
		array & $cookie = [],
		array & $files = [],
		$inputStream = NULL
	) {
		$app = self::$app ?: (self::$app = \MvcCore\Application::GetInstance());
		self::$routerClass = self::$routerClass ?: $app->GetRouterClass();
		$this->globalServer = & $server;
		$this->globalGet = & $get;
		$this->globalPost = & $post;
		$this->globalCookies = & $cookie;
		$this->globalFiles = & $files;
		$this->cli = php_sapi_name() === 'cli';
		$inputStreamDefault = $this->cli
			? 'php://stdin'
			: 'php://input';
		$this->inputStream = $inputStream !== NULL
			? $inputStream
			: $inputStreamDefault;
		if ($this->cli)
			$this->initCli();
	}

	/**
	 * @inheritDoc
	 * @return \MvcCore\Request
	 */
	public function InitAll () {
		$this->GetScriptName();
		$this->GetMethod();
		$this->GetBasePath();
		$this->GetScheme();
		$this->IsSecure();
		$this->GetHostName();
		$this->GetHost();
		$this->GetRequestPath();
		$this->GetFullUrl();
		$this->GetReferer();
		$this->GetStartTime();
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
