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
trait Escaping {

	/**
	 * @inheritDocs
	 * @param  string $str 
	 * @param  string $encoding 
	 * @return string
	 */
	public function Escape ($str, $encoding = 'UTF-8') {
		return htmlspecialchars(
			(string) $str, $this->escapeGetFlags(ENT_QUOTES), $encoding
		);
	}
	
	/**
	 * @inheritDocs
	 * @param  string $str 
	 * @param  string $encoding 
	 * @return string
	 */
	public function EscapeHtml ($str, $encoding = 'UTF-8') {
		return htmlspecialchars(
			(string) $str, $this->escapeGetFlags(ENT_NOQUOTES), $encoding
		);
	}
	
	/**
	 * @inheritDocs
	 * @param  string $str 
	 * @param  bool   $double 
	 * @param  string $encoding 
	 * @return string
	 */
	public function EscapeAttr ($str, $double = TRUE, $encoding = 'UTF-8') {
		$str = (string) $str;
		if (mb_strpos($str, '`') !== FALSE && strpbrk($str, ' <>"\'') === FALSE) 
			$str .= ' '; // protection against innerHTML mXSS vulnerability
		return htmlspecialchars(
			$str, $this->escapeGetFlags(ENT_QUOTES), $encoding, $double
		);
	}
	
	/**
	 * @inheritDocs
	 * @param  string $str 
	 * @param  string $encoding 
	 * @return string
	 */
	public function EscapeXml ($str, $encoding = 'UTF-8') {
		$str = preg_replace('#[\x00-\x08\x0B\x0C\x0E-\x1F]#', "\u{FFFD}", (string) $str);
		return htmlspecialchars(
			$str, $this->escapeGetFlags(ENT_XML1 | ENT_QUOTES), $encoding
		);
	}
	
	/**
	 * @inheritDocs
	 * @param  string $str 
	 * @param  int    $flags 
	 * @param  int    $depth 
	 * @return string
	 */
	public function EscapeJs ($str, $flags = 0, $depth = 512) {
		$toolClass = self::$toolClass;
		$json = $toolClass::EncodeJson($str, JSON_UNESCAPED_UNICODE);
		return str_replace([']]>', '<!'], [']]\x3E', '\x3C!'], $json);
	}
	
	/**
	 * @inheritDocs
	 * @see    http://www.w3.org/TR/2006/WD-CSS21-20060411/syndata.html#q6
	 * @param  string $str 
	 * @return string
	 */
	public function EscapeCss ($str) {
		return addcslashes((string) $str, "\x00..\x1F!\"#$%&'()*+,./:;<=>?@[\\]^`{|}~");
	}
	
	/**
	 * @inheritDocs
	 * @see    https://www.ietf.org/rfc/rfc5545.txt
	 * @param  string $str 
	 * @return string
	 */
	public function EscapeICal ($str) {
		$str = str_replace("\r", '', (string) $str);
		$str = preg_replace('#[\x00-\x08\x0B-\x1F]#', "\u{FFFD}", $str);
		return addcslashes($str, "\";\\,:\n");
	}

	/**
	 * Complete flags for `htmlspecialchars()` by view type.
	 * @param  int $flagsToAdd
	 * @return int
	 */
	protected function escapeGetFlags ($flagsToAdd) {
		static $allEscapeFlags = [
			\MvcCore\IView::DOCTYPE_HTML4	=> ENT_HTML401,
			\MvcCore\IView::DOCTYPE_XHTML	=> ENT_XHTML,
			\MvcCore\IView::DOCTYPE_HTML5	=> ENT_HTML5,
			\MvcCore\IView::DOCTYPE_XML		=> ENT_XML1,
		];
		$doctype = static::$doctype;
		$flags = isset($allEscapeFlags[$doctype])
			? $allEscapeFlags[$doctype]
			: ENT_QUOTES;
		return $flags | ENT_SUBSTITUTE | $flagsToAdd;
	}
}
