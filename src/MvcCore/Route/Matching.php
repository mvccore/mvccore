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

namespace MvcCore\Route;

/**
 * @mixin \MvcCore\Route
 */
trait Matching {

	/**
	 * @inheritDocs
	 * @param  \MvcCore\Request $request The request object instance.
	 * @throws \LogicException           Route configuration property is missing.
	 * @throws \InvalidArgumentException Wrong route pattern format.
	 * @return array                     Matched and params array, keys are matched
	 *                                   params or controller and action params.
	 */
	public function Matches (\MvcCore\IRequest $request) {
		$matchedParams = NULL;
		$pattern = $this->matchesGetPattern();
		$subject = $this->matchesGetSubject($request);
		$matchedValues = $this->match($pattern, $subject);
		if (isset($matchedValues[0]) && count($matchedValues[0]) > 0) {
			$defaultParams = $this->GetDefaults();
			$matchedParams = $this->matchesParseRewriteParams($matchedValues, $defaultParams);
			if (isset($matchedParams[$this->lastPatternParam])) 
				$matchedParams[$this->lastPatternParam] = rtrim(
				$matchedParams[$this->lastPatternParam], '/'
			);
		}
		return $matchedParams;
	}

	/**
	 * Return pattern value used for `preg_match_all()` route match processing.
	 * Check if `match` property has any value and if it has, process internal
	 * route initialization only on `reverse` (or `pattern`) property, because 
	 * `match` regular expression is probably prepared and initialized manually. 
	 * If there is no value in `match` property (`NULL`), process internal 
	 * initialization on `pattern` property (or on `reverse` if exists) and 
	 * complete regular expression into `match` property and metadata about 
	 * `reverse` property to build URL address any time later on this route.
	 * @throws \LogicException Route configuration property is missing.
	 * @throws \InvalidArgumentException Wrong route pattern format.
	 * @return string
	 */
	protected function matchesGetPattern () {
		if ($this->match === NULL) {
			$this->initMatchAndReverse();
		} else {
			$this->initReverse();
		}
		return $this->match;
	}

	/**
	 * Return subject value used for `preg_match_all()` route match processing.
	 * Complete subject by route flags. If route `pattern` (or `reverse`) contains
	 * domain part or base path, prepare those values from request object. Than 
	 * prepare always request path and if route `pattern` (or `reverse`) contains
	 * any query string part, append into result subject query string from request
	 * object.
	 * @param  \MvcCore\Request $request 
	 * @return string
	 */
	protected function matchesGetSubject (\MvcCore\IRequest $request) {
		$subject = $this->matchesGetSubjectHostAndBase($request) 
			. $request->GetPath(TRUE);
		if (($this->flags & static::FLAG_QUERY_INCL) != 0)
			$subject .= $request->GetQuery(TRUE, TRUE);
		return $subject;
	}

	/**
	 * Process `preg_match_all()` by given `$pattern` on given `$subject`.
	 * If subject contains higher characters than ASCII, add unicode modifier 
	 * after pattern if necessary.
	 * @param  string $pattern 
	 * @param  string $subject 
	 * @return array
	 */
	protected function match ($pattern, & $subject) {
		// convert pattern to UTF-8 with UTF-8 regexp flag
		// if subject string contains UTF-8 chars:
		if ($this->getStrIsUtf8($subject)) {
			$lastHashPos = mb_strrpos($pattern, '#');
			$patternHasUtf8Flag = mb_strpos($pattern, 'u', $lastHashPos + 1) !== FALSE;
			if (!$patternHasUtf8Flag) $pattern .= 'u';
			$patternEncoding = mb_detect_encoding($pattern, NULL);
			$pattern = iconv($patternEncoding, 'UTF-8', $pattern);
		}
		preg_match_all($pattern, $subject, $matchedValues);
		return $matchedValues;
	}

	/**
	 * Return subject value scheme, domain and base path part, used for 
	 * `preg_match_all()` route match processing. Check which scheme route
	 * `pattern` (or `reverse`) contains and prepare scheme string. Than check 
	 * if route `pattern` (or `reverse`) contains domain part with any domain 
	 * placeholders and prepare domain part with the placeholders. Then also in 
	 * the same way prepare base path part if necessary, there is also base path 
	 * placeholder possibility.
	 * @param  \MvcCore\Request $request 
	 * @return string
	 */
	protected function matchesGetSubjectHostAndBase (\MvcCore\IRequest $request) {
		$basePathDefined = FALSE;
		$basePath = '';
		if (($this->flags & static::FLAG_HOST_BASEPATH) != 0) {
			$basePathDefined = TRUE;
			$basePath = static::PLACEHOLDER_BASEPATH;
		}
		if (($this->flags & static::FLAG_SCHEME_ANY) != 0) {
			if (!$basePathDefined)
				$basePath = $request->GetBasePath();
			$subject = $this->matchesGetSubjectScheme()
				. $this->matchesGetSubjectHost($request)
				. $basePath;
		} else {
			$subject = $basePathDefined ? $basePath : '';
		}
		return $subject;
	}

