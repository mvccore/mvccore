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

namespace MvcCore\Request;

/**
 * @mixin \MvcCore\Request
 */
trait GettersSetters {
	
	/**
	 * @inheritDoc
	 * @param  array<string> $twoSegmentTlds,... List of two-segment top-level domains without leading dot.
	 * @return void
	 */
	public static function SetTwoSegmentTlds ($twoSegmentTlds) {
		$tlds = func_get_args();
		if (count($tlds) === 1 && is_array($tlds[0])) $tlds = $tlds[0];
		self::$twoSegmentTlds = [];
		self::$twoSegmentTlds = array_combine(
			$tlds, array_fill(0, count($tlds), TRUE)
		);
	}

	/**
	 * @inheritDoc
	 * @param  array<string> $twoSegmentTlds,... List of two-segment top-level domains without leading dot.
	 * @return void
	 */
	public static function AddTwoSegmentTlds ($twoSegmentTlds) {
		$tlds = func_get_args();
		if (count($tlds) === 1 && is_array($tlds[0])) $tlds = $tlds[0];
		self::$twoSegmentTlds = array_combine(
			$tlds, array_fill(0, count($tlds), TRUE)
		);
	}
	
	/**
	 * @inheritDoc
	 * @param  array<string>|array<int> $defaultPorts,... List of default ports, not defined in server name by default.
	 * @return void
	 */
	public static function SetDefaultPorts ($defaultPorts) {
		$ports = func_get_args();
		if (count($ports) === 1 && is_array($ports[0])) $ports = $ports[0];
		self::$defaultPorts = [];
		self::$defaultPorts = array_combine(
			array_map('strval', $ports), 
			array_fill(0, count($ports), TRUE)
		);
	}

	/**
	 * @inheritDoc
	 * @param  array<string>|array<int> $defaultPorts,... List of default ports, not defined in server name by default.
	 * @return void
	 */
	public static function AddDefaultPorts ($defaultPorts) {
		$ports = func_get_args();
		if (count($ports) === 1 && is_array($ports[0])) $ports = $ports[0];
		self::$defaultPorts = array_combine(
			array_map('strval', $ports), 
			array_fill(0, count($ports), TRUE)
		);
	}

	/**
	 * @inheritDoc
	 * @return bool
	 */
	public function IsInternalRequest () {
		if ($this->appRequest === NULL) {
			try {
				$ctrl = $this->GetControllerName();
				$action = $this->GetActionName();
			} catch (\Throwable $e) {
				$ctrl = NULL;
				$action = NULL;
			}
			if ($ctrl !== NULL && $action !== NULL) {
				$this->appRequest = FALSE;
				if ($ctrl === 'controller' && $action === 'asset')
					$this->appRequest = TRUE;
			}
			if ($this->appRequest === NULL && \PHP_SAPI === 'cli-server') {
				$server = & $this->globalServer;
				if (isset($server['SCRIPT_FILENAME'])) {
					$requestedFullPath = str_replace('\\', '/', $server['SCRIPT_FILENAME']);
					$reqDocRoot = $this->GetDocumentRoot();
					$scriptFullPath = $reqDocRoot . $this->GetScriptName();
					if (
						mb_strpos($requestedFullPath, $reqDocRoot) === 0 && 
						$requestedFullPath !== $scriptFullPath &&
						file_exists($requestedFullPath) && 
						is_file($requestedFullPath)
					) {
						$reqUri = mb_substr($requestedFullPath, mb_strlen($reqDocRoot));
						$this->controllerName = 'controller';
						$this->actionName = 'asset';
						$this->SetParam('path', $reqUri, \MvcCore\IRequest::PARAM_TYPE_QUERY_STRING);
						$this->appRequest = TRUE;
					}
				}
			}
			if ($this->appRequest === NULL)
				$this->appRequest = FALSE;
		}
		return $this->appRequest;
	}

