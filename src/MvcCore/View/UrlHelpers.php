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

/**
 * @mixin \MvcCore\View
 */
trait UrlHelpers {

	/**
	 * @inheritDoc
	 * @param  string $controllerActionOrRouteName Should be `"Controller:Action"` combination or just any route name as custom specific string.
	 * @param  array  $params                      Optional, array with params, key is param name, value is param value.
	 * @return string
	 */
	public function Url ($controllerActionOrRouteName = 'Index:Index', array $params = []) {
		return $this->controller->GetRouter()->Url($controllerActionOrRouteName, $params);
	}

	/**
	 * @inheritDoc
	 * @param  string $path
	 * @return string
	 */
	public function AssetUrl ($path) {
		return $this->controller->AssetUrl($path);
	}
}
