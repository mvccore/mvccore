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

namespace MvcCore\Router;

interface IUrlBuilding {
	
	/**
	 * Generates url:
	 * - By `"Controller:Action"` name and params array
	 *   (for routes configuration when routes array has keys with 
	 *   `"Controller:Action"` strings and routes has not controller name and 
	 *   action name defined inside).
	 * - By route name and params array
	 *   (route name is key in routes configuration array, should be any string,
	 *   routes must have information about controller name and action name 
	 *   inside).
	 * Result address (url string) should have two forms:
	 * - Nice rewritten URL by routes configuration (for apps with URL rewrite 
	 *   support [Apache `.htaccess` or IIS URL rewrite module] and when first 
	 *   param is key in routes configuration array).
	 * - For all other cases is URL form like: 
	 *   `"index.php?controller=ctrlName&amp;action=actionName"`
	 *   (when first param is not founded in routes configuration array).
	 * Method tries to find any route between routes by first argument and if
	 * there is no route but if there is any pre route URL building handler 
	 * defined, the handler is called to assign desired routes from database 
	 * or any other place and then there is processed route search between 
	 * routes again. If there is still no routes, result url is completed 
	 * in query string form.
	 * @param  string              $controllerActionOrRouteName
	 * Should be `"Controller:Action"` combination 
	 * or just any route name as custom specific string.
	 * @param  array<string,mixed> $params
	 * Optional, array with params, key is 
	 * param name, value is param value.
	 * @throws \InvalidArgumentException
	 * @return string
	 */
	public function Url ($controllerActionOrRouteName = 'Index:Index', array $params = []);
	
	/**
	 * Encode URL special chars to percent (%) sign followed by two hex digits:
	 * - encode ASCII chars with lower index than 33, including space (\x00-\x20)
	 * - encode ASCII special chars for HTML `" ' < > \` (\x22\x27\x3C\x3E\x5C)
	 * - encode ASCII chars not used in HTML and URL `$ ( ) * + ´ ^ ` { }` (\x24\x28\x29\x2A\x2B\x2C\x5E\x60\x7B\x7D)
	 * - keep ASCII special chars for URL `! # % & - . / : ; = ? @ [ ] _ ~` (\x21\x23\x25\x26\x2D\x2E\x2F\x3A\x3B\x3D\x3F\x40\x5B\x5D\x5F\x7E)
	 * - keep ASCII alphanumeric chars (\x30-\x39,\x41-\x5A)
	 * @param  string $url 
	 * @return string
	 */
	public function EncodeUrl ($url);

}
