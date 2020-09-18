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

trait UrlBuilding
{
	/**
	 * Filter given `array $params` by configured `"in" | "out"` filter `callable`.
	 * This function return `array` with first item as `bool` about successful
	 * filter processing in `try/catch` and second item as filtered params `array`.
	 * @param array		$params
	 * @param array		$defaultParams
	 * @param string	$direction
	 * @return array	Filtered params array.
	 */
	public function Filter (array & $params = [], array & $defaultParams = [], $direction = \MvcCore\IRoute::CONFIG_FILTER_IN) {
		/** @var $this \MvcCore\Route */
		if (!$this->filters || !isset($this->filters[$direction]))
			return [TRUE, $params];
		list($closureCalling, $handler) = $this->filters[$direction];
		try {
			$req = \MvcCore\Application::GetInstance()->GetRequest();
			if ($closureCalling) {
				$newParams = $handler($params, $defaultParams, $req);
			} else {
				$newParams = call_user_func_array($handler, [$params, $defaultParams, $req]);
			}
			$success = TRUE;
		} catch (\RuntimeException $e) {
			$debugClass = \MvcCore\Application::GetInstance()->GetDebugClass();
			$debugClass::Log($e, \MvcCore\IDebug::ERROR);
			$success = FALSE;
			$newParams = $params;
		}
		return [$success, $newParams];
	}

	/**
	 * Complete route URL by given params array and route internal reverse
	 * replacements pattern string. If there are more given params in first
	 * argument than total count of replacement places in reverse pattern,
	 * then create URL with query string params after reverse pattern,
	 * containing that extra record(s) value(s). Returned is an array with only
	 * one string as result URL or it could be returned for extended classes
	 * an array with two strings - result URL in two parts - first part as scheme,
	 * domain and base path and second as path and query string.
	 * Example:
	 *	Input (`$params`):
	 *		`[
	 *			"name"		=> "cool-product-name",
	 *			"color"		=> "blue",
	 *			"variants"	=> ["L", "XL"],
	 *		];`
	 *	Input (`\MvcCore\Route::$reverse`):
	 *		`"/products-list/<name>/<color*>"`
	 *	Output:
	 *		`["/any/app/base/path/products-list/cool-product-name/blue?variant[]=L&amp;variant[]=XL"]`
	 *		or:
	 *		`[
	 *			"/any/app/base/path",
	 *			"/products-list/cool-product-name/blue?variant[]=L&amp;variant[]=XL"
	 *		]`
	 * @param \MvcCore\Request	$request
	 *							Currently requested request object.
	 * @param array				$params
	 *							URL params from application point completed
	 *							by developer.
	 * @param array				$defaultUrlParams
	 *							Requested URL route params and query string
	 *							params without escaped HTML special chars:
	 *							`< > & " ' &`.
	 * @param string			$queryStringParamsSepatator
	 *							Query params separator, `&` by default. Always
	 *							automatically completed by router instance.
	 * @param bool				$splitUrl
	 *							Boolean value about to split completed result URL
	 *							into two parts or not. Default is FALSE to return
	 *							a string array with only one record - the result
	 *							URL. If `TRUE`, result url is split into two
	 *							parts and function return array with two items.
	 * @return \string[]		Result URL address in array. If last argument is
	 *							`FALSE` by default, this function returns only
	 *							single item array with result URL. If last
	 *							argument is `TRUE`, function returns result URL
	 *							in two parts - domain part with base path and
	 *							path part with query string.
	 */
	public function Url (\MvcCore\IRequest $request, array & $params = [], array & $defaultUrlParams = [], $queryStringParamsSepatator = '&', $splitUrl = FALSE) {
		/** @var $this \MvcCore\Route */
		// check reverse initialization
		if ($this->reverseParams === NULL) $this->initReverse();
		// unset all params with the same values as route defaults configuration
		foreach ($params as $paramName => $paramValue) 
			if (isset($this->defaults[$paramName]) && $this->defaults[$paramName] == $paramValue)
				unset($params[$paramName]);
		// complete and filter all params to build reverse pattern
		if (count($this->reverseParams) === 0) {
			$allParamsClone = array_merge([], $params);
		} else {// complete params with necessary values to build reverse pattern (and than query string)
			$emptyReverseParams = array_fill_keys(array_keys($this->reverseParams), NULL);
			$allMergedParams = array_merge($this->defaults, $defaultUrlParams, $params);
			// all params clone contains only keys necessary to build reverse
			// pattern for this route and all given `$params` keys, nothing more
			// from currently requested URL
			$allParamsClone = array_merge(
				$emptyReverseParams, array_intersect_key($allMergedParams, $emptyReverseParams), $params
			);
		}
		// filter params
		list(,$filteredParams) = $this->Filter($allParamsClone, $defaultUrlParams, \MvcCore\IRoute::CONFIG_FILTER_OUT);
		// split params into domain params array and into path and query params array
		$domainParams = $this->urlGetAndRemoveDomainPercentageParams($filteredParams);
		// build reverse pattern
		$result = $this->urlComposeByReverseSectionsAndParams(
			$this->reverse,
			$this->reverseSections,
			$this->reverseParams,
			$filteredParams,
			$this->defaults
		);
		// add all remaining params to query string
		if ($filteredParams) {
			// `http_build_query()` automatically converts all XSS chars to entities (`< > & " ' &`):
			$result .= (mb_strpos($result, '?') !== FALSE ? $queryStringParamsSepatator : '?')
				. str_replace('%2F', '/', http_build_query($filteredParams, '', $queryStringParamsSepatator, PHP_QUERY_RFC3986));
		}
		return $this->urlAbsPartAndSplit($request, $result, $domainParams, $splitUrl);
	}