	/**
	 * @inheritDoc
	 * @param  string $controllerName
	 * @return \MvcCore\Request
	 */
	public function SetControllerName ($controllerName) {
		$this->controllerName = $controllerName;
		$routerClass = self::$routerClass;
		$router = $routerClass::GetInstance();
		$this->params[$router::URL_PARAM_CONTROLLER] = $controllerName;
		return $this;
	}

	/**
	 * @inheritDoc
	 * @return string
	 */
	public function GetControllerName () {
		if ($this->controllerName === NULL) {
			$routerClass = self::$routerClass;
			$router = $routerClass::GetInstance();
			if (isset($this->globalGet[$router::URL_PARAM_CONTROLLER]))
				$this->controllerName = $this->GetParam($router::URL_PARAM_CONTROLLER, 'a-zA-Z0-9\-_/', '', 'string');
		}
		return $this->controllerName;
	}

	/**
	 * @inheritDoc
	 * @param  string $actionName
	 * @return \MvcCore\Request
	 */
	public function SetActionName ($actionName) {
		$this->actionName = $actionName;
		$routerClass = self::$routerClass;
		$router = $routerClass::GetInstance();
		$this->params[$router::URL_PARAM_ACTION] = $actionName;
		return $this;
	}

	/**
	 * @inheritDoc
	 * @return string
	 */
	public function GetActionName () {
		if ($this->actionName === NULL) {
			$routerClass = self::$routerClass;
			$router = $routerClass::GetInstance();
			if (isset($this->globalGet[$router::URL_PARAM_ACTION]))
				$this->actionName = $this->GetParam($router::URL_PARAM_ACTION, 'a-zA-Z0-9\-_', '', 'string');
		}
		return $this->actionName;
	}

	/**
	 * @inheritDoc
	 * @return bool
	 */
	public function IsCli () {
		return $this->cli;
	}

	/**
	 * @inheritDoc
	 * @param  string|NULL $lang
	 * @return \MvcCore\Request
	 */
	public function SetLang ($lang) {
		$this->lang = $lang;
		return $this;
	}

	/**
	 * @inheritDoc
	 * @return string|NULL
	 */
	public function GetLang () {
		if ($this->lang === NULL) $this->initLangAndLocale();
		return $this->lang;
	}

	/**
	 * @inheritDoc
	 * @param  string|NULL $locale
	 * @return \MvcCore\Request
	 */
	public function SetLocale ($locale) {
		$this->locale = $locale;
		return $this;
	}

	/**
	 * @inheritDoc
	 * @return string|NULL
	 */
	public function GetLocale () {
		if ($this->locale === NULL) $this->initLangAndLocale();
		return $this->locale;
	}

	/**
	 * @inheritDoc
	 * @param  string|NULL $mediaSiteVersion
	 * @return \MvcCore\Request
	 */
	public function SetMediaSiteVersion ($mediaSiteVersion) {
		$this->mediaSiteVersion = $mediaSiteVersion;
		return $this;
	}

	/**
	 * @inheritDoc
	 * @return string|NULL
	 */
	public function GetMediaSiteVersion () {
		return $this->mediaSiteVersion;
	}


	/**
	 * @inheritDoc
	 * @param  string           $rawName
	 * @param  array<int,mixed> $arguments
	 * @throws \InvalidArgumentException
	 * @return mixed|\MvcCore\Request
	 */
	public function __call ($rawName, $arguments = []) {
		$nameBegin = strtolower(substr($rawName, 0, 3));
		$name = substr($rawName, 3);
		$lcName = lcfirst($name);
		if ($nameBegin == 'get') {
			if (property_exists($this, $lcName)) 
				return $this->{$lcName};
			if (property_exists($this, $name)) 
				return $this->$name;
			return NULL;
		} else if ($nameBegin == 'set') {
			$value = isset($arguments[0]) ? $arguments[0] : NULL;
			if (property_exists($this, $name)) {
				$this->{$name} = $value;
			} else {
				$this->{$lcName} = $value;
			}
			return $this;
		} else {
			throw new \InvalidArgumentException("[".get_class($this)."] No method `{$rawName}()` defined.");
		}
	}

