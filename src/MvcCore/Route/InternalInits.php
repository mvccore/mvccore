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

namespace MvcCore\Route;

trait InternalInits
{
	/**
	 * Initialize all possible protected values (`match`, `reverse` etc...). This
	 * method is not recommended to use in production mode, it's designed mostly
	 * for development purposes, to see what could be inside route object.
	 * @return \MvcCore\Route|\MvcCore\IRoute
	 */
	public function InitAll () {
		/** @var $this \MvcCore\Route */
		if ($this->match === NULL && $this->reverse === NULL) {
			$this->initMatchAndReverse();
		} else if ($this->match !== NULL && ($this->reverseParams === NULL || $this->lastPatternParam === NULL)) {
			$this->initReverse();
		}
		return $this;
	}

	/**
	 * Initialize properties `match`, `reverse` and other internal properties
	 * about those values, when there is necessary to prepare `pattern` value
	 * for: a) PHP `preg_match_all()` route match processing, b) for `reverse`
	 * value for later self URL building. This method is usually called in core
	 * request routing process from `\MvcCore\Router::Matches();` method on each
	 * route.
	 * @throws \LogicException Route configuration property is missing.
	 * @throws \InvalidArgumentException Wrong route pattern format.
	 * @return void
	 */
	protected function initMatchAndReverse () {
		if ($this->reverseSections !== NULL) return;
		if ($this->pattern === NULL)
			$this->throwExceptionIfKeyPropertyIsMissing('pattern');

		$this->lastPatternParam = NULL;
		$match = addcslashes($this->pattern, "#(){}-?!=^$.+|:*\\");
		$reverse = $this->reverse !== NULL
			? $this->reverse
			: $this->pattern;

		list($this->reverseSections, $matchSections) = $this->initSectionsInfoForMatchAndReverse(
			$reverse, $match
		);
		$this->reverse = & $reverse;
		$this->reverseParams = $this->initReverseParams(
			$reverse, $this->reverseSections, $this->constraints, $match
		);
		$this->initFlagsByPatternOrReverse($reverse);
		$this->match = $this->initMatchComposeRegex(
			$match, $matchSections, $this->reverseParams, $this->constraints
		);
	}

	/**
	 * Process together given `match` and `reverse` value and complete two
	 * arrays with sections info about those two given values. Also change the
	 * given `match` and `reverse` string references and remove all brackets
	 * `[]` defining variable section(s). Every result array with this sections
	 * info, with those statistics contains for each fixed section or variable
	 * section defined with brackets `[]` info about it's type (fixed or
	 * variable), about start position, end position and length. Those
	 * statistics are always used to build URL later.
	 * @param string $match		A match string prepared from `pattern` property.
	 * @param string $reverse	A reverse string value, directly from `reverse`
	 *							property or from `pattern` property if `reverse`
	 *							property is empty.
	 * @return \stdClass[][] Two arrays with array with `\stdClass` objects.
	 */
	protected function initSectionsInfoForMatchAndReverse (& $match, & $reverse) {
		$matchInfo = [];
		$reverseInfo = [];
		$reverseIndex = 0;
		$matchIndex = 0;
		$reverseLength = mb_strlen($reverse);
		$matchLength = mb_strlen($match);
		$matchOpenPos = FALSE;
		$matchClosePos = FALSE;
		while ($reverseIndex < $reverseLength) {
			$reverseOpenPos = mb_strpos($reverse, '[', $reverseIndex);
			$reverseClosePos = FALSE;
			if ($reverseOpenPos !== FALSE) {
				$reverseClosePos = mb_strpos($reverse, ']', $reverseOpenPos);
				$matchOpenPos = mb_strpos($match, '[', $matchIndex);
				$matchClosePos = mb_strpos($match, ']', $matchOpenPos);
			}
			if ($reverseClosePos === FALSE) {
				$reverseInfo[] = (object) [
					'fixed'=>TRUE, 'start'=>$reverseIndex, 'end'=>$reverseLength,
					'length' => $reverseLength - $reverseIndex
				];
				$matchInfo[]   = (object) [
					'fixed' => TRUE, 'start'=>$matchIndex, 'end'=>$matchLength,
					'length' => $matchLength - $matchIndex
				];
				break;
			} else {
				if ($reverseIndex < $reverseOpenPos) {
					$reverseInfo[]	= (object) [
						'fixed'=> TRUE,			'start'=> $reverseIndex,
						'end'=> $reverseOpenPos,
						'length' => $reverseOpenPos - $reverseIndex
					];
					$matchInfo[]	= (object) [
						'fixed'=> TRUE,
						'start'=> $matchIndex,
						'end'=> $matchOpenPos,
						'length'=> $matchOpenPos - $matchIndex];
				}
				$reverseOpenPosPlusOne = $reverseOpenPos + 1;
				$reverseLocalLength = $reverseClosePos - $reverseOpenPosPlusOne;
				$reverse = mb_substr($reverse, 0, $reverseOpenPos) . mb_substr(
					$reverse, $reverseOpenPosPlusOne, $reverseLocalLength
					) . mb_substr($reverse, $reverseClosePos + 1);
				$reverseLength -= 2;
				$reverseClosePos -= 1;
				$reverseInfo[] = (object) [
					'fixed'	=> FALSE,				'start'	=> $reverseOpenPos,
					'end'	=> $reverseClosePos,	'length'=> $reverseLocalLength
				];
				$matchOpenPosPlusOne = $matchOpenPos + 1;
				$matchLocalLength = $matchClosePos - $matchOpenPosPlusOne;
				$match = mb_substr($match, 0, $matchOpenPos)
					. mb_substr($match, $matchOpenPosPlusOne, $matchLocalLength)
					. mb_substr($match, $matchClosePos + 1);
				$matchLength -= 2;
				$matchClosePos -= 1;
				$matchInfo[] = (object) [
					'fixed'	=> FALSE,			'start'	=> $matchOpenPos,
					'end'	=> $matchClosePos,	'length'=> $matchLocalLength
				];
			}
			$reverseIndex = $reverseClosePos;
			$matchIndex = $matchClosePos;
		}
		return [$matchInfo, $reverseInfo];
	}