	/**
	 * Compose URL by reverse pattern value, by reverse (fixed or variable)
	 * sections info, by reverse params info (start, end, length), by given
	 * params array with final values and by default params values defined by
	 * route object. Unset all applied params from given `$params` array to not
	 * render them again in possible query string later.
	 * @param string		$reverse			A route reverse string without brackets defining
	 *											URL variable sections.
	 * @param \stdClass[]	$reverseSections	Reverse sections info, where each item contains
	 *											data about if section is fixed or not, start,
	 *											length and end of section.
	 * @param array			$reverseParams		An array with keys as param names and values as
	 *											`\stdClass` objects with data about each reverse param.
	 * @param array			$params				An array with keys as param names and values as
	 *											param final values.
	 * @param array			$defaults			An array with keys as param names and values as
	 *											param default values defined in route object.
	 * @return string
	 */
	protected function urlComposeByReverseSectionsAndParams (& $reverse, & $reverseSections, & $reverseParams, & $params, & $defaults) {
		/** @var $this \MvcCore\Route */
		$sections = [];
		$paramIndex = 0;
		$reverseParamsKeys = array_keys($reverseParams);
		$paramsCount = count($reverseParamsKeys);
		$anyParams = $paramsCount > 0;
		foreach ($reverseSections as $sectionIndex => & $section) {
			$fixed = $section->fixed;
			$sectionResult = '';
			if ($anyParams) {
				$sectionOffset = $section->start;
				$sectionParamsCount = 0;
				$defaultValuesCount = 0;
				while ($paramIndex < $paramsCount) {
					$paramKey = $reverseParamsKeys[$paramIndex];
					$reverseParam = $reverseParams[$paramKey];
					if ($reverseParam->sectionIndex !== $sectionIndex) break;
					$sectionParamsCount++;
					$paramStart = $reverseParam->reverseStart;
					if ($sectionOffset < $paramStart)
						$sectionResult .= mb_substr($reverse, $sectionOffset, $paramStart - $sectionOffset);
					$paramName = $reverseParam->name;
					$paramValue = $params[$paramName];
					$paramValueStr = is_array($paramValue) ? implode(',', $paramValue) : strval($paramValue);
					if (
						$paramValue === NULL || (
							array_key_exists($paramName, $defaults) && 
							$paramValueStr == strval($defaults[$paramName])
						)
					) $defaultValuesCount++;
					$sectionResult .= htmlspecialchars($paramValueStr, ENT_QUOTES);
					unset($params[$paramName]);
					$paramIndex += 1;
					$sectionOffset = $reverseParam->reverseEnd;
				}
				$sectionEnd = $section->end;
				if (!$fixed && $sectionParamsCount === $defaultValuesCount) {
					$sectionResult = '';
				} else if ($sectionOffset < $sectionEnd) {
					$sectionResult .= mb_substr($reverse, $sectionOffset, $sectionEnd - $sectionOffset);
				}
			} else if ($fixed) {
				$sectionResult = mb_substr($reverse, $section->start, $section->length);
			}
			$sections[] = $sectionResult;
		}
		$result = implode('', $sections);
		$result = & $this->urlCorrectTrailingSlashBehaviour($result);
		return $result;
	}