	/**
	 * @inheritDoc
	 * @param  string $name
	 * @return mixed
	 */
	public function __get ($name) {
		$lcPropName = lcfirst($name);
		if (isset($this->{$lcPropName}))
			return $this->{$lcPropName};
		if (isset($this->{$name}))
			return $this->{$name};
		return NULL;
	}

	/**
	 * @inheritDoc
	 * @param  string $name
	 * @param  mixed  $value
	 * @return void
	 */
	public function __set ($name, $value) {
		$lcPropName = lcfirst($name);
		if (property_exists($this, $lcPropName)) {
			$this->{$lcPropName} = $value;
		} else {
			$this->{$name} = $value;
		}
	}

	
	/**
	 * @inheritDoc
	 * @return string
	 */
	public function GetScriptName () {
		if ($this->scriptName === NULL) $this->initScriptNameAndBasePath();
		return $this->scriptName;
	}
	
	/**
	 * @inheritDoc
	 * @param  string $scriptName
	 * @return \MvcCore\Request
	 */
	public function SetScriptName ($scriptName) {
		$this->scriptName = $scriptName;
		return $this;
	}
	
	/**
	 * @inheritDoc
	 * @return string
	 */
	public function GetAppRoot () {
		if ($this->appRoot === NULL) {
			if (defined('MVCCORE_APP_ROOT')) {
				$this->appRoot = constant('MVCCORE_APP_ROOT');
				$insidePhar = class_exists('\Phar') && strlen(\Phar::running()) > 0;
				if (!$insidePhar)
					$this->appRoot = ucfirst($this->appRoot);
			} else {
				$docRoot = $this->GetDocumentRoot();
				$app = self::$app ?: (self::$app = \MvcCore\Application::GetInstance()); // @phpstan-ignore-line
				$docRootDirName = $app->GetDocRootDir();
				$docRootDirNamePos = mb_strrpos($docRoot, '/' . $docRootDirName);
				$estimatedPos = mb_strlen($docRoot) - mb_strlen($docRootDirName) - 1;
				$this->appRoot = $docRootDirNamePos !== FALSE && $docRootDirNamePos === $estimatedPos
					? mb_substr($docRoot, 0, $estimatedPos)
					: $docRoot;
				define('MVCCORE_APP_ROOT', $this->appRoot);
			}
		}
		return $this->appRoot;
	}
	
	/**
	 * @inheritDoc
	 * @param  string $appRoot
	 * @return \MvcCore\Request
	 */
	public function SetAppRoot ($appRoot) {
		$this->appRoot = $appRoot;
		return $this;
	}
	
	/**
	 * @inheritDoc
	 * @return string
	 */
	public function GetDocumentRoot () {
		if ($this->documentRoot === NULL) {
			// `ucfirst()` - cause IIS has lower case drive name here - different from __DIR__ value
			$insidePhar = class_exists('\Phar') && strlen(\Phar::running()) > 0;
			if (defined('MVCCORE_DOC_ROOT')) {
				$this->documentRoot = constant('MVCCORE_DOC_ROOT');
				if (!$insidePhar)
					$this->documentRoot = ucfirst($this->documentRoot);
			} else {
				if (mb_strpos(\PHP_SAPI, 'cli') === 0) {
					$backtraceItems = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
					$scriptFilename = $backtraceItems[count($backtraceItems) - 1]['file'];
					// If php is running by direct input like `php -r "/* php code */":
					if (
						mb_strpos($scriptFilename, DIRECTORY_SEPARATOR) === FALSE &&
						empty($this->globalServer['SCRIPT_FILENAME'])
					) {
						// Try to define app root and document root 
						// by possible Composer class location:
						$composerFullClassName = 'Composer\\Autoload\\ClassLoader';
						if (class_exists($composerFullClassName, TRUE)) {
							$ccType = new \ReflectionClass($composerFullClassName);
							$scriptFilename = dirname($ccType->getFileName(), 2);
						} else {
							// If there is no composer class, define 
							// document root by called current working directory:
							$scriptFilename = getcwd();
						}
					}
				} else {
					$scriptFilename = $this->globalServer['SCRIPT_FILENAME'];
				}
				$docRoot = $insidePhar
					? $scriptFilename
					: dirname($scriptFilename);
				// `ucfirst()` - cause IIS has lower case drive name here - different from __DIR__ value
				$docRoot = str_replace(['\\', '//'], '/', ucfirst($docRoot));
				$this->documentRoot = $insidePhar ? 'phar://' . $docRoot : $docRoot;
				define('MVCCORE_DOC_ROOT', $this->documentRoot);
			}
		}
		return $this->documentRoot;
	}
	
