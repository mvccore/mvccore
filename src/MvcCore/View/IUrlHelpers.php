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

namespace MvcCore\View;

interface IUrlHelpers {
	
	/**
	 * Generates url:
	 * - By `"Controller:Action"` name and params array
	 *   (for routes configuration when routes array has keys with `"Controller:Action"` strings
	 *   and routes has not controller name and action name defined inside).
	 * - By route name and params array
	 *   (route name is key in routes configuration array, should be any string
	 *   but routes must have information about controller name and action name inside).
	 * Result address (url string) should have two forms:
	 * - Nice rewritten URL by routes configuration
	 *   (for apps with URL rewrite support (Apache `.htaccess` or IIS URL rewrite module)
	 *   and when first param is key in routes configuration array).
	 * - For all other cases is URL form like: `"index.php?controller=ctrlName&amp;action=actionName"`
	 *   (when first param is not founded in routes configuration array).
	 * @param  string              $controllerActionOrRouteName
	 * Should be `"Controller:Action"` combination or just any route name as custom specific string.
	 * @param  array<string,mixed> $params
	 * Optional, array with params, key is param name, value is param value.
	 * @return string
	 */
	public function Url ($controllerActionOrRouteName = 'Index:Index', array $params = []);

	/**
	 * Return asset path or single file mode URL for small assets
	 * handled by internal controller action `"Controller:Asset"`.
	 * Example: `echo $this->AssetUrl('/static/img/favicon.ico');`
	 * @param  string $path
	 * @return string
	 */
	public function AssetUrl ($path);

}
