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

trait Escaping
{
	public function EscapeHtml ($str, $encoding = 'UTF-8') {
		return htmlspecialchars(
			(string) $str, $this->escapeGetFlags(ENT_QUOTES | ENT_SUBSTITUTE), $encoding
		);
	}
	
	public function EscapeHtmlText ($str, $encoding = 'UTF-8') {
		return htmlspecialchars(
			(string) $str, $this->escapeGetFlags(ENT_NOQUOTES | ENT_SUBSTITUTE), $encoding
		);
	}
	
	public function EscapeHtmlAttr ($str, $double = TRUE, $encoding = 'UTF-8') {
		$str = (string) $str;
		if (mb_strpos($str, '`') !== FALSE && strpbrk($str, ' <>"\'') === FALSE) 
			$str .= ' '; // protection against innerHTML mXSS vulnerability
		return htmlspecialchars(
			$str, $this->escapeGetFlags(ENT_QUOTES | ENT_SUBSTITUTE), $encoding, $double
		);
	}
	
	public function EscapeXml ($str, $encoding = 'UTF-8') {
		// XML 1.0:	\x09 \x0A \x0D and C1 allowed directly, C0 forbidden
		// XML 1.1:	\x00 forbidden directly and as a character reference,
		//			\x09 \x0A \x0D \x85 allowed directly, C0, C1 and \x7F allowed as character references
		$str = preg_replace('#[\x00-\x08\x0B\x0C\x0E-\x1F]#', "\u{FFFD}", (string) $str);
		return htmlspecialchars(
			$str, $this->escapeGetFlags(ENT_QUOTES | ENT_SUBSTITUTE), $encoding
		);
	}
	
	public function EscapeJs ($str, $flags = 0, $depth = 512) {
		$toolClass = \MvcCore\Application::GetInstance()->GetToolClass();
		$json = $toolClass::EncodeJson($str, JSON_UNESCAPED_UNICODE);
		return str_replace([']]>', '<!'], [']]\x3E', '\x3C!'], $json);
	}
	
	/**
	 * @see http://www.w3.org/TR/2006/WD-CSS21-20060411/syndata.html#q6
	 * @param string $str 
	 * @return string
	 */
	public function EscapeCss ($str) {
		return addcslashes((string) $str, "\x00..\x1F!\"#$%&'()*+,./:;<=>?@[\\]^`{|}~");
	}
	
	public function EscapeICal ($str, $encoding = 'UTF-8') {
		
	}

	/**
	 * @param int $flagsToAdd
	 * @return int
	 */
	public function escapeGetFlags ($flagsToAdd) {
		static $allEscapeFlags = [
			\MvcCore\IView::DOCTYPE_HTML4	=> ENT_HTML401,
			\MvcCore\IView::DOCTYPE_XHTML	=> ENT_XHTML,
			\MvcCore\IView::DOCTYPE_HTML5	=> ENT_HTML5,
			\MvcCore\IView::DOCTYPE_XML		=> ENT_XML1,
		];
		return isset($allEscapeFlags[static::$doctype])
			? $allEscapeFlags[static::$doctype] | $flagsToAdd
			: ENT_QUOTES | $flagsToAdd;
	}
}