	/**
	 * @inheritDoc
	 * @param  string $documentRoot
	 * @return \MvcCore\Request
	 */
	public function SetDocumentRoot ($documentRoot) {
		$this->documentRoot = $documentRoot;
		return $this;
	}

	/**
	 * @inheritDoc
	 * @param  string $rawMethod
	 * @return \MvcCore\Request
	 */
	public function SetMethod ($rawMethod) {
		$this->method = $rawMethod;
		return $this;
	}

	/**
	 * @inheritDoc
	 * @return string
	 */
	public function GetMethod () {
		if ($this->method === NULL) {
			$this->method = strtoupper($this->globalServer['REQUEST_METHOD']);
		}
		return $this->method;
	}

	/**
	 * @inheritDoc
	 * @param  string $rawBasePath
	 * @return \MvcCore\Request
	 */
	public function SetBasePath ($rawBasePath) {
		$this->basePath = $rawBasePath;
		$this->baseUrl = NULL;
		$this->requestUrl = NULL;
		$this->fullUrl = NULL;
		return $this;
	}

	/**
	 * @inheritDoc
	 * @return string
	 */
	public function GetBasePath () {
		if ($this->basePath === NULL) $this->initScriptNameAndBasePath();
		return $this->basePath;
	}

	/**
	 * @inheritDoc
	 * @param  string $rawProtocol
	 * @return \MvcCore\Request
	 */
	public function SetScheme ($rawProtocol) {
		$this->scheme = $rawProtocol;
		$this->domainUrl = NULL;
		$this->baseUrl = NULL;
		$this->requestUrl = NULL;
		$this->fullUrl = NULL;
		return $this;
	}

	/**
	 * @inheritDoc
	 * @return string
	 */
	public function GetScheme () {
		if ($this->scheme === NULL) {
			$this->scheme = (
				(
					isset($this->globalServer['HTTPS']) && 
					strtolower($this->globalServer['HTTPS']) == 'on'
				) || intval($this->globalServer['SERVER_PORT']) === 443
			)
				? static::SCHEME_HTTPS
				: static::SCHEME_HTTP;
		}
		return $this->scheme;
	}

	/**
	 * @inheritDoc
	 * @return bool
	 */
	public function IsSecure () {
		if ($this->secure === NULL)
			$this->secure = in_array($this->GetScheme(), [
				static::SCHEME_HTTPS,
				static::SCHEME_FTPS,
				static::SCHEME_IRCS,
				static::SCHEME_SSH
			], TRUE);
		return $this->secure;
	}

