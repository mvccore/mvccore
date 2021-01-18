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

namespace MvcCore\Route;

interface IConstants {

	/**
	 * Route advanced configuration key for filtering params in - `"in"`.
	 */
	const CONFIG_FILTER_IN = 'in';

	/**
	 * Route advanced configuration key for filtering params out - `"out"`.
	 */
	const CONFIG_FILTER_OUT = 'out';

	/**
	 * Route advanced configuration key to define the only matching http method.
	 */
	const CONFIG_METHOD = 'method';

	/**
	 * Route advanced configuration key to define another route name to redirect matched request to.
	 */
	const CONFIG_REDIRECT = 'redirect';

	/**
	 * Route advanced configuration key to complete always absolute URL address.
	 */
	const CONFIG_ABSOLUTE = 'absolute';


	/**
	 * Route INTERNAL flag if route `pattern` or `reverse` is relative with requested path only.
	 */
	const FLAG_SCHEME_NO = 0;

	/**
	 * Route INTERNAL flag if route `pattern` or `reverse` contains `//` at the beginning.
	 * The value is also length of string `//`.
	 */
	const FLAG_SCHEME_ANY = 2;

	/**
	 * Route INTERNAL flag if route `pattern` or `reverse` contains `http://` at the beginning.
	 * The value is also length of string `http://`.
	 */
	const FLAG_SCHEME_HTTP = 7;

	/**
	 * Route INTERNAL flag if route `pattern` or `reverse` contains `https://` at the beginning.
	 * The value is also length of string `https://`.
	 */
	const FLAG_SCHEME_HTTPS = 8;

	/**
	 * Route INTERNAL flag if route `pattern` or `reverse` doesn't contain any query string chars.
	 */
	const FLAG_QUERY_NO = 0;

	/**
	 * Route INTERNAL flag if route `pattern` or `reverse` contains some query string part.
	 */
	const FLAG_QUERY_INCL = 1;
	
	/**
	 * Route INTERNAL flag if route `pattern` or `reverse` doesn't contain any host targeting.
	 */
	const FLAG_HOST_NO = 0;

	/**
	 * Route INTERNAL flag if route `pattern` or `reverse` contains whole `%host%` targeting.
	 */
	const FLAG_HOST_HOST = 1;

	/**
	 * Route INTERNAL flag if route `pattern` or `reverse` contains `%domain%` host targeting.
	 */
	const FLAG_HOST_DOMAIN = 2;

	/**
	 * Route INTERNAL flag if route `pattern` or `reverse` contains `%tld%` host targeting.
	 */
	const FLAG_HOST_TLD = 3;

	/**
	 * Route INTERNAL flag if route `pattern` or `reverse` contains `%sld%` host targeting.
	 */
	const FLAG_HOST_SLD = 4;

	/**
	 * Route INTERNAL flag if route `pattern` or `reverse` contains `%basePath%` targeting.
	 */
	const FLAG_HOST_BASEPATH = 10;
	
	/**
	 * Route INTERNAL placeholder for `pattern` to match any request host and for `reverse`
	 * to hold place for given `host` URL param or for currently requested host.
	 */
	const PLACEHOLDER_HOST = '%host%';
	
	/**
	 * Route INTERNAL placeholder for `pattern` to match any request domain and for `reverse`
	 * to hold place for given `domain` URL param or for currently requested domain.
	 */
	const PLACEHOLDER_DOMAIN = '%domain%';

	/**
	 * Route INTERNAL placeholder for `pattern` to match any request top level 
	 * domain and for `reverse` to hold place for given `tld` URL param or for 
	 * currently requested top level domain.
	 */
	const PLACEHOLDER_TLD = '%tld%';

	/**
	 * Route INTERNAL placeholder for `pattern` to match any request second 
	 * level domain and for `reverse` to hold place for given `sld` URL param 
	 * or for currently requested second level domain.
	 */
	const PLACEHOLDER_SLD = '%sld%';

	/**
	 * Route INTERNAL placeholder for `pattern` to match any request basePath and for `reverse`
	 * to hold place for given `basePath` URL param or for currently requested basePath.
	 */
	const PLACEHOLDER_BASEPATH = '%basePath%';
}