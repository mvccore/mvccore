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
	 * This function return `array` with first item as `bool` about successfull
	 * filter processing in `try/catch` and second item as filtered params `array`.
	 * @param array $params 
	 * @param array $defaultParams
	 * @param string $direction 
	 * @return array
	 */
	public function Filter (array & $params = [], array & $defaultParams = [], $direction = \MvcCore\IRoute::CONFIG_FILTER_IN) {
		if (!$this->filters || !isset($this->filters[$direction])) 
			return [TRUE, $params];
		list($closureCalling, $handler) = $this->filters[$direction];
		try {
			$req = & \MvcCore\Application::GetInstance()->GetRequest();
			if ($closureCalling) {
				$newParams = $handler($params, $defaultParams, $req);
			} else {
				$newParams = call_user_func_array($handler, [$params, $defaultParams, $req]);
			}
			$success = TRUE;
		} catch (\RuntimeException $e) {
			\MvcCore\Debug::Log($e, \MvcCore\IDebug::ERROR);
			$success = FALSE;
			$newParams = $params;
		}
		return [$success, $newParams];
	}

	/**
	 * Complete route url by given params array and route
	 * internal reverse replacements pattern string.
	 * If there are more given params in first argument
	 * than count of replacement places in reverse pattern,
	 * then create url with query string params after reverse
	 * pattern, containing that extra record(s) value(s).
	 *
	 * Example:
	 *	Input (`$params`):
	 *		`array(
	 *			"name"		=> "cool-product-name",
	 *			"color"		=> "blue",
	 *			"variants"	=> array("L", "XL"),
	 *		);`
	 *	Input (`\MvcCore\Route::$reverse`):
	 *		`"/products-list/<name>/<color*>"`
	 *	Output:
	 *		`"/products-list/cool-product-name/blue?variant[]=L&amp;variant[]=XL"`
	 * @param \MvcCore\Request $request Currently requested request object.
	 * @param array $params URL params from application point completed by developer.
	 * @param array $defaultUrlParams Requested url route prams nad query string params without escaped HTML special chars: `< > & " ' &`.
	 * @param string $queryStringParamsSepatator Query params separator, `&` by default. Always automatically completed by router instance.
	 * @return \string[] Result URL addres in two parts - domain part with base path and path part with query string.
	 */
	public function Url (\MvcCore\IRequest & $request, array & $params = [], array & $defaultUrlParams = [], $queryStringParamsSepatator = '&') {
		// check reverse initialization
		if ($this->reverseParams === NULL) $this->initReverse();
		// complete and filter all params to build reverse pattern
		if (count($this->reverseParams) === 0) {
			$allParamsClone = array_merge([], $params);
		} else {// complete params with necessary values to build reverse pattern (and than query string)
			$emptyReverseParams = array_fill_keys(array_keys($this->reverseParams), '');
			$allMergedParams = array_merge($this->defaults, $defaultUrlParams, $params);
			// all params clone contains only keys necessary to build reverse 
			// patern for this route and all given `$params` keys, nothing more 
			// from currently requested url
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
		return $this->urlSplitResultToBaseAndPathWithQuery($request, $result, $domainParams);
	}

	protected function urlComposeByReverseSectionsAndParams (& $reverse, & $reverseSections, & $reverseParams, & $params, & $defaults) {
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
					$param = $reverseParams[$paramKey];
					if ($param->sectionIndex !== $sectionIndex) break;
					$sectionParamsCount++;
					$paramStart = $param->reverseStart;
					if ($sectionOffset < $paramStart)
						$sectionResult .= mb_substr($reverse, $sectionOffset, $paramStart - $sectionOffset);
					$paramName = $param->name;
					$paramValue = (string) $params[$paramName];
					if (isset($defaults[$paramName]) && $paramValue == (string) $defaults[$paramName]) 
						$defaultValuesCount++;
					$sectionResult .= htmlspecialchars($paramValue, ENT_QUOTES);
					unset($params[$paramName]);
					$paramIndex += 1;
					$sectionOffset = $param->reverseEnd;
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
		$result = str_replace('//', '/', implode('', $sections));
		$result = & $this->urlCorrectTrailingSlashBehaviour($result);
		return $result;
	}

	/**
	 * Return request base path and completed result URL address by route, if 
	 * route instance is not defined as absolute pattern/match/reverse. Otherwise,
	 * if route IS defined as absolute, split completed result URL address by 
	 * route into parts with base part (domain part and base path) and into part
	 * with request path and query string.
	 * @param string $resultUrl 
	 * @return \string[]
	 */
	protected function urlSplitResultToBaseAndPathWithQuery (\MvcCore\IRequest & $request, $resultUrl, & $domainParams) {
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
			// try to find url position after domain part and after base path part
			if ($basePathInReverse) {
				return $this->urlSplitResultByReverseBasePath($request, $resultUrl, $domainParams);
			} else {
				return $this->urlSplitResultByRequestedBasePath($request, $resultUrl);
			}
		} else {
			// route is not defined as absolute, there could be only flag 
			// in domain params array to complete absolute url by developer
			// and there could be also `basePath` param defined.
			return $this->urlSplitResultByAbsoluteAndBasePath($request, $resultUrl, $domainParams, $domainParamsFlag);
		}
	}

	protected function urlReplaceDomainReverseParams (\MvcCore\IRequest & $request, & $resultUrl, & $domainParams, $domainParamsFlag) {
		$replacements = [];
		$values = [];
		$router = & $this->router;
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

	protected function urlSplitResultByReverseBasePath (\MvcCore\IRequest & $request, $resultUrl, & $domainParams) {
		$doubleSlashPos = mb_strpos($resultUrl, '//');
		$doubleSlashPos = $doubleSlashPos === FALSE
			? 0
			: $doubleSlashPos + 2;
		$router = & $this->router;
		$basePathPlaceHolderPos = mb_strpos($resultUrl, static::PLACEHOLDER_BASEPATH, $doubleSlashPos);
		if ($basePathPlaceHolderPos === FALSE) {
			return $this->urlSplitResultByRequestedBasePath ($request, $resultUrl);
		} else {
			$pathPart = mb_substr($resultUrl, $basePathPlaceHolderPos + mb_strlen(static::PLACEHOLDER_BASEPATH));
			$basePart = mb_substr($resultUrl, 0, $basePathPlaceHolderPos);
			$basePathParamName = $router::URL_PARAM_BASEPATH;
			$basePart .= isset($domainParams[$basePathParamName])
				? $domainParams[$basePathParamName]
				: $request->GetBasePath();
		}
		if ($this->flags[0] === static::FLAG_SCHEME_ANY)
			$basePart = $request->GetProtocol() . $basePart;
		return [$basePart, $pathPart];
	}

	protected function urlSplitResultByRequestedBasePath (\MvcCore\IRequest & $request, $resultUrl) {
		$doubleSlashPos = mb_strpos($resultUrl, '//');
		$doubleSlashPos = $doubleSlashPos === FALSE
			? 0
			: $doubleSlashPos + 2;
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
			$basePart = $request->GetProtocol() . $basePart;
		return [
			$basePart,
			mb_substr($resultUrl, $baseUrlPartEndPos)
		];
	}

	protected function urlSplitResultByAbsoluteAndBasePath (\MvcCore\IRequest & $request, $resultUrl, & $domainParams, $basePathInReverse) {
		$router = & $this->router;
		$basePathParamName = $router::URL_PARAM_BASEPATH;
		$basePart = isset($domainParams[$basePathParamName])
			? isset($domainParams[$basePathParamName])
			: $request->GetBasePath();
		// if there is `%basePath%` placeholder in reverse, put before `$basePart`
		// what is before matched `%basePath%` placeholder and edit `$resultUrl`
		// to use only part after `%basePath%` placeholder:
		if ($basePathInReverse) {
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
		return [$basePart, $resultUrl];
	}

	/**
	 * Get `TRUE` if given `array $params` contains `boolean` record under 
	 * `"absolute"` array key and if the record is `TRUE`. Unset the absolute 
	 * flag from `$params` in any case.
	 * @param array $params 
	 * @return boolean
	 */
	protected function urlGetAndRemoveDomainPercentageParams (array & $params = []) {
		static $domainPercentageParams = [];
		$absolute = FALSE;
		$router = & $this->router;
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
	 * cached router configuration property `\MvcCore\Router::$trailingSlashBehaviour;`.
	 * @param string $urlPath
	 * @return string
	 */
	protected function & urlCorrectTrailingSlashBehaviour (& $urlPath) {
		$trailingSlashBehaviour = $this->router->GetTrailingSlashBehaviour();
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