	/**
	 * @inheritDoc
	 * @param  bool $rawInput Get raw input if `TRUE`. `FALSE` by default to get value through `htmlspecialchars($result);` without ampersand `&` escaping.
	 * @return string
	 */
	public function GetReferer ($rawInput = FALSE) {
		if ($this->referer === NULL) {
			$referer = isset($this->globalServer['HTTP_REFERER'])
				? $this->globalServer['HTTP_REFERER']
				: '';
			if ($referer) {
				$safetyCounter = 0;
				while (preg_match("#%([0-9a-zA-Z]{2})#", $referer) && $safetyCounter++ < 5) {
					$referer = rawurldecode($referer);
				}
				$referer = str_replace('%', '%25', $referer);
			}
			$this->referer = $referer;
		}
		return $rawInput ? $this->referer : static::HtmlSpecialChars($this->referer);
	}

	/**
	 * @inheritDoc
	 * @return float
	 */
	public function GetStartTime () {
		if ($this->microtime === NULL) $this->microtime = $this->globalServer['REQUEST_TIME_FLOAT'];
		return $this->microtime;
	}

	/**
	 * @inheritDoc
	 * @param  string|NULL $topLevelDomain
	 * @return \MvcCore\Request
	 */
	public function SetTopLevelDomain ($topLevelDomain) {
		if ($this->domainParts === NULL) $this->initDomainSegments();
		$this->domainParts[2] = $topLevelDomain;
		$this->hostName = trim(implode('.', $this->domainParts), '.');
		if ($this->hostName && $this->portDefined)
			$this->host = $this->hostName . ':' . $this->GetPort();
		$this->domainUrl = NULL;
		$this->baseUrl = NULL;
		$this->requestUrl = NULL;
		$this->fullUrl = NULL;
		return $this;
	}

	/**
	 * @inheritDoc
	 * @return string|NULL
	 */
	public function GetTopLevelDomain () {
		if ($this->domainParts === NULL) $this->initDomainSegments();
		return $this->domainParts[2];
	}

	/**
	 * @inheritDoc
	 * @param  string|NULL $secondLevelDomain
	 * @return \MvcCore\Request
	 */
	public function SetSecondLevelDomain ($secondLevelDomain) {
		if ($this->domainParts === NULL) $this->initDomainSegments();
		$this->domainParts[1] = $secondLevelDomain;
		$this->hostName = trim(implode('.', $this->domainParts), '.');
		if ($this->hostName && $this->portDefined)
			$this->host = $this->hostName . ':' . $this->GetPort();
		$this->domainUrl = NULL;
		$this->baseUrl = NULL;
		$this->requestUrl = NULL;
		$this->fullUrl = NULL;
		return $this;
	}

	/**
	 * @inheritDoc
	 * @return string|NULL
	 */
	public function GetSecondLevelDomain () {
		if ($this->domainParts === NULL) $this->initDomainSegments();
		return isset($this->domainParts[1]) ? $this->domainParts[1] : NULL;
	}

	/**
	 * @inheritDoc
	 * @param  string|NULL $thirdLevelDomain
	 * @return \MvcCore\Request
	 */
	public function SetThirdLevelDomain ($thirdLevelDomain) {
		if ($this->domainParts === NULL) $this->initDomainSegments();
		$this->domainParts[0] = $thirdLevelDomain;
		$this->hostName = trim(implode('.', $this->domainParts), '.');
		if ($this->hostName && $this->portDefined)
			$this->host = $this->hostName . ':' . $this->GetPort();
		$this->domainUrl = NULL;
		$this->baseUrl = NULL;
		$this->requestUrl = NULL;
		$this->fullUrl = NULL;
		return $this;
	}

	/**
	 * @inheritDoc
	 * @return string|NULL
	 */
	public function GetThirdLevelDomain () {
		if ($this->domainParts === NULL) $this->initDomainSegments();
		return isset($this->domainParts[0]) ? $this->domainParts[0] : NULL;
	}

	/**
	 * @inheritDoc
	 * @param  string $rawHostName
	 * @return \MvcCore\Request
	 */
	public function SetHostName ($rawHostName) {
		if ($this->hostName !== $rawHostName) $this->domainParts = NULL;
		$this->hostName = $rawHostName;
		$this->domainUrl = NULL;
		$this->baseUrl = NULL;
		$this->requestUrl = NULL;
		$this->fullUrl = NULL;
		if ($rawHostName && $this->portDefined)
			$this->host = $rawHostName . ':' . $this->GetPort();
		return $this;
	}

