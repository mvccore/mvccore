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

namespace MvcCore\Controller;

interface IFlashMessages {
	
	/**
	 * @inheritDoc
	 * @param  string            $msg
	 * Flash message text to display in next request(s).
	 * @param  int|list<int>     $options
	 * Could be defined as integer or as array with integer 
	 * keys and values. Use flags like 
	 * `\MvcCore\IController::FLASH_MESSAGE_*`.
	 * @param  array<int,string> $replacements
	 * Array with integer (`{0},{1},{2}...`) or 
	 * named (`{two},{two},{three}...`) replacements.
	 * @return \MvcCore\Controller Returns current controller context.
	 */
	public function FlashMessageAdd ($msg, $options = \MvcCore\IController::FLASH_MESSAGE_TYPE_DEFAULT, array $replacements = []);

	/**
	 * Get flash messages from previous request 
	 * to render it and clean flash messages records.
	 * @return array<string,\stdClass>
	 */
	public function FlashMessagesGetClean ();

}