	/**
	 * Initialize property `reverse` and other internal properties about this
	 * value, when there is necessary to prepare it for: a) URL building, b) for
	 * request routing, when there is configured `match` property directly
	 * an when is necessary to initialize route flags from `reverse` to complete
	 * correctly subject to match.
	 * @throws \LogicException Route configuration property is missing.
	 * @throws \InvalidArgumentException Wrong route pattern format.
	 * @return void
	 */
	protected function initReverse () {
		if ($this->reverseSections !== NULL) return;
		$reverse = NULL;
		if ($this->reverse !== NULL) {
			$reverse = $this->reverse;
		} else if ($this->pattern !== NULL) {
			$reverse = $this->pattern;
		} else/* if ($this->pattern === NULL)*/ {
			if ($this->redirect !== NULL)
				return $this->initFlagsByPatternOrReverse(
					$this->pattern !== NULL
						? $this->pattern
						: str_replace(['\\', '(?', ')?', '/?'], '', $this->match)
				);
			$this->throwExceptionIfKeyPropertyIsMissing('reverse', 'pattern');
		}

		$this->lastPatternParam = NULL;

		$this->reverseSections = $this->initSectionsInfo($reverse);
		$this->reverse = $reverse;

		$match = NULL;
		$this->reverseParams = $this->initReverseParams(
			$reverse, $this->reverseSections, $this->constraints, $match
		);

		$this->initFlagsByPatternOrReverse($reverse);
	}

	/**
	 * Process given `reverse` value and complete array with sections info about
	 * this given value. Also change the given `reverse` string reference and
	 * remove all brackets `[]` defining variable section(s). The result array
	 * with sections info, with this statistic contains for each fixed section
	 * or variable section defined with brackets `[]` info about it's type
	 * (fixed or variable), about start position, end position and length. This
	 * statistic is always used to build URL later.
	 * @param string $pattern A reverse string value, directly from `reverse`
	 *						  property or from `pattern` property if `reverse`
	 *						  property is empty.
	 * @return \stdClass[][] An array with `\stdClass` objects.
	 */
	protected function & initSectionsInfo (& $pattern) {
		$result = [];
		$index = 0;
		$length = mb_strlen($pattern);
		while ($index < $length) {
			$openPos = mb_strpos($pattern, '[', $index);
			$closePos = FALSE;
			if ($openPos !== FALSE)
				$closePos = mb_strpos($pattern, ']', $openPos);
			if ($closePos === FALSE) {
				$result[] = (object) [
					'fixed'	=> TRUE,	'start'		=> $index,
					'end'	=> $length,	'length'	=> $length - $index
				];
				break;
			} else {
				if ($index < $openPos)
					$result[] = (object) [
					'fixed'	=> TRUE,		'start'		=> $index,
					'end'	=> $openPos,	'length'	=> $openPos - $index
				];
				$openPosPlusOne = $openPos + 1;
				$lengthLocal = $closePos - $openPosPlusOne;
				$pattern = mb_substr($pattern, 0, $openPos)
					. mb_substr($pattern, $openPosPlusOne, $lengthLocal)
					. mb_substr($pattern, $closePos + 1);
				$length -= 2;
				$closePos -= 1;
				$result[] = (object) [
					'fixed'	=> FALSE,		'start'		=> $openPos,
					'end'	=> $closePos,	'length'	=> $lengthLocal
				];
			}
			$index = $closePos;
		}
		return $result;
	}