	/**
	 * @inheritDoc
	 * @return string
	 */
	public function GetHostName () {
		if ($this->hostName === NULL)
			$this->hostName = $this->globalServer['SERVER_NAME'];
		return $this->hostName;
	}

	/**
	 * @inheritDoc
	 * @param  string $rawHost
	 * @return \MvcCore\Request
	 */
	public function SetHost ($rawHost) {
		$this->host = $rawHost;
		$this->domainUrl = NULL;
		$this->baseUrl = NULL;
		$this->requestUrl = NULL;
		$this->fullUrl = NULL;
		$doubleDotPos = mb_strpos($rawHost, ':');
		if ($doubleDotPos !== FALSE) {
			$hostName = mb_substr($rawHost, 0, $doubleDotPos);
			$this->SetPort(mb_substr($rawHost, $doubleDotPos + 1));
		} else {
			$hostName = $rawHost;
			$this->port = '';
			$this->portDefined = FALSE;
		}
		return $this->SetHostName($hostName);
	}

	/**
	 * @inheritDoc
	 * @return string
	 */
	public function GetHost () {
		if ($this->host === NULL) {
			if ($this->port === NULL)
				$this->initUrlSegments();
			$hostName = $this->GetHostName();
			$this->host = $this->portDefined
				? $hostName . ':' . $this->port
				: $hostName;
		}
		return $this->host;
	}

	/**
	 * @inheritDoc
	 * @param  string|int $rawPort
	 * @return \MvcCore\Request
	 */
	public function SetPort ($rawPort) {
		$this->port = trim((string) $rawPort);
		$this->domainUrl = NULL;
		$this->baseUrl = NULL;
		$this->requestUrl = NULL;
		$this->fullUrl = NULL;
		if (strlen($this->port) > 0) {
			$this->host = $this->hostName . ':' . $this->port;
			$this->portDefined = TRUE;
		} else {
			$this->host = $this->hostName;
			$this->portDefined = FALSE;
		}
		return $this;
	}

	/**
	 * @inheritDoc
	 * @return string
	 */
	public function GetPort () {
		if ($this->port === NULL) $this->initUrlSegments();
		return $this->port;
	}

	/**
	 * @inheritDoc
	 * @param  string $rawPathValue
	 * @return \MvcCore\Request
	 */
	public function SetPath ($rawPathValue) {
		$this->path = $rawPathValue;
		$this->requestUrl = NULL;
		$this->requestPath = NULL;
		$this->fullUrl = NULL;
		return $this;
	}

	/**
	 * @inheritDoc
	 * @param  bool $rawInput Get raw input if `TRUE`. `FALSE` by default to get value through `htmlspecialchars($result);` without ampersand `&` escaping.
	 * @return string
	 */
	public function GetPath ($rawInput = FALSE) {
		if ($this->path === NULL)
			$this->initUrlSegments();
		return $rawInput ? $this->path : static::HtmlSpecialChars($this->path);
	}

	/**
	 * @inheritDoc
	 * @param  string $rawQuery
	 * @return \MvcCore\Request
	 */
	public function SetQuery ($rawQuery) {
		$this->query = ltrim($rawQuery, '?');
		$this->fullUrl = NULL;
		$this->requestPath = NULL;
		return $this;
	}