	/**
	 * Return subject value - the scheme part, used for `preg_match_all()` route 
	 * match processing. Given flag value contains scheme part string length,  
	 * which is an array index inside local static property to return real scheme 
	 * string by the flag.
	 * @return string
	 */
	protected function matchesGetSubjectScheme () {
		$httpScheme = ($this->flags & static::FLAG_SCHEME_HTTP) != 0;
		$httpsScheme = ($this->flags & static::FLAG_SCHEME_HTTPS) != 0;
		if ($httpScheme && $httpsScheme) 
			return '//';
		if ($httpScheme)
			return 'http://';
		if ($httpsScheme) 
			return 'https://';
		return '';
	}
	
	/**
	 * Return subject value - the domain part, used for `preg_match_all()` route 
	 * match processing. Given flag value contains integer about which placeholder 
	 * strings the route `pattern` (or `reverse`) contains. Result is only the 
	 * domain part with requested domain parts or placeholders to match pattern 
	 * and subject in match processing.
	 * @param  \MvcCore\Request $request 
	 * @return string
	 */
	protected function matchesGetSubjectHost (\MvcCore\IRequest $request) {
		$hostPart = '';
		if (($this->flags & static::FLAG_HOST_NO) != 0) {
			$hostPart = $request->GetHostName();
		} else if (($this->flags & static::FLAG_HOST_HOST) != 0) {
			$hostPart = static::PLACEHOLDER_HOST;
		} else if (($this->flags & static::FLAG_HOST_DOMAIN) != 0) {
			$hostPart = $request->GetThirdLevelDomain() . '.' . static::PLACEHOLDER_DOMAIN;
		} else if (($this->flags & static::FLAG_HOST_TLD) != 0) {
			$hostPart = $request->GetThirdLevelDomain() 
				. '.' . $request->GetSecondLevelDomain()
				. '.' . static::PLACEHOLDER_TLD;
		} else if (($this->flags & static::FLAG_HOST_SLD) != 0) {
			$hostPart = $request->GetThirdLevelDomain() 
				. '.' . static::PLACEHOLDER_SLD
				. '.' . $request->GetTopLevelDomain();
		} else if (
			($this->flags & static::FLAG_HOST_TLD) != 0 &&
			($this->flags & static::FLAG_HOST_SLD) != 0
		) {
			$hostPart = $request->GetThirdLevelDomain() 
				. '.' . static::PLACEHOLDER_SLD
				. '.' . static::PLACEHOLDER_TLD;
		}
		return $hostPart;
	}

	/**
	 * Parse rewrite params from `preg_match_all()` `$matches` result array into 
	 * array, keyed by param name with parsed value. If route has defined any
	 * `controller` or `action` property, those values are defined into result 
	 * array first, converted into dashed case. If any rewrite param defines 
	 * `controller` or `action` again, those values are overwritten in result 
	 * array by values from regular expression `$matches` array.
	 * @param  array $matchedValues 
	 * @param  array $defaults 
	 * @return array
	 */
	protected function matchesParseRewriteParams (& $matchedValues, & $defaults) {
		/** @var \MvcCore\Tool $toolClass */
		$toolClass = \MvcCore\Application::GetInstance()->GetToolClass();
		$matchedParams = [];
		$router = $this->router;
		if ($this->controller !== NULL) 
			$matchedParams[$router::URL_PARAM_CONTROLLER] = $toolClass::GetDashedFromPascalCase(
				str_replace(['_', '\\'], '/', $this->controller)
			);
		if ($this->action !== NULL)
			$matchedParams[$router::URL_PARAM_ACTION] = $toolClass::GetDashedFromPascalCase(
				$this->action
			);
		array_shift($matchedValues); // first item is always matched whole `$request->GetPath()` string.
		foreach ($matchedValues as $key => $matchedValueArr) {
			if (is_numeric($key)) continue;
			$matchedValue = (string) current($matchedValueArr);
			if (!isset($defaults[$key])) 
				$defaults[$key] = NULL;
			$matchedEmptyString = mb_strlen($matchedValue) === 0;
			if ($matchedEmptyString)
				$matchedValue = $defaults[$key];
			// continue if there is already valid ctrl and action from route ctrl or action configuration
			if (isset($matchedParams[$key]) && $matchedEmptyString) continue;
			$matchedParams[$key] = $matchedValue;
		}
		return $matchedParams;
	}
}
