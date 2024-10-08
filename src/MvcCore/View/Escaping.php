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
	 * @inheritDoc
	 * @param  string      $str 
	 * @param  int         $flags
	 * @param  string|NULL $encoding 
	 * @param  bool        $double 
	 * @param  bool        $jsTemplate
	 * @return string
	 */
	public function Escape ($str, $flags = ENT_QUOTES, $encoding = NULL, $double = FALSE, $jsTemplate = FALSE) {
		$str = htmlspecialchars(
			(string) $str, 
			$this->escapeGetFlags($flags), 
			$encoding ?: $this->__protected['encoding'], 
			$double
		);
		return $jsTemplate
			? str_replace('{{', '{<!-- -->{', $str)
			: $str ;
	}

	/**
	 * @inheritDoc
	 * @param  string      $str 
	 * @param  string|NULL $encoding 
	 * @param  bool        $double 
	 * @return string
	 */
	public function EscapeHtml ($str, $encoding = NULL, $double = FALSE) {
		return htmlspecialchars(
			(string) $str, 
			$this->escapeGetFlags(ENT_QUOTES), 
			$encoding ?: $this->__protected['encoding'], 
			$double
		);
	}
	
	/**
	 * @inheritDoc
	 * @param  string      $str 
	 * @param  int         $flags
	 * @param  string|NULL $encoding 
	 * @param  bool        $double 
	 * @return string
	 */
	public function EscapeAttr ($str, $flags = ENT_QUOTES, $encoding = NULL, $double = FALSE) {
		$str = (string) $str;
		if (mb_strpos($str, '`') !== FALSE && strpbrk($str, ' <>"\'') === FALSE) 
			$str .= ' '; // protection against innerHTML mXSS vulnerability
		$str = htmlspecialchars(
			$str, 
			$this->escapeGetFlags($flags), 
			$encoding ?: $this->__protected['encoding'], 
			$double
		);
		return $this->escapeJsExecBegin($str);
	}
	
	/**
	 * @inheritDoc
	 * @param  string      $str 
	 * @param  string|NULL $encoding 
	 * @return string
	 */
	public function EscapeXml ($str, $encoding = NULL, $double = FALSE) {
		$str = preg_replace('#[\x00-\x08\x0B\x0C\x0E-\x1F]#', "\u{FFFD}", (string) $str);
		return htmlspecialchars(
			$str, 
			$this->escapeGetFlags(ENT_XML1 | ENT_QUOTES), 
			$encoding ?: $this->__protected['encoding'],
			$double
		);
	}

	/**
	 * @inheritDoc
	 * @param  mixed  $obj 
	 * @param  int    $flags 
	 * @param  int    $depth 
	 * @return string
	 */
	public function EscapeJs ($obj, $flags = 0, $depth = 512) {
		$toolClass = self::$toolClass;
		$json = $toolClass::JsonEncode($obj, $flags | JSON_HEX_QUOT | JSON_HEX_APOS, $depth);
		return strtr($json, [']]>' => ']]\x3E', '<!' => '\x3C!', '</script' => '<\/script']);
	}

	/**
	 * @inheritDoc
	 * @param  mixed  $obj 
	 * @param  int    $flags 
	 * @param  int    $depth 
	 * @return string
	 */
	public function EscapeAttrJs ($obj, $flags = 0, $depth = 512) {
		$toolClass = self::$toolClass;
		$json = $toolClass::JsonEncode($obj, $flags | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_APOS, $depth);
		return strtr($json, ["'" => "&apos;", '"' => "&quot;"]);
	}
	
	/**
	 * @inheritDoc
	 * @see    http://www.w3.org/TR/2006/WD-CSS21-20060411/syndata.html#q6
	 * @param  string $str 
	 * @return string
	 */
	public function EscapeCss ($str) {
		return addcslashes((string) $str, "\x00..\x1F!\"#$%&'()*+,./:;<=>?@[\\]^`{|}~");
	}
	
	/**
	 * @inheritDoc
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

	/**
	 * If given string starts with `  javascript:',
	 * remove this string begin.
	 * @param  string $str 
	 * @return string
	 */
	protected function escapeJsExecBegin ($str) {
		$result = ltrim($str);
		$jsExecSubStr = 'javascript:';
		$jsExecSubStrLen = mb_strlen($jsExecSubStr);
		if (mb_strtolower(mb_substr($result, 0, $jsExecSubStrLen)) === $jsExecSubStr) {
			$result = mb_substr($result, $jsExecSubStrLen);
		} else {
			$result = $str;
		}
		return $result;
	}
}
