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

interface IEscaping {
	
	/**
	 * Escape string to use it inside HTML/XHTML/HTML5 node in HTML content.
	 * JS template param escapes: `{{variable}}` => `{<!-- -->{variable}}`.
	 * @param  string      $str 
	 * @param  int         $flags
	 * @param  string|NULL $encoding 
	 * @param  bool        $double 
	 * @param  bool        $jsTemplate
	 * @return string
	 */
	public function Escape ($str, $flags = ENT_QUOTES, $encoding = NULL, $double = FALSE, $jsTemplate = FALSE);

	/**
	 * Escape string for use inside HTML/XHTML/HTML5 node in HTML content.
	 * This method is always used to escape texts in view components.
	 * @param  string      $str 
	 * @param  string|NULL $encoding 
	 * @param  bool        $double 
	 * @return string
	 */
	public function EscapeHtml ($str, $encoding = NULL, $double = FALSE);
	
	/**
	 * Escape string to use it inside HTML/XHTML/HTML5 attribute in HTML context.
	 * @param  string      $str 
	 * @param  int         $flags
	 * @param  string|NULL $encoding 
	 * @param  bool        $double 
	 * @return string
	 */
	public function EscapeAttr ($str, $flags = ENT_QUOTES, $encoding = NULL, $double = FALSE);
	
	/**
	 * Escape string to use it inside XML template.
	 * XML 1.0: \x09 \x0A \x0D and C1 allowed directly, C0 forbidden.
	 * XML 1.1: \x00 forbidden directly and as a character reference,
	 *          \x09 \x0A \x0D \x85 allowed directly, C0, C1 and \x7F allowed as character references.
	 * @param  string      $str 
	 * @param  string|NULL $encoding 
	 * @param  bool        $double 
	 * @return string
	 */
	public function EscapeXml ($str, $encoding = NULL, $double = FALSE);
	
	/**
	 * Escape any object to use it inside JS context.
	 * Objects are JSON encoded, strings are initialized with double quotes.
	 * @param  mixed  $obj 
	 * @param  int    $flags 
	 * @param  int    $depth 
	 * @return string
	 */
	public function EscapeJs ($obj, $flags = 0, $depth = 512);
	
	/**
	 * Escape any object to use it inside HTML/XHTML/HTML5 attribute in JS context.
	 * Objects are JSON encoded, all strings are initialized with single quotes.
	 * @param  mixed  $obj 
	 * @param  int    $flags 
	 * @param  int    $depth 
	 * @return string
	 */
	public function EscapeAttrJs ($obj, $flags = 0, $depth = 512);
	
	/**
	 * Escape string to use it inside CSS context.
	 * @see http://www.w3.org/TR/2006/WD-CSS21-20060411/syndata.html#q6
	 * @param  string $str 
	 * @return string
	 */
	public function EscapeCss ($str);
	
	/**
	 * Escape string to use it inside iCal template.
	 * @see https://www.ietf.org/rfc/rfc5545.txt
	 * @param  string $str 
	 * @return string
	 */
	public function EscapeICal ($str);

}