	/**
	 * @inheritDoc
	 * @param  bool   $withQuestionMark
	 *                If `FALSE` (by default), query string is returned always without 
	 *                question mark character at the beginning. If `TRUE`, and query 
	 *                string contains any character(s), query string is returned with 
	 *                question mark character at the beginning. But if query string 
	 *                contains no character(s), query string is returned as EMPTY STRING 
	 *                WITHOUT question mark character.
	 * @param  bool   $rawInput 
	 *                Get raw input if `TRUE`. `FALSE` by default to get value 
	 *                through `htmlspecialchars($result);` without ampersand `&` escaping.
	 * @return string
	 */
	public function GetQuery ($withQuestionMark = FALSE, $rawInput = FALSE) {
		if ($this->query === NULL)
			$this->initUrlSegments();
		$result = ($withQuestionMark && mb_strlen($this->query) > 0)
			? '?' . $this->query
			: $this->query;
		return $rawInput ? $result : static::HtmlSpecialChars($result);
	}

	/**
	 * @inheritDoc
	 * @param  bool $rawInput Get raw input if `TRUE`. `FALSE` by default to get value through `htmlspecialchars($result);` without ampersand `&` escaping.
	 * @return string
	 */
	public function GetRequestPath ($rawInput = FALSE) {
		if ($this->requestPath === NULL) {
			$this->requestPath = $this->GetPath(TRUE) . $this->GetQuery(TRUE, TRUE) . $this->GetFragment(TRUE, TRUE);
		}
		return $rawInput ? $this->requestPath : static::HtmlSpecialChars($this->requestPath);
	}

	/**
	 * @inheritDoc
	 * @return string
	 */
	public function GetDomainUrl () {
		if ($this->domainUrl === NULL)
			$this->domainUrl = $this->GetScheme() . '//' . $this->GetHost();
		return $this->domainUrl;
	}

	/**
	 * @inheritDoc
	 * @return string
	 */
	public function GetBaseUrl () {
		if ($this->baseUrl === NULL)
			$this->baseUrl = $this->GetDomainUrl() . $this->GetBasePath();
		return $this->baseUrl;
	}

	/**
	 * @inheritDoc
	 * @param  bool $rawInput Get raw input if `TRUE`. `FALSE` by default to get value through `htmlspecialchars($result);` without ampersand `&` escaping.
	 * @return string
	 */
	public function GetRequestUrl ($rawInput = FALSE) {
		if ($this->requestUrl === NULL)
			$this->requestUrl = $this->GetBaseUrl() . $this->GetPath(TRUE);
		return $rawInput ? $this->requestUrl : static::HtmlSpecialChars($this->requestUrl);
	}

	/**
	 * @inheritDoc
	 * @param  bool $rawInput Get raw input if `TRUE`. `FALSE` by default to get value through `htmlspecialchars($result);` without ampersand `&` escaping.
	 * @return string
	 */
	public function GetFullUrl ($rawInput = FALSE) {
		if ($this->fullUrl === NULL)
			$this->fullUrl = $this->GetRequestUrl(TRUE) . $this->GetQuery(TRUE, TRUE) . $this->GetFragment(TRUE, TRUE);
		return $rawInput ? $this->fullUrl : static::HtmlSpecialChars($this->fullUrl);
	}

	/**
	 * @inheritDoc
	 * @param  bool $withHash 
	 *              If `FALSE` (by default), fragment is returned always without 
	 *              hash character at the beginning. If `TRUE`, and fragment 
	 *              contains any character(s), fragment is returned with hash 
	 *              character at the beginning. But if fragment contains no
	 *              character(s), fragment is returned as EMPTY STRING WITHOUT hash character.
	 * @param  bool $rawInput 
	 *              Get raw input if `TRUE`. `FALSE` by default to get value 
	 *              through `htmlspecialchars($result);` without ampersand `&` escaping.
	 * @return string
	 */
	public function GetFragment ($withHash = FALSE, $rawInput = FALSE) {
		if ($this->fragment === NULL)
			$this->initUrlSegments();
		$result = ($withHash && mb_strlen($this->fragment) > 0)
			? '?' . $this->fragment
			: $this->fragment;
		return $rawInput ? $result : static::HtmlSpecialChars($result);
	}