	/**
	 * After final URL is completed, split result URL into two parts. First part
	 * as scheme, domain part and base path and second part as application
	 * request path and query string.
	 * @param \MvcCore\IRequest $request
	 *							A request object.
	 * @param string			$resultUrl
	 *							Result URL to split. REsult URL still could
	 *							contain domain part or base path replacements.
	 * @param array				$domainParams
	 *							Array with params for first URL part (scheme,
	 *							domain, base path).
	 * @param bool				$splitUrl
	 *							Boolean value about to split completed result URL
	 *							into two parts or not. Default is FALSE to return
	 *							a string array with only one record - the result
	 *							URL. If `TRUE`, result url is split into two
	 *							parts and function return array with two items.
	 * @return \string[]		Result URL address in array. If last argument is
	 *							`FALSE` by default, this function returns only
	 *							single item array with result URL. If last
	 *							argument is `TRUE`, function returns result URL
	 *							in two parts - domain part with base path and
	 *							path part with query string.
	 */
	protected function urlAbsPartAndSplit (\MvcCore\IRequest $request, $resultUrl, & $domainParams, $splitUrl) {
		/** @var $this \MvcCore\Route */
		$domainParamsFlag = $this->flags[1];
		$basePathInReverse = FALSE;
		if ($domainParamsFlag >= static::FLAG_HOST_BASEPATH) {
			$basePathInReverse = TRUE;
			$domainParamsFlag -= static::FLAG_HOST_BASEPATH;
		}
		if ($this->flags[0]) {
			// route is defined as absolute with possible `%domain%` and other params
			// process possible replacements in reverse result - `%host%`, `%domain%`, `%tld%` and `%sld%`
			$this->urlReplaceDomainReverseParams($request, $resultUrl, $domainParams, $domainParamsFlag);
			// try to find URL position after domain part and after base path part
			if ($basePathInReverse) {
				return $this->urlAbsPartAndSplitByReverseBasePath($request, $resultUrl, $domainParams, $splitUrl);
			} else {
				return $this->urlAbsPartAndSplitByRequestedBasePath($request, $resultUrl, $splitUrl);
			}
		} else {
			// route is not defined as absolute, there could be only flag
			// in domain params array to complete absolute URL by developer
			// and there could be also `basePath` param defined.
			return $this->urlAbsPartAndSplitByGlobalSwitchOrBasePath(
				$request, $resultUrl, $domainParams, $domainParamsFlag, $splitUrl
			);
		}
	}

	/**
	 * Replace all domain params percentage replacements in finally completed
	 * URL by given `$domainParams` array values. If there is founded any
	 * percentage replacement which is not presented in `$domainParams` array,
	 * there is used value from request object.
	 * @param \MvcCore\IRequest $request			A request object.
	 * @param string			$resultUrl			Result URL to split. REsult URL
	 *												still could contain domain part
	 *												or base path replacements.
	 * @param array				$domainParams		Array with params for first URL
	 *												part (scheme, domain, base path).
	 * @param mixed				$domainParamsFlag	Second route flag about domain
	 *												without value about base path.
	 *												This value contains info what
	 *												percentage replacements was
	 *												contained in reverse pattern.
	 */
	protected function urlReplaceDomainReverseParams (\MvcCore\IRequest $request, & $resultUrl, & $domainParams, $domainParamsFlag) {
		/** @var $this \MvcCore\Route */
		$replacements = [];
		$values = [];
		$router = $this->router;
		if ($domainParamsFlag == static::FLAG_HOST_HOST) {
			$hostParamName = $router::URL_PARAM_HOST;
			$replacements[] = static::PLACEHOLDER_HOST;
			$values[] = isset($domainParams[$hostParamName])
				? $domainParams[$hostParamName]
				: $request->GetHost();
		} else if ($domainParamsFlag == static::FLAG_HOST_DOMAIN) {
			$domainParamName = $router::URL_PARAM_DOMAIN;
			$replacements[] = static::PLACEHOLDER_DOMAIN;
			$values[] = isset($domainParams[$domainParamName])
				? $domainParams[$domainParamName]
				: $request->GetSecondLevelDomain() . '.' . $request->GetTopLevelDomain();
		} else {
			if ($domainParamsFlag == static::FLAG_HOST_TLD) {
				$tldParamName = $router::URL_PARAM_TLD;
				$replacements[] = static::PLACEHOLDER_TLD;
				$values[] = isset($domainParams[$tldParamName])
					? $domainParams[$tldParamName]
					: $request->GetTopLevelDomain();
			} else if ($domainParamsFlag == static::FLAG_HOST_SLD) {
				$sldParamName = $router::URL_PARAM_SLD;
				$replacements[] = static::PLACEHOLDER_SLD;
				$values[] = isset($domainParams[$sldParamName])
					? $domainParams[$sldParamName]
					: $request->GetSecondLevelDomain();
			} else if ($domainParamsFlag == static::FLAG_HOST_TLD + static::FLAG_HOST_SLD) {
				$tldParamName = $router::URL_PARAM_TLD;
				$sldParamName = $router::URL_PARAM_SLD;
				$replacements[] = static::PLACEHOLDER_TLD;
				$replacements[] = static::PLACEHOLDER_SLD;
				$values[] = isset($domainParams[$tldParamName])
					? $domainParams[$tldParamName]
					: $request->GetTopLevelDomain();
				$values[] = isset($domainParams[$sldParamName])
					? $domainParams[$sldParamName]
					: $request->GetSecondLevelDomain();
			}
		}
		$resultUrl = str_replace($replacements, $values, $resultUrl);
	}

