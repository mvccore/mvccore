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

interface IInstancing {
	
	/**
	 * Static factory to get every time new instance of http request object.
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
	 * @return \MvcCore\Request
	 */
	public static function CreateInstance (
		array & $server = [],
		array & $get = [],
		array & $post = [],
		array & $cookie = [],
		array & $files = [],
		$inputStream = NULL
	);

	/**
	 * Initialize all possible protected values from all global variables,
	 * including all http headers, all params and application inputs.
	 * This method is not recommended to use in production mode, it's
	 * designed mostly for development purposes, to see in one moment,
	 * what could be inside request after calling any getter method.
	 * @return \MvcCore\Request
	 */
	public function InitAll ();

}