	/**
	 * Initialize reverse params info array. Each item in completed array is
	 * `\stdClass` object with records about founded parameter place: `name`,
	 * `greedy`, `sectionIndex`, `reverseStart`, `reverseEnd`. Records
	 * `matchStart` and `matchEnd` could be values `-1` when function argument
	 * `$match` is `NULL`, because this function is used to complete `match` and
	 * `reverse` properties together and also to complete `reverse` property
	 * separately and only. Result array is always used as `reverseParams`
	 * property to complete URL rewrite params inside result `reverse` string.
	 * @param string		$reverse				A reverse string with `<param>`s.
	 * @param \stdClass[]	$reverseSectionsInfo	Reverse sections statistics with
	 *												fixed and variable sections.
	 * @param array			$constraints			Route constraints array.
	 * @param string|NULL	$match					A match string, could be `NULL`.
	 * @throws \InvalidArgumentException Wrong route pattern format.
	 * @return array		An array with keys as param names and values as
	 *						`\stdClass` objects with data about each reverse param.
	 */
	protected function & initReverseParams (& $reverse, & $reverseSectionsInfo, & $constraints, & $match = NULL) {
		$result = [];
		$completeMatch = $match !== NULL;
		$reverseIndex = 0;
		$matchIndex = 0;
		$sectionIndex = 0;
		$section = $reverseSectionsInfo[$sectionIndex];
		$reverseLength = mb_strlen($reverse);
		$greedyCaught = FALSE;
		$matchOpenPos = -1;
		$matchClosePos = -1;
		$this->lastPatternParam = '';
		while ($reverseIndex < $reverseLength) {
			$reverseOpenPos = mb_strpos($reverse, '<', $reverseIndex);
			$reverseClosePos = FALSE;
			if ($reverseOpenPos !== FALSE) {
				$reverseClosePos = mb_strpos($reverse, '>', $reverseOpenPos);
				if ($completeMatch) {
					$matchOpenPos = mb_strpos($match, '<', $matchIndex);
					$matchClosePos = mb_strpos($match, '>', $matchOpenPos) + 1;
				}}
			if ($reverseClosePos === FALSE) break;// no other param caught
			// check if param belongs to current section
			// and if not, move to next (or next...) section
			$reverseClosePos += 1;
			if ($reverseClosePos > $section->end) {
				$reverseSectionsInfoCountMinusOne = count($reverseSectionsInfo) - 1;
				while ($sectionIndex < $reverseSectionsInfoCountMinusOne) {
					$nextSection = $reverseSectionsInfo[$sectionIndex + 1];
					if ($reverseClosePos > $nextSection->end) {
						$sectionIndex += 1;
					} else {
						$sectionIndex += 1;
						$section = $reverseSectionsInfo[$sectionIndex];
						break;
					}}}
			// complete param section length and param name
			$paramLength = $reverseClosePos - $reverseOpenPos;
			$paramName = mb_substr($reverse, $reverseOpenPos + 1, $paramLength - 2);
			list ($greedyFlag, $sectionIsLast) = $this->initReverseParamsGetGreedyInfo(
				$reverseSectionsInfo, $constraints,
				$paramName, $sectionIndex, $greedyCaught
			);
			if ($greedyFlag && $sectionIsLast) {
				$lastSectionChar = mb_substr(
					$reverse, $reverseClosePos, $reverseSectionsInfo[$sectionIndex]->end - $reverseClosePos
				);
				if ($lastSectionChar == '/') {
					$lastSectionChar = '';
					$reverseSectionsInfo[$sectionIndex]->end -= 1;
				}
				if ($lastSectionChar === '')
					$this->lastPatternParam = $paramName;
			}
			$result[$paramName] = (object) [
				'name'			=> $paramName,
				'greedy'		=> $greedyFlag,
				'sectionIndex'	=> $sectionIndex,
					'length'		=> $paramLength,
				'reverseStart'	=> $reverseOpenPos,
				'reverseEnd'	=> $reverseClosePos,
				'matchStart'	=> $matchOpenPos,
				'matchEnd'		=> $matchClosePos,
			];
			$reverseIndex = $reverseClosePos;
			$matchIndex = $matchClosePos;
		}
		return $result;
	}

