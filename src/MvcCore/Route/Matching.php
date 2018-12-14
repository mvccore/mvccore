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

trait Matching
{
	/**
	 * Return array of all matched params, with matched controller and action 
	 * names, if route matches (always) request property `\MvcCore\Request::$path` 
	 * by PHP `preg_match_all()`. Sometimes, matching subject could be different, 
	 * if route specifies it - if route `pattern` (or `match`) property contains
	 * domain (or base path part) - it means if it is absolute or if `pattern` 
	 * (or `match`) property contains a query string part.
	 * This method is usually called in core request routing process
	 * from `\MvcCore\Router::Route();` method and it's sub-methods.
	 * @param \MvcCore\Request $request The request object instance.
	 * @throws \LogicException Route configuration property is missing.
	 * @throws \InvalidArgumentException Wrong route pattern format.
	 * @return array Matched and params array, keys are matched
	 *				 params or controller and action params.
	 */
	public function & Matches (\MvcCore\IRequest & $request) {
		$matchedParams = NULL;
		$pattern = & $this->matchesGetPattern();
		$subject = $this->matchesGetSubject($request);
		preg_match_all($pattern, $subject, $matchedValues);
		if (isset($matchedValues[0]) && count($matchedValues[0]) > 0) {
			$matchedParams = $this->matchesParseRewriteParams($matchedValues, $this->GetDefaults());
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
	protected function & matchesGetPattern () {
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
	 * @param \MvcCore\IRequest $request 
	 * @return string
	 */
	protected function matchesGetSubject (\MvcCore\IRequest & $request) {
		$subject = $this->matchesGetSubjectHostAndBase($request) 
			. $request->GetPath(TRUE);
		if ($this->flags[2]) 
			$subject .= $request->GetQuery(TRUE, TRUE);
		return $subject;
	}

	/**
	 * Return subject value scheme, domain and base path part, used for 
	 * `preg_match_all()` route match processing. Check which protocol route
	 * `pattern` (or `reverse`) contains and prepare protocol string. Than check 
	 * if route `pattern` (or `reverse`) contains domain part with any domain 
	 * placeholders and prepare domain part with the placeholders. Then also in 
	 * the same way prepare base path part if necessary, there is also base path 
	 * placeholder possibility.
	 * @param \MvcCore\IRequest $request 
	 * @return string
	 */
	protected function matchesGetSubjectHostAndBase (\MvcCore\IRequest & $request) {
		$schemeFlag = $this->flags[0];
		$basePathDefined = FALSE;
		$basePath = '';
		$hostFlag = $this->flags[1];
		if ($hostFlag >= static::FLAG_HOST_BASEPATH /* 10 */) {
			$hostFlag -= static::FLAG_HOST_BASEPATH;
			$basePath = static::PLACEHOLDER_BASEPATH;
			$basePathDefined = TRUE;
		}
		if ($schemeFlag) {
			if (!$basePathDefined)
				$basePath = $request->GetBasePath();
			$subject = $this->matchesGetSubjectScheme($schemeFlag)
				. $this->matchesGetSubjectHost($request, $hostFlag)
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
	 * @param int $schemeFlag 
	 * @return string
	 */
	protected function matchesGetSubjectScheme (& $schemeFlag) {
		static $prefixes = NULL;
		if ($prefixes === NULL) $prefixes = [
			static::FLAG_SCHEME_NO		=> '',			// 0
			static::FLAG_SCHEME_ANY		=> '//',		// 2
			static::FLAG_SCHEME_HTTP	=> 'http://',	// 7
			static::FLAG_SCHEME_HTTPS	=> 'https://',	// 8
		];
		return $prefixes[$schemeFlag];
	}
	
	/**
	 * Return subject value - the domain part, used for `preg_match_all()` route 
	 * match processing. Given flag value contains integer about which placeholder 
	 * strings the route `pattern` (or `reverse`) contains. Result is only the 
	 * domain part with requested domain parts or placeholders to match pattern 
	 * and subject in match processing.
	 * @param \MvcCore\IRequest $request 
	 * @param int $hostFlag 
	 * @return string
	 */
	protected function matchesGetSubjectHost (\MvcCore\IRequest & $request, & $hostFlag) {
		$hostPart = '';
		if ($hostFlag == static::FLAG_HOST_NO /* 0 */) {
			$hostPart = $request->GetHostName();
		} else if ($hostFlag == static::FLAG_HOST_HOST /* 1 */) {
			$hostPart = static::PLACEHOLDER_HOST;
		} else if ($hostFlag == static::FLAG_HOST_DOMAIN /* 2 */) {
			$hostPart = $request->GetThirdLevelDomain() . '.' . static::PLACEHOLDER_DOMAIN;
		} else if ($hostFlag == static::FLAG_HOST_TLD /* 3 */) {
			$hostPart = $request->GetThirdLevelDomain() 
				. '.' . $request->GetSecondLevelDomain()
				. '.' . static::PLACEHOLDER_TLD;
		} else if ($hostFlag == static::FLAG_HOST_SLD /* 4 */) {
			$hostPart = $request->GetThirdLevelDomain() 
				. '.' . static::PLACEHOLDER_SLD
				. '.' . $request->GetTopLevelDomain();
		} else if ($hostFlag == static::FLAG_HOST_TLD + static::FLAG_HOST_SLD /* 7 */) {
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
	 * @param array $matchedValues 
	 * @param array $defaults 
	 * @return array
	 */
	protected function & matchesParseRewriteParams (& $matchedValues, & $defaults) {
		$toolClass = \MvcCore\Application::GetInstance()->GetToolClass();
		$matchedParams = [];
		if ($this->controller !== NULL) 
			$matchedParams['controller'] = $toolClass::GetDashedFromPascalCase(
				str_replace(['_', '\\'], '/', $this->controller)
			);
		if ($this->action !== NULL)
			$matchedParams['action'] = $toolClass::GetDashedFromPascalCase(
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
