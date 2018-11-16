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

namespace MvcCore\Config;

trait IniDump
{
	public function Dump () {
		$environment = static::GetEnvironment(TRUE);
		list($sections, $envSpecifics) = $this->dumpSectionsInfo();
		$levelKey = '';
		$basicData = [];
		$sectionsData = [];
		foreach ($this->data as $key => & $value) {
			if (is_object($value) || is_array($value)) {
				if ($sectionsData) $sectionsData[] = '';
				$sectionType = isset($sections[$key]) ? $sections[$key] : 0;
				$environmentSpecificSection = $sectionType === 3;
				if ($sectionType) {
					unset($sections[$key]);
					$sectionsData[] = ($environmentSpecificSection 
						? '[' . $environment . ' > ' . $key . ']'
						: '[' . $key . ']');
					$levelKey = '';
				} else {
					$levelKey = (string) $key;
				}
				$this->dumpRecursive($levelKey, $value, $sectionsData);
				if ($environmentSpecificSection && isset($envSpecifics[$key])) {
					foreach ($envSpecifics[$key] as $envName => $sectionLines) {
						if ($envName === $environment) continue;
						$sectionsData[] = '';
						foreach ($sectionLines as $sectionLine)
							$sectionsData[] = $sectionLine;
					}
				}
			} else {
				$basicData[] = $key . ' = ' . $this->dumpScalarValue($value);
			}
		}
		$result = '';
		if ($basicData) $result = implode(PHP_EOL, $basicData);
		if ($sectionsData) $result .= PHP_EOL . PHP_EOL . implode(PHP_EOL, $sectionsData);
		return $result;
	}

	protected function dumpSectionsInfo () {
		$sections = [];
		$envSpecifics = [];
		if (file_exists($this->fullPath)) {
			$rawIniLines = file($this->fullPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
			// detect current environment sections and foreign sections
			$contentFilling = [];
			foreach ($rawIniLines as $rawIniLine) {
				$rawIniLine = trim($rawIniLine);
				$firstChar = mb_substr($rawIniLine, 0, 1);
				if ($firstChar === ';') continue;
				$rawSection =  NULL;
				if ($firstChar == '[' && mb_substr($rawIniLine, -1, 1) == ']') 
					$rawSection =  mb_substr($rawIniLine, 1, -1);
				if ($rawSection) {
					if (strpos($rawSection, '>') !== FALSE) {
						list($envNameLocal, $sectionName) = explode('>', str_replace(' ', '', $rawSection));
						$sections[$sectionName] = 3;
						if (!isset($envSpecifics[$sectionName]))
							$envSpecifics[$sectionName] = [];
						$envSpecifics[$sectionName][$envNameLocal] = [];
						$contentFilling = & $envSpecifics[$sectionName][$envNameLocal];
					} else {
						$sections[$rawSection] = 2;
						$contentFilling = [];
					}
				}
				$contentFilling[] = $rawIniLine;
			}
		} else {
			$sections = array_fill_keys(array_keys($this->data), 1); // all sections will be new
		}
		return [$sections, $envSpecifics];
	}

	protected function dumpRecursive ($levelKey, & $data, & $rawData) {
		if (is_object($data) || is_array($data)) {
			if (strlen($levelKey) > 0) $levelKey .= '.';
			foreach ((array) $data as $key => & $value) {
				$this->dumpRecursive($levelKey . $key, $value, $rawData);
			}
		} else {
			$rawData[] = $levelKey . ' = ' . $this->dumpScalarValue($data);
		}
	}

	protected function dumpScalarValue ($value) {
		if (is_numeric($value)) {
			return (string) $value;
		} else if (is_bool($value)) {
			return $value ? 'true' : 'false';
		} else if ($value === NULL) {
			return 'null';
		} else {
			static $specialChars = [
				'=', '/', '.', '#', '&', '!', '?', '-', '@', "'", '"', '*', '^',
				'[', ']', '(', ')', '{', '}', '<', '>', '\n', '\r', 
			];
			$valueStr = (string) $value;
			$specialCharCaught = FALSE;
			foreach ($specialChars as $specialChar) {
				if (mb_strpos($valueStr, $specialChar)) {
					$specialCharCaught = TRUE;
					break;
				}
			}
			if ($specialCharCaught) {
				return '"' . addcslashes($valueStr, '"') . '"';
			} else {
				return $valueStr;
			}
		}
	}
}