	/**
	 * Get if founded param place is greedy or not. If it's greedy, check if it
	 * is only one greedy param in whole pattern string and if it is the last
	 * param between other params. Get also if given section index belongs to
	 * the last section info in line.
	 * @param \stdClass[]	$reverseSectionsInfo	Whole sections info array ref.
	 *												with `\stdClass` objects.
	 * @param array			$constraints			Route params constraints.
	 * @param string		$paramName				Route parsed params.
	 * @param int			$sectionIndex			Currently checked section index.
	 * @param bool			$greedyCaught			Boolean about if param is checked as greedy.
	 * @throws \InvalidArgumentException Wrong route pattern format.
	 * @return \bool[]		Array with two boolean values. First is greedy flag
	 *						and second is about if section is last or not. The
	 *						second could be `NULL`
	 */
	protected function initReverseParamsGetGreedyInfo (& $reverseSectionsInfo, & $constraints, & $paramName, & $sectionIndex, & $greedyCaught) {
		// complete greedy flag by star character inside param name
		$greedyFlag = mb_strpos($paramName, '*') !== FALSE;
		$sectionIsLast = NULL;
		// check greedy param specifics
		if ($greedyFlag) {
			if ($greedyFlag && $greedyCaught) {
				$selfClass = \PHP_VERSION_ID >= 50500 ? self::class : __CLASS__;
				throw new \InvalidArgumentException(
					"[".$selfClass."] Route pattern definition can have only one greedy `<param_name*>` "
					." with star (to include everything - all characters and slashes . `.*`) ($this)."
				);
			}
			$reverseSectionsCount = count($reverseSectionsInfo);
			$sectionIndexPlusOne = $sectionIndex + 1;
			if (// next section is optional
				$sectionIndexPlusOne < $reverseSectionsCount &&
				!($reverseSectionsInfo[$sectionIndexPlusOne]->fixed)
			) {
				// check if param is really greedy or not
				$constraintDefined = isset($constraints[$paramName]);
				$constraint = $constraintDefined ? $constraints[$paramName] : NULL ;
				$greedyReal = !$constraintDefined || ($constraintDefined && (
					mb_strpos($constraint, '.*') !== FALSE || mb_strpos($constraint, '.+') !== FALSE
				));
				if ($greedyReal) {
					$selfClass = \PHP_VERSION_ID >= 50500 ? self::class : __CLASS__;
					throw new \InvalidArgumentException(
						"[".$selfClass."] Route pattern definition can not have greedy `<param_name*>` with star "
						."(to include everything - all characters and slashes . `.*`) immediately before optional "
						."section ($this)."
					);
				}
			}
			$greedyCaught = TRUE;
			$paramName = str_replace('*', '', $paramName);
			$sectionIsLast = $sectionIndexPlusOne === $reverseSectionsCount;
		}
		return [$greedyFlag, $sectionIsLast];
	}

	/**
	 * Initialize three route integer flags. About if and what scheme definition
	 * is contained in given pattern, if and what domain parts are contained in
	 * given pattern and if given pattern contains any part of query string.
	 * Given pattern is `reverse` and if reverse is empty, it's `pattern` prop.
	 * @param string $pattern
	 * @return void
	 */
	protected function initFlagsByPatternOrReverse ($pattern) {
		$scheme = static::FLAG_SCHEME_NO;
		if (mb_strpos($pattern, '//') === 0) {
			$scheme = static::FLAG_SCHEME_ANY;
		} else if (mb_strpos($pattern, 'http://') === 0) {
			$scheme = static::FLAG_SCHEME_HTTP;
		} else if (mb_strpos($pattern, 'https://') === 0) {
			$scheme = static::FLAG_SCHEME_HTTPS;
		}
		$host = static::FLAG_HOST_NO;
		if ($scheme) {
			if (mb_strpos($pattern, static::PLACEHOLDER_HOST) !== FALSE) {
				$host = static::FLAG_HOST_HOST;
			} else if (mb_strpos($pattern, static::PLACEHOLDER_DOMAIN) !== FALSE) {
				$host = static::FLAG_HOST_DOMAIN;
			} else {
				if (mb_strpos($pattern, static::PLACEHOLDER_TLD) !== FALSE)
					$host += static::FLAG_HOST_TLD;
				if (mb_strpos($pattern, static::PLACEHOLDER_SLD) !== FALSE)
					$host += static::FLAG_HOST_SLD;
			}
			if (mb_strpos($pattern, static::PLACEHOLDER_BASEPATH) !== FALSE)
				$host += static::FLAG_HOST_BASEPATH;
		}
		$queryString = mb_strpos($pattern, '?') !== FALSE
			? static::FLAG_QUERY_INCL
			: static::FLAG_QUERY_NO;
		$this->flags = [$scheme, $host, $queryString];
	}