	/**
	 * After final URL is completed, split result URL into two parts if there
	 * is contained domain part and base path in reverse, what is base material
	 * to complete result URL. If there is found base path percentage
	 * replacement in result url, split url after that percentage replacement
	 * and replace that part with domain param value or request base path value.
	 * @param \MvcCore\IRequest $request
	 *							A request object.
	 * @param string			$resultUrl
	 *							Result URL to split. Result URL still could
	 *							contain domain part or base path replacements.
	 * @param array				$domainParams
	 *							Array with params for first URL part (scheme,
	 *							domain, base path).
	 * @param bool				$splitUrl
	 *							Boolean value about to split completed result URL
	 *							into two parts or not. Default is FALSE to return
	 *							a string array with only one record - the result
	 *							URL. If `TRUE`, result url is split into two
	 *							parts and function return array with two items.
	 * @return \string[]		Result URL address in array. If last argument is
	 *							`FALSE` by default, this function returns only
	 *							single item array with result URL. If last
	 *							argument is `TRUE`, function returns result URL
	 *							in two parts - domain part with base path and
	 *							path part with query string.
	 */
	protected function urlAbsPartAndSplitByReverseBasePath (\MvcCore\IRequest $request, $resultUrl, & $domainParams, $splitUrl) {
		/** @var $this \MvcCore\Route */
		$doubleSlashPos = mb_strpos($resultUrl, '//');
		$doubleSlashPos = $doubleSlashPos === FALSE
			? 0
			: $doubleSlashPos + 2;
		$router = $this->router;
		$basePathPlaceHolderPos = mb_strpos($resultUrl, static::PLACEHOLDER_BASEPATH, $doubleSlashPos);
		if ($basePathPlaceHolderPos === FALSE) {
			return $this->urlAbsPartAndSplitByRequestedBasePath(
				$request, $resultUrl, $splitUrl
			);
		}
		$pathPart = mb_substr($resultUrl, $basePathPlaceHolderPos + mb_strlen(static::PLACEHOLDER_BASEPATH));
		$questionMarkPos = mb_strpos($pathPart, '?');
		if ($questionMarkPos === FALSE) {
			$pathPart = str_replace('//', '/', $pathPart);	
		} else {
			$pathPart = str_replace('//', '/', mb_substr($pathPart, 0, $questionMarkPos))
				. mb_substr($pathPart, $questionMarkPos);
		}
		$basePart = mb_substr($resultUrl, 0, $basePathPlaceHolderPos);
		$basePathParamName = $router::URL_PARAM_BASEPATH;
		$basePart .= isset($domainParams[$basePathParamName])
			? $domainParams[$basePathParamName]
			: $request->GetBasePath();
		if ($this->flags[0] === static::FLAG_SCHEME_ANY)
			$basePart = $request->GetScheme() . $basePart;
		if ($splitUrl) return [$basePart, $pathPart];
		return [$basePart . $pathPart];
	}

