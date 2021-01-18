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

namespace MvcCore;

/**
 * @inheritDocs
 */
class View implements IView {
	use \MvcCore\View\Props;
	use \MvcCore\View\GettersSetters;
	use \MvcCore\View\Rendering;
	use \MvcCore\View\DirectoryMethods;
	use \MvcCore\View\MagicMethods;
	use \MvcCore\View\UrlHelpers;
	use \MvcCore\View\ViewHelpers;
	use \MvcCore\View\Escaping;
	use \MvcCore\View\LocalMethods;
}