	/**
	 * Compose regular expression pattern to match incoming request or not.
	 * This method is called in route matching process, when it's necessary to
	 * complete route `match` property from `pattern` property. The result
	 * regular expression is always composed to match trailing slash or missing
	 * trailing slash and any fixed and variable sections defined by `pattern`.
	 * @param string		$match				A pattern string with escaped all special regular
	 *											expression special characters except `<>` chars.
	 * @param \stdClass[]	$matchSectionsInfo	Match sections info about fixed or variable
	 *											section, param name, start, end and length.
	 * @param array			$reverseParams		An array with keys as param names and values as
	 *											`\stdClass` objects with data about reverse params.
	 * @param array			$constraints		Route params regular expression constraints
	 *											Defining which value each param could contain or not.
	 *											If there is no constraint for param, there is used
	 *											default constraint defined in route static property.
	 * @return string
	 */
	protected function initMatchComposeRegex (& $match, & $matchSectionsInfo, & $reverseParams, & $constraints) {
		$sections = [];
		$paramIndex = 0;
		$reverseParamsKeys = array_keys($reverseParams);
		$paramsCount = count($reverseParamsKeys);
		$anyParams = $paramsCount > 0;
		$defaultPathConstraint = static::$defaultPathConstraint;
		$defaultDomainConstraint = static::$defaultDomainConstraint;
		$schemeFlag = $this->flags[0];
		$matchIsAbsolute = boolval($schemeFlag);
		$firstPathSlashPos = 0;
		if ($matchIsAbsolute) {
			$matchIsAbsolute = TRUE;
			$defaultConstraint = $defaultDomainConstraint;
			// if scheme flag is `http://` or `https://`, there is necessary to increase
			// `mb_strpos()` index by one, because there is always backslash in match pattern
			// before `:` - like `http\://` or `https\://`
			$firstPathSlashPos = mb_strpos($match, '/', $schemeFlag + ($schemeFlag > static::FLAG_SCHEME_ANY ? 1 : 0));
		} else {
			$defaultConstraint = $defaultPathConstraint;
		}
		$pathFixedSectionsCount = 0;
		$lastPathFixedSectionIndex = 0;
		$trailingSlash = '?';
		$one = $matchIsAbsolute ? 0 : 1;
		$sectionsCountMinusOne = count($matchSectionsInfo) - 1;
		foreach ($matchSectionsInfo as $sectionIndex => $section) {
			$sectionEnd = $section->end;
			if ($anyParams) {
				$sectionOffset = $section->start;
				$sectionResult = '';
				while ($paramIndex < $paramsCount) {
					$paramKey = $reverseParamsKeys[$paramIndex];
					$param = $reverseParams[$paramKey];
					if ($param->sectionIndex !== $sectionIndex) break;
					$paramStart = $param->matchStart;
					if ($matchIsAbsolute && $paramStart > $firstPathSlashPos)
						$defaultConstraint = $defaultPathConstraint;
					if ($sectionOffset < $paramStart)
						$sectionResult .= mb_substr($match, $sectionOffset, $paramStart - $sectionOffset);
					$paramName = $param->name;
					$customConstraint = isset($constraints[$paramName]);
					if (!$customConstraint && $param->greedy) $defaultConstraint = '.*';
					if ($customConstraint) {
						$constraint = $constraints[$paramName];
					} else {
						$constraint = $defaultConstraint;
					}
					$sectionResult .= '(?<' . $paramName . '>' . $constraint . ')';
					$paramIndex += 1;
					$sectionOffset = $param->matchEnd;
				}
				if ($sectionOffset < $sectionEnd)
					$sectionResult .= mb_substr($match, $sectionOffset, $sectionEnd - $sectionOffset);
			} else {
				$sectionResult = mb_substr($match, $section->start, $section->length);
			}
			if ($matchIsAbsolute && $sectionEnd > $firstPathSlashPos) $one = 1;
			if ($section->fixed) {
				$pathFixedSectionsCount += $one;
				$lastPathFixedSectionIndex = $sectionIndex;
			} else {
				$sectionResult = '(' . $sectionResult . ')?';
			}
			$sections[] = $sectionResult;
		}
		if ($pathFixedSectionsCount > 0) {
			$lastFixedSectionContent = & $sections[$lastPathFixedSectionIndex];
			if ($sectionsCountMinusOne == 0 && $lastPathFixedSectionIndex == 0 &&
				$lastFixedSectionContent === '/'
			) {
				$trailingSlash = ''; // homepage -> `/`
			} else {
				$lastCharIsSlash = mb_substr($lastFixedSectionContent, -1, 1) == '/';
				if ($lastPathFixedSectionIndex == $sectionsCountMinusOne) {// last section is fixed section
					if (!$lastCharIsSlash) $trailingSlash = '/?';
				} else {// last section is optional section or sections
					$lastFixedSectionContent .= ($lastCharIsSlash ? '' : '/') . '?';
					$trailingSlash = '/?';
				}}}
		$result =  '#^' . implode('', $sections) . $trailingSlash . '$#';
		if (preg_match('#[^\x20-\x7f]#', $result))
			$result .= 'u'; // add UTF-8 modifier if string contains higher chars than ASCII
		return $result;
	}