	/**
	 * After final URL is completed, split result URL into two parts if there
	 * is contained domain part and base path in reverse, what is base material
	 * to complete result URL. Try to found the point in result URL, where is
	 * base path end and application request path begin. By that point split and
	 * return result URL.
	 * @param \MvcCore\IRequest $request
	 *							A request object.
	 * @param string			$resultUrl
	 *							Result URL to split. Result URL still could
	 *							contain domain part or base path replacements.
	 * @param bool				$splitUrl
	 *							Boolean value about to split completed result URL
	 *							into two parts or not. Default is FALSE to return
	 *							a string array with only one record - the result
	 *							URL. If `TRUE`, result url is split into two
	 *							parts and function return array with two items.
	 * @return \string[]		Result URL address in array. If last argument is
	 *							`FALSE` by default, this function returns only
	 *							single item array with result URL. If last
	 *							argument is `TRUE`, function returns result URL
	 *							in two parts - domain part with base path and
	 *							path part with query string.
	 */
	protected function urlAbsPartAndSplitByRequestedBasePath (\MvcCore\IRequest $request, $resultUrl, $splitUrl) {
		/** @var $this \MvcCore\Route */
		$doubleSlashPos = mb_strpos($resultUrl, '//');
		$doubleSlashPos = $doubleSlashPos === FALSE
			? 0
			: $doubleSlashPos + 2;
		if (!$splitUrl) {
			$resultSchemePart = mb_substr($resultUrl, 0, $doubleSlashPos);
			$resultAfterScheme = mb_substr($resultUrl, $doubleSlashPos);
			$resultAfterScheme = str_replace('//', '/', $resultAfterScheme);
			if ($this->flags[0] === static::FLAG_SCHEME_ANY) {
				$resultUrl = $request->GetScheme() . '//' . $resultAfterScheme;
			} else {
				$resultUrl = $resultSchemePart . $resultAfterScheme;
			}
			return [$resultUrl];
		} else {
			$nextSlashPos = mb_strpos($resultUrl, '/', $doubleSlashPos);
			if ($nextSlashPos === FALSE) {
				$queryStringPos = mb_strpos($resultUrl, '?', $doubleSlashPos);
				$baseUrlPartEndPos = $queryStringPos === FALSE
					? mb_strlen($resultUrl)
					: $queryStringPos;
			} else {
				$baseUrlPartEndPos = $nextSlashPos;
			}
			$requestedBasePath = $request->GetBasePath();
			$basePathLength = mb_strlen($requestedBasePath);
			if ($basePathLength > 0) {
				$basePathPos = mb_strpos($resultUrl, $requestedBasePath, $baseUrlPartEndPos);
				if ($basePathPos === $baseUrlPartEndPos)
					$baseUrlPartEndPos += $basePathLength;
			}
			$basePart = mb_substr($resultUrl, 0, $baseUrlPartEndPos);
			if ($this->flags[0] === static::FLAG_SCHEME_ANY)
				$basePart = $request->GetScheme() . $basePart;
			$pathAndQueryPart = mb_substr($resultUrl, $baseUrlPartEndPos);
			$questionMarkPos = mb_strpos($pathAndQueryPart, '?');
			if ($questionMarkPos === FALSE) {
				$pathAndQueryPart = str_replace('//', '/', $pathAndQueryPart);	
			} else {
				$pathAndQueryPart = str_replace('//', '/', mb_substr($pathAndQueryPart, 0, $questionMarkPos))
					. mb_substr($pathAndQueryPart, $questionMarkPos);
			}
			return [$basePart, $pathAndQueryPart];
		}
	}