	/**
	 * @inheritDoc
	 * @return string
	 */
	public function GetServerIp () {
		if ($this->serverIp === NULL) {
			$this->serverIp = (isset($this->globalServer['SERVER_ADDR'])
				? $this->globalServer['SERVER_ADDR']
				: (isset($this->globalServer['LOCAL_ADDR'])
					? $this->globalServer['LOCAL_ADDR']
					: ''));
		}
		return $this->serverIp;
	}

	/**
	 * @inheritDoc
	 * @return string
	 */
	public function GetClientIp () {
		if ($this->clientIp === NULL) {
			$this->clientIp = (isset($this->globalServer['REMOTE_ADDR'])
				? $this->globalServer['REMOTE_ADDR']
				: (isset($this->globalServer['HTTP_X_CLIENT_IP'])
					? $this->globalServer['HTTP_X_CLIENT_IP']
					: ''));
			$this->clientIp = preg_replace("#[^0-9a-zA-Z\.\:\[\]]#", '', $this->clientIp);
		}
		return $this->clientIp;
	}

	/**
	 * @inheritDoc
	 * @return bool
	 */
	public function IsAjax () {
		if ($this->ajax === NULL) {
			$rawHeader = isset($this->globalServer['HTTP_X_REQUESTED_WITH'])
				? $this->globalServer['HTTP_X_REQUESTED_WITH']
				: $this->GetHeader('X-Requested-With', FALSE);
			$this->ajax = $rawHeader !== NULL && mb_strtolower($rawHeader) === 'xmlhttprequest';
		}
		return $this->ajax;
	}

	/**
	 * @inheritDoc
	 * @return int|NULL
	 */
	public function GetContentLength () {
		if ($this->contentLength === NULL) {
			if (
				isset($this->globalServer['CONTENT_LENGTH']) &&
				ctype_digit($this->globalServer['CONTENT_LENGTH'])
			) {
				$this->contentLength = intval($this->globalServer['CONTENT_LENGTH']);
			} else {
				$rawHeader = $this->GetHeader('Content-Length', '0-9', '');
				if ($rawHeader)
					$this->contentLength = intval($rawHeader);
			}
		}
		return $this->contentLength;
	}

	/**
	 * @inheritDoc
	 * @return string
	 */
	public function GetBody () {
		if ($this->body === NULL) $this->initBody();
		return $this->body;
	}

	/**
	 * @inheritDoc
	 * @see http://php.net/manual/en/function.htmlspecialchars.php
	 * @param  string $str
	 * @return string
	 */
	public static function HtmlSpecialChars ($str) {
		static $chars = [
			// Base ASCII chars from 0 to 31:
			"\x00"	=> '',	"\x08"	=> '',			"\x10"	=> '',	"\x18"	=> '',
			"\x01"	=> '',	/*"\x09"	=> "\t",*/	"\x11"	=> '',	"\x19"	=> '',
			"\x02"	=> '',	/*"\x0A"	=> "\n",*/	"\x12"	=> '',	"\x1A"	=> '',
			"\x03"	=> '',	"\x0B"	=> '',			"\x13"	=> '',	"\x1B"	=> '',
			"\x04"	=> '',	"\x0C"	=> '',			"\x14"	=> '',	"\x1C"	=> '',
			"\x05"	=> '',	/*"\x0D"	=> "\r",*/	"\x15"	=> '',	"\x1D"	=> '',
			"\x06"	=> '',	"\x0E"	=> '',			"\x16"	=> '',	"\x1E"	=> '',
			"\x07"	=> '',	"\x0F"	=> '',			"\x17"	=> '',	"\x1F"	=> '',
			// HTML special chars except `&`:
			'"'=>'&quot;',
			"'"=>'&apos;',
			'<'=>'&lt;',
			'>'=>'&gt;',
			/*'&' => '&amp;',*/
		];
		return strtr($str, $chars);
	}
}