	/**
	 * Thrown a logic exception about missing key property in route object to
	 * parse `pattern` or `reverse`. Those properties are necessary to complete
	 * correctly `match` property to route incoming request or to complete
	 * correctly `reverse` property to build URL address.
	 * @throws \LogicException Route configuration property is missing.
	 * @param \string[] $propsNames,... Missing properties names.
	 * @return void
	 */
	protected function throwExceptionIfKeyPropertyIsMissing ($propsNames) {
		$propsNames = func_get_args();
		$selfClass = \PHP_VERSION_ID >= 50500 ? self::class : __CLASS__;
		throw new \LogicException(
			"[".$selfClass."] Route configuration property/properties is/are"
			." missing: `" . implode("`, `", $propsNames) . "`, to parse and"
			." complete key properties `match` and/or `reverse` to route"
			." or build URL correctly ($this)."
		);
	}

	/**
	 * This method serve only for debug and development purposes. It renders all
	 * instance properties values into string, to print whole route in logic
	 * exception message about what property is missing.
	 * @return string
	 */
	public function __toString () {
		$type = new \ReflectionClass($this);
		$allProps = $type->getProperties(
			\ReflectionProperty::IS_PUBLIC |
			\ReflectionProperty::IS_PROTECTED |
			\ReflectionProperty::IS_PRIVATE
		);
		$result = [];
		/** @var $prop \ReflectionProperty */
		foreach ($allProps as $prop) {
			if ($prop->isStatic()) continue;
			if ($prop->isPrivate()) $prop->setAccessible(TRUE);
			$value = NULL;
			try {
				$value = $prop->getValue($this);
			} catch (\Exception $e) {};
			$result[] = '"' . $prop->getName() . '":"' . ($value === NULL ? 'NULL' : var_export($value)) . '"';
		}
		return '{'.implode(', ', $result) . '}';
	}

	/**
	 * Collect all properties names to serialize them by `serialize()` method.
	 * Collect all instance properties declared as private, protected and public
	 * and if there is not configured in `static::$protectedProperties` anything
	 * under property name, return those properties in result array.
	 * @return \string[]
	 */
	public function __sleep () {
		/** @var $this \MvcCore\Route */
		return static::__getPropsNames();
	}

	/**
	 * Return property names to be serialized.
	 * @return \string[]
	 */
	private static function __getPropsNames () {
		/** @var $this \MvcCore\Route */
		static $__propsNames = NULL;
		if ($__propsNames == NULL) {
			$props = (new \ReflectionClass(get_called_class()))->getProperties(
				\ReflectionProperty::IS_PUBLIC |
				\ReflectionProperty::IS_PROTECTED |
				\ReflectionProperty::IS_PRIVATE
			);
			$__propsNames = [];
			foreach ($props as $prop)
				if (
					!$prop->isStatic() &&
					!isset(static::$protectedProperties[$prop->name])
				)
					$__propsNames[] = $prop->name;
		}
		return $__propsNames;
	}

	/**
	 * Assign router instance to local property `$this->router;`.
	 * @return void
	 */
	public function __wakeup () {
		$this->router = \MvcCore\Application::GetInstance()->GetRouter();
	}
}
