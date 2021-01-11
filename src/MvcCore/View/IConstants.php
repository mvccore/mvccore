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

namespace MvcCore\View;

interface IConstants {

	/**
	 * Default rendering mode.
	 * Render action view first into output buffer, then render layout view
	 * wrapped around rendered action view string also into output buffer.
	 * Then set up rendered content from output buffer into response object
	 * and then send HTTP headers and content after all.
	 * @var int
	 */
	const RENDER_WITH_OB_FROM_ACTION_TO_LAYOUT	= 0b01;

	/**
	 * Special rendering mode to continuously sent larger data to client.
	 * Render layout view and render action view together inside it without
	 * output buffering. There is not used reponse object body property for
	 * this rendering mode. Http headers are sent before view rendering.
	 * @var int
	 */
	const RENDER_WITHOUT_OB_CONTINUOUSLY		= 0b10;

	/**
	 * View output document type HTML4.
	 * @var string
	 */
	const DOCTYPE_HTML4 = 'HTML4';

	/**
	 * View output document type XHTML.
	 * @var string
	 */
	const DOCTYPE_XHTML = 'XHTML';

	/**
	 * View output document type HTML5.
	 * @var string
	 */
	const DOCTYPE_HTML5 = 'HTML5';

	/**
	 * View output document type for any XML file.
	 * @var string
	 */
	const DOCTYPE_XML = 'XML';

	/**
	 * MvcCore extension class name for view helpers.
	 * Helpers view implementing this interface could have better setup.
	 */
	const HELPERS_INTERFACE_CLASS_NAME = 'MvcCore\\Ext\\Views\\Helpers\\IHelper';
}