	/**
	 * After final URL is completed and if there is contained no scheme, no
	 * domain part and no base path part in route pattern (or reverse), then make
	 * checks if it needs scheme part, domain part or base path part by global
	 * route flag property `absolute` or by given params. Then complete absolute
	 * part and return result URL as single array record or split result URL by
	 * base path end point.
	 * @param \MvcCore\IRequest $request
	 *							A request object.
	 * @param string			$resultUrl
	 *							Result URL to split. Result URL still could
	 *							contain domain part or base path replacements.
	 * @param array				$domainParams
	 *							Array with params for first URL part (scheme,
	 *							domain, base path).
	 * @param bool				$domainParamsFlag
	 *							Route second int flag value without base path.
	 * @param bool				$splitUrl
	 *							Boolean value about to split completed result URL
	 *							into two parts or not. Default is FALSE to return
	 *							a string array with only one record - the result
	 *							URL. If `TRUE`, result url is split into two
	 *							parts and function return array with two items.
	 * @return \string[]		Result URL address in array. If last argument is
	 *							`FALSE` by default, this function returns only
	 *							single item array with result URL. If last
	 *							argument is `TRUE`, function returns result URL
	 *							in two parts - domain part with base path and
	 *							path part with query string.
	 */
	protected function urlAbsPartAndSplitByGlobalSwitchOrBasePath (\MvcCore\IRequest $request, $resultUrl, & $domainParams, $domainParamsFlag, $splitUrl) {
		/** @var $this \MvcCore\Route */
		$router = $this->router;
		$basePathParamName = $router::URL_PARAM_BASEPATH;
		$basePart = isset($domainParams[$basePathParamName])
			? isset($domainParams[$basePathParamName])
			: $request->GetBasePath();
		// If there is `%basePath%` placeholder in reverse, put before `$basePart`
		// what is before matched `%basePath%` placeholder and edit `$resultUrl`
		// to use only part after `%basePath%` placeholder:
		if ($domainParamsFlag) {
			$placeHolderBasePath = static::PLACEHOLDER_BASEPATH;
			$basePathPlaceHolderPos = mb_strpos($resultUrl, $placeHolderBasePath);
			if ($basePathPlaceHolderPos !== FALSE) {
				$basePart = mb_substr($resultUrl, 0, $basePathPlaceHolderPos) . $basePart;
				$resultUrl = mb_substr($resultUrl, $basePathPlaceHolderPos + mb_strlen($placeHolderBasePath));
			}
		}
		$absoluteParamName = $router::URL_PARAM_ABSOLUTE;
		if (
			$this->absolute || (
				isset($domainParams[$absoluteParamName]) && $domainParams[$absoluteParamName]
			)
		)
			$basePart = $request->GetDomainUrl() . $basePart;
		$questionMarkPos = mb_strpos($resultUrl, '?');
		if ($questionMarkPos === FALSE) {
			$resultUrl = str_replace('//', '/', $resultUrl);	
		} else {
			$resultUrl = str_replace('//', '/', mb_substr($resultUrl, 0, $questionMarkPos))
				. mb_substr($resultUrl, $questionMarkPos);
		}
		if ($splitUrl) return [$basePart, $resultUrl];
		return [$basePart . $resultUrl];
	}

	/**
	 * Return `TRUE` if there are any domain params or `absolute` boolean flag
	 * found in given `$params` array. All those domain params and possible
	 * `absolute` flag unset from given `$params` array and return it in result
	 * array as domain params. Keys as param name, values as domain param value.
	 * @param array $params
	 * @return array
	 */
	protected function urlGetAndRemoveDomainPercentageParams (array & $params = []) {
		/** @var $this \MvcCore\Route */
		static $domainPercentageParams = [];
		$absolute = FALSE;
		$router = $this->router;
		$absoluteParamName = $router::URL_PARAM_ABSOLUTE;
		$result = [];
		if (!$domainPercentageParams) {
			$domainPercentageParams = [
				$router::URL_PARAM_HOST,
				$router::URL_PARAM_DOMAIN,
				$router::URL_PARAM_TLD,
				$router::URL_PARAM_SLD,
				$router::URL_PARAM_BASEPATH,
			];
		}
		foreach ($domainPercentageParams as $domainPercentageParam) {
			if (isset($params[$domainPercentageParam])) {
				$absolute = TRUE;
				$result[$domainPercentageParam] = $params[$domainPercentageParam];
				unset($params[$domainPercentageParam]);
			}
		}
		if ($absolute) {
			$result[$absoluteParamName] = TRUE;
		} else if (isset($params[$absoluteParamName])) {
			$result[$absoluteParamName] = (bool) $params[$absoluteParamName];
			unset($params[$absoluteParamName]);
		}
		return $result;
	}

	/**
	 * Correct last character in path element completed in `Url()` method by
	 * cached router configuration protected property
	 * `\MvcCore\Router::$trailingSlashBehaviour;`.
	 * @param string $urlPath
	 * @return string
	 */
	protected function & urlCorrectTrailingSlashBehaviour (& $urlPath) {
		/** @var $this \MvcCore\Route */
		$trailingSlashBehaviour = $this->_trailingSlashBehaviour ?: (
			$this->_trailingSlashBehaviour = $this->router->GetTrailingSlashBehaviour()
		);
		$urlPathLength = mb_strlen($urlPath);
		$lastCharIsSlash = $urlPathLength > 0 && mb_substr($urlPath, $urlPathLength - 1) === '/';
		if (!$lastCharIsSlash && $trailingSlashBehaviour === \MvcCore\IRouter::TRAILING_SLASH_ALWAYS) {
			$urlPath .= '/';
		} else if ($lastCharIsSlash && $trailingSlashBehaviour === \MvcCore\IRouter::TRAILING_SLASH_REMOVE) {
			$urlPath = mb_substr($urlPath, 0, $urlPathLength - 1);
		}
		if ($urlPath === '')
			$urlPath = '/';
		return $urlPath;
	}
}
