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

namespace MvcCore\Application;

interface IConstants {
	
	/***********************************************************************************
	 *                       `\MvcCore\Application` - Constants                        *
	 ***********************************************************************************/
	
	/**
	 * MvcCore application mode describing that the application is compiled in <b>ONE BIG PHP FILE</b>.
	 * In PHP app mode should be packed php files or any asset files - PHTML templates, INI files
	 * or any static files. Unknown asset files or binary files are included as binary or base64 string.
	 * This mode has always best speed, because it should not work with hard drive if you don't want to.
	 * Only with many or with large asset files, there may be greater demands on memory and processor,
	 * which shouldn't be good for your application. Be aware to do that, if you have low memory limits.
	 * Result application packed in PHP mode has special `\Packager_Php_Wrapper` class included
	 * before any application content. This special class handles allowed file operations and assets
	 * as binary or base64 encoded. Everything should be configured before PHP packing.
	 * This mode has always four sub-modes started with PHP substring. All PHP package modes are:
	 * - `\Packager_Php_Wrapper::PHP_PRESERVE_HDD`
	 * - `\Packager_Php_Wrapper::PHP_PRESERVE_PACKAGE`
	 * - `\Packager_Php_Wrapper::PHP_STRICT_HDD`
	 * - `\Packager_Php_Wrapper::PHP_STRICT_PACKAGE`
	 * So to check if app is in PHP package mode - check it by `substr();`.
	 * @var string
	 */
	const COMPILED_PHP = 'PHP';

	/**
	 * MvcCore application mode describing that the application is compiled in <b>ONE BIG PHAR FILE</b>.
	 * There could be any content included. But in this mode, there is no speed advantages, but it's
	 * still good way to pack your app into single file tool for any web-hosting needs:-)
	 * This mode has always lower speed then `PHP` mode above, because it fully emulates hard drive
	 * for content of this file and it costs a time. But it has lower memory usage then `PHP` mode above.
	 * @see http://php.net/manual/en/phar.creating.php
	 * @var string
	 */
	const COMPILED_PHAR = 'PHAR';

	/**
	 * MvcCore application mode describing that the application is in <b>THE STATE BEFORE
	 * THEIR OWN COMPILATION INTO `PHP` OR `PHAR`</b> archive. This mode is always used to generate final
	 * javascript and css files into temporary directory to pack them later into result php/phar file.
	 * Shortcut `SFU` means "Single File Url". Application running in this mode has to generate
	 * single file URLs in form: "index.php?..." and everything has to work properly before
	 * application will be compiled into PHP/PHAR package. Use this mode in index.php before
	 * application compilation to generate and test everything necessary before app compilation by:
	 * `\MvcCore\Application::GetInstance()->Dispatch();`
	 * - `TRUE` means to switch application into temporary into SFU mode.
	 * @var string
	 */
	const COMPILED_SFU = 'SFU';

	/**
	 * MvcCore application mode describing that the application is running as <b>STANDARD PHP PROJECT</b>
	 * with many files on hard drive, using auto-loading or anything else. It's also standard development mode.
	 * @var string
	 */
	const NOT_COMPILED = '';


	/**
	 * Security protection - disabled to generate form hidden 
	 * input with token and disabled to send http cookie.
	 * @var int
	 */
	const SECURITY_PROTECTION_DISABLED		= 0;
	
	/**
	 * Security protection - enabled to generate form hidden 
	 * input with token in all forms by default.
	 * This protection mode is default application value.
	 * @var int
	 */
	const SECURITY_PROTECTION_FORM_TOKEN	= 1;
	
	/**
	 * Security protection - enabled to send http cookie.
	 * @var int
	 */
	const SECURITY_PROTECTION_COOKIE		= 2;

}